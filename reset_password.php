<?php
require_once __DIR__ . '/helpers.php';

$token = $_GET['token'] ?? '';
if (empty($token) || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    set_flash('error', 'Invalid password reset token format.');
    redirect('/login.php');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > CURRENT_TIMESTAMP");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'Password reset token is invalid or has expired.');
    redirect('/forgot_password.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $errors[] = 'Invalid form submission.'; }

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6 || strlen($password) > 255) {
        $errors[] = 'Password must be between 6 and 255 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $options = defined('PASSWORD_ARGON2ID') ? ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2] : ['cost' => 12];
        $hash = password_hash($password, $algo, $options);
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        set_flash('success', 'Your password has been reset successfully. You can now log in.');
        redirect('/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?= h(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="/css/logosq.png">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="brand">
                <img src="/css/favicon.png" alt="HeyyGuru Logo" class="logo">
                <span><?= h(APP_NAME) ?></span>
            </a>
            <div class="nav-links" id="navLinks">
                <a href="/">Home</a>
                <a href="/login.php">Login / Signup</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <h1 class="page-title" style="text-align: center;">Reset Password</h1>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= h($e) ?></div>
        <?php endforeach; ?>
        <div class="card">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Enter new password">
                </div>
                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm new password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>

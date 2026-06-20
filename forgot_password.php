<?php
require_once __DIR__ . '/helpers.php';

if (is_logged_in()) {
    $u = current_user();
    redirect($u['role'] === 'mentor' ? '/mentor/dashboard.php' : '/student/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $errors[] = 'Invalid form submission.'; }

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email under 150 characters.';
    }

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $stmt->execute([$reset_token, $expires_at, $user['id']]);

            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $reset_token;
            $email_body = "Hi {$user['name']},<br><br>You requested a password reset. Click the link below to reset your password. This link is valid for 1 hour.<br><a href='$reset_link'>Reset Password</a><br><br>If you did not request this, you can ignore this email.<br><br>Thanks,<br>HeyyGuru Team";
            
            sendEmail($email, "Password Reset Request", $email_body);
        }
        
        // Always show the same success message to prevent user enumeration
        set_flash('success', 'If an account with that email exists, we have sent a password reset link.');
        redirect('/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= h(APP_NAME) ?></title>
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
        <h1 class="page-title" style="text-align: center;">Forgot Password</h1>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= h($e) ?></div>
        <?php endforeach; ?>
        <div class="card">
            <p>Enter your email address and we'll send you a link to reset your password.</p>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= h($email ?? '') ?>" required placeholder="Enter your email">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>
            <div style="margin-top: 15px; text-align: center;">
                <a href="/login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>

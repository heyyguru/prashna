<?php
require_once __DIR__ . '/../helpers.php';

if (is_logged_in()) {
    $u = current_user();
    redirect($u['role'] === 'mentor' ? '/mentor/dashboard.php' : '/student/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $errors[] = 'Invalid form submission.'; }

    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone === '') $errors[] = 'Phone number is required.';
    if ($password === '') $errors[] = 'Password is required.';

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND role = 'mentor'");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            redirect('/mentor/dashboard.php');
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Login - <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="brand">
                <img src="/css/logo.jpg" alt="<?= h(APP_NAME) ?> Logo" class="logo">
                <span><?= h(APP_NAME) ?></span>
            </a>
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="nav-links" id="navLinks">
                <a href="/">Home</a>
                <a href="/login.php">Login / Signup</a>
                <a href="https://heyyguru.in/courses" class="btn-explore">Explore Courses</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <h1 class="page-title" style="text-align: center;">Mentor Login</h1>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= h($e) ?></div>
        <?php endforeach; ?>
        <div class="card">
            <div style="display: flex; border-bottom: 2px solid var(--border); margin-bottom: 25px;">
                <a href="/login.php" style="flex: 1; text-align: center; padding: 15px; font-weight: 700; color: var(--text-light); text-decoration: none;">Student</a>
                <a href="/mentor/login.php" style="flex: 1; text-align: center; padding: 15px; font-weight: 800; color: var(--primary); border-bottom: 3px solid var(--primary); text-decoration: none;">Mentor</a>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required placeholder="9999999999">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    <div class="hint">Default mentor: phone 9999999999, password: password</div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Mentor Login</button>
            </form>
        </div>
    </div>
    <script src="/js/chat.js"></script>
</body>
</html>

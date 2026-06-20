<?php
require_once __DIR__ . '/helpers.php';

if (is_logged_in()) {
    $u = current_user();
    redirect($u['role'] === 'mentor' ? '/mentor/dashboard.php' : '/student/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $errors[] = 'Invalid form submission.'; }

    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        $errors[] = 'Invalid phone number format.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) > 255) {
        $errors[] = 'Invalid password.';
    }

    if (empty($errors)) {
        $pdo = getDB();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $stmt_rate = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND phone = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
        $stmt_rate->execute([$ip_address, $phone]);
        $attempts = (int)$stmt_rate->fetch()['attempts'];
        
        if ($attempts >= 5) {
            $errors[] = 'Too many login attempts. Please try again after 15 minutes.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND role = 'student'");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['email_verified_at'] === null) {
                    $errors[] = 'Please verify your email address before logging in. Check your email for the verification link.';
                } else {
                    $stmt_clear = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND phone = ?");
                    $stmt_clear->execute([$ip_address, $phone]);
                    
                    login_user((int)$user['id']);
                    redirect('/student/dashboard.php');
                }
            } else {
                $stmt_fail = $pdo->prepare("INSERT INTO login_attempts (ip_address, phone) VALUES (?, ?)");
                $stmt_fail->execute([$ip_address, $phone]);
                
                $errors[] = 'Invalid phone number or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - <?= h(APP_NAME) ?></title>
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
        <h1 class="page-title" style="text-align: center;">Student Login</h1>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= h($e) ?></div>
        <?php endforeach; ?>
        <?php $f = get_flash(); if ($f): ?>
            <div class="alert alert-<?= $f['type'] ?>"><?= h($f['msg']) ?></div>
        <?php endif; ?>
        <div class="card">
            <div style="display: flex; border-bottom: 2px solid var(--border); margin-bottom: 25px;">
                <a href="/login.php" style="flex: 1; text-align: center; padding: 15px; font-weight: 800; color: var(--primary); border-bottom: 3px solid var(--primary); text-decoration: none;">Student</a>
                <a href="/mentor/login.php" style="flex: 1; text-align: center; padding: 15px; font-weight: 700; color: var(--text-light); text-decoration: none;">Mentor</a>
            </div>
            <div style="display: flex; border-bottom: 2px solid var(--border); margin-bottom: 25px;">
                <a href="/login.php" style="flex: 1; text-align: center; padding: 15px; font-weight: 800; color: var(--primary); border-bottom: 3px solid var(--primary); text-decoration: none;">Login</a>
                <a href="/register.php" style="flex: 1; text-align: center; padding: 15px; font-weight: 700; color: var(--text-light); text-decoration: none;">Register</a>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= h($phone ?? '') ?>" required placeholder="Enter your phone number">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login to Dashboard</button>
            </form>
        </div>
    </div>
    <script src="/js/chat.js"></script>
</body>
</html>

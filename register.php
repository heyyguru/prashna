<?php
require_once __DIR__ . '/helpers.php';

if (is_logged_in()) redirect('/student/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $errors[] = 'Invalid form submission.'; }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';
    if (!preg_match('/^[0-9]{7,15}$/', $phone)) $errors[] = 'Enter a valid phone number (digits only).';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this phone number already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (role, name, phone, email, password_hash) VALUES ('student', ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email ?: null, $hash]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        set_flash('success', 'Registration successful! Welcome to HeyyGuru.');
        redirect('/student/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="brand">
                <img src="/css/logo.jpg" alt="HeyyGuru Logo" class="logo">
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
        <h1 class="page-title">Student Registration</h1>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= h($e) ?></div>
        <?php endforeach; ?>
        <div class="card">
            <div style="display: flex; border-bottom: 2px solid var(--border); margin-bottom: 25px;">
                <a href="/login.php" style="flex: 1; text-align: center; padding: 15px; font-weight: 700; color: var(--text-light); text-decoration: none;">Login</a>
                <a href="/register.php" style="flex: 1; text-align: center; padding: 15px; font-weight: 800; color: var(--primary); border-bottom: 3px solid var(--primary); text-decoration: none;">Register</a>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?= h($name ?? '') ?>" required placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="text" name="phone" value="<?= h($phone ?? '') ?>" required placeholder="e.g. 9876543210">
                </div>
                <div class="form-group">
                    <label>Email (optional)</label>
                    <input type="email" name="email" value="<?= h($email ?? '') ?>" placeholder="Enter your email address">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Create a strong password">
                    <div class="hint">At least 6 characters</div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required placeholder="Re-type your password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create My Account</button>
            </form>
        </div>
    </div>
    <script src="/js/chat.js"></script>
</body>
</html>

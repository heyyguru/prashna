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

    if ($name === '') {
        $errors[] = 'Name is required.';
    } elseif (!preg_match('/^[\p{L}\s.-]{2,100}$/u', $name)) {
        $errors[] = 'Name contains invalid characters or is too long/short.';
    }

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        $errors[] = 'Enter a valid phone number (digits only, 7-15 length).';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email under 150 characters.';
    }

    if (strlen($password) < 6 || strlen($password) > 255) {
        $errors[] = 'Password must be between 6 and 255 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this phone number already exists.';
        }
    }

    if (empty($errors)) {
        // Use Argon2id if available, fallback to bcrypt
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $options = defined('PASSWORD_ARGON2ID') ? ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2] : ['cost' => 12];
        
        $hash = password_hash($password, $algo, $options);
        $verification_token = bin2hex(random_bytes(32));
        
        $stmt = $pdo->prepare("INSERT INTO users (role, name, phone, email, password_hash, verification_token) VALUES ('student', ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $hash, $verification_token]);
        
        $verify_link = "http://" . $_SERVER['HTTP_HOST'] . "/verify_email.php?token=" . $verification_token;
        $email_body = "Hi $name,<br><br>Please verify your email address by clicking the link below:<br><a href='$verify_link'>Verify Email</a><br><br>Thanks,<br>HeyyGuru Team";
        
        sendEmail($email, "Verify Your HeyyGuru Account", $email_body);
        
        set_flash('success', 'Registration successful! Please check your email to verify your account before logging in.');
        redirect('/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= h(APP_NAME) ?></title>
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
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= h($email ?? '') ?>" required placeholder="Enter your email address">
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

<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
}

function get_jwt_user_id(): ?int {
    if (!isset($_COOKIE['access_token'])) {
        return refresh_access_token();
    }
    
    $decoded = decode_jwt($_COOKIE['access_token'], JWT_SECRET);
    if ($decoded && isset($decoded['user_id'])) {
        return (int)$decoded['user_id'];
    }
    
    return refresh_access_token();
}

function refresh_access_token(): ?int {
    if (!isset($_COOKIE['refresh_token'])) return null;
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE refresh_token = ?");
    $stmt->execute([$_COOKIE['refresh_token']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $access_payload = [
            'user_id' => $user['id'],
            'exp' => time() + 15 * 60 // 15 mins
        ];
        $access_token = encode_jwt($access_payload, JWT_SECRET);
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('access_token', $access_token, [
            'expires' => time() + 15 * 60,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        $_COOKIE['access_token'] = $access_token;
        return (int)$user['id'];
    }
    return null;
}

function login_user(int $user_id): void {
    require_once __DIR__ . '/jwt.php';
    
    $access_payload = [
        'user_id' => $user_id,
        'exp' => time() + 15 * 60 // 15 mins
    ];
    $access_token = encode_jwt($access_payload, JWT_SECRET);
    
    $refresh_token = bin2hex(random_bytes(32));
    
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET refresh_token = ? WHERE id = ?");
    $stmt->execute([$refresh_token, $user_id]);
    
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('access_token', $access_token, [
        'expires' => time() + 15 * 60,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    setcookie('refresh_token', $refresh_token, [
        'expires' => time() + 7 * 24 * 60 * 60,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    $_COOKIE['access_token'] = $access_token;
    $_COOKIE['refresh_token'] = $refresh_token;
    
    // Regenerate CSRF on login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function is_logged_in(): bool {
    return get_jwt_user_id() !== null;
}

function current_user(): ?array {
    $user_id = get_jwt_user_id();
    if (!$user_id) return null;
    
    static $user = null;
    if ($user !== null) return $user;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user;
}

function require_student(): void {
    if (!is_logged_in()) { header('Location: /login.php'); exit; }
    $u = current_user();
    if (!$u || $u['role'] !== 'student') { header('Location: /login.php'); exit; }
}

function require_mentor(): void {
    if (!is_logged_in()) { header('Location: /mentor/login.php'); exit; }
    $u = current_user();
    if (!$u || $u['role'] !== 'mentor') { header('Location: /mentor/login.php'); exit; }
}

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function unread_count(int $student_id): int {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM doubts WHERE student_id = ? AND status = 'answered'");
    $stmt->execute([$student_id]);
    return (int)$stmt->fetch()['c'];
}

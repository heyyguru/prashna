<?php
session_start();
require_once __DIR__ . '/config.php';

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

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user !== null) return $user;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
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

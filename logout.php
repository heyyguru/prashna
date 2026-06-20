<?php
require_once __DIR__ . '/helpers.php';

if (isset($_COOKIE['refresh_token'])) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET refresh_token = NULL WHERE refresh_token = ?");
    $stmt->execute([$_COOKIE['refresh_token']]);
}

$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
setcookie('access_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Strict'
]);
setcookie('refresh_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

$_SESSION = [];
session_destroy();
header('Location: /login.php');
exit;

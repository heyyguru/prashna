<?php
require_once __DIR__ . '/helpers.php';

if (isset($_COOKIE['refresh_token'])) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET refresh_token = NULL WHERE refresh_token = ?");
    $stmt->execute([$_COOKIE['refresh_token']]);
}

setcookie('access_token', '', time() - 3600, '/');
setcookie('refresh_token', '', time() - 3600, '/');

$_SESSION = [];
session_destroy();
header('Location: /login.php');
exit;

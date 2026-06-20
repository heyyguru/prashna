<?php
require_once __DIR__ . '/helpers.php';

$token = $_GET['token'] ?? '';

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    set_flash('error', 'Invalid or missing verification token.');
    redirect('/login.php');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if ($user) {
    $stmt = $pdo->prepare("UPDATE users SET email_verified_at = CURRENT_TIMESTAMP, verification_token = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    set_flash('success', 'Email verified successfully! You can now log in.');
} else {
    set_flash('error', 'Invalid verification token or email already verified.');
}

redirect('/login.php');

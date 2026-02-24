<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (!is_logged_in() && !in_array(strtolower($message), ['hi', 'hello', 'hey', 'hii', 'helo', 'heyy'])) {
    echo json_encode(['reply' => 'Please login to submit your doubt.']);
    exit;
}

if ($message === '') {
    echo json_encode(['reply' => 'Please type a message.']);
    exit;
}

$lower = strtolower($message);

$greetings = ['hi', 'hello', 'hey', 'hii', 'helo', 'heyy'];
if (in_array($lower, $greetings)) {
    echo json_encode(['reply' => "Hi! I'm HeyyGuru Assistant. Please login to ask your doubt, or type your question if you're already logged in."]);
    exit;
}

if (strpos($lower, 'how are you') !== false) {
    echo json_encode(['reply' => "I'm good! How can I help you today?"]);
    exit;
}

$user = current_user();
if (!$user || $user['role'] !== 'student') {
    if (!in_array($lower, $greetings) && strpos($lower, 'how are you') === false) {
        echo json_encode(['reply' => 'Please login to submit your doubt.']);
        exit;
    }
}

if ($user && $user['role'] === 'student') {
    $csrfToken = $input['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        echo json_encode(['reply' => 'Session expired. Please refresh the page and try again.']);
        exit;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO doubts (student_id, subject, question_text) VALUES (?, 'General', ?)");
    $stmt->execute([$user['id'], $message]);

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    echo json_encode(['reply' => "Thanks! Your doubt is submitted. A mentor will reply soon.", 'csrf_token' => $_SESSION['csrf_token']]);
    exit;
} else {
    // If user is mentor, they shouldn't be asking doubts here.
    if ($user && $user['role'] === 'mentor') {
        echo json_encode(['reply' => 'Mentors cannot submit doubts. Please login as a student.']);
        exit;
    }
    echo json_encode(['reply' => 'Please login as a student to submit your doubt.']);
    exit;
}

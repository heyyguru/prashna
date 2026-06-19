<?php
require_once __DIR__ . '/../helpers.php';
require_student();
$user = current_user();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $errors[] = 'Invalid form submission.'; }

    $subject = trim($_POST['subject'] ?? '');
    $question = trim($_POST['question_text'] ?? '');

    if ($subject === '') $errors[] = 'Subject is required.';
    if ($question === '') $errors[] = 'Question is required.';

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO doubts (student_id, subject, question_text) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $subject, $question]);
        $doubt_id = $pdo->lastInsertId();
        
        // Notify Mentors
        $mentor_subject = "New Doubt Asked: " . $subject;
        $mentor_body = "A new doubt has been posted by " . h($user['name']) . ".<br>Subject: " . h($subject) . "<br>Question: " . h($question) . "<br><a href='https://heyyguru.in/mentor/view_doubt.php?id=" . $doubt_id . "'>View and Reply</a>";
        sendEmailToMentors($mentor_subject, $mentor_body);

        // Notify Student
        $student_subject = "Doubt Submitted Successfully";
        $student_body = "Hi " . h($user['name']) . ", your doubt on " . h($subject) . " has been submitted. Our mentors will reply soon.";
        sendEmail($user['email'], $student_subject, $student_body);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        set_flash('success', 'Your doubt has been submitted! A mentor will reply soon.');
        redirect('/student/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask a Doubt - <?= h(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="/css/logosq.png">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="brand">
                <img src="/css/favicon.png" alt="<?= h(APP_NAME) ?> Logo" class="logo">
                <span><?= h(APP_NAME) ?></span>
            </a>
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="nav-overlay" id="navOverlay"></div>
            <div class="nav-links" id="navLinks">
                <a href="/">Home</a>
                <a href="/student/dashboard.php">My Doubts</a>
                <a href="/student/new_doubt.php">Ask Doubt</a>
                <a href="/logout.php" class="btn-heyyguru">Logout</a>
                <a href="https://heyyguru.in/courses" class="btn-explore">Explore Courses</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <h1 class="page-title">Ask a New Doubt</h1>
        <?php $f = get_flash(); if ($f): ?>
            <div class="alert alert-<?= $f['type'] ?>"><?= h($f['msg']) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= h($e) ?></div>
        <?php endforeach; ?>
        <div class="card">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Subject *</label>
                    <select name="subject" required>
                        <option value="">-- Select Subject --</option>
                        <option <?= ($subject ?? '') === 'Mathematics/Vedic Maths' ? 'selected' : '' ?>>Mathematics</option>
                        <option <?= ($subject ?? '') === 'Physics' ? 'selected' : '' ?>>Physics</option>
                        <option <?= ($subject ?? '') === 'Chemistry' ? 'selected' : '' ?>>Chemistry</option>
                        <option <?= ($subject ?? '') === 'Biology' ? 'selected' : '' ?>>Biology</option>
                        <option <?= ($subject ?? '') === 'Learn India' ? 'selected' : '' ?>>Learn India</option>
                        <option <?= ($subject ?? '') === 'English Grammar' ? 'selected' : '' ?>>English</option>
                        <option <?= ($subject ?? '') === 'EVS/Science' ? 'selected' : '' ?>>EVS/Science</option>
                        <option <?= ($subject ?? '') === 'Social Science' ? 'selected' : '' ?>>Social Science</option>
                        <option <?= ($subject ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Your Question *</label>
                    <textarea name="question_text" required placeholder="Describe your doubt in detail..."><?= h($question ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Date of Doubt</label>
                    <input type="date" name="doubt_date" value="<?= date('Y-m-d') ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Submit Doubt</button>
            </form>
            <div style="margin-top: 25px; text-align: center; border-top: 1px solid var(--border); padding-top: 20px;">
                <a href="/student/dashboard.php" class="btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to My Doubts
                </a>
            </div>
        </div>
    </div>
    <script src="/js/chat.js"></script>
</body>
</html>

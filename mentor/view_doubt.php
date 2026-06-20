<?php
require_once __DIR__ . '/../helpers.php';

require_mentor();
$user = current_user();
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/mentor/dashboard.php');

// Delete doubt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doubt'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid form submission.');
    } else {
        $checkStmt = $pdo->prepare("SELECT 1 FROM replies WHERE doubt_id = ? AND mentor_id = ?");
        $checkStmt->execute([$id, $user['id']]);
        if ($checkStmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM doubts WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('success', 'Doubt deleted successfully.');
            redirect('/mentor/dashboard.php');
        } else {
            set_flash('error', 'Permission denied: You can only delete doubts you have replied to.');
            redirect('/mentor/view_doubt.php?id=' . $id);
        }
    }
}

// Fetch doubt + student
$stmt = $pdo->prepare("
    SELECT d.*,
           u.name  AS student_name,
           u.phone AS student_phone,
           u.email AS student_email
    FROM doubts d
    JOIN users u ON d.student_id = u.id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$doubt = $stmt->fetch();

if (!$doubt) {
    set_flash('error', 'Doubt not found.');
    redirect('/mentor/dashboard.php');
}

// Fetch replies
$rs = $pdo->prepare("
    SELECT r.*, u.name AS mentor_name
    FROM replies r
    JOIN users u ON r.mentor_id = u.id
    WHERE r.doubt_id = ?
    ORDER BY r.created_at DESC
");
$rs->execute([$id]);
$replies = $rs->fetchAll();

$hasReplied = false;
foreach ($replies as $r) {
    if ((int)$r['mentor_id'] === (int)$user['id']) {
        $hasReplied = true;
        break;
    }
}

// Reply submit
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_doubt'])) {
    if (!verify_csrf()) { $errors[] = 'Invalid form submission.'; }

    $answer = trim($_POST['answer_text'] ?? '');
    
    if ($answer === '') {
        $errors[] = 'Answer cannot be empty.';
    } elseif (strlen($answer) > 5000) {
        $errors[] = 'Answer is too long (maximum 5000 characters).';
    }

    if (empty($errors)) {
        // Insert reply
        $stmt = $pdo->prepare("INSERT INTO replies (doubt_id, mentor_id, answer_text) VALUES (?, ?, ?)");
        $stmt->execute([$id, $user['id'], $answer]);

        // MySQL: update doubt status + timestamp
        $pdo->prepare("UPDATE doubts SET status = 'answered', answered_at = NOW() WHERE id = ?")
            ->execute([$id]);

        // Notify student (email optional)
        $studentEmail = $doubt['student_email'] ?? '';
        if (!empty($studentEmail)) {
            $emailBody =
                "Hi " . h($doubt['student_name']) . ", your doubt on '" . h($doubt['subject']) . "' has been answered.<br><br>"
                . "<strong>Mentor's Reply:</strong><br>" . nl2br(h($answer)) . "<br><br>"
                . "Login to view more details.";

            sendEmail($studentEmail, 'Your doubt has been answered!', $emailBody);
        }

        // ✅ Notify student (SMS optional - won't crash)
        if (function_exists('sendSMS')) {
            sendSMS($doubt['student_phone'], "Hi {$doubt['student_name']}, your doubt has been answered on HeyyGuru!");
        }

        set_flash('success', 'Reply sent successfully!');
        redirect('/mentor/view_doubt.php?id=' . $id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Doubt - <?= h(APP_NAME) ?></title>
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
                <a href="/mentor/dashboard.php">Dashboard</a>
                <a href="/logout.php" class="btn-heyyguru">Logout</a>
                <a href="https://heyyguru.in/courses" class="btn-explore">Explore Courses</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title" style="display:flex; justify-content:center; align-items:center; gap:15px;">
            Doubt #<?= (int)$id ?>
            <span class="badge badge-<?= h($doubt['status']) ?>"><?= h($doubt['status']) ?></span>

            <?php if ($hasReplied): ?>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this doubt?');" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" name="delete_doubt" class="btn btn-sm btn-danger" style="padding: 8px 15px; font-size: 0.9rem;">Delete Doubt</button>
            </form>
            <?php endif; ?>
        </h1>

        <?php $f = get_flash(); if ($f): ?>
            <div class="alert alert-<?= h($f['type']) ?>"><?= h($f['msg']) ?></div>
        <?php endif; ?>

        <div class="student-info">
            <strong>Student:</strong> <?= h($doubt['student_name']) ?> |
            <strong>Phone:</strong> <?= h($doubt['student_phone']) ?>
            <?php if (!empty($doubt['student_email'])): ?>
                | <strong>Email:</strong> <?= h($doubt['student_email']) ?>
            <?php endif; ?>
        </div>

        <div class="card doubt-detail-card">
            <div class="doubt-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid var(--border);">
                <span class="badge badge-secondary" style="background:#e7f5ff; color:#1971c2; border:1px solid #a5d8ff;">
                    <?= h($doubt['subject']) ?>
                </span>
                <span class="badge badge-<?= h($doubt['status']) ?>"><?= h($doubt['status']) ?></span>
            </div>

            <div class="doubt-content">
                <p style="white-space:pre-wrap; font-size:1.15rem; color:var(--text); line-height:1.6; margin-bottom:20px;">
                    <?= h($doubt['question_text']) ?>
                </p>

                <div class="doubt-footer" style="font-size:0.9rem; color:var(--text-light); background:#f8fafc; padding:15px; border-radius:12px; border:1px solid var(--border);">
                    <strong>Asked by:</strong> <?= h($doubt['student_name']) ?> (<?= h($doubt['student_phone']) ?>)<br>
                    <strong>Date:</strong> <?= h($doubt['created_at']) ?>
                </div>
            </div>
        </div>

        <?php if (!empty($replies)): ?>
            <h2 style="font-size:1.1rem;margin:20px 0 10px;">Replies</h2>
            <?php foreach ($replies as $r): ?>
                <div class="reply-box">
                    <div class="reply-label">Reply by <?= h($r['mentor_name']) ?></div>
                    <p style="white-space:pre-wrap;"><?= h($r['answer_text']) ?></p>
                    <p style="font-size:0.8rem;color:#636E72;margin-top:6px;"><?= h($r['created_at']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="card">
            <h2 style="font-size:1.1rem;margin-bottom:12px;">Write a Reply</h2>

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-error"><?= h($e) ?></div>
            <?php endforeach; ?>

            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <textarea name="answer_text" required placeholder="Type your answer here..."><?= h($_POST['answer_text'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block"
                        style="background: linear-gradient(135deg, var(--success) 0%, #00a884 100%); font-weight: 800; padding: 15px; border-radius: 16px; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(0, 184, 148, 0.2);">
                    Send Reply
                </button>
            </form>
        </div>

        <div style="margin: 30px 0; text-align: center;">
            <a href="/mentor/dashboard.php" class="btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <script src="/js/chat.js"></script>
</body>
</html>
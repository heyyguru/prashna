<?php
require_once __DIR__ . '/../helpers.php';
require_student();
$user = current_user();
$pdo = getDB();

$stmt = $pdo->prepare("SELECT d.*, (SELECT COUNT(*) FROM replies r WHERE r.doubt_id = d.id) as reply_count FROM doubts d WHERE d.student_id = ? ORDER BY d.created_at DESC");
$stmt->execute([$user['id']]);
$doubts = $stmt->fetchAll();

$open = 0; $answered = 0;
foreach ($doubts as $d) {
    if ($d['status'] === 'open') $open++;
    else $answered++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - <?= h(APP_NAME) ?></title>
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
        <h1 class="page-title">Welcome, <?= h($user['name']) ?>!</h1>

        <?php $f = get_flash(); if ($f): ?>
            <div class="alert alert-<?= $f['type'] ?>"><?= h($f['msg']) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="number"><?= count($doubts) ?></div>
                <div class="label">Total Doubts</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $open ?></div>
                <div class="label">Open</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $answered ?></div>
                <div class="label">Answered</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header-flex" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                <h2 style="font-size:1.4rem; font-weight:850; color:var(--text); letter-spacing:-0.5px;">My Doubts</h2>
                <a href="/student/new_doubt.php" class="btn btn-primary" style="padding:12px 24px; border-radius:14px; font-size:0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    New Doubt
                </a>
            </div>

            <?php if (empty($doubts)): ?>
                <div class="empty-state">
                    <p>You haven't asked any doubts yet.</p>
                    <a href="/student/new_doubt.php" class="btn btn-primary" style="margin-top:12px;">Ask Your First Doubt</a>
                </div>
            <?php else: ?>
                <div class="doubt-list">
                    <?php foreach ($doubts as $d): ?>
                        <div class="doubt-item-container">
                            <div class="doubt-item">
                                <div class="doubt-info">
                                    <h3><?= h($d['subject']) ?></h3>
                                    <p style="font-size:1.1rem; line-height:1.6; color:var(--text);"><?= h($d['question_text']) ?></p>
                                    <p style="margin-top:8px;font-size:0.8rem;color:var(--text-light);"><?= h($d['created_at']) ?></p>
                                </div>
                                <div class="doubt-meta">
                                    <span class="badge badge-<?= $d['status'] ?>"><?= h($d['status']) ?></span>
                                    <span class="badge badge-secondary"><?= $d['reply_count'] ?> replies</span>
                                </div>
                            </div>
                            <?php if ($d['status'] === 'answered'):
                                $rs = $pdo->prepare("SELECT r.*, u.name as mentor_name FROM replies r JOIN users u ON r.mentor_id = u.id WHERE r.doubt_id = ? ORDER BY r.created_at DESC LIMIT 1");
                                $rs->execute([$d['id']]);
                                $reply = $rs->fetch();
                                if ($reply): ?>
                                    <div class="reply-box">
                                        <div class="reply-label">Mentor Reply (<?= h($reply['mentor_name']) ?>)</div>
                                        <p style="white-space:pre-wrap; line-height:1.6;"><?= h($reply['answer_text']) ?></p>
                                        <p style="font-size:0.85rem;color:var(--text-light);margin-top:12px;"><?= h($reply['created_at']) ?></p>
                                    </div>
                            <?php endif; endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="/js/chat.js"></script>
</body>
</html>

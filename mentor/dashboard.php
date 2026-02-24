<?php
require_once __DIR__ . '/../helpers.php';
require_mentor();
$user = current_user();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid form submission.');
    } else {
        $deleteId = (int)$_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM doubts WHERE id = ?");
        $stmt->execute([$deleteId]);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        set_flash('success', 'Doubt deleted successfully.');
    }
    redirect('/mentor/dashboard.php');
}

$filter = $_GET['filter'] ?? 'open';
if ($filter === 'all') {
    $stmt = $pdo->prepare("SELECT d.*, u.name as student_name FROM doubts d JOIN users u ON d.student_id = u.id ORDER BY d.created_at DESC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT d.*, u.name as student_name FROM doubts d JOIN users u ON d.student_id = u.id WHERE d.status = ? ORDER BY d.created_at DESC");
    $stmt->execute([$filter]);
}
$doubts = $stmt->fetchAll();

$countOpen = $pdo->query("SELECT COUNT(*) as c FROM doubts WHERE status='open'")->fetch()['c'];
$countAnswered = $pdo->query("SELECT COUNT(*) as c FROM doubts WHERE status='answered'")->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard - <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="brand">
                <img src="/css/logo.jpg" alt="Prashna Logo" class="logo">
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
        <h1 class="page-title">Mentor Dashboard</h1>

        <div style="margin-bottom: 20px; text-align: right;">
            <button id="downloadRecords" class="btn btn-secondary" style="font-size: 0.9rem; padding: 8px 16px;">Download Records (CSV)</button>
        </div>

        <?php $f = get_flash(); if ($f): ?>
            <div class="alert alert-<?= $f['type'] ?>"><?= h($f['msg']) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="number"><?= $countOpen ?></div>
                <div class="label">Open Doubts</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $countAnswered ?></div>
                <div class="label">Answered</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $countOpen + $countAnswered ?></div>
                <div class="label">Total</div>
            </div>
        </div>

        <div class="card">
            <div class="search-box" style="margin-bottom: 20px;">
                <input type="text" id="mentorSearch" placeholder="Search by student name or subject..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;" class="card-header-flex">
                <h2 style="font-size:1.4rem; font-weight:850; color:var(--text); letter-spacing:-0.5px;">Student Doubts</h2>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="?filter=open" class="btn <?= $filter === 'open' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:10px 20px; border-radius:12px; font-size:0.9rem;">Open (<?= $countOpen ?>)</a>
                    <a href="?filter=answered" class="btn <?= $filter === 'answered' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:10px 20px; border-radius:12px; font-size:0.9rem;">Answered (<?= $countAnswered ?>)</a>
                    <a href="?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:10px 20px; border-radius:12px; font-size:0.9rem;">All</a>
                </div>
            </div>

            <?php if (empty($doubts)): ?>
                <div class="empty-state">
                    <p>No doubts found.</p>
                </div>
            <?php else: ?>
                <div class="doubt-list">
                    <?php foreach ($doubts as $d): ?>
                        <div class="doubt-item-container <?= $d['status'] === 'answered' ? 'answered' : '' ?>">
                            <div class="doubt-item">
                                <div class="doubt-info">
                                    <h3><?= h($d['subject']) ?> - <?= h($d['student_name']) ?></h3>
                                    <p><?= h(mb_strimwidth($d['question_text'], 0, 150, '...')) ?></p>
                                    <p style="margin-top:8px;font-size:0.8rem;color:var(--text-light);"><?= h($d['created_at']) ?></p>
                                </div>
                                <div class="doubt-meta">
                                    <span class="badge badge-<?= $d['status'] ?>"><?= h($d['status']) ?></span>
                                    <div style="display:flex; gap:8px; align-items: center;">
                                        <a href="/mentor/view_doubt.php?id=<?= $d['id'] ?>" class="btn-view-custom">
                                            <?= $d['status'] === 'open' ? 'Reply' : 'View' ?>
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this doubt?');" style="display:inline; margin:0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="delete_id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="btn-delete-custom">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="/js/chat.js"></script>
</body>
</html>

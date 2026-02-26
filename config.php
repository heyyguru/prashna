<?php
define('APP_NAME', 'Prashna');
define('APP_VERSION', '1.0');

// For Hostinger/production: change to 'mysql' and update credentials below
// For Replit local development: 'sqlite' works out of the box
define('DB_DRIVER', 'sqlite');
define('DB_HOST', 'localhost');
define('DB_NAME', 'heyyguru');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SQLITE_PATH', __DIR__ . '/db/heyyguru.sqlite');

define('ENABLE_EMAIL', true);
define('SMTP_HOST', 'smtp.heyyguru.in');
define('SMTP_PORT', 587);
define('SMTP_USER', 'doubt@heyyguru.in');
define('SMTP_PASS', 'your_password_here'); 
define('SMTP_FROM', 'doubt@heyyguru.in');

function sendEmail(string $to, string $subject, string $body): bool {
    if (!ENABLE_EMAIL || empty($to)) return false;
    $headers = "From: " . SMTP_FROM . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers);
}

function sendEmailToMentors(string $subject, string $body): void {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'mentor' AND email IS NOT NULL AND email != ''");
    $stmt->execute();
    $mentors = $stmt->fetchAll();
    foreach ($mentors as $m) {
        sendEmail($m['email'], $subject, $body);
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } else {
        $dbPath = SQLITE_PATH;
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        $needsInit = !file_exists($dbPath);
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
        if ($needsInit) {
            initSQLiteDB($pdo);
        }
    }
    return $pdo;
}

function initSQLiteDB(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        role TEXT NOT NULL DEFAULT 'student' CHECK(role IN ('student','mentor')),
        name TEXT NOT NULL,
        phone TEXT NOT NULL UNIQUE,
        email TEXT DEFAULT NULL,
        password_hash TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS doubts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        subject TEXT NOT NULL,
        question_text TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'open' CHECK(status IN ('open','answered')),
        created_at DATETIME NOT NULL DEFAULT (datetime('now')),
        answered_at DATETIME DEFAULT NULL,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS replies (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        doubt_id INTEGER NOT NULL,
        mentor_id INTEGER NOT NULL,
        answer_text TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (doubt_id) REFERENCES doubts(id) ON DELETE CASCADE,
        FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $hash = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = '9999999999'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (role, name, phone, email, password_hash) VALUES ('mentor', 'Guru Mentor', '9999999999', 'mentor@heyyguru.com', ?)");
        $stmt->execute([$hash]);
    }
}

function sendEmail(string $to, string $subject, string $body): bool {
    if (!ENABLE_EMAIL || empty($to)) return false;
    $headers = "From: " . SMTP_FROM . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers);
}

function sendEmailToMentors(string $subject, string $body): void {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'mentor' AND email IS NOT NULL AND email != ''");
    $stmt->execute();
    $mentors = $stmt->fetchAll();
    foreach ($mentors as $m) {
        sendEmail($m['email'], $subject, $body);
    }
}

function sendSMS(string $phone, string $msg): bool {
    return false;
}

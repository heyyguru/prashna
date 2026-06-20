<?php
// -------------------------
// DEBUG (turn OFF later)
// -------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Log errors to a file (create logs/ folder)
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php-error.log');

// -------------------------
// App
// -------------------------
define('APP_NAME', 'Prashna');
define('APP_VERSION', '1.0');

// -------------------------
// Load .env
// -------------------------
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim(trim($value), '"\'');
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// -------------------------
// DB (MySQL)
// -------------------------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

// -------------------------
// Security
$jwtSecret = getenv('JWT_SECRET');
if (empty($jwtSecret) || $jwtSecret === 'fallback_secret_key_change_me') {
    die("CRITICAL ERROR: JWT_SECRET environment variable is not set properly.");
}
define('JWT_SECRET', $jwtSecret);

// -------------------------
// Email (Zoho SMTP settings)
// NOTE: PHP mail() will ignore SMTP_*.
// We'll keep config ready; use PHPMailer to actually send via Zoho.
// -------------------------
define('ENABLE_EMAIL', true);
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.zoho.in'); // or smtp.zoho.com
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);            // 587 (TLS) recommended; use 465 (SSL) if needed
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: ''); // <-- App Password only
define('SMTP_FROM', getenv('SMTP_FROM') ?: '');

// -------------------------
// DB Connection
// -------------------------
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        // Log full error and show short error on screen
        error_log("DB Connection Failed: " . $e->getMessage());
        http_response_code(500);
        die("Database connection failed. Check logs/php-error.log");
    }

    return $pdo;
}

// -------------------------
// TEMP Email (uses mail())
// This does NOT use Zoho SMTP.
// Replace with PHPMailer if you want Zoho SMTP delivery.
// -------------------------
function sendEmail(string $to, string $subject, string $body): bool {
    if (!ENABLE_EMAIL || empty($to)) return false;

    $headers = "From: " . SMTP_FROM . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $body, $headers);
}

function sendEmailToMentors(string $subject, string $body): void {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT email FROM users WHERE role='mentor' AND email IS NOT NULL AND email != ''");
    $stmt->execute();

    foreach ($stmt->fetchAll() as $m) {
        sendEmail($m['email'], $subject, $body);
    }
}
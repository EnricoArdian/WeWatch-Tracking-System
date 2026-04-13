<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

function loadEnv(string $envFilePath): void
{
    if (!is_file($envFilePath) || !is_readable($envFilePath)) {
        return;
    }

    $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '') {
            continue;
        }

        $isQuoted = (str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, '\'') && str_ends_with($value, '\''));
        if ($isQuoted && strlen($value) >= 2) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function envString(string $key, string $default): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return (string) $value;
}

function envBool(string $key, bool $default): bool
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

loadEnv(__DIR__ . '/.env');

define('DB_HOST', envString('DB_HOST', '127.0.0.1'));
define('DB_NAME', envString('DB_NAME', 'sales_tracking'));
define('DB_USER', envString('DB_USER', 'root'));
define('DB_PASS', envString('DB_PASS', ''));
define('DB_CHARSET', envString('DB_CHARSET', 'utf8mb4'));

define('MIDTRANS_IS_PRODUCTION', envBool('MIDTRANS_IS_PRODUCTION', false));
define('MIDTRANS_SERVER_KEY', envString('MIDTRANS_SERVER_KEY', 'YOUR_SERVER_KEY'));
define('MIDTRANS_CLIENT_KEY', envString('MIDTRANS_CLIENT_KEY', 'YOUR_CLIENT_KEY'));

define('APP_BASE_URL', envString('APP_BASE_URL', 'http://localhost/LINK'));

$dsn = 'mysql:host=' . (string) DB_HOST . ';dbname=' . (string) DB_NAME . ';charset=' . (string) DB_CHARSET;

try {
    $pdo = new PDO($dsn, (string) DB_USER, (string) DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed.');
}

function appTablesReady(PDO $pdo): bool
{
    $required = ['sellers', 'seller_links', 'transactions'];
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = :db_name
        AND table_name = :table_name
    ");

    foreach ($required as $table) {
        $stmt->execute([
            'db_name' => (string) DB_NAME,
            'table_name' => $table,
        ]);
        if ((int) $stmt->fetchColumn() < 1) {
            return false;
        }
    }

    return true;
}

function ensureAppInstalled(PDO $pdo): void
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === 'install.php') {
        return;
    }

    if (appTablesReady($pdo)) {
        return;
    }

    $installUrl = rtrim((string) APP_BASE_URL, '/') . '/install.php';
    $safeInstallUrl = htmlspecialchars($installUrl, ENT_QUOTES, 'UTF-8');
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup Required</title></head><body style="font-family:Arial,sans-serif;padding:24px"><h2>Setup Required</h2><p>Database tables are not installed yet.</p><p>Please run <a href="' . $safeInstallUrl . '">' . $safeInstallUrl . '</a> and click <b>Run Setup</b>.</p></body></html>';
    exit;
}

ensureAppInstalled($pdo);

function tableColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = :db_name
        AND table_name = :table_name
        AND column_name = :column_name
    ");
    $stmt->execute([
        'db_name' => (string) DB_NAME,
        'table_name' => $tableName,
        'column_name' => $columnName,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensureAppSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin','seller') NOT NULL DEFAULT 'seller',
            remember_token VARCHAR(64) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role ENUM('admin','seller') NOT NULL,
            username VARCHAR(100) NOT NULL,
            activity VARCHAR(120) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Backward-compatible upgrades for older database versions.
    if (!tableColumnExists($pdo, 'transactions', 'seller_id')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN seller_id INT DEFAULT NULL");
    }
    if (!tableColumnExists($pdo, 'transactions', 'seller_link_code')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN seller_link_code VARCHAR(50) DEFAULT NULL");
    }
    if (!tableColumnExists($pdo, 'transactions', 'buyer_email')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN buyer_email VARCHAR(150) DEFAULT NULL");
    }
    if (!tableColumnExists($pdo, 'transactions', 'commission_amount')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN commission_amount INT NOT NULL DEFAULT 0");
    }
    if (!tableColumnExists($pdo, 'transactions', 'payment_method')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'Not selected'");
    }
    if (!tableColumnExists($pdo, 'transactions', 'payment_proof')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN payment_proof VARCHAR(255) DEFAULT NULL");
    }
    if (!tableColumnExists($pdo, 'sellers', 'remember_token')) {
        $pdo->exec("ALTER TABLE sellers ADD COLUMN remember_token VARCHAR(64) DEFAULT NULL");
    }
    if (!tableColumnExists($pdo, 'sellers', 'commission_percent')) {
        $pdo->exec("ALTER TABLE sellers ADD COLUMN commission_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00");
        if (tableColumnExists($pdo, 'sellers', 'commission_rate')) {
            $pdo->exec("UPDATE sellers SET commission_percent = commission_rate");
        }
    }

    $statusTypeStmt = $pdo->prepare("
        SELECT COLUMN_TYPE
        FROM information_schema.columns
        WHERE table_schema = :db_name
        AND table_name = 'transactions'
        AND column_name = 'status'
        LIMIT 1
    ");
    $statusTypeStmt->execute(['db_name' => (string) DB_NAME]);
    $statusType = strtolower((string) $statusTypeStmt->fetchColumn());
    if ($statusType !== '' && strpos($statusType, 'rejected') === false) {
        $pdo->exec("ALTER TABLE transactions MODIFY status ENUM('pending','paid','failed','rejected') NOT NULL DEFAULT 'pending'");
    }

    $seedAdmin = $pdo->prepare('SELECT id FROM users WHERE role = :role LIMIT 1');
    $seedAdmin->execute(['role' => 'admin']);
    if (!$seedAdmin->fetch()) {
        $insertAdmin = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)');
        $insertAdmin->execute([
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
    }
}

ensureAppSchema($pdo);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function appUrl(string $path = ''): string
{
    $base = rtrim((string) APP_BASE_URL, '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function publicBaseUrl(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        $host = trim((string) ($_SERVER['SERVER_ADDR'] ?? 'localhost'));
    }

    $scriptName = trim((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseDir = trim(str_replace('\\', '/', dirname($scriptName)), '/');

    return $baseDir === ''
        ? $scheme . '://' . $host
        : $scheme . '://' . $host . '/' . $baseDir;
}

function formatRupiah(int $value): string
{
    return 'Rp ' . number_format($value, 0, ',', '.');
}

function getProducts(): array
{
    return [
        'Starter Package' => 99000,
        'Growth Package' => 149000,
        'Pro Package' => 249000,
    ];
}

function sanitizeRef(string $ref): string
{
    $ref = strtolower(trim($ref));
    if ($ref === '' || !preg_match('/^(seller|link)_[a-z0-9_]{3,44}$/', $ref)) {
        return '';
    }
    return $ref;
}

function sanitizeText(string $text, int $max = 100): string
{
    $text = trim($text);
    $text = preg_replace('/\s+/', ' ', $text ?? '') ?? '';
    if ($text === '' || mb_strlen($text) > $max) {
        return '';
    }
    return $text;
}

function sanitizeUsername(string $username): string
{
    $username = strtolower(trim($username));
    if (!preg_match('/^[a-z0-9_]{4,30}$/', $username)) {
        return '';
    }
    return $username;
}

function findSellerByReferralCode(PDO $pdo, string $ref): ?array
{
    if ($ref === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, name, referral_code, commission_rate FROM sellers WHERE referral_code = :referral_code LIMIT 1');
    $stmt->execute(['referral_code' => $ref]);
    $seller = $stmt->fetch();

    return $seller ?: null;
}

function generateSellerReferralCode(PDO $pdo, string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug ?? '') ?? '';
    $slug = trim($slug, '_');
    if ($slug === '') {
        $slug = 'seller';
    }

    $base = 'seller_' . substr($slug, 0, 20);
    $attempt = 0;

    while (true) {
        $suffix = substr(bin2hex(random_bytes(3)), 0, 6);
        $referralCode = $base . '_' . $suffix;

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM sellers WHERE referral_code = :referral_code');
        $stmt->execute(['referral_code' => $referralCode]);
        $exists = (int) $stmt->fetchColumn() > 0;

        if (!$exists) {
            return $referralCode;
        }

        $attempt++;
        if ($attempt > 20) {
            throw new RuntimeException('Unable to generate unique referral code.');
        }
    }
}

function generateLinkCode(PDO $pdo, int $sellerId, string $label): string
{
    $slug = strtolower(trim($label));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug ?? '') ?? 'channel';
    $slug = trim($slug, '_');
    if ($slug === '') {
        $slug = 'channel';
    }
    $base = 'link_' . $sellerId . '_' . substr($slug, 0, 12);

    for ($i = 0; $i < 25; $i++) {
        $code = $base . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM seller_links WHERE link_code = :code');
        $stmt->execute(['code' => $code]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $code;
        }
    }

    throw new RuntimeException('Unable to generate unique link code.');
}

function generateTransactionCode(PDO $pdo): string
{
    $datePart = date('Ymd');
    $prefix = 'INV-' . $datePart . '-';

    $stmt = $pdo->prepare('SELECT transaction_code FROM transactions WHERE transaction_code LIKE :prefix ORDER BY id DESC LIMIT 1');
    $stmt->execute(['prefix' => $prefix . '%']);
    $lastCode = (string) $stmt->fetchColumn();

    $lastNumber = 0;
    if ($lastCode !== '' && preg_match('/^INV-\d{8}-(\d{4})$/', $lastCode, $matches)) {
        $lastNumber = (int) $matches[1];
    }

    return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
}

function findSellerByUsername(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM sellers WHERE LOWER(username) = LOWER(:username) LIMIT 1');
    $stmt->execute(['username' => $username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function findAdminByUsername(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(username)=LOWER(:username) AND role=:role LIMIT 1');
    $stmt->execute(['username' => $username, 'role' => 'admin']);
    $row = $stmt->fetch();
    return $row ?: null;
}

function rememberCookieOptions(int $expires): array
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function setRememberLoginCookie(string $payload): void
{
    setcookie('user_login', $payload, rememberCookieOptions(time() + (86400 * 30)));
    $_COOKIE['user_login'] = $payload;
}

function clearRememberLoginCookie(): void
{
    setcookie('user_login', '', rememberCookieOptions(time() - 3600));
    unset($_COOKIE['user_login']);
}

function logActivity(PDO $pdo, int $userId, string $role, string $username, string $activity): void
{
    $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, role, username, activity, ip_address) VALUES (:user_id, :role, :username, :activity, :ip_address)');
    $stmt->execute([
        'user_id' => $userId,
        'role' => $role,
        'username' => $username,
        'activity' => $activity,
        'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    ]);
}

function loginAdminSession(array $admin, bool $remember): void
{
    global $pdo;
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = (string) $admin['username'];
    $_SESSION['role'] = 'admin';
    if (!$remember) {
        clearRememberLoginCookie();
    }
    if ($remember) {
        $token = bin2hex(random_bytes(16));
        $pdo->prepare('UPDATE users SET remember_token=:token WHERE id=:id')->execute(['token' => $token, 'id' => (int) $admin['id']]);
        setRememberLoginCookie('admin:' . (int) $admin['id'] . ':' . $token);
    }
}

function loginSellerSession(array $seller, bool $remember): void
{
    global $pdo;
    $_SESSION['seller_logged_in'] = true;
    $_SESSION['seller_id'] = (int) $seller['id'];
    $_SESSION['seller_name'] = (string) $seller['name'];
    $_SESSION['role'] = 'seller';
    if (!$remember) {
        clearRememberLoginCookie();
    }
    if ($remember) {
        $token = bin2hex(random_bytes(16));
        $pdo->prepare('UPDATE sellers SET remember_token=:token WHERE id=:id')->execute(['token' => $token, 'id' => (int) $seller['id']]);
        setRememberLoginCookie('seller:' . (int) $seller['id'] . ':' . $token);
    }
}

function autoLoginFromRememberCookie(PDO $pdo): void
{
    if (!empty($_SESSION['role'])) {
        return;
    }
    $raw = (string) ($_COOKIE['user_login'] ?? '');
    if ($raw === '' || substr_count($raw, ':') !== 2) {
        return;
    }
    [$role, $idRaw, $token] = explode(':', $raw, 3);
    $id = (int) $idRaw;
    if ($id <= 0 || $token === '') {
        clearRememberLoginCookie();
        return;
    }
    if ($role === 'admin') {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id=:id AND role=:role LIMIT 1');
        $stmt->execute(['id' => $id, 'role' => 'admin']);
        $admin = $stmt->fetch();
        if ($admin && hash_equals((string) ($admin['remember_token'] ?? ''), $token)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = (string) $admin['username'];
            $_SESSION['role'] = 'admin';
            return;
        }
    }
    if ($role === 'seller') {
        $stmt = $pdo->prepare('SELECT * FROM sellers WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $seller = $stmt->fetch();
        if ($seller && hash_equals((string) ($seller['remember_token'] ?? ''), $token)) {
            $_SESSION['seller_logged_in'] = true;
            $_SESSION['seller_id'] = (int) $seller['id'];
            $_SESSION['seller_name'] = (string) $seller['name'];
            $_SESSION['role'] = 'seller';
            return;
        }
    }
    clearRememberLoginCookie();
}

autoLoginFromRememberCookie($pdo);

function requireAdmin(): void
{
    if (empty($_SESSION['admin_logged_in']) || (string) ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: login.php');
        exit;
    }
}

function requireSeller(): void
{
    if (empty($_SESSION['seller_logged_in']) || empty($_SESSION['seller_id']) || (string) ($_SESSION['role'] ?? '') !== 'seller') {
        header('Location: login.php');
        exit;
    }
}

function midtransApiBase(): string
{
    return (bool) MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com'
        : 'https://app.sandbox.midtrans.com';
}

function midtransSnapBase(): string
{
    return (bool) MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
}

function createSnapToken(array $payload): array
{
    $url = midtransApiBase() . '/snap/v1/transactions';
    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Unable to initialize cURL.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode((string) MIDTRANS_SERVER_KEY . ':'),
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        return ['ok' => false, 'error' => 'Midtrans request failed: ' . $curlError];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'Invalid Midtrans response.'];
    }

    if ($httpCode >= 400 || empty($decoded['token'])) {
        $message = (string) ($decoded['error_messages'][0] ?? $decoded['status_message'] ?? 'Failed to create Snap token.');
        return ['ok' => false, 'error' => $message];
    }

    return ['ok' => true, 'token' => (string) $decoded['token']];
}

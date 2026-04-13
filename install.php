<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$status = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec('CREATE DATABASE IF NOT EXISTS sales_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE sales_tracking');
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sellers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                referral_code VARCHAR(50) NOT NULL UNIQUE,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                commission_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
                commission_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS seller_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                seller_id INT NOT NULL,
                link_code VARCHAR(50) NOT NULL UNIQUE,
                link_label VARCHAR(100) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_code VARCHAR(50) NOT NULL UNIQUE,
                buyer_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                buyer_email VARCHAR(150) DEFAULT NULL,
                product VARCHAR(100) NOT NULL,
                price INT NOT NULL,
                seller_id INT DEFAULT NULL,
                seller_link_code VARCHAR(50) DEFAULT NULL,
                commission_amount INT NOT NULL DEFAULT 0,
                payment_method VARCHAR(30) NOT NULL DEFAULT 'Not selected',
                payment_proof VARCHAR(255) DEFAULT NULL,
                status ENUM('pending','paid','failed','rejected') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("INSERT IGNORE INTO sellers (id, name, referral_code, username, password_hash, commission_rate, commission_percent) VALUES (1, 'Default Seller', 'seller_default_001', 'seller1', '" . password_hash('seller123', PASSWORD_DEFAULT) . "', 10.00, 10.00)");
        $pdo->exec("INSERT IGNORE INTO seller_links (seller_id, link_code, link_label) VALUES (1, 'link_1_default_000001', 'Default Channel')");
        $status[] = 'Database and table are ready.';
        $status[] = 'Default seller login: seller1 / seller123';
        $status[] = 'Configure Midtrans keys in .env before payment test.';
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-slate-50 p-4 font-sans text-slate-900 sm:p-8">
    <div class="mx-auto max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-xl sm:p-8">
        <h1 class="flex items-center gap-2 text-2xl font-bold"><i data-lucide="wrench" class="h-6 w-6 text-indigo-600"></i>Install Sales System</h1>
        <p class="mt-1 text-sm text-slate-500">Initialize database for local XAMPP usage.</p>

        <?php if ($error !== ''): ?>
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (count($status) > 0): ?>
            <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                <?php foreach ($status as $line): ?>
                    <p>- <?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-6 flex flex-col gap-3 sm:flex-row">
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Run Setup</button>
            <a href="index.php" class="rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold hover:bg-slate-100">Open App</a>
            <a href="admin/login.php" class="rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold hover:bg-slate-100">Open Admin</a>
        </form>
    </div>
    <script>
        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>
</body>
</html>

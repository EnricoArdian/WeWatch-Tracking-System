<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['admin_logged_in']) && (string) ($_SESSION['role'] ?? '') === 'admin') {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

    $admin = findAdminByUsername($pdo, $username);
    if ($admin && password_verify($password, (string) $admin['password_hash'])) {
        loginAdminSession($admin, $remember);
        logActivity($pdo, (int) $admin['id'], 'admin', (string) $admin['username'], 'User logged in');
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We Watch Asia Tracking System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/ui.css">
</head>
<body class="wwa-page text-white">
    <div class="min-h-screen flex items-center justify-center px-6">
    <div class="w-full max-w-md">
        <div class="rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-lg">
        <a href="../landing.php" class="wwa-logo-wrap mb-3 flex justify-center">
            <img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 object-contain" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
            <span class="hidden rounded-xl bg-slate-700 px-3 py-1 text-sm font-bold text-white">WeWatch Asia</span>
        </a>
        <h1 class="text-center text-2xl font-bold">We Watch Asia Tracking System</h1>
        <p class="mt-1 text-center text-sm text-slate-400">Admin login</p>

        <?php if ($error !== ''): ?>
            <div class="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-400"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-4" data-submit-spinner>
            <div>
                <label for="username" class="mb-1 block text-sm font-medium">Username</label>
                <input id="username" name="username" type="text" required class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" required class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-500 bg-slate-900">Remember Me</label>
            <button type="submit" class="wwa-btn w-full rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-all duration-300 ease-in-out"><span data-btn-label>Login</span></button>
        </form>
        </div>
        <p class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</p>
    </div>
    </div>
    <script src="../assets/ui.js"></script>
</body>
</html>

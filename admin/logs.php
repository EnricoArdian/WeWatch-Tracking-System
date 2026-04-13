<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
requireAdmin();

$rows = $pdo->query('SELECT username, role, activity, ip_address, created_at FROM activity_logs ORDER BY id DESC LIMIT 500')->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Activity Logs - We Watch Asia</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../assets/ui.css"></head>
<body class="wwa-page text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
<aside class="border-r border-slate-800 bg-slate-900 p-6 text-slate-300"><div class="mb-6 flex items-center gap-3"><a href="../landing.php" class="inline-flex items-center gap-3"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 w-10 object-contain"><span class="text-lg font-semibold text-white">WeWatch Asia</span></a></div><p class="mt-1 text-xs text-slate-300">We Watch Asia Tracking System</p><nav class="mt-6 space-y-2 text-sm"><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="dashboard.php">Dashboard</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="transactions.php">Transactions</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="sellers.php">Sellers</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="analytics.php">Analytics</a><a class="block rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white" href="logs.php">Activity Logs</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" data-confirm-link data-confirm-message="Logout now?" href="logout.php">Logout</a></nav></aside>
<main class="p-4 sm:p-6 lg:p-8"><div class="mb-1 flex items-center gap-2"><img src="../assets/logo.png" alt="logo" class="h-10 object-contain"><h1 class="text-2xl font-bold">Activity Logs</h1></div><p class="text-sm text-slate-500">Authentication and user activity tracking.</p><div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-[900px] w-full text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="px-3 py-2 text-left">Username</th><th class="px-3 py-2 text-left">Role</th><th class="px-3 py-2 text-left">Activity</th><th class="px-3 py-2 text-left">IP Address</th><th class="px-3 py-2 text-left">Time</th></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No activity logs found.</td></tr><?php else: foreach ($rows as $row): ?><tr class="border-t border-slate-100"><td class="px-3 py-2"><?= e((string) $row['username']) ?></td><td class="px-3 py-2"><?= e((string) $row['role']) ?></td><td class="px-3 py-2"><?= e((string) $row['activity']) ?></td><td class="px-3 py-2"><?= e((string) $row['ip_address']) ?></td><td class="px-3 py-2"><?= e((string) $row['created_at']) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></main>
</div>
<footer class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</footer>
<script src="../assets/ui.js"></script>
</body></html>

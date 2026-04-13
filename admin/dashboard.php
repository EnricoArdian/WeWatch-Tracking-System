<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo 'STEP 1 OK<br>'; }

requireAdmin();
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo 'STEP 2 OK<br>'; }

$flash = (string) ($_SESSION['admin_flash'] ?? '');
unset($_SESSION['admin_flash']);

$cardsStmt = $pdo->query("
SELECT
 (SELECT COALESCE(SUM(price),0) FROM transactions WHERE status='paid') AS revenue,
 (SELECT COUNT(*) FROM transactions) AS total_transactions,
 (SELECT COUNT(*) FROM sellers) AS total_sellers,
 (SELECT COALESCE(SUM(commission_amount),0) FROM transactions WHERE status='paid') AS total_commission
");
$cards = $cardsStmt ? ($cardsStmt->fetch() ?: []) : [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>We Watch Asia Tracking System</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><link rel="stylesheet" href="../assets/ui.css"></head>
<body class="wwa-page text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
<aside class="border-r border-slate-800 bg-slate-900 p-6 text-slate-300">
<div class="mb-6 flex items-center gap-3"><a href="../landing.php" class="inline-flex items-center gap-3"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 w-10 object-contain"><span class="text-lg font-semibold text-white">WeWatch Asia</span></a></div>
<p class="mt-1 text-xs text-slate-300">We Watch Asia Tracking System</p>
<nav class="mt-6 space-y-2 text-sm">
<a class="block rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white" href="dashboard.php">Dashboard</a>
<a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="transactions.php">Transactions</a>
<a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="sellers.php">Sellers</a>
<a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="analytics.php">Analytics</a>
<a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="logs.php">Activity Logs</a>
<a class="hidden rounded-lg px-4 py-2 hover:bg-slate-800 lg:block" data-confirm-link data-confirm-message="Logout now?" href="logout.php">Logout</a>
</nav>
</aside>

<main class="p-4 sm:p-6 lg:p-8 transition duration-200">
<div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
<div><div class="mb-1 flex items-center gap-2"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 object-contain" onerror="this.style.display='none';"><h1 class="text-2xl font-bold">We Watch Asia Tracking System</h1></div><p class="text-sm text-slate-500">Monitor revenue, sellers, and recent transaction activity.</p></div>
<a class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700" href="export.php">Export CSV Transactions</a>
</div>
<?php if ($flash !== ''): ?><div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700"><?= e($flash) ?></div><?php endif; ?>

<div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
<div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Revenue Paid</p><p class="wwa-number mt-1 text-xl font-bold"><?= e(formatRupiah((int) ($cards['revenue'] ?? 0))) ?></p></div>
<div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Transactions</p><p class="wwa-number mt-1 text-xl font-bold"><?= number_format((int) ($cards['total_transactions'] ?? 0)) ?></p></div>
<div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sellers</p><p class="wwa-number mt-1 text-xl font-bold"><?= number_format((int) ($cards['total_sellers'] ?? 0)) ?></p></div>
<div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Commission Payout</p><p class="wwa-number mt-1 text-xl font-bold"><?= e(formatRupiah((int) ($cards['total_commission'] ?? 0))) ?></p></div>
</div>

<div class="mt-6 grid gap-4 md:grid-cols-3">
<a href="transactions.php" class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><h2 class="text-sm font-semibold text-white">Transactions</h2><p class="mt-1 text-sm text-slate-300">Review proofs and approve/reject payments.</p></a>
<a href="sellers.php" class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><h2 class="text-sm font-semibold text-white">Sellers</h2><p class="mt-1 text-sm text-slate-300">Manage seller accounts and password resets.</p></a>
<a href="analytics.php" class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><h2 class="text-sm font-semibold text-white">Analytics</h2><p class="mt-1 text-sm text-slate-300">See charts for revenue, links, and seller performance.</p></a>
</div>
</main>
</div>
<footer class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</footer>
<script src="../assets/ui.js"></script>
</body></html>

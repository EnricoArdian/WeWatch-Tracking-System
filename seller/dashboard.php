<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (isset($_GET['logout'])) {
    $sellerId = (int) ($_SESSION['seller_id'] ?? 0);
    $sellerName = (string) ($_SESSION['seller_name'] ?? '');
    if ($sellerId > 0) {
        $pdo->prepare('UPDATE sellers SET remember_token = NULL WHERE id = :id')->execute(['id' => $sellerId]);
        logActivity($pdo, $sellerId, 'seller', $sellerName, 'User logged out');
    }
    clearRememberLoginCookie();
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

requireSeller();
$sellerId = (int) $_SESSION['seller_id'];
$cards = $pdo->prepare("
SELECT
 COALESCE(SUM(CASE WHEN status='paid' THEN price ELSE 0 END),0) AS total_sales,
 COALESCE(SUM(CASE WHEN status='paid' THEN commission_amount ELSE 0 END),0) AS total_commission,
 COUNT(*) AS total_transactions
FROM transactions
WHERE seller_id=:seller_id
");
$cards->execute(['seller_id' => $sellerId]);
$stats = $cards->fetch() ?: [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>We Watch Asia Tracking System</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../assets/ui.css"></head>
<body class="wwa-page text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
<aside class="border-r border-slate-800 bg-slate-900 p-6 text-slate-300">
<div class="mb-6 flex items-center gap-3"><a href="../landing.php" class="inline-flex items-center gap-3"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 w-10 object-contain"><span class="text-lg font-semibold text-white">WeWatch Asia</span></a></div>
<p class="mt-1 text-xs text-slate-300">Welcome, <?= e((string) $_SESSION['seller_name']) ?></p>
<nav class="mt-6 space-y-2 text-sm">
<a class="block rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white" href="dashboard.php">Dashboard</a>
<a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="transactions.php">Transactions</a>
<a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="analytics.php">Analytics</a>
<a class="block rounded-lg px-4 py-2 hover:bg-slate-800" data-confirm-link data-confirm-message="Logout now?" href="?logout=1">Logout</a>
</nav>
</aside>
<main class="p-4 sm:p-6 lg:p-8 transition duration-200">
<div class="mb-1 flex items-center gap-2"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 object-contain" onerror="this.style.display='none';"><h1 class="text-2xl font-bold">We Watch Asia Tracking System</h1></div>
<p class="text-sm text-slate-500">Track your sales and commission with separated pages.</p>
<div class="mt-5 grid gap-3 md:grid-cols-3">
<div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Sales</p><p id="rtTotalSales" class="wwa-number mt-1 text-xl font-bold"><?= e(formatRupiah((int) ($stats['total_sales'] ?? 0))) ?></p></div>
<div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Commission</p><p id="rtTotalCommission" class="wwa-number mt-1 text-xl font-bold"><?= e(formatRupiah((int) ($stats['total_commission'] ?? 0))) ?></p></div>
<div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Transactions</p><p id="rtTotalTransactions" class="wwa-number mt-1 text-xl font-bold"><?= number_format((int) ($stats['total_transactions'] ?? 0)) ?></p></div>
</div>
<div class="mt-6 grid gap-4 md:grid-cols-2">
<a href="transactions.php" class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><h2 class="text-sm font-semibold text-white">Transactions</h2><p class="mt-1 text-sm text-slate-300">View and search your transaction list.</p></a>
<a href="analytics.php" class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition duration-200 hover:bg-slate-700"><h2 class="text-sm font-semibold text-white">Analytics</h2><p class="mt-1 text-sm text-slate-300">See sales trend and link performance charts.</p></a>
</div>
</main>
</div>
<footer class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</footer>
<script>
(() => {
    const salesEl = document.getElementById('rtTotalSales');
    const commEl = document.getElementById('rtTotalCommission');
    const txEl = document.getElementById('rtTotalTransactions');
    if (!salesEl || !commEl || !txEl) return;

    const refreshStats = () => {
        fetch('dashboard_stats.php', { cache: 'no-store' })
            .then((res) => {
                if (!res.ok) throw new Error('Failed to fetch dashboard stats');
                return res.json();
            })
            .then((payload) => {
                if (!payload || payload.status !== 'success' || !payload.data) return;
                salesEl.textContent = payload.data.total_sales_formatted ?? salesEl.textContent;
                commEl.textContent = payload.data.total_commission_formatted ?? commEl.textContent;
                txEl.textContent = payload.data.total_transactions_formatted ?? txEl.textContent;
            })
            .catch(() => {});
    };

    setInterval(refreshStats, 5000);
})();
</script>
<script src="../assets/ui.js"></script>
</body></html>

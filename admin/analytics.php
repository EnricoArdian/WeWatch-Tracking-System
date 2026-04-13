<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
requireAdmin();

$days = (int) ($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90], true)) {
    $days = 30;
}

$dailyStmt = $pdo->prepare("
    SELECT DATE(created_at) AS d,
           COALESCE(SUM(CASE WHEN status='paid' THEN price ELSE 0 END),0) AS revenue,
           COALESCE(SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END),0) AS tx_count
    FROM transactions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
    GROUP BY DATE(created_at)
    ORDER BY d ASC
");
$dailyStmt->bindValue(':days', $days, PDO::PARAM_INT);
$dailyStmt->execute();
$dailyRows = $dailyStmt->fetchAll();

$sellerPerfStmt = $pdo->prepare("
    SELECT s.id,
           s.name,
           COALESCE(SUM(CASE WHEN t.status='paid' THEN 1 ELSE 0 END),0) AS total_sales,
           COALESCE(SUM(CASE WHEN t.status='paid' THEN t.price ELSE 0 END),0) AS total_revenue,
           COALESCE(SUM(CASE WHEN t.status='paid' THEN t.commission_amount ELSE 0 END),0) AS total_commission
    FROM sellers s
    LEFT JOIN transactions t
        ON t.seller_id = s.id
       AND t.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
    GROUP BY s.id, s.name
    ORDER BY total_sales DESC, total_revenue DESC
");
$sellerPerfStmt->bindValue(':days', $days, PDO::PARAM_INT);
$sellerPerfStmt->execute();
$sellerRows = $sellerPerfStmt->fetchAll();

$kpiStmt = $pdo->prepare("
    SELECT
      COALESCE(SUM(CASE WHEN status='paid' THEN price ELSE 0 END),0) AS paid_revenue,
      COALESCE(SUM(CASE WHEN status='paid' THEN commission_amount ELSE 0 END),0) AS paid_commission,
      COALESCE(SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END),0) AS paid_transactions
    FROM transactions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
");
$kpiStmt->bindValue(':days', $days, PDO::PARAM_INT);
$kpiStmt->execute();
$kpis = $kpiStmt->fetch() ?: ['paid_revenue' => 0, 'paid_commission' => 0, 'paid_transactions' => 0];

$revChartLabels = array_map(static fn($r) => (string) $r['d'], $dailyRows);
$revChartValues = array_map(static fn($r) => (int) $r['revenue'], $dailyRows);
$txChartValues = array_map(static fn($r) => (int) $r['tx_count'], $dailyRows);
$sellerChartLabels = array_map(static fn($r) => (string) $r['name'], $sellerRows);
$sellerChartValues = array_map(static fn($r) => (int) $r['total_sales'], $sellerRows);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Analytics - We Watch Asia</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><link rel="stylesheet" href="../assets/ui.css"></head>
<body class="wwa-page text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
<aside class="border-r border-slate-800 bg-slate-900 p-6 text-slate-300"><div class="mb-6 flex items-center gap-3"><a href="../landing.php" class="inline-flex items-center gap-3"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 w-10 object-contain"><span class="text-lg font-semibold text-white">WeWatch Asia</span></a></div><p class="mt-1 text-xs text-slate-300">We Watch Asia Tracking System</p><nav class="mt-6 space-y-2 text-sm"><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="dashboard.php">Dashboard</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="transactions.php">Transactions</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="sellers.php">Sellers</a><a class="block rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white" href="analytics.php">Analytics</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="logs.php">Activity Logs</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" data-confirm-link data-confirm-message="Logout now?" href="logout.php">Logout</a></nav></aside>
<main class="p-4 sm:p-6 lg:p-8 transition duration-200"><div class="wwa-sticky-header mb-1 flex items-center justify-between gap-3"><div class="flex items-center gap-2"><img src="../assets/logo.png" alt="logo" class="h-10 object-contain"><h1 class="text-2xl font-bold">Analytics</h1></div><form method="GET" class="flex items-center gap-2"><label for="days" class="text-sm text-slate-300">Range</label><select id="days" name="days" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white"><option value="7" <?= $days === 7 ? 'selected' : '' ?>>7 days</option><option value="30" <?= $days === 30 ? 'selected' : '' ?>>30 days</option><option value="90" <?= $days === 90 ? 'selected' : '' ?>>90 days</option></select><button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition duration-200">Apply</button></form></div><div class="mt-4 grid gap-3 md:grid-cols-3"><div class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Paid Revenue</p><p class="wwa-number mt-1 text-xl font-bold"><?= e(formatRupiah((int) ($kpis['paid_revenue'] ?? 0))) ?></p></div><div class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Paid Transactions</p><p class="wwa-number mt-1 text-xl font-bold"><?= number_format((int) ($kpis['paid_transactions'] ?? 0)) ?></p></div><div class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Commission</p><p class="wwa-number mt-1 text-xl font-bold"><?= e(formatRupiah((int) ($kpis['paid_commission'] ?? 0))) ?></p></div></div><div class="mt-5 grid gap-4 lg:grid-cols-3"><div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md lg:col-span-2"><h2 class="mb-2 text-sm font-semibold text-white">Revenue & Paid Transactions Trend</h2><canvas id="revChart"></canvas></div><div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md"><h2 class="mb-2 text-sm font-semibold text-white">Sales Per Seller</h2><canvas id="sellerChart"></canvas></div></div><div class="mt-4 rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md"><h2 class="mb-3 text-sm font-semibold text-white">Seller Revenue & Commission</h2><div class="overflow-x-auto"><table class="min-w-[760px] w-full text-sm"><thead><tr class="border-b border-slate-700 text-slate-400"><th class="px-3 py-2 text-left">Seller</th><th class="px-3 py-2 text-left">Total Sales</th><th class="px-3 py-2 text-left">Total Revenue</th><th class="px-3 py-2 text-left">Total Commission</th></tr></thead><tbody><?php if (!$sellerRows): ?><tr><td colspan="4" class="px-3 py-4 text-center text-slate-400">No data available.</td></tr><?php else: foreach ($sellerRows as $row): ?><tr class="border-b border-slate-800"><td class="px-3 py-2 text-white"><?= e((string) $row['name']) ?></td><td class="px-3 py-2"><?= number_format((int) $row['total_sales']) ?></td><td class="wwa-number px-3 py-2"><?= e(formatRupiah((int) $row['total_revenue'])) ?></td><td class="wwa-number px-3 py-2"><?= e(formatRupiah((int) $row['total_commission'])) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></main></div>
<footer class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</footer>
<script>
new Chart(document.getElementById('sellerChart'),{type:'bar',data:{labels:<?= json_encode($sellerChartLabels, JSON_UNESCAPED_SLASHES) ?>,datasets:[{label:'Sales',data:<?= json_encode($sellerChartValues, JSON_UNESCAPED_SLASHES) ?>,backgroundColor:'#6366f1',borderRadius:6}]},options:{plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(148,163,184,.12)'},ticks:{color:'#94a3b8'}},y:{beginAtZero:true,grid:{color:'rgba(148,163,184,.12)'},ticks:{color:'#94a3b8'}}}});
new Chart(document.getElementById('revChart'),{type:'line',data:{labels:<?= json_encode($revChartLabels, JSON_UNESCAPED_SLASHES) ?>,datasets:[{label:'Revenue',data:<?= json_encode($revChartValues, JSON_UNESCAPED_SLASHES) ?>,borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.12)',fill:false,tension:.3,yAxisID:'y'},{label:'Transactions',data:<?= json_encode($txChartValues, JSON_UNESCAPED_SLASHES) ?>,borderColor:'#22c55e',backgroundColor:'rgba(34,197,94,.10)',fill:false,tension:.25,yAxisID:'y1'}]},options:{plugins:{legend:{labels:{color:'#cbd5e1'}}},scales:{x:{grid:{color:'rgba(148,163,184,.12)'},ticks:{color:'#94a3b8'}},y:{beginAtZero:true,grid:{color:'rgba(148,163,184,.12)'},ticks:{color:'#94a3b8'}},y1:{position:'right',beginAtZero:true,grid:{drawOnChartArea:false},ticks:{color:'#94a3b8'}}}});
</script>
<script src="../assets/ui.js"></script>
</body></html>

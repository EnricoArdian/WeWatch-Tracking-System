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

$linkRows = $pdo->prepare("
SELECT COALESCE(sl.link_label, t.seller_link_code) AS link_label, COUNT(*) AS tx_count, COALESCE(SUM(CASE WHEN t.status='paid' THEN t.price ELSE 0 END),0) AS revenue
FROM transactions t
LEFT JOIN seller_links sl ON sl.link_code=t.seller_link_code
WHERE t.seller_id=:seller_id
GROUP BY COALESCE(sl.link_label, t.seller_link_code)
ORDER BY revenue DESC
");
$linkRows->execute(['seller_id' => $sellerId]);
$links = $linkRows->fetchAll();

$trendRows = $pdo->prepare("
SELECT DATE(created_at) AS d,
       COALESCE(SUM(CASE WHEN status='paid' THEN price ELSE 0 END),0) AS amount,
       COALESCE(SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END),0) AS tx_count
FROM transactions
WHERE seller_id=:seller_id
GROUP BY DATE(created_at)
ORDER BY d ASC
LIMIT 30
");
$trendRows->execute(['seller_id' => $sellerId]);
$trend = $trendRows->fetchAll();

$linkChartLabels = array_map(static fn($r) => (string) $r['link_label'], $links);
$linkChartValues = array_map(static fn($r) => (int) $r['revenue'], $links);
$trendChartLabels = array_map(static fn($r) => (string) $r['d'], $trend);
$trendChartValues = array_map(static fn($r) => (int) $r['amount'], $trend);
$trendTxValues = array_map(static fn($r) => (int) $r['tx_count'], $trend);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Seller Analytics - We Watch Asia</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><link rel="stylesheet" href="../assets/ui.css"></head>
<body class="wwa-page text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
<aside class="border-r border-slate-800 bg-slate-900 p-6 text-slate-300"><div class="mb-6 flex items-center gap-3"><a href="../landing.php" class="inline-flex items-center gap-3"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 w-10 object-contain"><span class="text-lg font-semibold text-white">WeWatch Asia</span></a></div><p class="mt-1 text-xs text-slate-300">Welcome, <?= e((string) $_SESSION['seller_name']) ?></p><nav class="mt-6 space-y-2 text-sm"><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="dashboard.php">Dashboard</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="transactions.php">Transactions</a><a class="block rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white" href="analytics.php">Analytics</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" data-confirm-link data-confirm-message="Logout now?" href="?logout=1">Logout</a></nav></aside>
<main class="p-4 sm:p-6 lg:p-8 transition duration-200"><div class="wwa-sticky-header"><h1 class="text-2xl font-bold">Analytics</h1></div><div class="mt-5 grid gap-4 lg:grid-cols-3"><div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md lg:col-span-2"><h2 class="mb-2 text-sm font-semibold text-white">Revenue Trend</h2><canvas id="trendChart"></canvas></div><div data-skeleton class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md"><h2 class="mb-2 text-sm font-semibold text-white">Revenue Per Link</h2><canvas id="linkChart"></canvas></div></div></main>
</div>
<footer class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</footer>
<script>
new Chart(document.getElementById('linkChart'),{type:'bar',data:{labels:<?= json_encode($linkChartLabels, JSON_UNESCAPED_SLASHES) ?>,datasets:[{data:<?= json_encode($linkChartValues, JSON_UNESCAPED_SLASHES) ?>,backgroundColor:'#6366f1',borderRadius:8}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('trendChart'),{type:'line',data:{labels:<?= json_encode($trendChartLabels, JSON_UNESCAPED_SLASHES) ?>,datasets:[{label:'Revenue',data:<?= json_encode($trendChartValues, JSON_UNESCAPED_SLASHES) ?>,borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.1)',fill:false,tension:.3,yAxisID:'y'},{label:'Paid Tx',data:<?= json_encode($trendTxValues, JSON_UNESCAPED_SLASHES) ?>,borderColor:'#22c55e',backgroundColor:'rgba(34,197,94,.1)',fill:false,tension:.25,yAxisID:'y1'}]},options:{plugins:{legend:{labels:{color:'#cbd5e1'}}},scales:{x:{grid:{color:'rgba(148,163,184,.12)'},ticks:{color:'#94a3b8'}},y:{beginAtZero:true,grid:{color:'rgba(148,163,184,.12)'},ticks:{color:'#94a3b8'}},y1:{position:'right',beginAtZero:true,grid:{drawOnChartArea:false},ticks:{color:'#94a3b8'}}}}});
</script>
<script src="../assets/ui.js"></script>
</body></html>

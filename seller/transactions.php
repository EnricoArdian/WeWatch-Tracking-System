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

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$allowed = ['pending', 'paid', 'rejected', 'failed'];
if ($statusFilter !== '' && !in_array($statusFilter, $allowed, true)) {
    $statusFilter = '';
}

$query = 'SELECT transaction_code,buyer_name,product,price,commission_amount,status,created_at FROM transactions WHERE seller_id=:seller_id';
$params = ['seller_id' => $sellerId];
if ($statusFilter !== '') {
    $query .= ' AND status = :status';
    $params['status'] = $statusFilter;
}
$query .= ' ORDER BY id DESC';
$tx = $pdo->prepare($query);
$tx->execute($params);
$transactions = $tx->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Seller Transactions - We Watch Asia</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../assets/ui.css"></head>
<body class="wwa-page text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
<aside class="border-r border-slate-800 bg-slate-900 p-6 text-slate-300"><div class="mb-6 flex items-center gap-3"><a href="../landing.php" class="inline-flex items-center gap-3"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 w-10 object-contain"><span class="text-lg font-semibold text-white">WeWatch Asia</span></a></div><p class="mt-1 text-xs text-slate-300">Welcome, <?= e((string) $_SESSION['seller_name']) ?></p><nav class="mt-6 space-y-2 text-sm"><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="dashboard.php">Dashboard</a><a class="block rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white" href="transactions.php">Transactions</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="analytics.php">Analytics</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" data-confirm-link data-confirm-message="Logout now?" href="?logout=1">Logout</a></nav></aside>
<main class="p-4 sm:p-6 lg:p-8 transition duration-200"><div class="wwa-sticky-header"><h1 class="text-2xl font-bold">Transactions</h1></div><form class="mt-4 grid gap-3 md:grid-cols-3" method="GET"><select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">All status</option><option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option><option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option><option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option></select><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition duration-200">Apply</button><a class="rounded-lg border border-slate-300 px-4 py-2 text-center text-sm transition duration-200 hover:bg-slate-700" href="transactions.php">Reset</a></form><div data-skeleton class="mt-4 rounded-xl border border-slate-700 bg-slate-800 shadow-md"><div class="px-4 py-3"><input id="searchInput" data-search-input data-search-target="#txTableBody tr" data-search-empty-id="sellerTxNoResults" type="text" placeholder="Search transactions... (Press / to focus)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div><div class="overflow-x-auto"><table class="min-w-[860px] w-full text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="px-3 py-2 text-left">Code</th><th class="px-3 py-2 text-left">Buyer</th><th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">Price</th><th class="px-3 py-2 text-left">Commission</th><th class="px-3 py-2 text-left">Status</th></tr></thead><tbody id="txTableBody"><?php if (count($transactions) === 0): ?><tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No data available</td></tr><?php else: foreach ($transactions as $row): ?><tr class="border-t border-slate-100"><td class="px-3 py-2"><?= e((string) $row['transaction_code']) ?></td><td class="px-3 py-2"><?= e((string) $row['buyer_name']) ?></td><td class="px-3 py-2"><?= e((string) $row['product']) ?></td><td class="wwa-number px-3 py-2"><?= e(formatRupiah((int) $row['price'])) ?></td><td class="wwa-number px-3 py-2"><?= e(formatRupiah((int) $row['commission_amount'])) ?></td><td class="px-3 py-2"><span class="rounded-full px-2 py-1 text-xs <?= $row['status'] === 'paid' ? 'bg-green-100 text-green-700' : (($row['status'] === 'rejected' || $row['status'] === 'failed') ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>"><?= e((string) $row['status']) ?></span></td></tr><?php endforeach; endif; ?></tbody></table></div><div id="sellerTxNoResults" class="hidden px-3 py-6 text-center text-slate-400">No results found</div></div></main>
</div>
<footer class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</footer>
<script src="../assets/ui.js"></script>
</body></html>

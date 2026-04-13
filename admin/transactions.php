<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
requireAdmin();

$flash = (string) ($_SESSION['admin_flash'] ?? '');
$whatsAppLink = (string) ($_SESSION['admin_flash_wa_link'] ?? '');
unset($_SESSION['admin_flash']);
unset($_SESSION['admin_flash_wa_link']);

function normalizeWhatsAppNumber(string $rawPhone): string
{
    $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';
    if ($digits === '') {
        return '';
    }
    if (strpos($digits, '0') === 0) {
        $digits = '62' . substr($digits, 1);
    }
    return $digits;
}

function recalculateTransactionCommission(PDO $pdo, int $txId): void
{
    if ($txId <= 0) {
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE transactions t
        LEFT JOIN sellers s ON s.id = t.seller_id
        SET t.commission_amount = CASE
            WHEN t.seller_id IS NULL THEN 0
            ELSE ROUND(t.price * COALESCE(s.commission_percent, s.commission_rate, 0) / 100)
        END
        WHERE t.id = :id
    ");
    $stmt->execute(['id' => $txId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $txId = (int) ($_POST['transaction_id'] ?? 0);
    if ($txId > 0 && in_array($action, ['approve', 'reject'], true)) {
        $statusStmt = $pdo->prepare('SELECT status FROM transactions WHERE id = :id LIMIT 1');
        $statusStmt->execute(['id' => $txId]);
        $currentStatus = (string) ($statusStmt->fetchColumn() ?: '');

        if ($currentStatus === 'rejected') {
            $_SESSION['admin_flash'] = 'Transaction already rejected. Final status cannot be changed.';
            header('Location: transactions.php');
            exit;
        }

        if ($currentStatus === 'paid') {
            $_SESSION['admin_flash'] = 'Transaction already approved. Final status cannot be changed.';
            header('Location: transactions.php');
            exit;
        }

        if ($currentStatus !== 'pending') {
            $_SESSION['admin_flash'] = 'Only pending transactions can be updated.';
            header('Location: transactions.php');
            exit;
        }

        if ($action === 'approve') {
            recalculateTransactionCommission($pdo, $txId);
        }
        $newStatus = $action === 'approve' ? 'paid' : 'rejected';
        $stmt = $pdo->prepare('UPDATE transactions SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $newStatus, 'id' => $txId]);

        if ($newStatus === 'paid') {
            $notifyStmt = $pdo->prepare('SELECT buyer_name, phone, buyer_email FROM transactions WHERE id = :id LIMIT 1');
            $notifyStmt->execute(['id' => $txId]);
            $notify = $notifyStmt->fetch() ?: [];
            $buyerPhone = normalizeWhatsAppNumber((string) ($notify['phone'] ?? ''));
            $waMessage = rawurlencode('Pembayaran kamu sudah diverifikasi admin. Terima kasih!');
            if ($buyerPhone !== '') {
                $_SESSION['admin_flash_wa_link'] = 'https://wa.me/' . $buyerPhone . '?text=' . $waMessage;
            }

            $buyerEmail = trim((string) ($notify['buyer_email'] ?? ''));
            if ($buyerEmail !== '' && filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
                @mail(
                    $buyerEmail,
                    'Pembayaran Verified',
                    "Halo " . ((string) ($notify['buyer_name'] ?? 'Customer')) . ",\n\nPembayaran kamu sudah diverifikasi admin. Terima kasih."
                );
            }
        }

        if (!empty($_SESSION['admin_id'])) {
            logActivity($pdo, (int) $_SESSION['admin_id'], 'admin', (string) ($_SESSION['admin_username'] ?? 'admin'), 'Transaction ' . $newStatus);
        }
        $_SESSION['admin_flash'] = $action === 'approve' ? 'Transaction approved.' : 'Transaction rejected.';
    }
    header('Location: transactions.php');
    exit;
}

$allowedStatuses = ['pending', 'paid', 'rejected', 'failed'];
$sellerFilter = (int) ($_GET['seller_id'] ?? 0);
$statusFilter = trim((string) ($_GET['status'] ?? ''));
if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
if ($sellerFilter > 0) {
    $where[] = 't.seller_id = :seller_id';
    $params['seller_id'] = $sellerFilter;
}
if ($statusFilter !== '') {
    $where[] = 't.status = :status';
    $params['status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sellerOptions = $pdo->query('SELECT id, name FROM sellers ORDER BY name ASC')->fetchAll();
$tx = $pdo->prepare("
SELECT t.*, s.name AS seller_name, sl.link_label
FROM transactions t
LEFT JOIN sellers s ON s.id=t.seller_id
LEFT JOIN seller_links sl ON sl.link_code=t.seller_link_code
{$whereSql}
ORDER BY t.id DESC
");
$tx->execute($params);
$transactions = $tx->fetchAll();

function statusMeta(string $status): array
{
    if ($status === 'paid') {
        return ['bg-green-100 text-green-700', 'Payment confirmed'];
    }
    if ($status === 'rejected' || $status === 'failed') {
        return ['bg-red-100 text-red-700', 'Payment invalid / rejected'];
    }
    return ['bg-yellow-100 text-yellow-700', 'Waiting for admin verification'];
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Transactions - We Watch Asia</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="../assets/ui.css"></head>
<body class="wwa-page text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
<aside class="border-r border-slate-800 bg-slate-900 p-6 text-slate-300"><div class="mb-6 flex items-center gap-3"><a href="../landing.php" class="inline-flex items-center gap-3"><img src="../assets/logo.png" alt="WeWatch Asia logo" class="h-10 w-10 object-contain"><span class="text-lg font-semibold text-white">WeWatch Asia</span></a></div><p class="mt-1 text-xs text-slate-300">We Watch Asia Tracking System</p><nav class="mt-6 space-y-2 text-sm"><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="dashboard.php">Dashboard</a><a class="block rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white" href="transactions.php">Transactions</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="sellers.php">Sellers</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="analytics.php">Analytics</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" href="logs.php">Activity Logs</a><a class="block rounded-lg px-4 py-2 hover:bg-slate-800" data-confirm-link data-confirm-message="Logout now?" href="logout.php">Logout</a></nav></aside>
<main class="p-4 sm:p-6 lg:p-8 transition duration-200">
<div class="wwa-sticky-header flex items-center justify-between"><h1 class="text-2xl font-bold">Transactions</h1><div class="flex items-center gap-2"><button type="button" id="resetAllBtn" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-600 transition duration-200">Reset All Transactions</button><a class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition duration-200" href="export.php">Export CSV</a></div></div>
<?php if ($flash !== ''): ?><div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700"><?= e($flash) ?></div><?php endif; ?>
<?php if ($whatsAppLink !== ''): ?><div class="mt-2 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-700">Buyer notification ready: <a class="font-semibold underline" href="<?= e($whatsAppLink) ?>" target="_blank" rel="noopener">Send via WhatsApp</a></div><?php endif; ?>
<?php if ($flash !== ''): ?><div data-toast data-toast-type="success" class="hidden"><?= e($flash) ?></div><?php endif; ?>
<form class="mt-4 grid gap-3 md:grid-cols-4" method="GET"><select name="seller_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="0">All sellers</option><?php foreach ($sellerOptions as $s): ?><option value="<?= (int) $s['id'] ?>" <?= $sellerFilter === (int) $s['id'] ? 'selected' : '' ?>><?= e((string) $s['name']) ?></option><?php endforeach; ?></select><select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">All status</option><option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option><option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option><option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option></select><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Apply</button><a class="rounded-lg border border-slate-300 px-4 py-2 text-center text-sm" href="transactions.php">Reset</a></form>
<div data-skeleton class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="px-4 py-3"><input id="searchInput" data-search-input data-search-target="#txTableBody tr" data-search-empty-id="txNoResults" type="text" placeholder="Search transactions... (Press / to focus)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div><div class="overflow-x-auto"><table class="min-w-[1100px] w-full text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="px-3 py-2 text-left">Code</th><th class="px-3 py-2 text-left">Buyer</th><th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">Seller</th><th class="px-3 py-2 text-left">Commission</th><th class="px-3 py-2 text-left">Proof</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Actions</th></tr></thead><tbody id="txTableBody"><?php if (count($transactions) === 0): ?><tr><td colspan="8" class="px-3 py-6 text-center text-slate-500">No data available</td></tr><?php else: foreach ($transactions as $row): $meta = statusMeta((string) $row['status']); ?><tr id="row-<?= (int) $row['id'] ?>" class="border-t border-slate-100"><td class="px-3 py-2"><?= e((string) $row['transaction_code']) ?></td><td class="px-3 py-2"><?= e((string) $row['buyer_name']) ?></td><td class="px-3 py-2"><?= e((string) $row['product']) ?></td><td class="px-3 py-2"><?= e((string) ($row['seller_name'] ?? 'Official Store')) ?></td><td class="wwa-number px-3 py-2"><?= e(formatRupiah((int) $row['commission_amount'])) ?></td><td class="px-3 py-2"><?php if (!empty($row['payment_proof'])): ?><a class="text-sky-600 underline" data-image-preview target="_blank" href="../<?= e((string) $row['payment_proof']) ?>">View</a><?php else: ?>-<?php endif; ?></td><td class="px-3 py-2"><span class="rounded-full px-2 py-1 text-xs <?= e($meta[0]) ?>"><?= e((string) $row['status']) ?></span><div class="mt-1 text-xs text-slate-500"><?= e($meta[1]) ?></div></td><td class="px-3 py-2"><?php $rowStatus = (string) $row['status']; ?><?php if ($rowStatus === 'pending'): ?><div class="flex gap-2"><form method="POST" data-submit-spinner data-confirm data-confirm-message="Approve this payment?"><input type="hidden" name="action" value="approve"><input type="hidden" name="transaction_id" value="<?= (int) $row['id'] ?>"><button class="rounded bg-emerald-600 px-2 py-1 text-xs font-semibold text-white">Approve</button></form><form method="POST" data-submit-spinner data-confirm data-confirm-message="Reject this payment?"><input type="hidden" name="action" value="reject"><input type="hidden" name="transaction_id" value="<?= (int) $row['id'] ?>"><button class="rounded bg-rose-600 px-2 py-1 text-xs font-semibold text-white">Reject</button></form><button type="button" onclick="resetSingle(<?= (int) $row['id'] ?>)" class="rounded bg-slate-700 px-2 py-1 text-xs font-semibold text-white hover:bg-slate-600">Delete</button></div><?php elseif ($rowStatus === 'rejected' || $rowStatus === 'failed'): ?><button class="bg-slate-700 text-slate-400 px-3 py-1 rounded-lg cursor-not-allowed text-xs font-semibold" disabled>Rejected</button><?php else: ?><button class="bg-slate-700 text-slate-400 px-3 py-1 rounded-lg cursor-not-allowed text-xs font-semibold" disabled>Approved</button><?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div><div id="txNoResults" class="hidden px-3 py-6 text-center text-slate-400">No results found</div></div>
</main></div>
<footer class="mt-6 text-center text-sm text-slate-500">© 2026 WeWatch Asia</footer>
<script>
function resetAll() {
    fetch('reset.php?nocache=' + Date.now(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'type=all'
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);
        if (data.status === 'success') {
            if (window.showToast) window.showToast('Data berhasil dihapus');
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            if (window.showToast) window.showToast(data.message || 'Gagal reset', 'error');
        }
    })
    .catch(() => {
        if (window.showToast) window.showToast('Error server', 'error');
    });
}

function resetSingle(id) {
    fetch('reset.php?nocache=' + Date.now(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'type=single&id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);
        if (data.status === 'success') {
            if (window.showToast) window.showToast('Transaksi dihapus');
            const row = document.getElementById('row-' + id);
            if (row) row.remove();
        } else {
            if (window.showToast) window.showToast(data.message || 'Gagal hapus', 'error');
        }
    })
    .catch(() => {
        if (window.showToast) window.showToast('Error server', 'error');
    });
}

document.getElementById('resetAllBtn')?.addEventListener('click', () => {
    if (window.showConfirm) {
        window.showConfirm('Hapus semua transaksi?', resetAll);
    } else {
        resetAll();
    }
});
</script>
<script src="../assets/ui.js"></script>
</body></html>

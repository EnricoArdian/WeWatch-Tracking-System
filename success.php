<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$orderId = trim((string) ($_GET['order_id'] ?? ''));
if ($orderId === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT
        t.transaction_code,
        t.seller_link_code,
        t.payment_method,
        t.payment_proof,
        t.status,
        COALESCE(s.name, "Official Store") AS seller_name
    FROM transactions t
    LEFT JOIN sellers s ON s.id = t.seller_id
    WHERE t.transaction_code = :transaction_code
    LIMIT 1
');
$stmt->execute(['transaction_code' => $orderId]);
$transaction = $stmt->fetch();

if (!$transaction) {
    $_SESSION['flash'] = 'Transaction not found.';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body class="wwa-page px-4 py-10 font-sans text-slate-300">
    <div class="mx-auto max-w-xl rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-md">
        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-green-100 px-4 py-2 text-xs font-semibold text-green-700"><i data-lucide="badge-check" class="h-4 w-4"></i>PAYMENT STATUS</div>
        <h1 class="text-2xl font-bold">Transaction Summary</h1>
        <p class="mt-1 text-sm text-slate-500">Your order status has been recorded.</p>

        <div class="mt-6 space-y-3 rounded-xl border border-slate-200 p-4">
            <p class="text-sm"><span class="font-semibold">Transaction Code:</span> <?= e((string) $transaction['transaction_code']) ?></p>
            <p class="text-sm"><span class="font-semibold">Seller Link:</span> <?= e((string) ($transaction['seller_link_code'] ?? '-')) ?></p>
            <p class="text-sm"><span class="font-semibold">Seller Name:</span> <?= e((string) ($transaction['seller_name'] ?? '-')) ?></p>
            <p class="text-sm"><span class="font-semibold">Payment Method:</span> <?= e((string) ($transaction['payment_method'] ?? 'Not selected')) ?></p>
            <?php if (!empty($transaction['payment_proof'])): ?>
                <p class="text-sm"><span class="font-semibold">Receipt:</span> <a class="text-sky-600 underline" data-image-preview target="_blank" href="<?= e((string) $transaction['payment_proof']) ?>">View Receipt</a></p>
            <?php endif; ?>
            <p class="text-sm">
                <span class="font-semibold">Status:</span>
                <?php if ($transaction['status'] === 'paid'): ?>
                    <span class="ml-1 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">PAID</span>
                <?php elseif ($transaction['status'] === 'failed'): ?>
                    <span class="ml-1 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">FAILED</span>
                <?php else: ?>
                    <span class="ml-1 rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">PENDING</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="payment.php?order_id=<?= rawurlencode((string) $transaction['transaction_code']) ?>" class="inline-flex flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Open Payment Page</a>
        </div>
    </div>
    <script>
        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>
    <script src="assets/ui.js"></script>
</body>
</html>

<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$orderId = trim((string) ($_GET['order_id'] ?? ''));
if ($orderId === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing order_id',
    ]);
    exit;
}

$stmt = $pdo->prepare('
    SELECT
        t.transaction_code,
        t.status,
        t.price,
        t.buyer_name,
        t.product,
        t.payment_proof,
        COALESCE(s.name, "Official Store") AS seller_name
    FROM transactions t
    LEFT JOIN sellers s ON s.id = t.seller_id
    WHERE t.transaction_code = :transaction_code
    LIMIT 1
');
$stmt->execute(['transaction_code' => $orderId]);
$tx = $stmt->fetch();

if (!$tx) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Transaction not found',
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'transaction' => [
        'invoice_code' => (string) $tx['transaction_code'],
        'status' => (string) $tx['status'],
        'price' => formatRupiah((int) $tx['price']),
        'buyer_name' => (string) ($tx['buyer_name'] ?? ''),
        'product' => (string) ($tx['product'] ?? ''),
        'seller_name' => (string) ($tx['seller_name'] ?? '-'),
        'payment_proof' => (string) ($tx['payment_proof'] ?? ''),
    ],
]);
exit;

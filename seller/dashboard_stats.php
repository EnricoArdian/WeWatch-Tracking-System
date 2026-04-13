<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
requireSeller();

$sellerId = (int) ($_SESSION['seller_id'] ?? 0);
$stmt = $pdo->prepare("
SELECT
 COALESCE(SUM(CASE WHEN status='paid' THEN price ELSE 0 END),0) AS total_sales,
 COALESCE(SUM(CASE WHEN status='paid' THEN commission_amount ELSE 0 END),0) AS total_commission,
 COUNT(*) AS total_transactions
FROM transactions
WHERE seller_id=:seller_id
");
$stmt->execute(['seller_id' => $sellerId]);
$stats = $stmt->fetch() ?: ['total_sales' => 0, 'total_commission' => 0, 'total_transactions' => 0];

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'status' => 'success',
    'data' => [
        'total_sales' => (int) ($stats['total_sales'] ?? 0),
        'total_commission' => (int) ($stats['total_commission'] ?? 0),
        'total_transactions' => (int) ($stats['total_transactions'] ?? 0),
        'total_sales_formatted' => formatRupiah((int) ($stats['total_sales'] ?? 0)),
        'total_commission_formatted' => formatRupiah((int) ($stats['total_commission'] ?? 0)),
        'total_transactions_formatted' => number_format((int) ($stats['total_transactions'] ?? 0)),
    ],
], JSON_UNESCAPED_SLASHES);
exit;

<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
requireAdmin();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="transactions_export.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['buyer_name', 'product', 'seller', 'link', 'price', 'status', 'commission_amount', 'proof_of_payment']);

$stmt = $pdo->query('
SELECT t.buyer_name, t.product, COALESCE(s.name, "Official Store") AS seller_name, COALESCE(sl.link_label, "-") AS link_label, t.price, t.status, t.commission_amount, t.payment_proof
FROM transactions t
LEFT JOIN sellers s ON s.id=t.seller_id
LEFT JOIN seller_links sl ON sl.link_code=t.seller_link_code
ORDER BY t.id DESC
');
$baseUrl = rtrim(publicBaseUrl(), '/');
foreach ($stmt->fetchAll() as $row) {
    $proofPath = trim((string) ($row['payment_proof'] ?? ''));
    $proofUrl = '';
    if ($proofPath !== '') {
        $proofUrl = $baseUrl . '/' . ltrim($proofPath, '/');
    }
    fputcsv($out, [
        (string) $row['buyer_name'],
        (string) $row['product'],
        (string) $row['seller_name'],
        (string) $row['link_label'],
        (string) $row['price'],
        (string) $row['status'],
        (string) ((int) ($row['commission_amount'] ?? 0)),
        $proofUrl,
    ]);
}
fclose($out);
exit;

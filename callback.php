<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode((string) $rawInput, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo 'Invalid JSON';
    exit;
}

$orderId = (string) ($payload['order_id'] ?? '');
$transactionStatus = (string) ($payload['transaction_status'] ?? '');
$paymentType = strtoupper((string) ($payload['payment_type'] ?? ''));
$statusCode = (string) ($payload['status_code'] ?? '');
$grossAmount = (string) ($payload['gross_amount'] ?? '');
$signatureKey = (string) ($payload['signature_key'] ?? '');

if ($orderId === '' || $transactionStatus === '') {
    http_response_code(400);
    echo 'Missing required fields';
    exit;
}

$expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . MIDTRANS_SERVER_KEY);
if ($signatureKey !== '' && !hash_equals($expectedSignature, $signatureKey)) {
    http_response_code(403);
    echo 'Invalid signature';
    exit;
}

$normalizedStatus = 'pending';
if (in_array($transactionStatus, ['settlement', 'capture'], true)) {
    $normalizedStatus = 'paid';
} elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'], true)) {
    $normalizedStatus = 'failed';
}

$stmt = $pdo->prepare('UPDATE transactions SET status = :status, payment_method = :payment_method WHERE transaction_code = :transaction_code');
$stmt->execute([
    'status' => $normalizedStatus,
    'payment_method' => $paymentType !== '' ? $paymentType : 'UNKNOWN',
    'transaction_code' => $orderId,
]);

http_response_code(200);
echo 'OK';

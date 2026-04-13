<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$orderId = trim((string) ($_POST['order_id'] ?? ''));
if ($orderId === '') {
    $_SESSION['flash'] = 'Invalid order ID.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT transaction_code, status FROM transactions WHERE transaction_code = :code LIMIT 1');
$stmt->execute(['code' => $orderId]);
$tx = $stmt->fetch();
if (!$tx) {
    $_SESSION['flash'] = 'Transaction not found.';
    header('Location: index.php');
    exit;
}

$currentStatus = (string) ($tx['status'] ?? 'pending');
if ($currentStatus === 'paid') {
    $_SESSION['flash'] = 'Transaction already approved. Upload is locked.';
    header('Location: payment.php?order_id=' . rawurlencode($orderId));
    exit;
}
if ($currentStatus === 'rejected' || $currentStatus === 'failed') {
    $_SESSION['flash'] = 'Transaction already rejected. Please contact admin.';
    header('Location: payment.php?order_id=' . rawurlencode($orderId));
    exit;
}

if (!isset($_FILES['payment_proof']) || !is_array($_FILES['payment_proof'])) {
    $_SESSION['flash'] = 'Please choose image file.';
    header('Location: payment.php?order_id=' . rawurlencode($orderId));
    exit;
}

$file = $_FILES['payment_proof'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $_SESSION['flash'] = 'Upload failed.';
    header('Location: payment.php?order_id=' . rawurlencode($orderId));
    exit;
}

if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
    $_SESSION['flash'] = 'Image max 2MB.';
    header('Location: payment.php?order_id=' . rawurlencode($orderId));
    exit;
}

$tmp = (string) ($file['tmp_name'] ?? '');
$mime = function_exists('mime_content_type') ? (string) mime_content_type($tmp) : '';
$originalName = strtolower((string) ($file['name'] ?? ''));
$ext = pathinfo($originalName, PATHINFO_EXTENSION);
$allowedMimes = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png'];
$allowedExt = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png'];
$selectedExt = '';

if (isset($allowedMimes[$mime])) {
    $selectedExt = $allowedMimes[$mime];
} elseif (isset($allowedExt[$ext])) {
    $selectedExt = $allowedExt[$ext];
}

if ($selectedExt === '') {
    $_SESSION['flash'] = 'Only JPG/PNG allowed.';
    header('Location: payment.php?order_id=' . rawurlencode($orderId));
    exit;
}

$dir = __DIR__ . '/uploads';
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    $_SESSION['flash'] = 'Failed to prepare upload folder.';
    header('Location: payment.php?order_id=' . rawurlencode($orderId));
    exit;
}

$filename = 'proof_' . time() . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $selectedExt;
$target = $dir . '/' . $filename;
if (!move_uploaded_file($tmp, $target)) {
    $_SESSION['flash'] = 'Failed to save file.';
    header('Location: payment.php?order_id=' . rawurlencode($orderId));
    exit;
}

$relative = 'uploads/' . $filename;
$update = $pdo->prepare('UPDATE transactions SET payment_proof = :proof, payment_method = :method, status = :status WHERE transaction_code = :code');
$update->execute([
    'proof' => $relative,
    'method' => 'MANUAL_TRANSFER',
    'status' => 'pending',
    'code' => $orderId,
]);

$_SESSION['flash'] = 'Payment proof uploaded.';
$_SESSION['buyer_success_modal'] = '1';
header('Location: payment.php?order_id=' . rawurlencode($orderId));
exit;

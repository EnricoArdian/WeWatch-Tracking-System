<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$products = getProducts();
$buyerName = sanitizeText((string) ($_POST['buyer_name'] ?? ''), 100);
$phone = trim((string) ($_POST['phone'] ?? ''));
$buyerEmail = trim((string) ($_POST['buyer_email'] ?? ''));
$product = trim((string) ($_POST['product'] ?? ''));
$manualSellerId = (int) ($_POST['seller_id'] ?? 0);
$sellerLinkCode = sanitizeRef((string) ($_COOKIE['seller_link_code'] ?? $_POST['seller_link_code'] ?? ''));
$sellerId = null;
$commissionRate = 0.0;

if ($buyerName === '' || $phone === '' || $product === '') {
    $_SESSION['flash'] = 'Please complete all fields.';
    header('Location: index.php');
    exit;
}

if (!preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
    $_SESSION['flash'] = 'Phone number format is invalid.';
    header('Location: index.php');
    exit;
}

if ($buyerEmail !== '' && !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = 'Email format is invalid.';
    header('Location: index.php');
    exit;
}

if (!array_key_exists($product, $products)) {
    $_SESSION['flash'] = 'Selected product is invalid.';
    header('Location: index.php');
    exit;
}

$transactionCode = generateTransactionCode($pdo);
$price = (int) $products[$product];
$commissionAmount = 0;

$isReferralLocked = false;
if ($sellerLinkCode !== '') {
    $stmt = $pdo->prepare('
        SELECT s.id AS seller_id, COALESCE(s.commission_percent, s.commission_rate, 0) AS commission_percent
        FROM seller_links sl
        INNER JOIN sellers s ON s.id = sl.seller_id
        WHERE sl.link_code = :link_code
        LIMIT 1
    ');
    $stmt->execute(['link_code' => $sellerLinkCode]);
    $owner = $stmt->fetch();
    if ($owner) {
        $isReferralLocked = true;
        $sellerId = (int) $owner['seller_id'];
        $commissionRate = (float) $owner['commission_percent'];
        $commissionAmount = (int) round($price * $commissionRate / 100);
    } else {
        $sellerLinkCode = '';
    }
}

if (!$isReferralLocked && $manualSellerId > 0) {
    $stmt = $pdo->prepare('SELECT id, COALESCE(commission_percent, commission_rate, 0) AS commission_percent FROM sellers WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $manualSellerId]);
    $manualSeller = $stmt->fetch();
    if ($manualSeller) {
        $sellerId = (int) $manualSeller['id'];
        $commissionRate = (float) $manualSeller['commission_percent'];
        $commissionAmount = (int) round($price * $commissionRate / 100);
        $sellerLinkCode = '';
    }
}

if ($sellerId === null) {
    $_SESSION['flash'] = 'Please select seller.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('
    INSERT INTO transactions (transaction_code, buyer_name, phone, buyer_email, product, price, seller_id, seller_link_code, commission_amount, payment_method, payment_proof, status)
    VALUES (:transaction_code, :buyer_name, :phone, :buyer_email, :product, :price, :seller_id, :seller_link_code, :commission_amount, :payment_method, :payment_proof, :status)
');

$stmt->execute([
    'transaction_code' => $transactionCode,
    'buyer_name' => $buyerName,
    'phone' => $phone,
    'buyer_email' => $buyerEmail !== '' ? $buyerEmail : null,
    'product' => $product,
    'price' => $price,
    'seller_id' => $sellerId,
    'seller_link_code' => $sellerLinkCode !== '' ? $sellerLinkCode : null,
    'commission_amount' => $commissionAmount,
    'payment_method' => 'Not selected',
    'payment_proof' => null,
    'status' => 'pending',
]);

header('Location: payment.php?order_id=' . rawurlencode($transactionCode));
exit;

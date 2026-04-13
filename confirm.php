<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$orderId = trim((string) ($_POST['order_id'] ?? ''));
if ($orderId === '') {
    $_SESSION['flash'] = 'Invalid request.';
    header('Location: index.php');
    exit;
}

header('Location: payment.php?order_id=' . rawurlencode($orderId));
exit;

<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['admin_id'])) {
    $adminId = (int) $_SESSION['admin_id'];
    $adminUsername = (string) ($_SESSION['admin_username'] ?? 'admin');
    $pdo->prepare('UPDATE users SET remember_token = NULL WHERE id = :id')->execute(['id' => $adminId]);
    logActivity($pdo, $adminId, 'admin', $adminUsername, 'User logged out');
}
clearRememberLoginCookie();

$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

header('Location: login.php');
exit;

<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
requireAdmin();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.',
    ]);
    exit;
}

$type = trim((string) ($_POST['type'] ?? ''));
$id = (int) ($_POST['id'] ?? 0);

try {
    if ($type === 'all') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM transactions');
        $stmt->execute();
        $deleted = (int) $stmt->rowCount();
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'All transactions deleted',
            'deleted' => $deleted,
        ]);
        exit;
    }

    if ($type === 'single') {
        if ($id <= 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid transaction id.',
            ]);
            exit;
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $deleted = (int) $stmt->rowCount();
        $pdo->commit();

        if ($deleted < 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Transaction not found.',
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Transaction deleted.',
            'id' => $id,
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'error',
        'message' => 'Unknown reset type.',
    ]);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Reset failed: ' . $e->getMessage(),
    ]);
    exit;
}

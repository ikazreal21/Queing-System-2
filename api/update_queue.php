<?php
include('../db.php');
header('Content-Type: application/json');

try {
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $served_by = trim($_POST['served_by'] ?? '');

    if (!$id || !$status) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE requests
        SET 
            status = :status,
            serving_position = CASE WHEN :status = 'Serving' THEN 1 ELSE NULL END,
            served_by = :served_by,
            claim_date = CASE WHEN :status = 'In Queue Now' THEN NOW() ELSE claim_date END,
            updated_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        'status' => $status,
        'served_by' => $served_by,
        'id' => $id
    ]);

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

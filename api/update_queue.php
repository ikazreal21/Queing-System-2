<?php
include('../db.php');
header('Content-Type: application/json');

try {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    $id = intval($input['id'] ?? 0);
    $status = trim($input['status'] ?? '');
    $served_by = trim($input['served_by'] ?? '');

    if (!$id || !$status) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }

    // Update query
    $sql = "
        UPDATE requests
        SET 
            status = :status,
            served_by = :served_by,
            updated_at = NOW(),
            serving_position = CASE 
                WHEN :status = 'Serving' THEN 1 
                ELSE NULL 
            END,
            claim_date = CASE 
                WHEN :status = 'In Queue Now' THEN NOW() 
                ELSE claim_date 
            END,
            processing_start = CASE
                WHEN :status = 'Serving' THEN NOW()
                ELSE processing_start
            END,
            processing_end = CASE
                WHEN :status = 'Completed' THEN NOW()
                ELSE processing_end
            END
        WHERE id = :id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'status'     => $status,
        'served_by'  => $served_by,
        'id'         => $id
    ]);

    // Fetch updated row to return
    $fetch = $pdo->prepare("SELECT * FROM requests WHERE id = :id LIMIT 1");
    $fetch->execute(['id' => $id]);
    $updatedRow = $fetch->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'Request updated successfully.',
        'updated' => $updatedRow
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

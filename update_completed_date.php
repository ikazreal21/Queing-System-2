<?php
session_start();
include('db.php');

$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['request_id'] ?? null;
$approved_date = $data['approved_date'] ?? null;

if (!$request_id || !$approved_date) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE requests 
        SET approved_date = :approved_date, 
            updated_at = NOW() 
        WHERE id = :id
    ");
    $stmt->execute([
        ':approved_date' => $approved_date,
        ':id' => $request_id
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
header('Content-Type: application/json');

try {
    include('../db.php');

    $status = $_GET['status'] ?? '';

    if ($status) {
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE status = :status ORDER BY created_at ASC");
        $stmt->execute(['status' => $status]);
    } else {
        $stmt = $pdo->query("SELECT * FROM requests ORDER BY created_at ASC");
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

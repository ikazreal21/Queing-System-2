<?php
include('../db.php'); // your PDO connection
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'departments' => $departments]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

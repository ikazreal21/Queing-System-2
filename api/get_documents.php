<?php
header('Content-Type: application/json');
include('../db.php'); // your database connection

$response = array();

try {
    $stmt = $pdo->prepare("SELECT id, name, processing_days FROM documents ORDER BY name ASC");
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['status'] = 'success';
    $response['documents'] = $documents;
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

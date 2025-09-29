<?php
include('../db.php'); // PDO connection
header('Content-Type: application/json');

// Get the student number from GET parameter
$student_number = $_GET['student_number'] ?? '';

try {
    // ----- 1️⃣ Active Queue (In Queue Now or Serving) -----
    $stmtQueue = $pdo->query("
        SELECT id, first_name, last_name, student_number, section, department, documents, status, queueing_num, serving_position, created_at
        FROM requests
        WHERE status IN ('In Queue Now', 'Serving')
        ORDER BY queueing_num ASC
    ");
    $queue = $stmtQueue->fetchAll(PDO::FETCH_ASSOC);

    // ----- 2️⃣ All requests by this student -----
    $requests = [];
    if ($student_number) {
        $stmtRequests = $pdo->prepare("
            SELECT id, first_name, last_name, student_number, section, department, documents, status, queueing_num, serving_position, created_at, decline_reason
            FROM requests
            WHERE student_number = :student_number
            ORDER BY created_at DESC
        ");
        $stmtRequests->execute(['student_number' => $student_number]);
        $requests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'queue' => $queue,
        'requests' => $requests
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

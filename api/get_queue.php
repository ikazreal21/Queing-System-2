<?php
session_start(); // optional if you want logged-in user info
include('../db.php'); // PDO connection
header('Content-Type: application/json');

try {
    // ----- Queue: all requests (any status) -----
    $stmtQueue = $pdo->query("
        SELECT *
        FROM requests
        ORDER BY queueing_num ASC, created_at ASC
    ");
    $queue = $stmtQueue->fetchAll(PDO::FETCH_ASSOC);

    // ----- Requests: all entries that have a student_number -----
    $stmtRequests = $pdo->query("
        SELECT *
        FROM requests
        WHERE student_number IS NOT NULL
        ORDER BY created_at DESC
    ");
    $requests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'queue' => $queue,       // now includes all requests
        'requests' => $requests  // all requests for students
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

<?php
session_start();
include('db.php'); // $pdo connection

header('Content-Type: application/json');

// Today's date
$today = date('Y-m-d');

// Optional: filter by staff department
$staff_dept_id = $_SESSION['department_id'] ?? 0;

// Fetch Queueing requests for today
$stmt = $pdo->prepare("
    SELECT * FROM requests
    WHERE status = 'To Be Claimed' AND claim_date = :today
    ORDER BY queueing_num ASC, id ASC
");
$stmt->execute([':today' => $today]);
$queueing = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'queueing' => $queueing
]);

<?php
session_start();
include('db.php'); // $pdo connection

header('Content-Type: application/json');

// Get department from query parameter
$department = $_GET['department'] ?? 0;

// Fetch staff departments
$stmt = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id = ?");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
$staff_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($staff_departments)) {
    $staff_departments = [-1];
}

$inQuery = implode(',', array_fill(0, count($staff_departments), '?'));

// Today's date
$today = date('Y-m-d');

// Fetch Queueing requests (status = 'In Queue Now')
$stmt = $pdo->prepare("
    SELECT * FROM requests
    WHERE status = 'In Queue Now'
      AND (claim_date IS NULL OR DATE(claim_date) = CURDATE())
      AND department IN ($inQuery)
    ORDER BY id ASC
");
$stmt->execute($staff_departments);
$queueing = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'requests' => $queueing
]);

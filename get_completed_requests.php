<?php
include "db.php";
header('Content-Type: application/json');

$filter = $_GET['filter'] ?? 'daily'; // default daily

$sql = "
    SELECT u.first_name, u.last_name, u.counter_no,
           COUNT(r.id) AS completed_count
    FROM requests r
    INNER JOIN users u ON r.served_by = u.id
    WHERE r.status = 'Completed'
";

if ($filter === 'daily') {
    $sql .= " AND DATE(r.completed_date) = CURDATE()";
} elseif ($filter === 'weekly') {
    $sql .= " AND YEARWEEK(r.completed_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'monthly') {
    $sql .= " AND YEAR(r.completed_date) = YEAR(CURDATE())
              AND MONTH(r.completed_date) = MONTH(CURDATE())";
}

$sql .= " GROUP BY u.id, u.first_name, u.last_name, u.counter_no";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);

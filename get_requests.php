<?php
session_start();
include('db.php'); // $pdo connection

header('Content-Type: application/json');

// Get staff departments
$staff_departments = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $staff_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Filter requests by departments if needed
$deptSql = '';
$params = [];
if (!empty($staff_departments)) {
    $placeholders = implode(',', array_fill(0, count($staff_departments), '?'));
    $deptSql = " AND department IN ($placeholders)";
    $params = $staff_departments;
}

// Fetch requests by status
$statuses = ['Pending', 'Processing', 'To Be Claimed', 'Declined', 'Completed'];
$result = [];

foreach ($statuses as $status) {
    if ($status === 'To Be Claimed') {
        $sql = "SELECT * FROM requests WHERE status IN ('To Be Claimed','In Queue Now')" . $deptSql . " ORDER BY id ASC";
    } elseif ($status === 'Completed') {
        $sql = "SELECT * FROM requests WHERE status='Completed'" . $deptSql . " ORDER BY completed_date DESC";
    } else {
        $sql = "SELECT * FROM requests WHERE status=:status" . $deptSql . " ORDER BY created_at DESC";
        $params[':status'] = $status;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result[$status] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode(['success'=>true, 'requests'=>$result]);

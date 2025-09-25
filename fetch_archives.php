<?php
session_start();
include('db.php');

// Exit if no date provided
if (!isset($_GET['date']) || empty($_GET['date'])) {
    echo json_encode([]);
    exit;
}

$date = $_GET['date'];

// ================= FETCH STAFF DEPARTMENTS =================
$staff_departments = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id = ?");
    $stmt->execute([$user_id]);
    $staff_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fallback if staff has no departments
if (empty($staff_departments)) {
    echo json_encode([]);
    exit;
}

// ================= FETCH REQUESTS =================
// Now match 'department' column in requests table with staff department IDs
$placeholders = str_repeat('?,', count($staff_departments) - 1) . '?';
$sql = "SELECT * FROM requests WHERE DATE(created_at) = ? AND department IN ($placeholders) ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$date], $staff_departments));

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return JSON
echo json_encode($results);

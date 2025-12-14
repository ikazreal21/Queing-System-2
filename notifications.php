<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get staff departments
$stmt = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id = ?");
$stmt->execute([$user_id]);
$staff_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
if(empty($staff_departments)) $staff_departments = [0];

$placeholders = str_repeat('?,', count($staff_departments) - 1) . '?';

// Total Pending Requests
$stmt = $pdo->prepare("SELECT * FROM requests WHERE status='Pending' AND department IN ($placeholders) ORDER BY created_at DESC");
$stmt->execute($staff_departments);
$pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($pendingRequests);

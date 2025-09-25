<?php
include('db.php');

if (!isset($_GET['id'])) {
    echo "No request ID provided.";
    exit;
}

$request_id = $_GET['id'];

// Update request status to 'approved'
$stmt = $pdo->prepare("UPDATE requests SET status = 'approved' WHERE id = ?");
$stmt->execute([$request_id]);

// Redirect back to request page
header("Location: staff_requests.php");
exit;

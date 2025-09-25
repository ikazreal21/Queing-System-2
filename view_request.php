<?php
include('db.php');

if (!isset($_GET['id'])) {
    echo "No request ID provided.";
    exit;
}

$request_id = $_GET['id'];

// Fetch the request from the database
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo "Request not found.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="view_request.css">
    <title>Request Details</title>
</head>
<body>
    <div class="container">
        <h1>REQUEST DETAILS</h1>
        <hr>
        <br>
        <ul class="request-details">
            <li><strong>ID:</strong> <?= htmlspecialchars($request['id']) ?></li>
            <li><strong>Name:</strong> <?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></li>
            <li><strong>Student Number:</strong> <?= htmlspecialchars($request['student_number']) ?></li>
            <li><strong>Section:</strong> <?= htmlspecialchars($request['section']) ?></li>
            <li><strong>Last School Year:</strong> <?= htmlspecialchars($request['last_school_year']) ?></li>
            <li><strong>Last Semester:</strong> <?= htmlspecialchars($request['last_semester']) ?></li>
            <li><strong>Documents:</strong> <?= nl2br(htmlspecialchars($request['documents'])) ?></li>
            <li><strong>Notes:</strong> <?= nl2br(htmlspecialchars($request['notes'])) ?></li>
            <li><strong>Status:</strong> <?= htmlspecialchars($request['status']) ?></li>
            <li><strong>Submitted At:</strong> <?= htmlspecialchars($request['submitted_at']) ?></li>
        </ul>
        <br>
        <a href="staff_requests.php" class="back-btn">Back to Requests</a>
    </div>
</body>
</html>
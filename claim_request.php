<?php
session_start();
include "db.php"; // $pdo is your PDO connection

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['request_id'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid request'];
    header("Location: user_dashboard.php#my-requests");
    exit();
}

$request_id = intval($_POST['request_id']);

try {
    // Fetch the request
    $stmt = $pdo->prepare("SELECT status FROM requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception("Request not found");
    }
    if ($request['status'] !== 'To Be Claimed') {
        throw new Exception("Cannot claim: status is not 'To Be Claimed'");
    }

    // Update request to "In Queue Now"
    $stmtUpdate = $pdo->prepare("
        UPDATE requests
        SET status = 'In Queue Now',
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmtUpdate->execute([':id' => $request_id]);

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'text' => "Your request has been moved to the queue."
    ];

} catch (Exception $e) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => $e->getMessage()
    ];
}

// Redirect back to dashboard
header("Location: user_dashboard.php#my-requests");
exit();

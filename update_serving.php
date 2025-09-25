<?php
session_start();
include('db.php'); // PDO connection

header('Content-Type: application/json');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;
$request_id = $input['request_id'] ?? null;
$queueing_num = $input['queueing_num'] ?? null; // optional queue number

if (!$action || !$request_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Fetch current request
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :id");
$stmt->execute([':id' => $request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit;
}

try {
    switch ($action) {
        case 'serve':
    // Allow both 'To Be Claimed' and 'In Queue Now'
    if (!in_array($request['status'], ['To Be Claimed', 'In Queue Now'])) {
        throw new Exception("Cannot serve: request is not in Queueing");
    }

    if (!$queueing_num) throw new Exception("Queue number required for serving");

    // Current staff ID
    $staff_id = $_SESSION['user_id'];

    // Check if the queue number is already assigned
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status='Serving' AND queueing_num=:qnum");
    $stmtCheck->execute([':qnum' => $queueing_num]);
    if ($stmtCheck->fetchColumn() > 0) throw new Exception("Queue number $queueing_num is already assigned!");

    // Find next serving position
    $stmtPos = $pdo->query("SELECT MAX(serving_position) as max_pos FROM requests WHERE status='Serving'");
    $maxPos = $stmtPos->fetch(PDO::FETCH_ASSOC)['max_pos'] ?? 0;
    $newPos = $maxPos + 1;

    // Update request to Serving and record staff
    $stmt = $pdo->prepare("
        UPDATE requests 
        SET status='Serving',
            processing_start=NOW(),
            queueing_num=:queueing_num,
            serving_position=:pos,
            served_by=:staff_id,
            updated_at=NOW()
        WHERE id=:id
    ");
    $stmt->execute([
        ':queueing_num' => $queueing_num,
        ':pos' => $newPos,
        ':staff_id' => $staff_id,
        ':id' => $request_id
    ]);

    $message = 'Moved to Serving with queue number ' . $queueing_num;
    break;



        case 'back':
            if ($request['status'] !== 'Serving') throw new Exception("Cannot move back: not in Serving");

            // Move back and reset queue number
            $stmt = $pdo->prepare("
                UPDATE requests
                SET status='To Be Claimed',
                    processing_start=NULL,
                    serving_position=NULL,
                    queueing_num=0,
                    updated_at=NOW()
                WHERE id=:id
            ");
            $stmt->execute([':id' => $request_id]);

            // Reorder remaining Serving positions
            $stmtReorder = $pdo->query("SELECT id FROM requests WHERE status='Serving' ORDER BY serving_position ASC");
            $i = 1;
            while ($row = $stmtReorder->fetch(PDO::FETCH_ASSOC)) {
                $updatePos = $pdo->prepare("UPDATE requests SET serving_position=:pos WHERE id=:id");
                $updatePos->execute([':pos' => $i, ':id' => $row['id']]);
                $i++;
            }

            $message = 'Moved back to Queueing and queue number reset';
            break;

        case 'complete':
            if ($request['status'] !== 'Serving') throw new Exception("Cannot complete: not in Serving");

            // Complete request and reset queue number
            $stmt = $pdo->prepare("
                UPDATE requests
                SET status='Completed',
                    approved_date=NOW(),
                    completed_date=NOW(),
                    serving_position=NULL,
                    queueing_num=0,
                    updated_at=NOW()
                WHERE id=:id
            ");
            $stmt->execute([':id' => $request_id]);

            // Reorder remaining Serving positions
            $stmtReorder = $pdo->query("SELECT id FROM requests WHERE status='Serving' ORDER BY serving_position ASC");
            $i = 1;
            while ($row = $stmtReorder->fetch(PDO::FETCH_ASSOC)) {
                $updatePos = $pdo->prepare("UPDATE requests SET serving_position=:pos WHERE id=:id");
                $updatePos->execute([':pos' => $i, ':id' => $row['id']]);
                $i++;
            }

            $message = 'Marked as Completed and queue number reset';
            break;

        default:
            throw new Exception("Unknown action");
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

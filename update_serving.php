<?php
session_start();
include('db.php'); // PDO connection

header('Content-Type: application/json');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;
$request_id = $input['request_id'] ?? null;

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
    $staff_id = $_SESSION['user_id'];

    // Get the department of the current request
    $department = $request['department'];

    switch ($action) {
        /* ===================== SERVE ===================== */
        case 'serve':
            if (!in_array($request['status'], ['To Be Claimed', 'In Queue Now'])) {
                throw new Exception("Cannot serve: request is not in queueing state.");
            }

            // Move this request to Serving
            $stmt = $pdo->prepare("
                UPDATE requests
                SET status='Serving',
                    processing_start=NOW(),
                    served_by=:staff_id,
                    updated_at=NOW()
                WHERE id=:id
            ");
            $stmt->execute([
                ':staff_id' => $staff_id,
                ':id' => $request_id
            ]);

            // Reorder only within the same department and non-walk-ins
            $stmtReorder = $pdo->prepare("
                SELECT id FROM requests
                WHERE status='Serving'
                  AND department = :department
                  AND walk_in = 0
                ORDER BY processing_start ASC, id ASC
            ");
            $stmtReorder->execute([':department' => $department]);

            $i = 1;
            while ($row = $stmtReorder->fetch(PDO::FETCH_ASSOC)) {
                $updatePos = $pdo->prepare("
                    UPDATE requests
                    SET queueing_num=:q, serving_position=:q
                    WHERE id=:id
                ");
                $updatePos->execute([':q' => $i, ':id' => $row['id']]);
                $i++;
            }

            $message = "Moved to Serving";
            break;

        /* ===================== BACK ===================== */
        case 'back':
            if ($request['status'] !== 'Serving') {
                throw new Exception("Cannot move back: not in Serving");
            }

            // Move back to queue
            $stmt = $pdo->prepare("
                UPDATE requests
                SET status='In Queue Now',
                    processing_start=NULL,
                    serving_position=NULL,
                    queueing_num=0,
                    updated_at=NOW()
                WHERE id=:id
            ");
            $stmt->execute([':id' => $request_id]);

            // Reorder remaining Serving requests (same department only)
            $stmtReorder = $pdo->prepare("
                SELECT id FROM requests
                WHERE status='Serving'
                  AND department = :department
                  AND walk_in = 0
                ORDER BY processing_start ASC, id ASC
            ");
            $stmtReorder->execute([':department' => $department]);

            $i = 1;
            while ($row = $stmtReorder->fetch(PDO::FETCH_ASSOC)) {
                $updatePos = $pdo->prepare("
                    UPDATE requests
                    SET queueing_num=:q, serving_position=:q
                    WHERE id=:id
                ");
                $updatePos->execute([':q' => $i, ':id' => $row['id']]);
                $i++;
            }

            $message = "Moved back to queue";
            break;

        /* ===================== COMPLETE ===================== */
        case 'complete':
            if ($request['status'] !== 'Serving') {
                throw new Exception("Cannot complete: not in Serving");
            }

            // Mark as completed
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

            // Reorder remaining Serving requests (same department only)
            $stmtReorder = $pdo->prepare("
                SELECT id FROM requests
                WHERE status='Serving'
                  AND department = :department
                  AND walk_in = 0
                ORDER BY processing_start ASC, id ASC
            ");
            $stmtReorder->execute([':department' => $department]);

            $i = 1;
            while ($row = $stmtReorder->fetch(PDO::FETCH_ASSOC)) {
                $updatePos = $pdo->prepare("
                    UPDATE requests
                    SET queueing_num=:q, serving_position=:q
                    WHERE id=:id
                ");
                $updatePos->execute([':q' => $i, ':id' => $row['id']]);
                $i++;
            }

            $message = "Marked as Completed";
            break;

        default:
            throw new Exception("Unknown action");
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

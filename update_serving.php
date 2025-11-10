<?php
session_start();
include('db.php'); // PDO connection
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;
$request_id = $input['request_id'] ?? null;
$staff_id = $_SESSION['user_id'] ?? null;

if (!$staff_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

// -------------------- UTILITY: GET FIRST QUEUE ITEM --------------------
function getFirstQueueItem($pdo, $department, $exclude_id = null) {
    $sql = "
        SELECT * FROM requests
        WHERE status IN ('In Queue Now','To Be Claimed')
          AND department=:department
          AND DATE(claim_date)=CURDATE()
          AND (call_attempts IS NULL OR call_attempts < 3)
    ";
    $params = [':department' => $department];

    if ($exclude_id) {
        $sql .= " AND id != :exclude_id";
        $params[':exclude_id'] = $exclude_id;
    }

    $sql .= " ORDER BY created_at ASC LIMIT 1";

    $stmtQueue = $pdo->prepare($sql);
    $stmtQueue->execute($params);
    return $stmtQueue->fetch(PDO::FETCH_ASSOC);
}

// -------------------- RECALCULATE QUEUEING NUMBERS --------------------
function recalcQueueingNum($pdo, $department) {
    // Serving items = queueing_num 1
    $stmtServing = $pdo->prepare("SELECT id FROM requests WHERE status='Serving' AND department=:department");
    $stmtServing->execute([':department' => $department]);
    $servingItems = $stmtServing->fetchAll(PDO::FETCH_COLUMN);

    foreach ($servingItems as $id) {
        $stmtUpdate = $pdo->prepare("UPDATE requests SET queueing_num=1 WHERE id=:id");
        $stmtUpdate->execute([':id' => $id]);
    }

    // Queueing items = queueing_num 2, 3, 4...
    $stmtQueue = $pdo->prepare("
        SELECT id FROM requests
        WHERE status='In Queue Now'
          AND department=:department
        ORDER BY created_at ASC
    ");
    $stmtQueue->execute([':department' => $department]);
    $queueItems = $stmtQueue->fetchAll(PDO::FETCH_COLUMN);

    $queueNum = 2;
    foreach ($queueItems as $id) {
        $stmtUpdate = $pdo->prepare("UPDATE requests SET queueing_num=:queueing_num WHERE id=:id");
        $stmtUpdate->execute([':queueing_num' => $queueNum, ':id' => $id]);
        $queueNum++;
    }
}

// -------------------- MANUAL ACTIONS --------------------
try {
    switch ($action) {

        case 'serve':
        case 'auto-serve':
            // Determine department
            $stmtDept = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id=:staff_id");
            $stmtDept->execute([':staff_id' => $staff_id]);
            $departments = $stmtDept->fetchAll(PDO::FETCH_COLUMN);
            if (empty($departments)) throw new Exception("No departments assigned to this staff");
            $department = $departments[0];

            // Check if someone is already serving
            $stmtServing = $pdo->prepare("SELECT id FROM requests WHERE status='Serving' AND department=:department LIMIT 1");
            $stmtServing->execute([':department' => $department]);
            if ($stmtServing->fetch()) throw new Exception("A request is already being served");

            // Pick the first in queue
            $firstQueue = getFirstQueueItem($pdo, $department);
            if (!$firstQueue) throw new Exception("No queue items to serve");

            $stmtUpdate = $pdo->prepare("
                UPDATE requests
                SET status='Serving',
                    processing_start=NOW(),
                    served_by=:staff_id,
                    updated_at=NOW()
                WHERE id=:id
            ");
            $stmtUpdate->execute([':staff_id' => $staff_id, ':id' => $firstQueue['id']]);

            $message = "Serving request ID {$firstQueue['id']}";
            break;

        case 'back':
            if (!$request_id) throw new Exception("No request ID provided");

            $stmt = $pdo->prepare("SELECT * FROM requests WHERE id=:id");
            $stmt->execute([':id' => $request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) throw new Exception("Request not found");
            if ($request['status'] !== 'Serving') throw new Exception("Cannot move back: not in Serving");

            $attempts = (int)$request['call_attempts'] + 1;

            if ($attempts >= 3) {
                // Move to To Be Claimed
                $stmtUpdate = $pdo->prepare("
                    UPDATE requests
                    SET status='To Be Claimed',
                        call_attempts=0,
                        processing_start=NULL,
                        queueing_num=0,
                        serving_position=0,
                        updated_at=NOW()
                    WHERE id=:id
                ");
                $stmtUpdate->execute([':id' => $request_id]);
                $backMessage = "Moved to 'To Be Claimed' (3 attempts reached)";
            } else {
                // Move back to In Queue Now
                $stmtUpdate = $pdo->prepare("
                    UPDATE requests
                    SET status='In Queue Now',
                        call_attempts=:attempts,
                        processing_start=NULL,
                        updated_at=NOW()
                    WHERE id=:id
                ");
                $stmtUpdate->execute([':attempts' => $attempts, ':id' => $request_id]);
                $backMessage = "Moved back to queue (Attempt {$attempts})";
            }

            // Auto-serve next in queue
            $department = $request['department'];
            $nextQueue = getFirstQueueItem($pdo, $department, $request_id);
            $serveMessage = "";
            if ($nextQueue) {
                $stmtUpdate = $pdo->prepare("
                    UPDATE requests
                    SET status='Serving',
                        processing_start=NOW(),
                        served_by=:staff_id,
                        updated_at=NOW()
                    WHERE id=:id
                ");
                $stmtUpdate->execute([':staff_id' => $staff_id, ':id' => $nextQueue['id']]);
                $serveMessage = "Now serving ID {$nextQueue['id']}";
            }

            $message = $backMessage;
            if ($serveMessage) $message .= ". {$serveMessage}";
            break;

        case 'complete':
            if (!$request_id) throw new Exception("No request ID provided");

            $stmt = $pdo->prepare("SELECT * FROM requests WHERE id=:id");
            $stmt->execute([':id' => $request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) throw new Exception("Request not found");
            if ($request['status'] !== 'Serving') throw new Exception("Cannot complete: not in Serving");

            $stmtUpdate = $pdo->prepare("
                UPDATE requests
                SET status='Completed',
                    approved_date=NOW(),
                    completed_date=NOW(),
                    updated_at=NOW()
                WHERE id=:id
            ");
            $stmtUpdate->execute([':id' => $request_id]);

            // Auto-serve next in queue
            $department = $request['department'];
            $nextQueue = getFirstQueueItem($pdo, $department);
            if ($nextQueue) {
                $stmtUpdate = $pdo->prepare("
                    UPDATE requests
                    SET status='Serving',
                        processing_start=NOW(),
                        served_by=:staff_id,
                        updated_at=NOW()
                    WHERE id=:id
                ");
                $stmtUpdate->execute([':staff_id' => $staff_id, ':id' => $nextQueue['id']]);
                $message = "Completed request ID {$request_id}. Now serving ID {$nextQueue['id']}";
            } else {
                $message = "Completed request ID {$request_id}. No one in queue to serve now.";
            }
            break;

        default:
            throw new Exception("Unknown action");
    }

    // -------------------- RECALCULATE QUEUEING NUMBERS --------------------
    if (isset($department)) {
        recalcQueueingNum($pdo, $department);
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

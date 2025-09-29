<?php
session_start();
include('db.php'); // $pdo connection

// Auto-move expired processing requests
$pdo->prepare("
    UPDATE requests
    SET status = 'To Be Claimed',
        processing_end = NOW(),
        approved_date = NOW(),
        updated_at = NOW()
    WHERE status = 'Processing'
      AND scheduled_date <= NOW()
")->execute();

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method.']));
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$request_id = $input['request_id'] ?? $_POST['request_id'] ?? null;
$action     = $input['action'] ?? $_POST['action'] ?? null;

if (!$request_id || !$action) {
    die(json_encode(['success' => false, 'message' => 'Invalid request.']));
}

// Fetch current request
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :id");
$stmt->execute([':id' => $request_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$current) die(json_encode(['success'=>false,'message'=>'Request not found.']));

try {
    switch ($action) {

        // Approve request → move to Processing
        case 'approve':
            $docs = array_map('trim', explode(',', $current['documents']));
            $maxDays = 0;
            foreach($docs as $docName){
                $stmt = $pdo->prepare("SELECT processing_days FROM documents WHERE name = :doc");
                $stmt->execute([':doc' => $docName]);
                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
                $days = $doc['processing_days'] ?? 1;
                if ($days > $maxDays) $maxDays = $days;
            }
            $scheduled_date = date('Y-m-d H:i:s', strtotime("+$maxDays days"));

            $stmt = $pdo->prepare("
                UPDATE requests
                SET status = 'Processing',
                    processing_start = NOW(),
                    scheduled_date = :scheduled_date,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([':scheduled_date' => $scheduled_date, ':id' => $request_id]);
            $message = "Request approved. Scheduled for $scheduled_date";
            break;

        // Finish processing → move to To Be Claimed or In Queue Now for walk-ins
        case 'finish':
            if ($current['status'] !== 'Processing') throw new Exception("Cannot finish: Request not in Processing.");

            if ($current['walk_in'] == 1) {
                // Walk-in → move to In Queue Now
                $stmt = $pdo->prepare("
                    UPDATE requests
                    SET status = 'In Queue Now',
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([':id' => $request_id]);
                $message = "Walk-In moved to In Queue Now";
            } else {
                // Regular → move to To Be Claimed
                $stmt = $pdo->prepare("
                    UPDATE requests
                    SET status = 'To Be Claimed',
                        processing_end = NOW(),
                        approved_date = NOW(),
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([':id' => $request_id]);
                $message = "Moved to To Be Claimed";
            }
            break;

        // Decline request
        case 'decline':
            $reason = $input['reason'] ?? $_POST['reason'] ?? "No reason provided";
            $stmt = $pdo->prepare("
                UPDATE requests
                SET status = 'Declined',
                    decline_reason = :reason,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([':reason'=>$reason, ':id'=>$request_id]);
            $message = "Request declined";
            break;

        // Revert back to Pending (only for non-walk-in requests)
        case 'pending':
            if (!in_array($current['status'], ['Processing','To Be Claimed'])) {
                throw new Exception("Cannot revert: Request must be Processing or To Be Claimed.");
            }
            if ($current['walk_in'] == 1) {
                throw new Exception("Cannot revert walk-in requests to Pending.");
            }
            $stmt = $pdo->prepare("
                UPDATE requests
                SET status = 'Pending',
                    processing_start = NULL,
                    processing_end = NULL,
                    scheduled_date = NULL,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([':id'=>$request_id]);
            $message = "Moved back to Pending";
            break;

        // Mark as Walk-In
        case 'walkin':
            $stmt = $pdo->prepare("
                UPDATE requests
                SET status = 'In Queue Now',
                    walk_in = 1,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([':id'=>$request_id]);
            $message = "Added as Walk-In";
            break;

        // UPDATE CLAIM DATE
case 'update_claim_date':
    $claim_date = $input['claim_date'] ?? $_POST['claim_date'] ?? null;
    if (!$claim_date) throw new Exception("No claim date provided.");

    // Auto-decide status
    $today = date('Y-m-d');
    $picked = date('Y-m-d', strtotime($claim_date));

    if ($picked === $today) {
        $status = 'In Queue Now';
    } else {
        $status = 'To Be Claimed';
    }

    $stmt = $pdo->prepare("
        UPDATE requests
        SET claim_date = :claim_date,
            status = :status,
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':claim_date' => $claim_date, 
        ':status' => $status,
        ':id' => $request_id
    ]);

    $message = "Claim date updated to $claim_date. Status set to $status";
    break;



        default:
            throw new Exception("Unknown action.");
    }

    // Return updated request
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, student_number, section, department_id, department, documents, notes, attachment,
               status, processing_start, processing_end, approved_date, completed_date, scheduled_date, claim_date, updated_at, queueing_num, serving_position, walk_in
        FROM requests
        WHERE id = :id
    ");
    $stmt->execute([':id' => $request_id]);
    $updatedRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'=>true,
        'request'=>$updatedRequest,
        'message'=>$message,
        'request_id'=>$request_id
    ]);
    exit;

} catch (Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}
?>
<?php
session_start();

// SET TIMEZONE HERE
date_default_timezone_set('Asia/Manila');


 require 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
include('db.php'); // PDO connection

header('Content-Type: application/json');

// Auto-move expired processing requests to To Be Claimed
$pdo->prepare("
    UPDATE requests
    SET status = 'To Be Claimed',
        processing_end = NOW(),
        approved_date = NOW(),
        updated_at = NOW()
    WHERE status = 'Processing'
      AND processing_end <= NOW()
")->execute();

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$request_id = $input['request_id'] ?? $_POST['request_id'] ?? null;
$action     = $input['action'] ?? $_POST['action'] ?? null;

if (!$request_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Fetch current request
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :id FOR UPDATE");
$stmt->execute([':id' => $request_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    echo json_encode(['success'=>false,'message'=>'Request not found.']);
    exit;
}

try {
    $pdo->beginTransaction(); // Start transaction for concurrency safety
    switch ($action) {

      // ================= APPROVE =================
case 'approve':

    // 1. Calculate max processing days based on requested documents
    $docs = array_map('trim', explode(',', $current['documents']));
    $maxDays = 0;

    foreach ($docs as $docName) {
        $stmtDoc = $pdo->prepare("SELECT processing_days FROM documents WHERE name = :doc");
        $stmtDoc->execute([':doc' => $docName]);
        $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);
        $days = $doc['processing_days'] ?? 1;
        if ($days > $maxDays) $maxDays = $days;
    }

    $processing_start = date('Y-m-d H:i:s');
    $processing_end   = date('Y-m-d H:i:s', strtotime("+$maxDays days"));

    // 2. Safely generate a unique queue number
    do {
        $stmtQueue = $pdo->prepare("
            SELECT MAX(queueing_num) AS max_queue
            FROM requests
            WHERE department = :dept AND status IN ('Processing','In Queue Now')
        ");
        $stmtQueue->execute([':dept' => $current['department']]);
        $maxQueue = $stmtQueue->fetchColumn();
        $queueing_num = $maxQueue ? $maxQueue + 1 : 1;

        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) 
            FROM requests 
            WHERE department = :dept AND queueing_num = :qnum 
                  AND status IN ('Processing','In Queue Now')
        ");
        $stmtCheck->execute([':dept' => $current['department'], ':qnum' => $queueing_num]);
        $exists = $stmtCheck->fetchColumn();
    } while ($exists);

    // 3. Update the request
    $stmtUpdate = $pdo->prepare("
        UPDATE requests
        SET status = 'Processing',
            processing_start = :processing_start,
            processing_end   = :processing_end,
            queueing_num     = :queueing_num,
            updated_at       = NOW()
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        ':processing_start' => $processing_start,
        ':processing_end'   => $processing_end,
        ':queueing_num'     => $queueing_num,
        ':id'               => $request_id
    ]);

    // 4. Fetch the user
    $stmtUser = $pdo->prepare("
        SELECT email, first_name, last_name 
        FROM users 
        WHERE TRIM(student_num) = TRIM(:studnum)
        LIMIT 1
    ");
    $stmtUser->execute([':studnum' => $current['student_number']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['email'])) {
        $userEmail = $user['email'];
        $fullName = $user['first_name'] . ' ' . $user['last_name'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'olfuregistrarant@gmail.com';
            $mail->Password = 'tonteoegqyvqhnog';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('seanariel56@gmail.com', 'Our Lady of Fatima Antipolo Registrar Office');
            $mail->addAddress($userEmail, $fullName);

            $mail->isHTML(true);
            $mail->Subject = 'Your Request Has Been Approved';
            $mail->Body = "
                <p>Hi <strong>$fullName</strong>,</p>
                <p>Your request has been <strong>APPROVED</strong>. Here are the details:</p>
                <table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse;'>
                    <tr>
                        <th>Queue Number</th>
                        <th>Estimated Completion</th>
                    </tr>
                    <tr>
                        <td style='text-align:center;'>$queueing_num</td>
                        <td style='text-align:center;'>$processing_end</td>
                    </tr>
                </table>
                <p>Thank you,<br><strong>Our Lady of Fatima Antipolo Registrar Office</strong></p>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("EMAIL ERROR: " . $mail->ErrorInfo);
        }
    }

    $message = "Request approved. Queueing number: $queueing_num, Estimated completion: $processing_end";
    break;


// ================= FINISH =================
case 'finish':
    if ($current['status'] !== 'Processing') throw new Exception("Cannot finish: Request not in Processing.");

    if ($current['walk_in'] == 1) {
        $stmt = $pdo->prepare("
            UPDATE requests
            SET status = 'In Queue Now',
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':id' => $request_id]);
        $message = "Walk-In moved to In Queue Now";
        $emailBodyStatus = "is now in the queue for processing.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE requests
            SET status = 'To Be Claimed',
                processing_end = NOW(),
                approved_date = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':id' => $request_id]);
        $message = "Request processed and ready to be claimed";
        $emailBodyStatus = "has been processed and is now ready to be <strong>claimed</strong>.";
    }

    // SEND EMAIL
    $stmtUser = $pdo->prepare("
        SELECT email, first_name, last_name
        FROM users
        WHERE TRIM(student_num) = TRIM(:studnum)
        LIMIT 1
    ");
    $stmtUser->execute([':studnum' => $current['student_number']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['email'])) {
        $userEmail = $user['email'];
        $fullName = $user['first_name'] . ' ' . $user['last_name'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'olfuregistrarant@gmail.com';
            $mail->Password = 'tonteoegqyvqhnog';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('seanariel56@gmail.com', 'Our Lady of Fatima Antipolo Registrar Office');
            $mail->addAddress($userEmail, $fullName);

            $mail->isHTML(true);
            $mail->Subject = 'Your Request is Ready to be Claimed';
            $mail->Body = "
                <p>Hi <strong>$fullName</strong>,</p>
                <p>Your request <strong>{$current['documents']}</strong> $emailBodyStatus</p>
                <table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse;'>
                    <tr>
                        <th>Status</th>
                        <th>Processed On</th>
                    </tr>
                    <tr>
                        <td style='text-align:center;'>{$current['status']}</td>
                        <td style='text-align:center;'>" . date('Y-m-d H:i:s') . "</td>
                    </tr>
                </table>
                <p>Thank you,<br><strong>Our Lady of Fatima Antipolo Registrar Office</strong></p>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("EMAIL ERROR: " . $mail->ErrorInfo);
        }
    }
    break;



// ================= DECLINE =================
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

    // SEND EMAIL TO USER
    $stmtUser = $pdo->prepare("
        SELECT email, first_name, last_name
        FROM users
        WHERE TRIM(student_num) = TRIM(:studnum)
        LIMIT 1
    ");
    $stmtUser->execute([':studnum' => $current['student_number']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['email'])) {
        $userEmail = $user['email'];
        $fullName = $user['first_name'] . ' ' . $user['last_name'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'olfuregistrarant@gmail.com';
            $mail->Password = 'tonteoegqyvqhnog';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('seanariel56@gmail.com', 'Our Lady of Fatima Antipolo Registrar Office');
            $mail->addAddress($userEmail, $fullName);

            $mail->isHTML(true);
            $mail->Subject = 'Your Request Has Been Declined';
            $mail->Body = "
                Hi <strong>$fullName</strong>,<br><br>
                Your request has been <strong>DECLINED</strong>.<br>
                <strong>Reason:</strong> $reason <br><br>
                Thank you,<br>
                <strong>Our Lady of Fatima Antipolo Registrar Office</strong>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("EMAIL ERROR: " . $mail->ErrorInfo);
        }
    }
    break;

        // ================= PENDING =================
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

        // ================= WALKIN =================
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

        // ================= UPDATE CLAIM DATE =================
        case 'update_claim_date':
            $claim_date = $input['claim_date'] ?? $_POST['claim_date'] ?? null;
            if (!$claim_date) throw new Exception("No claim date provided.");

            $today = date('Y-m-d');
            $picked = date('Y-m-d', strtotime($claim_date));
            $status = ($picked === $today) ? 'In Queue Now' : 'To Be Claimed';

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

    $pdo->commit();

    // Return updated request
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :id");
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
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}
?>

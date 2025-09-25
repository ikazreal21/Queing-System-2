<?php
// update_countdown.php - accepts JSON { request_id, seconds }
// If seconds === 0 -> finish the request (Processing -> To Be Claimed).
// Otherwise adjust processing_start so remaining time = seconds.

session_start();
include('db.php');

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
if (!$raw) {
    echo json_encode(['success' => false, 'message' => 'Empty body']);
    exit;
}

$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$request_id = $data['request_id'] ?? null;
$seconds = isset($data['seconds']) ? (int)$data['seconds'] : null;

if (!$request_id || $seconds === null || $seconds < 0) {
    echo json_encode(['success' => false, 'message' => 'Missing/invalid parameters']);
    exit;
}

// check request exists and is Processing
$stmt = $pdo->prepare("SELECT status FROM requests WHERE id = :id");
$stmt->execute([':id' => $request_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit;
}

if ($row['status'] !== 'Processing') {
    // If already not Processing, fail
    echo json_encode(['success' => false, 'message' => 'Request not in Processing state']);
    exit;
}

try {
    if ($seconds === 0) {
        // finish
        $stmt = $pdo->prepare("UPDATE requests SET status = 'To Be Claimed', approved_date = NOW(), updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $request_id]);
        echo json_encode(['success' => true, 'message' => 'Moved to To Be Claimed']);
        exit;
    } else {
        // compute new processing_start such that (processing_start + 48h) - now = seconds
        // => processing_start = now - (48h - seconds)
        $now = time();
        $offset = (48 * 3600) - $seconds; // seconds to subtract from now to set processing_start
        $new_start_ts = $now - $offset;
        $new_start = date('Y-m-d H:i:s', $new_start_ts);

        $stmt = $pdo->prepare("UPDATE requests SET processing_start = :ps, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':ps' => $new_start, ':id' => $request_id]);

        echo json_encode(['success' => true, 'message' => 'Countdown updated', 'processing_start' => $new_start]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

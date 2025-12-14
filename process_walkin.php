<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name       = $_POST['first_name'] ?? '';
    $last_name        = $_POST['last_name'] ?? '';
    $student_number   = $_POST['student_number'] ?? '';
    $section          = $_POST['section'] ?? '';
    $department       = $_POST['department'] ?? ''; // numeric ID
    $last_school_year = $_POST['last_school_year'] ?? '';
    $last_semester    = $_POST['last_semester'] ?? '';
    $documents        = isset($_POST['documents']) ? $_POST['documents'] : [];
    $notes            = $_POST['notes'] ?? '';

    // 🔹 Priority checkbox (1 = checked, 0 = unchecked)
    $priority = isset($_POST['priority']) ? 1 : 0;

    // 🔹 Compute scheduled_date based on max processing_days
    $scheduled_date = date('Y-m-d'); // fallback = today
    if (!empty($documents)) {
        $placeholders = implode(',', array_fill(0, count($documents), '?'));
        $stmtDocs = $pdo->prepare("SELECT MAX(processing_days) FROM documents WHERE name IN ($placeholders)");
        $stmtDocs->execute($documents);
        $max_days = (int)$stmtDocs->fetchColumn();
        if ($max_days > 0) {
            $scheduled_date = date('Y-m-d', strtotime("+$max_days days"));
        }
    }

    // 🔹 Auto set dates for tracking
    $processing_start = date('Y-m-d H:i:s');   // when request is created
    $approved_date    = $processing_start;     // set same time initially
    $processing_end   = $scheduled_date;       // when processing ends

    // Handle file uploads
    $attachments = [];
    if (!empty($_FILES['attachment']['name'][0])) {
        foreach ($_FILES['attachment']['tmp_name'] as $key => $tmp_name) {
            $filename = time() . '_' . basename($_FILES['attachment']['name'][$key]);
            move_uploaded_file($tmp_name, 'uploads/' . $filename);
            $attachments[] = $filename;
        }
    }
    $attachments_str = implode(',', $attachments);

    // 🔹 Calculate queue number: continue from the last assigned in department
    $stmtLastQueue = $pdo->prepare("
        SELECT MAX(queueing_num) AS last_queue
        FROM requests
        WHERE department = ? AND status IN ('Processing','In Queue Now')
    ");
    $stmtLastQueue->execute([$department]);
    $last_queue = (int)$stmtLastQueue->fetchColumn();
    $queueing_num = $last_queue + 1; // next number in sequence

    // 🔹 Insert into requests table as Walk-In with priority and queue number
    $stmt = $pdo->prepare("INSERT INTO requests 
        (first_name, last_name, student_number, section, department, last_school_year, last_semester, documents, notes, attachment, status, walk_in, priority, created_at, processing_start, approved_date, processing_end, scheduled_date, queueing_num)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'In Queue Now', 1, ?, NOW(), ?, ?, ?, ?, ?)");
    $stmt->execute([
        $first_name,
        $last_name,
        $student_number,
        $section,
        $department,
        $last_school_year,
        $last_semester,
        implode(', ', $documents),
        $notes,
        $attachments_str,
        $priority,
        $processing_start,
        $approved_date,
        $processing_end,
        $scheduled_date,
        $queueing_num
    ]);

    header("Location: staff_requests.php?success=1");
    exit;
}
?>

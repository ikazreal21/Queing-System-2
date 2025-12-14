<?php
date_default_timezone_set('Asia/Manila');
session_start();
include('db.php');
require "cloudinary_helper.php"; // Cloudinary helper class

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name       = $_POST['first_name'] ?? '';
    $last_name        = $_POST['last_name'] ?? '';
    $student_number   = $_POST['student_number'] ?? '';
    $section          = $_POST['section'] ?? '';
    $department       = $_POST['department'] ?? '';
    $last_school_year = $_POST['last_school_year'] ?? '';
    $last_semester    = $_POST['last_semester'] ?? '';
    $documents        = isset($_POST['documents']) ? $_POST['documents'] : [];
    $notes            = $_POST['notes'] ?? '';
    $priority         = isset($_POST['priority']) ? 1 : 0;

    // Compute scheduled_date based on max processing_days
    $scheduled_date = date('Y-m-d');
    if (!empty($documents)) {
        $placeholders = implode(',', array_fill(0, count($documents), '?'));
        $stmtDocs = $pdo->prepare("SELECT MAX(processing_days) FROM documents WHERE name IN ($placeholders)");
        $stmtDocs->execute($documents);
        $max_days = (int)$stmtDocs->fetchColumn();
        if ($max_days > 0) {
            $scheduled_date = date('Y-m-d', strtotime("+$max_days days"));
        }
    }

    $processing_start = date('Y-m-d H:i:s');
    $approved_date    = $processing_start;
    $processing_end   = $scheduled_date;

    // Handle Cloudinary file uploads
    $attachments = [];
    $cloudinary = new CloudinaryHelper();
    $allowedTypes = ["jpg","jpeg","png","pdf"];

    if (!empty($_FILES['attachment']['name'][0])) {
        foreach ($_FILES['attachment']['name'] as $key => $name) {
            $fileTmp  = $_FILES['attachment']['tmp_name'][$key];
            $fileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $fileSize = $_FILES['attachment']['size'][$key];

            if (!in_array($fileType, $allowedTypes)) continue;
            if ($fileSize > 10 * 1024 * 1024) continue;
            if (!is_uploaded_file($fileTmp)) continue;

            $uploadResult = $cloudinary->uploadFile($fileTmp, $name);
            if ($uploadResult['success']) {
                $attachments[] = $uploadResult['url'];
            } else {
                error_log("Failed to upload file {$name}: " . $uploadResult['error']);
            }
        }
    }

    $attachments_str = !empty($attachments) ? json_encode($attachments, JSON_UNESCAPED_SLASHES) : null;

    // Calculate queue number
    $stmtLastQueue = $pdo->prepare("
        SELECT MAX(queueing_num) AS last_queue
        FROM requests
        WHERE department = ? AND status IN ('Processing','In Queue Now')
    ");
    $stmtLastQueue->execute([$department]);
    $last_queue = (int)$stmtLastQueue->fetchColumn();
    $queueing_num = $last_queue + 1;

    // Insert into requests table
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
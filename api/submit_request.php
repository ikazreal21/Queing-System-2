<?php
include('../db.php');
header('Content-Type: application/json');

try {
    $first_name       = trim($_POST['first_name'] ?? '');
    $last_name        = trim($_POST['last_name'] ?? '');
    $student_number   = trim($_POST['student_number'] ?? '');
    $section          = trim($_POST['section'] ?? '');
    $department_id    = trim($_POST['department'] ?? '');
    $last_school_year = trim($_POST['last_school_year'] ?? '');
    $last_semester    = trim($_POST['last_semester'] ?? '');
    $documents        = trim($_POST['document'] ?? '');
    $notes            = trim($_POST['notes'] ?? '');
    $walk_in          = intval($_POST['walk_in'] ?? 0);

    if (!$first_name || !$last_name || !$student_number || !$section || !$department_id || !$last_school_year || !$last_semester || !$documents) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
        exit;
    }

    // ✅ Handle file upload
    $attachmentName = null;
    if (!empty($_FILES['attachment']['name'])) {
        $uploadDir = "../uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileTmp  = $_FILES['attachment']['tmp_name'];
        $fileName = time() . "_" . basename($_FILES['attachment']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmp, $filePath)) {
            $attachmentName = $fileName; // store filename in DB
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO requests 
        (first_name, last_name, student_number, section, department, last_school_year, last_semester, documents, notes, walk_in, status, created_at, attachment)
        VALUES (:first_name, :last_name, :student_number, :section, :department, :last_school_year, :last_semester, :documents, :notes, :walk_in, 'Pending', NOW(), :attachment)
    ");

    $stmt->execute([
        'first_name'       => $first_name,
        'last_name'        => $last_name,
        'student_number'   => $student_number,
        'section'          => $section,
        'department'       => $department_id,
        'last_school_year' => $last_school_year,
        'last_semester'    => $last_semester,
        'documents'        => $documents,
        'notes'            => $notes,
        'walk_in'          => $walk_in,
        'attachment'       => $attachmentName
    ]);

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

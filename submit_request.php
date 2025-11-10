<?php
session_start();
require "db.php"; // your PDO connection ($pdo)

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Sanitize inputs
        $first_name       = $_POST['first_name'] ?? '';
        $last_name        = $_POST['last_name'] ?? '';
        $student_number   = $_POST['student_number'] ?? '';
        $section          = $_POST['section'] ?? '';
        $department       = $_POST['department'] ?? ''; 
        $last_school_year = $_POST['last_school_year'] ?? '';
        $last_semester    = $_POST['last_semester'] ?? '';
        $documents        = isset($_POST['documents']) ? implode(", ", $_POST['documents']) : '';
        $notes            = $_POST['notes'] ?? '';

        // Handle multiple file uploads
        $attachments = [];
        $status = "Declined"; // default if no attachment

        if (!empty($_FILES['attachment']['name'][0])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            foreach ($_FILES['attachment']['name'] as $key => $name) {
                $fileTmp  = $_FILES['attachment']['tmp_name'][$key];
                $fileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowedTypes = ["jpg", "jpeg", "png", "pdf"];

                if (in_array($fileType, $allowedTypes)) {
                    $fileName = time() . "_" . $key . "_" . basename($name);
                    $targetFilePath = $targetDir . $fileName;

                    if (move_uploaded_file($fileTmp, $targetFilePath)) {
                        $attachments[] = $fileName;
                    }
                }
            }

            if (!empty($attachments)) {
                $status = "Pending";
            }
        }

        $attachmentStr = !empty($attachments) ? implode(", ", $attachments) : null;

        // 🔹 Calculate processing time + dates
        $processing_time = null;
        $processing_start = null;
        $processing_deadline = null;
        $scheduled_date = null;

        if (!empty($documents)) {
            $docNames = explode(", ", $documents);
            $placeholders = rtrim(str_repeat('?,', count($docNames)), ',');
            $stmtDocs = $pdo->prepare("SELECT MAX(processing_days) FROM documents WHERE name IN ($placeholders)");
            $stmtDocs->execute($docNames);
            $max_days = (int)$stmtDocs->fetchColumn();

            if ($max_days > 0) {
                $processing_time = $max_days;
                $stmtNow = $pdo->query("SELECT NOW()");
                $processing_start = $stmtNow->fetchColumn();

                $stmtDeadline = $pdo->prepare("SELECT DATE_ADD(:now, INTERVAL :days DAY)");
                $stmtDeadline->execute([':now' => $processing_start, ':days' => $max_days]);
                $processing_deadline = $stmtDeadline->fetchColumn();
                $scheduled_date = $processing_deadline;
            }
        }

        // 🔹 Calculate queue number per department
        $stmtQueue = $pdo->prepare("SELECT MAX(queueing_num) FROM requests WHERE department = ?");
        $stmtQueue->execute([$department]);
        $maxQueue = $stmtQueue->fetchColumn();
        $queueing_num = $maxQueue ? $maxQueue + 1 : 1;

        // 🔹 Optional: serving_position same as queueing_num initially
        $serving_position = $queueing_num;

        // Insert into DB
        $sql = "INSERT INTO requests 
            (first_name, last_name, student_number, section, department, last_school_year, last_semester, documents, notes, attachment, status,
             processing_time, processing_start, processing_deadline, scheduled_date, queueing_num, serving_position, created_at, updated_at) 
            VALUES 
            (:first_name, :last_name, :student_number, :section, :department, :last_school_year, :last_semester, :documents, :notes, :attachment, :status,
             :processing_time, :processing_start, :processing_deadline, :scheduled_date, :queueing_num, :serving_position, NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':first_name'         => $first_name,
            ':last_name'          => $last_name,
            ':student_number'     => $student_number,
            ':section'            => $section,
            ':department'         => $department,
            ':last_school_year'   => $last_school_year,
            ':last_semester'      => $last_semester,
            ':documents'          => $documents,
            ':notes'              => $notes,
            ':attachment'         => $attachmentStr,
            ':status'             => $status,
            ':processing_time'    => $processing_time,
            ':processing_start'   => $processing_start,
            ':processing_deadline'=> $processing_deadline,
            ':scheduled_date'     => $scheduled_date,
            ':queueing_num'       => $queueing_num,
            ':serving_position'   => $serving_position
        ]);

        // Feedback
        if ($status === "Declined") {
            echo "<script>
                    alert('Your request was declined because no attachment was uploaded.');
                    window.location.href = 'user_dashboard.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Your request has been submitted successfully and is now Pending.');
                    window.location.href = 'user_dashboard.php'; 
                  </script>";
        }

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: user_dashboard.php");
    exit();
}
?>

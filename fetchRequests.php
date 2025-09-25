<?php
session_start();
include('db.php'); // PDO connection

if (!isset($_SESSION['user_id'])) {
    echo "<tr class='no-requests'><td colspan='11'>No user logged in.</td></tr>";
    exit;
}

// ✅ Auto-move expired processing requests
$pdo->prepare("
    UPDATE requests
    SET status = 'To Be Claimed',
        processing_end = NOW(),
        approved_date = NOW(),
        updated_at = NOW()
    WHERE status = 'Processing'
      AND scheduled_date <= NOW()
")->execute();

// ✅ Get staff departments
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$staff_departments = $stmt->fetchColumn();

if (!$staff_departments) {
    echo "<tr class='no-requests'><td colspan='11'>No department assigned.</td></tr>";
    exit;
}

$deptArray = array_map('intval', explode(',', $staff_departments));
$deptPlaceholders = implode(',', array_fill(0, count($deptArray), '?'));

// ✅ Fetch requests by status
$status = $_GET['status'] ?? '';
$stmt = $pdo->prepare("
    SELECT *
    FROM requests
    WHERE department_id IN ($deptPlaceholders)
      AND status = ?
    ORDER BY created_at DESC
");
$stmt->execute([...$deptArray, $status]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Only echo “No requests.” if truly none
if (!$rows) {
    echo "<tr class='no-requests'><td colspan='11'>No requests.</td></tr>";
    exit;
}

$i = 1;
foreach ($rows as $row) {
    $fullName   = htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name']));
    $scheduledDateRaw = $row['scheduled_date'];
    $scheduledDisplay = $scheduledDateRaw
        ? (new DateTime($scheduledDateRaw, new DateTimeZone('Asia/Manila')))->format('F d, Y h:i A')
        : "--";
    $statusVal  = htmlspecialchars($row['status']);
    $request_id = htmlspecialchars($row['id']);

    // ✅ add scheduled date in dataset for JS countdown
    echo "<tr data-request-id='{$request_id}' data-status='{$statusVal}'"
       . ($scheduledDateRaw ? " data-scheduled-date='{$scheduledDateRaw}'" : "")
       . ">
        <td>{$i}</td>
        <td>{$fullName}</td>
        <td>".htmlspecialchars($row['student_number'])."</td>
        <td>".htmlspecialchars($row['section'])."</td>
        <td>".htmlspecialchars($row['last_school_year'])."</td>
        <td>".htmlspecialchars($row['last_semester'])."</td>
        <td>".htmlspecialchars($row['documents'])."</td>
        <td>".htmlspecialchars($row['notes'])."</td>
        <td>{$scheduledDisplay}</td>
        <td>{$statusVal}</td>
        <td>
            <button class='viewDetails'
                data-request-id='{$request_id}'
                data-request-first-name='".htmlspecialchars($row['first_name'])."'
                data-request-last-name='".htmlspecialchars($row['last_name'])."'
                data-request-student-number='".htmlspecialchars($row['student_number'])."'
                data-request-section='".htmlspecialchars($row['section'])."'
                data-request-last-school-year='".htmlspecialchars($row['last_school_year'])."'
                data-request-last-semester='".htmlspecialchars($row['last_semester'])."'
                data-request-documents='".htmlspecialchars($row['documents'])."'
                data-request-notes='".htmlspecialchars($row['notes'])."'
                data-request-attachment='".htmlspecialchars($row['attachment'])."'
            >View</button>
        </td>
    </tr>";
    $i++;
}

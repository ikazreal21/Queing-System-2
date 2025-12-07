<?php
session_start();
include('db.php'); // PDO connection

// Get POSTed JSON data
$data = json_decode(file_get_contents('php://input'), true);
$selectedDate = $data['completed_date'] ?? null;
$departments = $data['departments'] ?? []; // array of allowed department IDs

try {
    // Base SQL
    $sql = "
        SELECT id, first_name, last_name, documents, attachment, completed_date, department
        FROM requests
        WHERE status = 'Completed'
    ";
    $params = [];

    // Add date filter only if provided
    if (!empty($selectedDate)) {
        $sql .= " AND DATE(completed_date) = :completed_date";
        $params[':completed_date'] = $selectedDate;
    }

    // Add department filter if provided
    if (!empty($departments)) {
        // Prepare placeholders for IN clause
        $inPlaceholders = [];
        foreach ($departments as $i => $deptId) {
            $key = ":dept$i";
            $inPlaceholders[] = $key;
            $params[$key] = $deptId;
        }
        $sql .= " AND department IN (" . implode(',', $inPlaceholders) . ")";
    }

    $sql .= " ORDER BY completed_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format completed_date for output
    foreach ($requests as &$req) {
        if (!empty($req['completed_date'])) {
            $dt = new DateTime($req['completed_date'], new DateTimeZone('Asia/Manila'));
            $req['completed_date'] = $dt->format('Y-m-d H:i');
        }
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

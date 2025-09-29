<?php
header('Content-Type: application/json');
session_start();
require "db.php"; // your PDO connection ($pdo)

try {
    // Get department ID(s) from GET parameter
    $department = $_GET['department'] ?? '';
    if (empty($department)) {
        echo json_encode([
            "status" => "error",
            "message" => "Department ID is required."
        ]);
        exit;
    }

    // If multiple departments allowed, you can pass comma-separated IDs
    $deptIds = array_map('trim', explode(',', $department));
    $placeholders = rtrim(str_repeat('?,', count($deptIds)), ',');

    // Fetch all requests with status In Queue Now or Serving
    $sql = "SELECT id, first_name, last_name, student_number, section, documents, notes, attachment,
                   status, queueing_num, serving_position, created_at
            FROM requests
            WHERE department IN ($placeholders)
              AND status IN ('In Queue Now', 'Serving')
            ORDER BY serving_position ASC, queueing_num ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($deptIds);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "requests" => $requests
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>

<?php
session_start();
header('Content-Type: application/json');
include "db.php"; // $pdo

try {
    $stmt = $pdo->prepare("
        SELECT queueing_num, serving_position,
               CONCAT(first_name, ' ', last_name) AS student_name,
               served_by AS counter_no
        FROM requests
        WHERE status = 'Serving'
        ORDER BY serving_position ASC, queueing_num ASC
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode([
            'success' => true,
            'queueing_num' => $row['queueing_num'],
            'serving_position' => $row['serving_position'],
            'student_name' => $row['student_name'],
            'counter_no' => $row['counter_no'] ?? "N/A"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No one is being served right now.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

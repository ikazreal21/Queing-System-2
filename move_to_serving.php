<?php
session_start();
require 'db.php'; // include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['id']) && isset($_SESSION['user_id'])) {
        $id = (int)$data['id'];
        $staff_id = $_SESSION['user_id']; // logged-in staff

        // ✅ Update request → assign staff + change status
        $sql = "UPDATE requests 
                SET status = 'Serving', served_by = :staff_id 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'staff_id' => $staff_id,
            'id' => $id
        ]);

        echo json_encode(['success' => true, 'served_by' => $staff_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing ID or not logged in']);
    }
}

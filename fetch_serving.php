<?php
session_start();
header('Content-Type: application/json');
include('db.php'); // PDO connection

// -------------------- AUTHENTICATION --------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Fetch user info
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'staff') {
    session_destroy();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// -------------------- STAFF DEPARTMENTS --------------------
$stmt = $pdo->prepare("SELECT department_id FROM staff_departments WHERE staff_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$staff_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($staff_departments)) {
    $staff_departments = [-1]; // dummy to prevent fetching
}

$inQuery = implode(',', array_fill(0, count($staff_departments), '?'));

// -------------------- FETCH SERVING --------------------
try {
    $stmt = $pdo->prepare("
        SELECT * FROM requests
        WHERE status = 'Serving'
          AND department IN ($inQuery)
        ORDER BY processing_start ASC
    ");
    $stmt->execute($staff_departments);
    $serving = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Assign positions and ensure status text is visible
    foreach ($serving as $i => &$req) {
        $req['serving_position'] = $i + 1;
        $req['status'] = 'Serving'; // explicitly set status for frontend
    }

    echo json_encode([
        'success' => true,
        'requests' => $serving
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

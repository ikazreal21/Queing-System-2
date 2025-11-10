<?php
session_start();
date_default_timezone_set('Asia/Manila');

// JSON response
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "queue";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Get POST data
$identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($identifier) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email/Student Number and password are required']);
    exit;
}

// Fetch user by email OR student_num
$stmt = $conn->prepare("
    SELECT id, student_num, first_name, last_name, email, password, role, department_id, counter_no
    FROM users
    WHERE email = ? OR student_num = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed']);
    exit;
}

$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No account found with this email or student number']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Incorrect password']);
    $stmt->close();
    $conn->close();
    exit;
}

// ✅ Make sure student_num exists, otherwise fail login
if (empty($user['student_num'])) {
    echo json_encode(['status' => 'error', 'message' => 'This account does not have a student number.']);
    $stmt->close();
    $conn->close();
    exit;
}

// ✅ Store user info in session (persist across requests)
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['student_number'] = $user['student_num']; // THIS FIXES REQUESTS API

// Success response
echo json_encode([
    'status' => 'success',
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'student_num' => $user['student_num'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'department_id' => $user['department_id'],
        'counter_no' => $user['counter_no']
    ]
]);

$stmt->close();
$conn->close();
exit;
?>

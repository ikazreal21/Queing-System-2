<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Ensure JSON response only
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
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

// Prepare statement to fetch user by email
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role, department_id, counter_no FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No account found with this email']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// Verify password using password_verify
if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Incorrect password']);
    $stmt->close();
    $conn->close();
    exit;
}

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

// Return success JSON with user info
echo json_encode([
    'status' => 'success',
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
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

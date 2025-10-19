<?php
header('Content-Type: application/json');

try {
    include('../db.php'); // Ensure this connects to the correct 'queue' database

    // Get POST data safely
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';

    // Validate required fields
    if (!$first_name || !$last_name || !$email || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Email already registered.']);
        exit;
    }

    // ==============================
    // Generate Student Number
    // ==============================
    $prefix = "0322000";
    $result = $pdo->query("SELECT student_num FROM users WHERE student_num IS NOT NULL ORDER BY id DESC LIMIT 1");
    $last_row = $result->fetch(PDO::FETCH_ASSOC);

    if ($last_row && isset($last_row['student_num'])) {
        $last_num = intval(substr($last_row['student_num'], -4)); // last 4 digits
        $new_num = str_pad($last_num + 1, 4, "0", STR_PAD_LEFT);
    } else {
        $new_num = "8701"; // starting number if empty
    }

    $student_num = $prefix . $new_num;

    // ==============================
    // Insert new user (with student_num)
    // ==============================
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, password, role, student_num)
        VALUES (:first_name, :last_name, :email, :password, 'user', :student_num)
    ");
    $stmt->execute([
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'email'        => $email,
        'password'     => $hashed_password,
        'student_num'  => $student_num
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'User registered successfully.',
        'student_num' => $student_num
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

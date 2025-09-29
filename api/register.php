<?php
header('Content-Type: application/json');

try {
    include('../db.php'); // adjust path if needed

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

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, password, role)
        VALUES (:first_name, :last_name, :email, :password, 'user')
    ");
    $stmt->execute([
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'password'   => $hashed_password
    ]);

    echo json_encode(['status' => 'success', 'message' => 'User registered successfully.']);

} catch (PDOException $e) {
    // Database error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // General error
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

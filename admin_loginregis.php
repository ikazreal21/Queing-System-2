<?php
session_start(); // Start the session to store login info
include('db.php'); // Include the database connection

// Registration process
if (isset($_POST['register'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'admin';  // Default role as admin (for admin-only registration)

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "Email already registered.";
        } else {
            // Insert user data into database
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (:first_name, :last_name, :email, :password, :role)");
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                echo "Registration successful!";
            } else {
                echo "Error: " . $stmt->errorInfo()[2];
            }
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Login process
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Verify password and check if role is 'admin'
            if (password_verify($password, $user['password']) && $user['role'] == 'admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                // Redirect to admin dashboard
                header("Location: admin_dashboard.php");
                exit();
            } else {
                echo "Invalid credentials or you are not an admin.";
            }
        } else {
            echo "No user found with that email.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="user_loginregis.css">
    <title>Admin Login and Registration</title>
</head>
<body>

<div class="hero">
    <div class="title">
        <h1>OUR LADY OF FATIMA ANTIPOLO REGISTRAR</h1>
        <p>Admin Login and Registration</p>
    </div>
    <div class="form-box">
        <div class="button-box">
            <div id="btn"></div>
            <button type="button" class="toggle-btn" onclick="login()">Login</button>
            <button type="button" class="toggle-btn" onclick="register()">Register</button>
        </div>

        <!-- Login Form -->
        <form id="login" class="input-group" method="POST" action="admin_loginregis.php">
            <input type="email" name="email" class="input-field" placeholder="Email" required>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
            <button type="submit" class="submit-btn" name="login">Login</button>
        </form>

        <!-- Register Form (Admin Only) -->
        <form id="register" class="input-group" method="POST" action="admin_loginregis.php">
            <input type="text" name="first_name" class="input-field" placeholder="First Name" required>
            <input type="text" name="last_name" class="input-field" placeholder="Last Name" required>
            <input type="email" name="email" class="input-field" placeholder="Email" required>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
            <!-- Role is hardcoded as 'admin' here, so no need for a selection -->
            <button type="submit" class="submit-btn" name="register">Register as Admin</button>
        </form>
            </div>
    <button type="button" class="back-btn" onclick="window.location.href='user_loginregis.php';">Back</button>
</div>
    </div>
</div>

<script src="user_loginregis.js"></script>

</body>
</html>

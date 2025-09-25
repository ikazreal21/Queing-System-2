<?php
session_start(); // Start the session

include('db.php'); // Include the database connection

// Check if the form is submitted
if (isset($_POST['login'])) {
    // Get input values from the form
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to check if the user exists with the staff role
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'staff'");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header("Location: staff_dashboard.php"); // Redirect to the staff dashboard
            exit();
        } else {
            $error_message = "Invalid password.";
        }
    } else {
        $error_message = "Invalid email or staff role.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="staff_login.css">
    <title>Admin Login and Registration</title>
</head>
<body>

<div class="hero">
    <div class="title">
        <h1>OUR LADY OF FATIMA ANTIPOLO REGISTRAR</h1>
        <p>Staff/Registrar Login and Registration</p>
    </div>
    <div class="form-box">
        <img src="assets/fatimalogo.jpg" alt="">

        <!-- Display error message if login fails -->
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form id="login" class="input-group" method="POST" action="staff_login.php">
            <input type="email" class="input-field" name="email" placeholder="Email" required>
            <input type="password" class="input-field" name="password" placeholder="Password" required>
            <button type="submit" name="login" class="login-btn">Login</button>
        </form>


            </div>
    <button type="button" class="back-btn" onclick="window.location.href='user_loginregis.php';">Back</button>
</div>
    </div>
</div>

<script src="staff_login.js"></script>

</body>
</html>

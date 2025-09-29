<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Ensure consistent timezone

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "queue";

// Display errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// ===================== REGISTRATION =====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); 
    $role = "user";

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='user_loginregis.php';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// ===================== LOGIN (for all roles) =====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Save session data
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] === 'staff') {
            header("Location: staff_dashboard.php");
        } elseif ($user['role'] === 'user') {
            header("Location: user_dashboard.php");
        } else {
            echo "<script>alert('Unknown role. Contact admin.');</script>";
        }
        exit;
    } else {
        echo "<script>alert('Invalid email or password.');</script>";
    }
    $stmt->close();
}


// ===================== AJAX Actions =====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    $action = $_POST['action'];
    header('Content-Type: application/json');
    ob_clean();

    // ---------------- SEND RESET CODE ----------------
    if ($action === 'send_code') {
        $email = $_POST['email'];

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            // Delete old codes
            $conn->query("DELETE FROM password_reset_codes WHERE user_id = $user_id");

            $reset_code = rand(100000, 999999);
            $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            $stmt = $conn->prepare("INSERT INTO password_reset_codes (user_id, reset_code, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $reset_code, $expires_at);
            $stmt->execute();
            $stmt->close();

            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'seanariel56@gmail.com';
                $mail->Password = 'fvhwztahvhnfpxjw'; // app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('seanariel56@gmail.com', 'Queueing Management');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Code';
                $mail->Body    = 'Your password reset code is: <strong>' . $reset_code . '</strong>';
                $mail->send();

                echo json_encode(['success' => true, 'message' => 'Reset code sent. Check your email.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No user found with that email.']);
        }
        exit;
    }

    // ---------------- VERIFY CODE ----------------
    if ($action === 'verify_code') {
        $email = $_POST['email'];
        $code = (int)$_POST['code'];

        $stmt = $conn->prepare("
            SELECT prc.user_id 
            FROM password_reset_codes prc
            JOIN users u ON prc.user_id = u.id
            WHERE u.email = ? 
              AND prc.reset_code = ? 
              AND prc.expires_at >= NOW()
            ORDER BY prc.expires_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("si", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['verified_code'] = $code;
            $_SESSION['verified_email'] = $email;
            echo json_encode(['success' => true, 'message' => 'Code verified. You can now reset your password.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code.']);
        }
        $stmt->close();
        exit;
    }

    // ---------------- RESET PASSWORD ----------------
    if ($action === 'reset_password') {
        $verification_code = $_SESSION['verified_code'] ?? null;
        $email = $_SESSION['verified_email'] ?? null;
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!$verification_code || !$email) {
            echo json_encode(['success' => false, 'message' => 'No verification code found. Please verify your code first.']);
            exit;
        }
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT prc.user_id 
            FROM password_reset_codes prc
            JOIN users u ON prc.user_id = u.id
            WHERE u.email = ? 
              AND prc.reset_code = ? 
              AND prc.expires_at >= NOW()
            ORDER BY prc.expires_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("si", $email, $verification_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_id = $result->fetch_assoc()['user_id'];
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();

            // Delete used code
            $conn->query("DELETE FROM password_reset_codes WHERE user_id = $user_id");

            unset($_SESSION['verified_code']);
            unset($_SESSION['verified_email']);
            echo json_encode(['success' => true, 'message' => 'Password successfully reset!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Verification code invalid or expired.']);
        }
        $stmt->close();
        exit;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="user_loginregis.css">
<title>User Login/Register</title>
</head>
<body>

<div class="hero">
    <div class="title">
        <h1>OUR LADY OF FATIMA ANTIPOLO REGISTRAR</h1>
        <p>Queueing Management System</p>
    </div>

    <div class="form-box">
        <div class="button-box">
            <div id="btn"></div>
            <button type="button" class="toggle-btn" onclick="login()">Login</button>
            <button type="button" class="toggle-btn" onclick="register()">Register</button>
        </div>

        <!-- Login Form -->
        <form id="login" class="input-group" method="POST">
            <input type="text" class="input-field" name="email" placeholder="Email" required>
            <input type="password" class="input-field" name="password" placeholder="Password" required>
            <input type="checkbox" class="check-box"><span>Remember Password</span>
            <a href="#" id="forgot-password-link">Forgot Password?</a>
            <button type="submit" class="submit-btn" name="login">Login</button>
        </form>

        <!-- Forgot Password Form -->
        <form id="forgot-password-form" class="input-group" style="display:none;">
            <input type="email" id="forgot-email" class="input-field" placeholder="Enter your email" required>
            <button type="button" id="send-reset-code" class="submit-btn">Send Reset Code</button>
            <div class="verify-container" style="display:none;" id="verify-container">
    <input type="text" id="verification-code" class="verification-input" placeholder="Enter code">
    <button type="button" id="verify-code-btn" class="verify-btn">Verify Code</button>
</div>
            <div class="reset-container" id="reset-container" style="display:none;">
    <input type="password" id="new-password" class="fp-new-password" placeholder="Enter new password" required>
    <input type="password" id="confirm-password" class="fp-confirm-password" placeholder="Confirm new password" required>
    <button type="button" id="reset-password-btn" class="fp-reset-btn">Reset Password</button>
</div>

            <button type="button" id="back-btn" class="back-btn">Back</button>
        </form>

        <!-- Registration Form -->
        <form id="register" class="input-group" method="POST">
            <input type="text" class="input-field" name="first_name" placeholder="First Name" required>
            <input type="text" class="input-field" name="last_name" placeholder="Last Name" required>
            <input type="email" class="input-field" name="email" placeholder="Email" required>
            <input type="password" class="input-field" name="password" placeholder="Password" required>
            <input type="checkbox" class="check-box" required><span>I agree to the</span>
            <a href="#" id="terms">terms & conditions</a>
            <button type="submit" class="submit-btn" name="register">Register</button>
        </form>
    </div>
    <button type="button" class="back-btn" onclick="window.location.href='index.php';">Back</button>
</div>

<script src="user_loginregis.js"></script>
</body>
</html>

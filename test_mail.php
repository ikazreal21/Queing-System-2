<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'seanariel56@gmail.com'; // Use your email
    $mail->Password = 'fvhwztahvhnfpxjw'; // Use your email password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('your-email@gmail.com', 'Mailer');
    $mail->addAddress('seanariel56@gmail.com'); // Add a recipient
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email sent via PHPMailer!';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

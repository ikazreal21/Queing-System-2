<?php

// SET TIMEZONE HERE
date_default_timezone_set('Asia/Manila');

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'olfuregistrarant@gmail.com'; // Gmail
    $mail->Password = 'tonteoegqyvqhnog';     // App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Debugging
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) { echo "$str<br>"; };

    $mail->setFrom('seanariel56@gmail.com', 'Registrar Office');
    $mail->addAddress('seanariel56@gmail.com', 'Test Recipient');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email sent via PHPMailer!';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

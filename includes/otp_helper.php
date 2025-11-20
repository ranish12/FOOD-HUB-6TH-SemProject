<?php
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../config/email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function generateOTP() {
    $digits = OTP_LENGTH;
    $otp = '';
    for ($i = 0; $i < $digits; $i++) {
        $otp .= rand(0, 9);
    }
    return $otp;
}

function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code - Food Hub';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #333;'>Your OTP Code</h2>
                <p>Your One-Time Password (OTP) is:</p>
                <div style='background-color: #f4f4f4; padding: 10px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                    <strong>{$otp}</strong>
                </div>
                <p>This OTP will expire in " . OTP_EXPIRY_MINUTES . " minutes.</p>
                <p>If you didn't request this OTP, please ignore this email.</p>
                <hr>
                <p style='color: #666; font-size: 12px;'>This is an automated message, please do not reply.</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

function storeOTP($email, $otp) {
    global $conn;
    $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    
    $stmt = $conn->prepare("UPDATE Users SET otp = ?, otp_expiry = ? WHERE email = ?");
    return $stmt->execute([$otp, $expiry, $email]);
}

function verifyOTP($email, $otp) {
    global $conn;
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $stored_otp = $user['otp'];
        $expiry = $user['otp_expiry'];
        $current_time = date('Y-m-d H:i:s');
        
        if ($stored_otp === $otp) {
            if (strtotime($expiry) > strtotime($current_time)) {
                return true;
            }
        }
    }
    return false;
} 
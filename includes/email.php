<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
date_default_timezone_set('Asia/Riyadh');

// After connecting to the database


function sendOTPEmail($email, $otp, $username) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'clash.f.o20.23@gmail.com';
        $mail->Password   = 'huif iawg hijb mfzq';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('your_gmail@gmail.com', 'Rasalah');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Rasalah - Email Verification';
        $mail->Body    = "
        <html>
        <head>
            <style>
                .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .otp-code { font-size: 24px; font-weight: bold; color: #667eea; text-align: center; padding: 20px; background: white; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>Rasalah</h1>
                </div>
                <div class='content'>
                    <h2>Hello $username,</h2>
                    <p>Thank you for registering with Rasalah. Please use the following OTP to verify your email address:</p>
                    <div class='otp-code'>$otp</div>
                    <p>This OTP will expire in 10 minutes.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendPasswordResetEmail($email, $token, $username) {
    $reset_link = "http://localhost/rasalah/reset-password.php?token=" . $token;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'clash.f.o20.23@gmail.com';
        $mail->Password   = 'huif iawg hijb mfzq';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('your_gmail@gmail.com', 'Rasalah');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Rasalah - Password Reset';
        $mail->Body    = "
        <html>
        <head>
            <style>
                .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .reset-btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>Rasalah</h1>
                </div>
                <div class='content'>
                    <h2>Hello $username,</h2>
                    <p>You requested a password reset. Click the button below to reset your password:</p>
                    <a href='$reset_link' class='reset-btn'>Reset Password</a>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>

// EmailService.java
import javax.mail.*;
import javax.mail.internet.*;
import java.util.Properties;

public class EmailService {
    private static final String SMTP_HOST = "smtp.gmail.com";
    private static final String SMTP_PORT = "587";
    private static final String EMAIL = "clash.f.o20.23@gmail.com";
    private static final String PASSWORD = "tjyu nbdy hcnx ffxn"; // Use an App Password
    
    public static boolean sendOTPEmail(String toEmail, String otp, String username) {
        Properties props = new Properties();
        props.put("mail.smtp.auth", "true");
        props.put("mail.smtp.starttls.enable", "true");
        props.put("mail.smtp.host", SMTP_HOST);
        props.put("mail.smtp.port", SMTP_PORT);
        
        Session session = Session.getInstance(props, new Authenticator() {
            @Override
            protected PasswordAuthentication getPasswordAuthentication() {
                return new PasswordAuthentication(EMAIL, PASSWORD);
            }
        });
        
        try {
            Message message = new MimeMessage(session);
            message.setFrom(new InternetAddress(EMAIL));
            message.setRecipients(Message.RecipientType.TO, InternetAddress.parse(toEmail));
            message.setSubject("Rasalah - Email Verification");
            
            String htmlContent = String.format(
                "<html><body style='font-family: Arial, sans-serif;'>" +
                "<div style='max-width: 600px; margin: 0 auto;'>" +
                "<div style='background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); color: white; padding: 20px; text-align: center;'>" +
                "<h1>Rasalah</h1>" +
                "</div>" +
                "<div style='padding: 20px; background: #f9f9f9;'>" +
                "<h2>Hello %s,</h2>" +
                "<p>Thank you for registering with Rasalah. Please use the following OTP to verify your email address:</p>" +
                "<div style='font-size: 24px; font-weight: bold; color: #667eea; text-align: center; padding: 20px; background: white; border-radius: 8px; margin: 20px 0;'>%s</div>" +
                "<p>This OTP will expire in 10 minutes.</p>" +
                "<p>If you didn't request this, please ignore this email.</p>" +
                "</div></div></body></html>",
                username, otp
            );
            
            message.setContent(htmlContent, "text/html");
            Transport.send(message);
            return true;
            
        } catch (MessagingException e) {
            e.printStackTrace();
            return false;
        }
    }
    
    public static void main(String[] args) {
        // Test email sending
        boolean sent = sendOTPEmail("test@example.com", "123456", "TestUser");
        System.out.println("Email sent: " + sent);
    }
}

// [mail function]
// sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"

<?php
use PHPMailer\PHPMailer\PHPMailer;
require 'vendor/autoload.php';

$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your_gmail@gmail.com';
$mail->Password = 'your_app_password'; // Use Gmail App Password
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('your_gmail@gmail.com', 'Your Name');
$mail->addAddress('recipient@example.com');
$mail->Subject = 'Test Email';
$mail->Body = 'Hello, this is a test email!';

if ($mail->send()) {
    echo 'Email sent!';
} else {
    echo 'Mailer Error: ' . $mail->ErrorInfo;
}
?>

smtp_server=smtp.gmail.com
smtp_port=587
smtp_ssl=auto
auth_username=clash.f.o20.23@gmail.com
auth_password=tjyu nbdy hcnx ffxn

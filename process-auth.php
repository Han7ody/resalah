<?php
// process-auth.php
require_once 'includes/functions.php';
require_once 'includes/email.php';
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'resend_otp':
            if (!isset($_SESSION['verify_user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid session'], JSON_INVALID_UTF8_SUBSTITUTE);
                exit();
            }
            
            $database = new Database();
            $db = $database->getConnection();
            
            // Get user info
            $query = "SELECT username, email FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['verify_user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Mark old OTPs as used
                $query = "UPDATE email_verifications SET is_used = 1 WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_SESSION['verify_user_id']]);
                
                // Generate new OTP
                $otp = generateOTP();
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                $query = "INSERT INTO email_verifications (user_id, otp, expires_at) VALUES (?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$_SESSION['verify_user_id'], $otp, $expires_at]);
                
                if (sendOTPEmail($user['email'], $otp, $user['username'])) {
                    echo json_encode(['success' => true, 'message' => 'New OTP sent'], JSON_INVALID_UTF8_SUBSTITUTE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send OTP'], JSON_INVALID_UTF8_SUBSTITUTE);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found'], JSON_INVALID_UTF8_SUBSTITUTE);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action'], JSON_INVALID_UTF8_SUBSTITUTE);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method'], JSON_INVALID_UTF8_SUBSTITUTE);
}
?>

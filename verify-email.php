<?php
// verify-email.php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!isset($_SESSION['verify_user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = sanitizeInput($_POST['otp']);
    
    if (empty($otp)) {
        setFlashMessage('error', 'Please enter the OTP');
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM email_verifications WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND is_used = 0";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['verify_user_id'], $otp]);
        
        if ($stmt->rowCount() > 0) {
            // Mark OTP as used
            $query = "UPDATE email_verifications SET is_used = 1 WHERE user_id = ? AND otp = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['verify_user_id'], $otp]);
            
            // Verify user
            $query = "UPDATE users SET is_verified = 1 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['verify_user_id']]);
            
            unset($_SESSION['verify_user_id']);
            setFlashMessage('success', 'Email verified successfully! You can now login.');
            header('Location: login.php');
            exit();
        } else {
            setFlashMessage('error', 'Invalid or expired OTP');
        }
    }
}

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Rasalah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <h1>Rasalah</h1>
            <p>Please enter the OTP sent to your email</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <div class="otp-container">
            <form method="POST" action="">
                <div class="otp-inputs">
                    <input type="text" class="otp-input" maxlength="1" required>
                    <input type="text" class="otp-input" maxlength="1" required>
                    <input type="text" class="otp-input" maxlength="1" required>
                    <input type="text" class="otp-input" maxlength="1" required>
                    <input type="text" class="otp-input" maxlength="1" required>
                    <input type="text" class="otp-input" maxlength="1" required>
                </div>
                
                <input type="hidden" name="otp" id="otp-hidden">
                <button type="submit" class="btn btn-primary">Verify Email</button>
            </form>

            <div class="auth-links">
                <p>Didn't receive the code? <span class="resend-otp" onclick="resendOTP()">Resend OTP</span></p>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Combine OTP inputs
        document.querySelector('form').addEventListener('submit', function() {
            const otpInputs = document.querySelectorAll('.otp-input');
            let otp = '';
            otpInputs.forEach(input => otp += input.value);
            document.getElementById('otp-hidden').value = otp;
        });
    </script>
</body>
</html>

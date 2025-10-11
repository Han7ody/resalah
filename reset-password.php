<?php
// reset-password.php
require_once 'includes/functions.php';
require_once 'includes/email.php';
require_once 'config/database.php';

$step = 'request'; // request, reset
$token = $_GET['token'] ?? '';

if ($token) {
    $step = 'reset';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 'request') {
        $email = sanitizeInput($_POST['email']);
        
        if (empty($email)) {
            setFlashMessage('error', 'Please enter your email address');
        } elseif (!validateEmail($email)) {
            setFlashMessage('error', 'Please enter a valid email address');
        } else {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, username FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $reset_token = generateToken();
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $query = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$reset_token, $expires, $user['id']]);
                
                if (sendPasswordResetEmail($email, $reset_token, $user['username'])) {
                    
                    setFlashMessage('success', 'Password reset link sent to your email');
                } else {
                    setFlashMessage('error', 'Failed to send reset email');
                }
            } else {
                setFlashMessage('error', 'Email address not found');
            }
        }
    } elseif ($step == 'reset') {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_password) || empty($confirm_password)) {
            setFlashMessage('error', 'Please fill in all fields');
        } elseif (!validatePassword($new_password)) {
            setFlashMessage('error', 'Password must be at least 8 characters with uppercase, lowercase, and numbers');
        } elseif ($new_password !== $confirm_password) {
            setFlashMessage('error', 'Passwords do not match');
        } else {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()";
            $stmt = $db->prepare($query);
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $query = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$hashed_password, $user['id']]);
                
                setFlashMessage('success', 'Password reset successfully! You can now login.');
                header('Location: login.php');
                exit();
            } else {
                setFlashMessage('error', 'Invalid or expired reset token');
            }
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
    <title>Reset Password - Rasalah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <h1>Rasalah</h1>
            <p><?php echo $step == 'request' ? 'Enter your email to reset password' : 'Enter your new password'; ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 'request'): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                    <small id="password-strength"></small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="auth-links">
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>

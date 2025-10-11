<?php
// register.php
require_once 'includes/functions.php';
require_once 'includes/email.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        setFlashMessage('error', 'Please fill in all fields');
    } elseif (!validateEmail($email)) {
        setFlashMessage('error', 'Please enter a valid email address');
    } elseif (!validatePassword($password)) {
        setFlashMessage('error', 'Password must be at least 8 characters with uppercase, lowercase, and numbers');
    } elseif ($password !== $confirm_password) {
        setFlashMessage('error', 'Passwords do not match');
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if user exists
        $query = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            setFlashMessage('error', 'Email or username already exists');
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_token = generateToken();
            
            $query = "INSERT INTO users (username, email, password, verification_token) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$username, $email, $hashed_password, $verification_token])) {
                $user_id = $db->lastInsertId();
                
                // Generate and save OTP
                $otp = generateOTP();
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                $query = "INSERT INTO email_verifications (user_id, otp, expires_at) VALUES (?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id, $otp, $expires_at]);
                
                // Send OTP email
                if (sendOTPEmail($email, $otp, $username)) {
                    $_SESSION['verify_user_id'] = $user_id;
                    header('Location: verify-email.php');
                    exit();
                } else {
                    setFlashMessage('error', 'Failed to send verification email');
                }
            } else {
                setFlashMessage('error', 'Registration failed. Please try again.');
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
    <title>Register - Rasalah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <h1>Rasalah</h1>
            <p>Create your account to get started</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small id="password-strength"></small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>

<?php
// enhanced-login.php
require_once 'includes/functions.php';
require_once 'includes/rate_limiter.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
$rateLimiter = new RateLimiter($db);

$userIP = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check rate limiting
    if ($rateLimiter->isBlocked($userIP, 'login')) {
        setFlashMessage('error', 'Too many failed attempts. Please try again later.');
    } else {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            setFlashMessage('error', 'Please fill in all fields');
            $rateLimiter->recordAttempt($userIP, 'login');
        } else {
            $query = "SELECT id, username, email, password, is_verified, failed_attempts, locked_until FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_verified']) {
                    // Check account lock
                    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                        setFlashMessage('error', 'Account temporarily locked. Please try again later.');
                    } else {
                        // Successful login
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['login_time'] = time();
                        
                        // Clear failed attempts
                        $query = "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$user['id']]);
                        
                        $rateLimiter->clearAttempts($userIP, 'login');
                        
                        // Log successful login
                        $this->logActivity($user['id'], 'login_success', $userIP);
                        
                        header('Location: dashboard.php');
                        exit();
                    }
                } else {
                    setFlashMessage('error', 'Please verify your email first');
                }
            } else {
                // Failed login
                if ($user) {
                    $failedAttempts = $user['failed_attempts'] + 1;
                    $lockedUntil = null;
                    
                    if ($failedAttempts >= 5) {
                        $lockedUntil = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
                    }
                    
                    $query = "UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$failedAttempts, $lockedUntil, $user['id']]);
                    
                    $this->logActivity($user['id'], 'login_failed', $userIP);
                }
                
                setFlashMessage('error', 'Invalid email or password');
                $rateLimiter->recordAttempt($userIP, 'login');
            }
        }
    }
}

function logActivity($userId, $action, $ip) {
    global $db;
    $query = "INSERT INTO activity_logs (user_id, action, ip_address, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId, $action, $ip]);
}

$flash = getFlashMessage();
?>

<?php
// advanced-dashboard.php
require_once 'includes/functions.php';
require_once 'includes/activity_logger.php';
require_once 'includes/two_factor.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$logger = new ActivityLogger($db);
$twoFA = new TwoFactorAuth($db);

// Get user info
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent activity
$activities = $logger->getUserActivity($_SESSION['user_id'], 10);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'disable_2fa':
            if ($twoFA->disableTwoFactor($_SESSION['user_id'])) {
                $logger->log($_SESSION['user_id'], '2fa_disabled');
                setFlashMessage('success', 'Two-factor authentication disabled');
            }
            break;
            
        case 'change_password':
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (password_verify($currentPassword, $user['password'])) {
                if (validatePassword($newPassword) && $newPassword === $confirmPassword) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                        $logger->log($_SESSION['user_id'], 'password_changed');
                        setFlashMessage('success', 'Password changed successfully');
                    }
                } else {
                    setFlashMessage('error', 'Invalid new password or passwords do not match');
                }
            } else {
                setFlashMessage('error', 'Current password is incorrect');
            }
            break;
            
        case 'logout':
            $logger->log($_SESSION['user_id'], 'logout');
            session_destroy();
            header('Location: login.php');
            exit();
    }
    
    header('Location: advanced-dashboard.php');
    exit();
}

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rasalah</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .dashboard-content {
            padding: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .card h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .user-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
        }
        
        .activity-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        
        .activity-item.failed {
            border-left-color: #dc3545;
        }
        
        .activity-action {
            font-weight: 600;
            color: #333;
        }
        
        .activity-details {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .security-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-small:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px;
            }
            
            .user-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                <p>Manage your account and security settings</p>
            </div>
            
            <?php if ($flash): ?>
                <div style="padding: 0 30px; padding-top: 20px;">
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo $flash['message']; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-content">
                <!-- User Information Card -->
                <div class="card">
                    <h3>Account Information</h3>
                    <div class="user-info-grid">
                        <div class="info-item">
                            <div class="info-label">Username</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Account Status</div>
                            <div class="info-value">
                                <?php echo $user['is_verified'] ? '✅ Verified' : '❌ Unverified'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">2FA Status</div>
                            <div class="info-value">
                                <?php echo $user['two_factor_enabled'] ? '🔒 Enabled' : '🔓 Disabled'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Member Since</div>
                            <div class="info-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Last Login</div>
                            <div class="info-value">
                                <?php echo $user['last_login'] ? date('M j, Y H:i', strtotime($user['last_login'])) : 'First time'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="security-actions">
                        <button onclick="openModal('changePasswordModal')" class="btn-small btn-warning">
                            Change Password
                        </button>
                        
                        <?php if ($user['two_factor_enabled']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="disable_2fa">
                                <button type="submit" class="btn-small btn-danger" 
                                        onclick="return confirm('Are you sure you want to disable 2FA?')">
                                    Disable 2FA
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="setup-2fa.php" class="btn-small btn-success">Enable 2FA</a>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="btn-small btn-danger">Logout</button>
                        </form>
                    </div>
                </div>
                
                <!-- Recent Activity Card -->
                <div class="card">
                    <h3>Recent Activity</h3>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($activities)): ?>
                            <p style="color: #666; text-align: center; padding: 20px;">No recent activity</p>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item <?php echo strpos($activity['action'], 'failed') !== false ? 'failed' : ''; ?>">
                                    <div class="activity-action">
                                        <?php 
                                        $actionLabels = [
                                            'login_success' => '✅ Successful Login',
                                            'login_failed' => '❌ Failed Login',
                                            'logout' => '🚪 Logout',
                                            'password_changed' => '🔑 Password Changed',
                                            '2fa_enabled' => '🔒 2FA Enabled',
                                            '2fa_disabled' => '🔓 2FA Disabled',
                                            'email_verified' => '📧 Email Verified'
                                        ];
                                        echo $actionLabels[$activity['action']] ?? ucfirst(str_replace('_', ' ', $activity['action']));
                                        ?>
                                    </div>
                                    <div class="activity-details">
                                        IP: <?php echo $activity['ip_address']; ?> • 
                                        <?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('changePasswordModal')">×</span>
            <h2>Change Password</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                    <small id="password-strength"></small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

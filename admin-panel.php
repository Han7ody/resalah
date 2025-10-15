<?php
require_once 'config/database.php';
session_start();

// Check if user is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Re-verify admin status on every page load
$stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || !$currentUser['is_admin']) {
    $_SESSION['is_admin'] = 0;
    header('Location: dashboard.php');
    exit();
}

// Handle POST requests for user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    if (isset($_POST['ban'])) {
        $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif (isset($_POST['unban'])) {
        $stmt = $db->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif (isset($_POST['make_admin'])) {
        $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif (isset($_POST['remove_admin'])) {
        $stmt = $db->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif (isset($_POST['edit'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->execute([$username, $email, $userId]);
    }
    header('Location: admin-panel.php'); // Redirect to refresh the page
    exit();
}

// Fetch all users
$stmt = $db->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #f4f7f6;
            color: #333;
        }
        .admin-container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-weight: 700;
        }
        .user-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        .user-card {
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
        }
        .user-card .form-group {
            margin-bottom: 15px;
        }
        .user-card .form-control {
            background: #f8f9fa;
        }
        .user-card .actions {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .user-card .btn {
            flex-grow: 1;
            padding: 10px;
            font-size: 14px;
        }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-info { background: #17a2b8; color: white; }
        .user-info span {
            font-weight: 500;
            color: #667eea;
        }
        .user-info p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Admin Control Panel</h1>
        <div class="user-cards-container">
            <?php foreach ($users as $user): ?>
            <div class="user-card">
                <form method="post" onsubmit="return confirmAction(this);">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    
                    <div class="user-info">
                        <p>ID: <span><?php echo $user['id']; ?></span></p>
                        <p>Admin: <span><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></span></p>
                        <p>Banned: <span><?php echo $user['is_banned'] ? 'Yes' : 'No'; ?></span></p>
                    </div>

                    <div class="form-group">
                        <label for="username-<?php echo $user['id']; ?>">Username</label>
                        <input type="text" id="username-<?php echo $user['id']; ?>" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email-<?php echo $user['id']; ?>">Email</label>
                        <input type="email" id="email-<?php echo $user['id']; ?>" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>

                    <div class="actions">
                        <button type="submit" name="edit" class="btn btn-primary">Save Changes</button>
                        
                        <?php if ($user['is_banned']): ?>
                            <button type="submit" name="unban" class="btn btn-success">Unban</button>
                        <?php else: ?>
                            <button type="submit" name="ban" class="btn btn-danger">Ban</button>
                        <?php endif; ?>
                        
                        <?php if ($user['is_admin']): ?>
                            <button type="submit" name="remove_admin" class="btn btn-warning">Remove Admin</button>
                        <?php else: ?>
                            <button type="submit" name="make_admin" class="btn btn-info">Make Admin</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function confirmAction(form) {
            const submitter = event.submitter;
            let action = '';

            if (submitter.name === 'edit') {
                action = 'save changes for this user';
            } else if (submitter.name === 'ban') {
                action = 'ban this user';
            } else if (submitter.name === 'unban') {
                action = 'unban this user';
            } else if (submitter.name === 'make_admin') {
                action = 'make this user an admin';
            } else if (submitter.name === 'remove_admin') {
                action = 'remove admin rights from this user';
            }

            if (action) {
                return confirm(`Are you sure you want to ${action}?`);
            }
            return true; // Default to allow submission if action is unknown
        }
    </script>
</body>
</html>

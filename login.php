<?php
// login.php
require_once 'includes/functions.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = sanitizeInput($_POST['login']);
    $password = $_POST['password'];
    
    if (empty($login) || empty($password)) {
        setFlashMessage('error', 'Please fill in all fields');
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, email, password, is_verified, is_admin, is_banned FROM users WHERE email = ? OR username = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_banned']) {
                setFlashMessage('error', 'Your account has been suspended.');
            } elseif ($user['is_verified']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];

                if ($user['is_admin']) {
                    header('Location: admin-panel.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                setFlashMessage('error', 'Please verify your email first');
            }
        } else {
            setFlashMessage('error', 'Invalid email or password');
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
    <title>Login - Rasalah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <h1>Rasalah</h1>
            <p>Welcome back! Please sign in to your account</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="login">Email or Username</label>
                <input type="text" id="login" name="login" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>

        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Sign up</a></p>
            <p><a href="reset-password.php">Forgot your password?</a></p>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>

<?php
// setup-2fa.php
require_once 'includes/functions.php';
require_once 'includes/two_factor.php';
require_once 'config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$twoFA = new TwoFactorAuth($db);

$step = $_GET['step'] ?? 'setup';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 'verify') {
        $code = sanitizeInput($_POST['code']);
        $secret = $_SESSION['temp_2fa_secret'];
        
        if ($twoFA->verifyCode($secret, $code)) {
            $twoFA->enableTwoFactor($_SESSION['user_id'], $secret);
            unset($_SESSION['temp_2fa_secret']);
            setFlashMessage('success', 'Two-factor authentication enabled successfully!');
            header('Location: dashboard.php');
            exit();
        } else {
            setFlashMessage('error', 'Invalid verification code');
        }
    }
}

if ($step == 'setup') {
    $secret = $twoFA->generateSecret();
    $_SESSION['temp_2fa_secret'] = $secret;
    $qrCodeUrl = $twoFA->getQRCodeUrl($_SESSION['username'], $secret);
}

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup 2FA - Rasalah</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .qr-container {
            text-align: center;
            margin: 20px 0;
        }
        .qr-container img {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }
        .secret-key {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <h1>Rasalah</h1>
            <p><?php echo $step == 'setup' ? 'Setup Two-Factor Authentication' : 'Verify 2FA Code'; ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 'setup'): ?>
            <div class="qr-container">
                <p>Scan this QR code with your authenticator app:</p>
                <?php
                $qrCode = new QrCode($qrCodeUrl);
                $writer = new PngWriter();
                $result = $writer->write($qrCode);
                $dataUri = $result->getDataUri();
                ?>
                <img src="<?php echo $dataUri; ?>" alt="QR Code">
                
                <p>Or enter this secret key manually:</p>
                <div class="secret-key"><?php echo $secret; ?></div>
                
                <p><small>Recommended apps: Google Authenticator, Authy, Microsoft Authenticator</small></p>
            </div>
            
            <a href="?step=verify" class="btn btn-primary">I've Added the Account</a>
            
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="code">Enter 6-digit code from your authenticator app:</label>
                    <input type="text" id="code" name="code" class="form-control" maxlength="6" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Verify & Enable 2FA</button>
            </form>
        <?php endif; ?>

        <div class="auth-links">
            <p><a href="dashboard.php">Back to Dashboard</a></p>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>

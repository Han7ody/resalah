<?php
require_once 'includes/database.php';
require_once 'includes/email.php';

date_default_timezone_set('Asia/Riyadh');

$email = $_POST['email'];
$otp = $_POST['otp'];

// Always get the latest OTP for this email
$sql = "SELECT * FROM otps WHERE email = ? ORDER BY expires_at DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$row = $stmt->fetch();

if ($row) {
    // Debugging: Uncomment to see what you get
    // var_dump($row);

    if ($row['otp'] == $otp) {
        if (strtotime($row['expires_at']) > time()) {
            echo "OTP is valid!";
            // Continue with verification
        } else {
            echo "OTP expired!";
        }
    } else {
        echo "Invalid OTP!";
    }
} else {
    echo "No OTP found for this email!";
}
?>
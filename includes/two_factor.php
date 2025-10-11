<?php
// includes/two_factor.php
require_once __DIR__ . '/../vendor/autoload.php';
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuth {
    private $google2fa;
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
        $this->google2fa = new Google2FA();
    }

    public function generateSecret() {
        return $this->google2fa->generateSecretKey();
    }

    public function getQRCodeUrl($username, $secret) {
        // You can customize the issuer (third parameter) as needed
        return $this->google2fa->getQRCodeUrl(
            'Rasalah', // Issuer
            $username, // Account name
            $secret
        );
    }

    public function verifyCode($secret, $code) {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function enableTwoFactor($userId, $secret) {
        $stmt = $this->db->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
        return $stmt->execute([$secret, $userId]);
    }

    public function disableTwoFactor($userId) {
        $stmt = $this->db->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}
?>

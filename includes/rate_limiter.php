<?php
// includes/rate_limiter.php
class RateLimiter {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
        $this->createTable();
    }
    
    private function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action VARCHAR(50) NOT NULL,
            attempts INT DEFAULT 1,
            last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            blocked_until TIMESTAMP NULL,
            INDEX idx_ip_action (ip_address, action)
        )";
        $this->db->exec($query);
    }
    
    public function isBlocked($ip, $action, $maxAttempts = 5, $blockDuration = 900) {
        $query = "SELECT attempts, blocked_until FROM rate_limits 
                  WHERE ip_address = ? AND action = ? AND last_attempt > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ip, $action]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            if ($result['blocked_until'] && strtotime($result['blocked_until']) > time()) {
                return true;
            }
            
            if ($result['attempts'] >= $maxAttempts) {
                $this->blockIP($ip, $action, $blockDuration);
                return true;
            }
        }
        
        return false;
    }
    
    public function recordAttempt($ip, $action) {
        $query = "INSERT INTO rate_limits (ip_address, action, attempts) 
                  VALUES (?, ?, 1) 
                  ON DUPLICATE KEY UPDATE 
                  attempts = attempts + 1, 
                  last_attempt = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ip, $action]);
    }
    
    private function blockIP($ip, $action, $duration) {
        $blockedUntil = date('Y-m-d H:i:s', time() + $duration);
        $query = "UPDATE rate_limits SET blocked_until = ? WHERE ip_address = ? AND action = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$blockedUntil, $ip, $action]);
    }
    
    public function clearAttempts($ip, $action) {
        $query = "DELETE FROM rate_limits WHERE ip_address = ? AND action = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ip, $action]);
    }
}
?>

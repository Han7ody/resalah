<?php
// includes/activity_logger.php
class ActivityLogger {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function log($userId, $action, $details = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $query = "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, details) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        
        $detailsJson = $details ? json_encode($details) : null;
        return $stmt->execute([$userId, $action, $ip, $userAgent, $detailsJson]);
    }
    
    public function getUserActivity($userId, $limit = 50) {
        $limit = (int)$limit; // Always cast to int for safety
        $query = "SELECT action, ip_address, details, created_at 
                  FROM activity_logs 
                  WHERE user_id = ?
                  ORDER BY created_at DESC
                  LIMIT $limit";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRecentFailedLogins($hours = 24) {
        $query = "SELECT COUNT(*) as count, ip_address 
                  FROM activity_logs 
                  WHERE action = 'login_failed' 
                  AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR) 
                  GROUP BY ip_address 
                  HAVING count > 5 
                  ORDER BY count DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$hours]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<?php
// api/blocked_users.php
header('Content-Type: application/json');

require_once '../includes/functions.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required'], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(401);
    exit();
}

$currentUserId = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "
        SELECT u.id, u.username
        FROM users u
        INNER JOIN friendships f ON (f.user_one_id = u.id OR f.user_two_id = u.id)
        WHERE
            f.status = 3
            AND f.action_user_id = ?
            AND u.id != ?
    ";
    $stmt = $db->prepare($query);
    $stmt->execute([$currentUserId, $currentUserId]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users], JSON_INVALID_UTF8_SUBSTITUTE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON encoding error in blocked_users.php: ' . json_last_error_msg());
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], JSON_INVALID_UTF8_SUBSTITUTE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON encoding error in blocked_users.php catch block: ' . json_last_error_msg());
    }
    http_response_code(500);
}
?>

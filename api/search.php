<?php
// api/search.php
header('Content-Type: application/json');

require_once '../includes/functions.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required'], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(401);
    exit();
}

$searchTerm = $_GET['term'] ?? '';

if (empty($searchTerm)) {
    echo json_encode(['success' => false, 'message' => 'Search term cannot be empty'], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(400);
    exit();
}

$currentUserId = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Search for users, excluding the current user and anyone who has blocked the current user.
    $query = "
        SELECT u.id, u.username
        FROM users u
        WHERE
            u.username LIKE ?
            AND u.id != ?
            AND u.is_admin = 0
            AND NOT EXISTS (
                SELECT 1
                FROM friendships f
                WHERE
                    ((f.user_one_id = u.id AND f.user_two_id = ?) OR (f.user_one_id = ? AND f.user_two_id = u.id))
                    AND f.status = 3
                    AND f.action_user_id = u.id
            )
        LIMIT 10
    ";
    $stmt = $db->prepare($query);
    $stmt->execute(['%' . $searchTerm . '%', $currentUserId, $currentUserId, $currentUserId]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users], JSON_INVALID_UTF8_SUBSTITUTE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON encoding error in search.php: ' . json_last_error_msg());
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], JSON_INVALID_UTF8_SUBSTITUTE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON encoding error in search.php catch block: ' . json_last_error_msg());
    }
    http_response_code(500);
}
?>
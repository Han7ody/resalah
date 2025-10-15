<?php
// api/unblock_user.php
header('Content-Type: application/json');

require_once '../includes/functions.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required'], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(401);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method'], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(405);
    exit();
}

$currentUserId = $_SESSION['user_id'];
$userIdToUnblock = $_POST['user_id'] ?? '';

if (empty($userIdToUnblock)) {
    echo json_encode(['success' => false, 'message' => 'User ID to unblock is required'], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(400);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "
        DELETE FROM friendships
        WHERE
            ((user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?))
            AND status = 3
            AND action_user_id = ?
    ";
    $stmt = $db->prepare($query);
    $stmt->execute([$currentUserId, $userIdToUnblock, $userIdToUnblock, $currentUserId, $currentUserId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User unblocked successfully'], JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not unblock user. You may not have blocked this user.'], JSON_INVALID_UTF8_SUBSTITUTE);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(500);
}
?>

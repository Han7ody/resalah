<?php
// api/friends.php
header('Content-Type: application/json');

require_once '../includes/functions.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required'], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(401);
    exit();
}

$currentUserId = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'send_request':
                $receiverId = $data['receiver_id'] ?? 0;
                if (empty($receiverId) || $receiverId == $currentUserId) {
                    throw new Exception('Invalid request');
                }

                // Ensure user_one_id is always the smaller ID
                $user_one = min($currentUserId, $receiverId);
                $user_two = max($currentUserId, $receiverId);

                // Check if a relationship already exists
                $query = "SELECT id, status FROM friendships WHERE user_one_id = ? AND user_two_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_one, $user_two]);
                $existing_friendship = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_friendship) {
                    throw new Exception('A friend request already exists or you are already friends.');
                }

                // Create new friend request
                $query = "INSERT INTO friendships (user_one_id, user_two_id, status, action_user_id) VALUES (?, ?, 0, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_one, $user_two, $currentUserId]);

                echo json_encode(['success' => true, 'message' => 'Friend request sent.'], JSON_INVALID_UTF8_SUBSTITUTE);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('JSON encoding error in friends.php/send_request: ' . json_last_error_msg());
                }
                break;

            case 'manage_request':
                $friendshipId = $data['friendship_id'] ?? 0;
                $newStatus = $data['status'] ?? -1; // 1 for accept, 2 for decline

                if (empty($friendshipId) || !in_array($newStatus, [1, 2])) {
                    throw new Exception('Invalid request parameters.');
                }

                // Verify the current user is the recipient and can action this request
                $query = "
                    UPDATE friendships
                    SET status = ?, action_user_id = ?
                    WHERE
                        id = ?
                        AND (user_one_id = ? OR user_two_id = ?)
                        AND action_user_id != ?
                        AND status = 0
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([$newStatus, $currentUserId, $friendshipId, $currentUserId, $currentUserId, $currentUserId]);

                if ($stmt->rowCount() > 0) {
                    $message = $newStatus == 1 ? 'Friend request accepted.' : 'Friend request declined.';
                    echo json_encode(['success' => true, 'message' => $message], JSON_INVALID_UTF8_SUBSTITUTE);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('JSON encoding error in friends.php/manage_request: ' . json_last_error_msg());
                    }
                } else {
                    throw new Exception('Could not manage request. It might be already processed or you are not authorized.');
                }
                break;
            case 'block_user':
                $friendId = $data['friend_id'] ?? 0;
                if (empty($friendId)) {
                    throw new Exception('Invalid friend ID.');
                }

                $user_one = min($currentUserId, $friendId);
                $user_two = max($currentUserId, $friendId);

                $query = "
                    UPDATE friendships
                    SET status = 3, action_user_id = ?
                    WHERE user_one_id = ? AND user_two_id = ?
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([$currentUserId, $user_one, $user_two]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'User blocked.'], JSON_INVALID_UTF8_SUBSTITUTE);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('JSON encoding error in friends.php/block_user: ' . json_last_error_msg());
                    }
                } else {
                    // If no friendship exists, create one to store the block
                    $query = "INSERT INTO friendships (user_one_id, user_two_id, status, action_user_id) VALUES (?, ?, 3, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_one, $user_two, $currentUserId]);
                }
                break;

            case 'unfriend':
                $friendId = $data['friend_id'] ?? 0;
                if (empty($friendId)) {
                    throw new Exception('Invalid friend ID.');
                }

                $user_one = min($currentUserId, $friendId);
                $user_two = max($currentUserId, $friendId);

                $query = "
                    DELETE FROM friendships
                    WHERE user_one_id = ? AND user_two_id = ? AND status = 1
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_one, $user_two]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'User unfriended.'], JSON_INVALID_UTF8_SUBSTITUTE);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('JSON encoding error in friends.php/unfriend: ' . json_last_error_msg());
                    }
                } else {
                    throw new Exception('Could not unfriend user. You may not be friends with this user.');
                }
                break;

            default:
                throw new Exception('Invalid action');
        }
    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'get_friends':
                $query = "
                    SELECT u.id, u.username 
                    FROM users u JOIN friendships f ON (u.id = f.user_one_id OR u.id = f.user_two_id)
                    WHERE (f.user_one_id = ? OR f.user_two_id = ?) AND f.status = 1 AND u.id != ?
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
                $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'friends' => $friends], JSON_INVALID_UTF8_SUBSTITUTE);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('JSON encoding error in friends.php/get_friends: ' . json_last_error_msg());
                }
                break;

            case 'get_pending_requests':
                // Get requests where the current user is a recipient
                $query = "
                    SELECT f.id, u.username, u.id as user_id
                    FROM friendships f
                    JOIN users u ON (u.id = f.user_one_id OR u.id = f.user_two_id)
                    WHERE 
                        (f.user_one_id = ? OR f.user_two_id = ?) 
                        AND f.status = 0 
                        AND f.action_user_id != ?
                        AND u.id != ?
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId]);
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'requests' => $requests], JSON_INVALID_UTF8_SUBSTITUTE);
                break;

            default:
                throw new Exception('Invalid action');
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(400);
}
?>
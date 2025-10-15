<?php
// api/messages.php
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
        $receiverId = $data['receiver_id'] ?? 0;
        $message = $data['message'] ?? '';

        if (empty($receiverId) || empty($message)) {
            throw new Exception('Receiver and message are required.');
        }

        // Check if the users have a blocked relationship
        $user_one = min($currentUserId, $receiverId);
        $user_two = max($currentUserId, $receiverId);
        $query = "SELECT status FROM friendships WHERE user_one_id = ? AND user_two_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_one, $user_two]);
        $friendship = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($friendship && $friendship['status'] == 3) {
            throw new Exception('Cannot send message. This user is blocked or has blocked you.');
        }

        $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$currentUserId, $receiverId, htmlspecialchars($message)]); // Sanitize message

        echo json_encode(['success' => true, 'message' => 'Message sent.'], JSON_INVALID_UTF8_SUBSTITUTE);

    } elseif ($method === 'GET') {
        $friendId = $_GET['friend_id'] ?? 0;

        if (empty($friendId)) {
            throw new Exception('Friend ID is required.');
        }

        // Fetch conversation
        $query = "
            SELECT id, sender_id, receiver_id, message, created_at, is_read
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark messages as read
        $updateQuery = "UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$friendId, $currentUserId]);

        echo json_encode(['success' => true, 'messages' => $messages], JSON_INVALID_UTF8_SUBSTITUTE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_INVALID_UTF8_SUBSTITUTE);
    http_response_code(400);
}
?>
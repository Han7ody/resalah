<?php
require_once 'includes/functions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$currentUserId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Rasalah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/messaging.css" rel="stylesheet">
</head>
<body>

    <div class="d-flex vh-100">
        <!-- Sidebar -->
        <div class="sidebar border-end">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rasalah Messenger</h5>
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm me-2" id="theme-toggler"><i class="bi bi-brightness-high-fill"></i></button>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary" title="Dashboard"><i class="bi bi-grid-3x3-gap-fill"></i></a>
                </div>
            </div>

            <!-- Search Users -->
            <div class="p-3 border-bottom">
                <div class="input-group">
                    <input type="text" class="form-control" id="user-search-input" placeholder="Find users...">
                    <button class="btn btn-outline-secondary" type="button" id="user-search-btn"><i class="bi bi-search"></i></button>
                </div>
                <div id="user-search-results" class="list-group mt-2"></div>
            </div>

            <!-- Friend Requests -->
            <div id="friend-requests-container" class="p-3 border-bottom"></div>

            <!-- Friends List -->
            <div id="friends-list-container" class="flex-grow-1 overflow-auto"></div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-area flex-grow-1 d-flex flex-column">
            <!-- Chat Header -->
            <div id="chat-header" class="p-3 border-bottom d-none">
                <h5 id="chat-with-username"></h5>
            </div>

            <!-- Messages -->
            <div id="messages-container" class="flex-grow-1 p-3 overflow-auto"></div>

            <!-- Message Input -->
            <div id="message-input-container" class="p-3 border-top d-none">
                <div class="input-group">
                    <input type="text" id="message-input" class="form-control" placeholder="Type a message...">
                    <button class="btn btn-primary" type="button" id="send-message-btn"><i class="bi bi-send-fill"></i></button>
                </div>
            </div>
            
            <!-- Welcome Screen -->
            <div id="welcome-screen" class="d-flex flex-grow-1 justify-content-center align-items-center">
                <div class="text-center">
                    <i class="bi bi-chat-dots-fill" style="font-size: 5rem; color: #6c757d;"></i>
                    <h3 class="mt-3">Welcome to Rasalah Messenger</h3>
                    <p class="text-muted">Select a friend to start chatting.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentUserId = <?php echo $currentUserId; ?>;
    </script>
    <script src="js/messaging.js"></script>
</body>
</html>

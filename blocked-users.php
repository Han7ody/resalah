<?php
// blocked-users.php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$flash = getFlashMessage();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocked Users - Rasalah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Blocked Users</h1>
        <p>Here you can see the users you have blocked and unblock them.</p>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <div id="blocked-users-list">
            <!-- Blocked users will be loaded here via JavaScript -->
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api/blocked_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const blockedUsersList = document.getElementById('blocked-users-list');
                        if (data.users.length > 0) {
                            let html = '<ul>';
                            data.users.forEach(user => {
                                html += `<li>${user.username} <button class="btn btn-sm btn-danger unblock-btn" data-user-id="${user.id}">Unblock</button></li>`;
                            });
                            html += '</ul>';
                            blockedUsersList.innerHTML = html;
                        } else {
                            blockedUsersList.innerHTML = '<p>You have not blocked any users.</p>';
                        }
                    } else {
                        console.error('Failed to load blocked users:', data.message);
                    }
                })
                .catch(error => console.error('Error fetching blocked users:', error));

            document.getElementById('blocked-users-list').addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('unblock-btn')) {
                    const userIdToUnblock = e.target.getAttribute('data-user-id');
                    if (confirm('Are you sure you want to unblock this user?')) {
                        fetch('api/unblock_user.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `user_id=${userIdToUnblock}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                e.target.closest('li').remove();
                            } else {
                                alert('Failed to unblock user: ' + data.message);
                            }
                        })
                        .catch(error => console.error('Error unblocking user:', error));
                    }
                }
            });
        });
    </script>
</body>
</html>

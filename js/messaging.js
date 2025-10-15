document.addEventListener('DOMContentLoaded', () => {

    // --- DOM Elements ---
    const themeToggler = document.getElementById('theme-toggler');
    const userSearchInput = document.getElementById('user-search-input');
    const userSearchResults = document.getElementById('user-search-results');
    const friendRequestsContainer = document.getElementById('friend-requests-container');
    const friendsListContainer = document.getElementById('friends-list-container');
    const chatHeader = document.getElementById('chat-header');
    const chatWithUsername = document.getElementById('chat-with-username');
    const messagesContainer = document.getElementById('messages-container');
    const messageInputContainer = document.getElementById('message-input-container');
    const messageInput = document.getElementById('message-input');
    const sendMessageBtn = document.getElementById('send-message-btn');
    const welcomeScreen = document.getElementById('welcome-screen');

    // --- State ---
    const state = {
        activeFriendId: null,
        pollingInterval: null,
    };

    // --- API Helper ---
    const api = async (endpoint, method = 'GET', body = null) => {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        if (body) {
            options.body = JSON.stringify(body);
        }
        try {
            const response = await fetch(`api/${endpoint}`, options);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'API Error');
            }
            return await response.json();
        } catch (error) {
            console.error(error);
            alert(`Error: ${error.message}`);
            return null;
        }
    };

    // --- Render Functions ---
    const renderFriendRequests = (requests) => {
        friendRequestsContainer.innerHTML = '<h6>Friend Requests</h6>';
        if (!requests || requests.length === 0) {
            friendRequestsContainer.innerHTML += '<p class="text-muted small">No new requests</p>';
            return;
        }
        const list = document.createElement('div');
        list.className = 'list-group';
        requests.forEach(req => {
            list.innerHTML += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${req.username}</span>
                    <div class="friend-request-actions">
                        <button class="btn btn-sm btn-success" data-request-id="${req.id}" data-action="accept">✓</button>
                        <button class="btn btn-sm btn-danger" data-request-id="${req.id}" data-action="decline">✗</button>
                    </div>
                </div>
            `;
        });
        friendRequestsContainer.appendChild(list);
    };

    const renderFriendsList = (friends) => {
        friendsListContainer.innerHTML = '<h6 class="p-3">Friends</h6>';
        if (!friends || friends.length === 0) {
            friendsListContainer.innerHTML += '<p class="text-muted small p-3">Add some friends to start chatting</p>';
            return;
        }
        const list = document.createElement('div');
        list.className = 'list-group list-group-flush';
        friends.forEach(friend => {
            const item = document.createElement('div');
            item.className = `list-group-item list-group-item-action d-flex justify-content-between align-items-center ${friend.id == state.activeFriendId ? 'active' : ''}`;
            item.dataset.friendId = friend.id;
            item.dataset.friendName = friend.username;
            item.innerHTML = `
                <span class="flex-grow-1" data-action="select-friend">${friend.username}</span>
                <button class="btn btn-sm btn-outline-warning" data-action="unfriend" data-friend-id="${friend.id}" title="Unfriend User"><i class="bi bi-person-x-fill"></i></button>
                <button class="btn btn-sm btn-outline-danger" data-action="block-friend" data-friend-id="${friend.id}" title="Block User"><i class="bi bi-shield-slash-fill"></i></button>
            `;
            list.appendChild(item);
        });
        friendsListContainer.appendChild(list);
    };

    const renderSearchResults = (users) => {
        userSearchResults.innerHTML = '';
        if (!users || users.length === 0) return;
        users.forEach(user => {
            userSearchResults.innerHTML += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${user.username}</span>
                    <button class="btn btn-sm btn-primary" data-user-id="${user.id}" data-action="add-friend">+</button>
                </div>
            `;
        });
    };

    const renderMessages = (messages) => {
        messagesContainer.innerHTML = '';
        if (!messages) return;
        messages.forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.sender_id == currentUserId ? 'sent' : 'received'}`;
            messageDiv.textContent = msg.message;
            messagesContainer.appendChild(messageDiv);
        });
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };

    // --- Core Functions ---
    const loadInitialData = async () => {
        const requests = await api('friends.php?action=get_pending_requests');
        if (requests && requests.success) {
            renderFriendRequests(requests.requests);
        }
        const friends = await api('friends.php?action=get_friends');
        if (friends && friends.success) {
            renderFriendsList(friends.friends);
        }
    };

    const selectFriend = async (friendId, friendName) => {
        if (state.activeFriendId === friendId) return;

        state.activeFriendId = friendId;
        document.querySelectorAll('#friends-list-container .list-group-item').forEach(el => el.classList.remove('active'));
        document.querySelector(`[data-friend-id='${friendId}']`).classList.add('active');

        welcomeScreen.classList.add('d-none');
        chatHeader.classList.remove('d-none');
        messageInputContainer.classList.remove('d-none');
        messagesContainer.innerHTML = ''; // Clear previous messages

        chatWithUsername.textContent = friendName;

        const result = await api(`messages.php?friend_id=${friendId}`);
        if (result && result.success) {
            renderMessages(result.messages);
        }

        // Start polling for new messages
        if (state.pollingInterval) clearInterval(state.pollingInterval);
        state.pollingInterval = setInterval(() => fetchNewMessages(friendId), 3000);
    };

    const fetchNewMessages = async (friendId) => {
        if (state.activeFriendId !== friendId) return;
        const result = await api(`messages.php?friend_id=${friendId}`);
        if (result && result.success) {
            renderMessages(result.messages);
        }
    };

    const handleSendMessage = async () => {
        const message = messageInput.value.trim();
        if (!message || !state.activeFriendId) return;

        const result = await api('messages.php', 'POST', {
            receiver_id: state.activeFriendId,
            message: message
        });

        if (result && result.success) {
            messageInput.value = '';
            // Add message to UI immediately for better UX
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message sent';
            messageDiv.textContent = message;
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    };

    const closeChat = () => {
        state.activeFriendId = null;
        if (state.pollingInterval) clearInterval(state.pollingInterval);
        
        chatHeader.classList.add('d-none');
        messageInputContainer.classList.add('d-none');
        messagesContainer.innerHTML = '';
        welcomeScreen.classList.remove('d-none');
    };

    // --- Event Listeners ---
    userSearchInput.addEventListener('keyup', async (e) => {
        const term = e.target.value.trim();
        if (term.length < 2) {
            userSearchResults.innerHTML = '';
            return;
        }
        const result = await api(`search.php?term=${term}`);
        if (result && result.success) {
            renderSearchResults(result.users);
        }
    });

    userSearchResults.addEventListener('click', async (e) => {
        if (e.target.dataset.action === 'add-friend') {
            const receiverId = e.target.dataset.userId;
            const result = await api('friends.php', 'POST', { action: 'send_request', receiver_id: receiverId });
            if (result && result.success) {
                alert('Friend request sent!');
                userSearchInput.value = '';
                userSearchResults.innerHTML = '';
            }
        }
    });

    friendRequestsContainer.addEventListener('click', async (e) => {
        const target = e.target;
        const action = target.dataset.action;
        const requestId = target.dataset.requestId;

        if (!action || !requestId) return;

        const newStatus = action === 'accept' ? 1 : 2;
        const result = await api('friends.php', 'POST', { action: 'manage_request', friendship_id: requestId, status: newStatus });

        if (result && result.success) {
            alert(result.message);
            loadInitialData(); // Refresh lists
        }
    });

    friendsListContainer.addEventListener('click', async (e) => {
        const target = e.target;
        // Use closest to handle clicks on icons inside buttons
        const blockButton = target.closest('[data-action="block-friend"]');
        const unfriendButton = target.closest('[data-action="unfriend"]');
        const selectButton = target.closest('[data-action="select-friend"]');

        if (selectButton) {
            const friendElement = target.closest('.list-group-item');
            const friendId = friendElement.dataset.friendId;
            const friendName = friendElement.dataset.friendName;
            selectFriend(friendId, friendName);
        } else if (blockButton) {
            const friendId = blockButton.dataset.friendId;
            if (confirm('Are you sure you want to block this user? This will also remove them from your friends list.')) {
                const result = await api('friends.php', 'POST', { action: 'block_user', friend_id: friendId });
                if (result && result.success) {
                    alert(result.message);
                    if (state.activeFriendId == friendId) {
                        closeChat();
                    }
                    loadInitialData();
                }
            }
        } else if (unfriendButton) {
            const friendId = unfriendButton.dataset.friendId;
            if (confirm('Are you sure you want to unfriend this user?')) {
                const result = await api('friends.php', 'POST', { action: 'unfriend', friend_id: friendId });
                if (result && result.success) {
                    alert(result.message);
                    if (state.activeFriendId == friendId) {
                        closeChat();
                    }
                    loadInitialData();
                }
            }
        }
    });

    sendMessageBtn.addEventListener('click', handleSendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleSendMessage();
        }
    });

    // --- Theme Toggler ---
    const applyTheme = (theme) => {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        themeToggler.innerHTML = theme === 'dark' ? '<i class="bi bi-moon-stars-fill"></i>' : '<i class="bi bi-brightness-high-fill"></i>';
    };

    themeToggler.addEventListener('click', () => {
        const currentTheme = localStorage.getItem('theme') || 'light';
        applyTheme(currentTheme === 'light' ? 'dark' : 'light');
    });

    // --- Initial Load ---
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
    loadInitialData();
});

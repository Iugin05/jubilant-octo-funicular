// Shared between user and admin interfaces
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on admin or user interface
    const isAdmin = document.querySelector('.admin-container') !== null;
    
    if (isAdmin) {
        initAdminChat();
    } else {
        initUserChat();
    }
});

function initUserChat() {
    const chatMessages = document.getElementById('chat-messages');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    
    // Load messages every 2 seconds
    loadMessages();
    setInterval(loadMessages, 2000);
    
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });
    
    function sendMessage() {
        const content = messageInput.value.trim();
        if (content === '') return;
        
        fetch('api/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                content: content,
                is_admin: false
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                loadMessages();
            }
        });
    }
    
    function loadMessages() {
        fetch(`api/get_messages.php?user_id=${userId}`)
            .then(response => response.json())
            .then(messages => {
                chatMessages.innerHTML = '';
                messages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${msg.is_admin ? 'admin-message' : 'user-message'}`;
                    messageDiv.textContent = msg.content;
                    chatMessages.appendChild(messageDiv);
                });
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
    }
}

function initAdminChat() {
    const conversationList = document.getElementById('conversation-list');
    const chatMessages = document.getElementById('admin-chat-messages');
    const messageInput = document.getElementById('admin-message-input');
    const sendButton = document.getElementById('admin-send-button');
    
    let currentUserId = null;
    
    // Load conversations every 5 seconds
    loadConversations();
    setInterval(loadConversations, 5000);
    
    sendButton.addEventListener('click', sendAdminMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendAdminMessage();
    });
    
    function loadConversations() {
        fetch('api/get_conversations.php')
            .then(response => response.json())
            .then(conversations => {
                conversationList.innerHTML = '';
                conversations.forEach(user => {
                    const userDiv = document.createElement('div');
                    userDiv.className = `conversation-item ${user.id === currentUserId ? 'active' : ''}`;
                    userDiv.textContent = `User #${user.id}`;
                    userDiv.addEventListener('click', () => {
                        currentUserId = user.id;
                        document.querySelectorAll('.conversation-item').forEach(el => {
                            el.classList.remove('active');
                        });
                        userDiv.classList.add('active');
                        loadAdminMessages();
                    });
                    conversationList.appendChild(userDiv);
                });
            });
    }
    
    function loadAdminMessages() {
        if (!currentUserId) return;
        
        fetch(`api/get_messages.php?user_id=${currentUserId}`)
            .then(response => response.json())
            .then(messages => {
                chatMessages.innerHTML = '';
                messages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${msg.is_admin ? 'admin-message' : 'user-message'}`;
                    messageDiv.textContent = msg.content;
                    chatMessages.appendChild(messageDiv);
                });
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
    }
    
    function sendAdminMessage() {
        if (!currentUserId) return;
        const content = messageInput.value.trim();
        if (content === '') return;
        
        fetch('api/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: currentUserId,
                content: content,
                is_admin: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                loadAdminMessages();
            }
        });
    }
}
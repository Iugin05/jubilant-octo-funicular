<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Chat Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Admin Dashboard</h1>
        <div class="conversation-list" id="conversation-list">
            <!-- Conversations will be loaded here -->
        </div>
        <div class="admin-chat">
            <div class="chat-messages" id="admin-chat-messages"></div>
            <div class="chat-input">
                <input type="text" id="admin-message-input" placeholder="Type your response...">
                <button id="admin-send-button">Send as Admin</button>
            </div>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
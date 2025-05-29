<?php
require_once '../db_connection.php';
header('Content-Type: application/json');

$stmt = $pdo->prepare("SELECT DISTINCT user_id FROM messages ORDER BY (SELECT MAX(timestamp) FROM messages m WHERE m.user_id = messages.user_id) DESC");
$stmt->execute();
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($conversations);
?>
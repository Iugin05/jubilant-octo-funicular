<?php
require_once '../db_connection.php';
header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY timestamp ASC");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?>
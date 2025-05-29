<?php
require_once '../db_connection.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$stmt = $pdo->prepare("INSERT INTO messages (user_id, content, is_admin) VALUES (?, ?, ?)");
$success = $stmt->execute([$data['user_id'], $data['content'], $data['is_admin'] ? 1 : 0]);

echo json_encode(['success' => $success]);
?>
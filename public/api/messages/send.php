<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('METHOD_NOT_ALLOWED', 'POST method required', 405);
}

require_login();

$input = json_decode(file_get_contents('php://input'), true);

$chat_id = $input['chat_id'] ?? null;
$body = $input['body'] ?? '';

if (!$chat_id || !is_numeric($chat_id)) {
    json_error('VALIDATION_ERROR', 'Chat ID is required');
}

if (empty($body) || strlen($body) > 1000) {
    json_error('VALIDATION_ERROR', 'Message body is required (max 1000 chars)');
}

$pdo = db_conn();
$user_id = current_user_id();

// チャットの参加者確認
$stmt = $pdo->prepare("SELECT user_a, user_b FROM chats WHERE id = ?");
$stmt->execute([$chat_id]);
$chat = $stmt->fetch();

if (!$chat) {
    json_error('NOT_FOUND', 'Chat not found', 404);
}

if ($chat['user_a'] != $user_id && $chat['user_b'] != $user_id) {
    json_error('FORBIDDEN', 'Not a participant of this chat', 403);
}

// メッセージ作成
$stmt = $pdo->prepare("INSERT INTO messages (chat_id, sender_id, body) VALUES (?, ?, ?)");

try {
    $stmt->execute([$chat_id, $user_id, $body]);
    $message_id = $pdo->lastInsertId();
    
    json_ok([
        'id' => $message_id,
        'chat_id' => $chat_id,
        'sender_id' => $user_id,
        'body' => $body,
        'created_at' => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    error_log("Message send error: " . $e->getMessage());
    json_error('DB_ERROR', 'Failed to send message', 500);
}

<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('METHOD_NOT_ALLOWED', 'GET method required', 405);
}

require_login();

$chat_id = $_GET['chat_id'] ?? null;
$after_id = $_GET['after_id'] ?? 0;

if (!$chat_id || !is_numeric($chat_id)) {
    json_error('VALIDATION_ERROR', 'Chat ID is required');
}

$pdo = db_conn();
$user_id = current_user_id();

// チャットの参加者確認とパートナー情報取得
$stmt = $pdo->prepare("
    SELECT c.user_a, c.user_b, c.post_id,
           p.title as post_title,
           CASE WHEN c.user_a = ? THEN c.user_b ELSE c.user_a END as partner_id
    FROM chats c
    INNER JOIN posts p ON c.post_id = p.id
    WHERE c.id = ?
");
$stmt->execute([$user_id, $chat_id]);
$chat = $stmt->fetch();

if (!$chat) {
    json_error('NOT_FOUND', 'Chat not found', 404);
}

if ($chat['user_a'] != $user_id && $chat['user_b'] != $user_id) {
    json_error('FORBIDDEN', 'Not a participant of this chat', 403);
}

// パートナー情報取得
$stmt = $pdo->prepare("SELECT id, display_name FROM users WHERE id = ?");
$stmt->execute([$chat['partner_id']]);
$partner = $stmt->fetch();

// メッセージ取得
$stmt = $pdo->prepare("
    SELECT m.id, m.sender_id, m.body, m.created_at, u.display_name
    FROM messages m
    INNER JOIN users u ON m.sender_id = u.id
    WHERE m.chat_id = ? AND m.id > ?
    ORDER BY m.id ASC
");
$stmt->execute([$chat_id, $after_id]);
$messages = $stmt->fetchAll();

$results = [];
foreach ($messages as $msg) {
    $results[] = [
        'id' => $msg['id'],
        'sender_id' => $msg['sender_id'],
        'sender_name' => $msg['display_name'],
        'body' => $msg['body'],
        'created_at' => $msg['created_at'],
        'is_mine' => ($msg['sender_id'] == $user_id)
    ];
}

json_ok([
    'messages' => $results,
    'chat_info' => [
        'partner_name' => $partner['display_name'],
        'post_title' => $chat['post_title']
    ]
]);

<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('METHOD_NOT_ALLOWED', 'POST method required', 405);
}

require_login();

$input = json_decode(file_get_contents('php://input'), true);
$post_id = $input['post_id'] ?? null;

if (!$post_id || !is_numeric($post_id)) {
    json_error('VALIDATION_ERROR', 'Post ID is required');
}

$pdo = db_conn();
$user_id = current_user_id();

// 投稿取得
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NULL AND expires_at >= NOW()");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    json_error('NOT_FOUND', 'Post not found', 404);
}

$post_owner_id = $post['user_id'];

// 自分の投稿ならエラー
if ($post_owner_id == $user_id) {
    json_error('INVALID_REQUEST', 'Cannot start chat with yourself');
}

// チャットID計算（user_a < user_b）
$user_a = min($user_id, $post_owner_id);
$user_b = max($user_id, $post_owner_id);

// 既存チャットチェック
$stmt = $pdo->prepare("SELECT id FROM chats WHERE post_id = ? AND user_a = ? AND user_b = ?");
$stmt->execute([$post_id, $user_a, $user_b]);
$existing = $stmt->fetch();

if ($existing) {
    json_ok(['chat_id' => $existing['id']]);
    exit;
}

// 新規チャット作成
$stmt = $pdo->prepare("INSERT INTO chats (post_id, user_a, user_b) VALUES (?, ?, ?)");
try {
    $stmt->execute([$post_id, $user_a, $user_b]);
    $chat_id = $pdo->lastInsertId();
    
    json_ok(['chat_id' => $chat_id]);
} catch (PDOException $e) {
    error_log("Chat start error: " . $e->getMessage());
    json_error('DB_ERROR', 'Failed to start chat', 500);
}

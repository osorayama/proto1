<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('METHOD_NOT_ALLOWED', 'POST method required', 405);
}

require_login();

$post_id = $_GET['id'] ?? null;

if (!$post_id || !is_numeric($post_id)) {
    json_error('VALIDATION_ERROR', 'Post ID is required');
}

$pdo = db_conn();
$user_id = current_user_id();

// 自分の投稿かチェック
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    json_error('NOT_FOUND', 'Post not found', 404);
}

if ($post['user_id'] != $user_id) {
    json_error('FORBIDDEN', 'You can only delete your own posts', 403);
}

// 論理削除
$stmt = $pdo->prepare("UPDATE posts SET deleted_at = NOW() WHERE id = ?");
$stmt->execute([$post_id]);

json_ok(['message' => 'Post deleted successfully']);

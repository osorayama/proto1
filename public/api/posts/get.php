<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('METHOD_NOT_ALLOWED', 'GET method required', 405);
}

require_login();

$post_id = $_GET['id'] ?? null;

if (!$post_id || !is_numeric($post_id)) {
    json_error('VALIDATION_ERROR', 'Post ID is required');
}

$pdo = db_conn();
$user_id = current_user_id();

// ブロックしているユーザーを除外
$blocked_ids = get_blocked_user_ids($pdo, $user_id);

$sql = "
    SELECT 
        p.id, p.category, p.title, p.body, p.lat, p.lng,
        p.created_at, p.expires_at,
        u.id as user_id, u.display_name, u.email
    FROM posts p
    INNER JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
    AND p.deleted_at IS NULL
    AND p.expires_at >= NOW()
";

$params = [$post_id];

// ブロック除外
if (!empty($blocked_ids)) {
    $placeholders = implode(',', array_fill(0, count($blocked_ids), '?'));
    $sql .= " AND p.user_id NOT IN ($placeholders)";
    $params = array_merge($params, $blocked_ids);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$post = $stmt->fetch();

if (!$post) {
    json_error('NOT_FOUND', 'Post not found', 404);
}

json_ok([
    'id' => $post['id'],
    'category' => $post['category'],
    'title' => $post['title'],
    'body' => $post['body'],
    'lat' => (float)$post['lat'],
    'lng' => (float)$post['lng'],
    'created_at' => $post['created_at'],
    'expires_at' => $post['expires_at'],
    'user' => [
        'id' => $post['user_id'],
        'display_name' => $post['display_name']
    ],
    'is_mine' => ($post['user_id'] == $user_id)
]);

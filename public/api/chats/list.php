<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('METHOD_NOT_ALLOWED', 'GET method required', 405);
}

require_login();

$pdo = db_conn();
$user_id = current_user_id();

// ブロックしているユーザーを除外
$blocked_ids = get_blocked_user_ids($pdo, $user_id);

$sql = "
    SELECT 
        c.id, c.post_id, c.created_at,
        p.title as post_title,
        CASE 
            WHEN c.user_a = ? THEN c.user_b 
            ELSE c.user_a 
        END as partner_id
    FROM chats c
    INNER JOIN posts p ON c.post_id = p.id
    WHERE (c.user_a = ? OR c.user_b = ?)
";

$params = [$user_id, $user_id, $user_id];

// ブロック除外
if (!empty($blocked_ids)) {
    $placeholders = implode(',', array_fill(0, count($blocked_ids), '?'));
    $sql .= " AND CASE 
        WHEN c.user_a = ? THEN c.user_b 
        ELSE c.user_a 
    END NOT IN ($placeholders)";
    $params[] = $user_id;
    $params = array_merge($params, $blocked_ids);
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$chats = $stmt->fetchAll();

$results = [];
foreach ($chats as $chat) {
    // パートナー情報取得
    $stmt = $pdo->prepare("SELECT id, display_name FROM users WHERE id = ?");
    $stmt->execute([$chat['partner_id']]);
    $partner = $stmt->fetch();
    
    // 最新メッセージ取得
    $stmt = $pdo->prepare("
        SELECT body, created_at 
        FROM messages 
        WHERE chat_id = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$chat['id']]);
    $last_message = $stmt->fetch();
    
    $results[] = [
        'id' => $chat['id'],
        'post' => [
            'id' => $chat['post_id'],
            'title' => $chat['post_title']
        ],
        'partner' => [
            'id' => $partner['id'],
            'display_name' => $partner['display_name']
        ],
        'last_message' => $last_message ? $last_message['body'] : null,
        'updated_at' => $last_message ? $last_message['created_at'] : $chat['created_at']
    ];
}

json_ok($results);

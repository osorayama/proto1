<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('METHOD_NOT_ALLOWED', 'GET method required', 405);
}

require_login();

$pdo = db_conn();
$user_id = current_user_id();

$stmt = $pdo->prepare("
    SELECT b.id, b.blocked_id, b.created_at, u.display_name, u.email
    FROM blocks b
    INNER JOIN users u ON b.blocked_id = u.id
    WHERE b.blocker_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$blocks = $stmt->fetchAll();

$results = [];
foreach ($blocks as $block) {
    $results[] = [
        'id' => $block['id'],
        'user_id' => $block['blocked_id'],
        'display_name' => $block['display_name'],
        'created_at' => $block['created_at']
    ];
}

json_ok($results);

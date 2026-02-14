<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('METHOD_NOT_ALLOWED', 'POST method required', 405);
}

require_login();

$input = json_decode(file_get_contents('php://input'), true);

$category = $input['category'] ?? '';
$title = $input['title'] ?? '';
$body = $input['body'] ?? '';
$lat = $input['lat'] ?? null;
$lng = $input['lng'] ?? null;
$expires_days = $input['expires_days'] ?? 30;

// バリデーション
$valid_categories = ['sell_buy', 'roommate', 'job', 'event'];
if (!in_array($category, $valid_categories)) {
    json_error('VALIDATION_ERROR', 'Invalid category');
}

if (empty($title) || strlen($title) > 80) {
    json_error('VALIDATION_ERROR', 'Title is required (max 80 chars)');
}

if (empty($body) || strlen($body) > 800) {
    json_error('VALIDATION_ERROR', 'Body is required (max 800 chars)');
}

if (!is_numeric($lat) || !is_numeric($lng)) {
    json_error('VALIDATION_ERROR', 'Valid latitude and longitude are required');
}

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    json_error('VALIDATION_ERROR', 'Invalid coordinates');
}

if (!is_numeric($expires_days) || $expires_days < 1 || $expires_days > 365) {
    $expires_days = 30;
}

$pdo = db_conn();
$user_id = current_user_id();

$expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_days} days"));

$stmt = $pdo->prepare("
    INSERT INTO posts (user_id, category, title, body, lat, lng, expires_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

try {
    $stmt->execute([$user_id, $category, $title, $body, $lat, $lng, $expires_at]);
    $post_id = $pdo->lastInsertId();
    
    json_ok([
        'id' => $post_id,
        'category' => $category,
        'title' => $title,
        'body' => $body,
        'lat' => (float)$lat,
        'lng' => (float)$lng,
        'expires_at' => $expires_at
    ]);
} catch (PDOException $e) {
    error_log("Post create error: " . $e->getMessage());
    json_error('DB_ERROR', 'Failed to create post', 500);
}

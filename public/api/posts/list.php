<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('METHOD_NOT_ALLOWED', 'GET method required', 405);
}

require_login();

$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;
$radius_km = $_GET['radius_km'] ?? 3;
$category = $_GET['category'] ?? 'all';

// バリデーション
if (!is_numeric($lat) || !is_numeric($lng)) {
    json_error('VALIDATION_ERROR', 'Latitude and longitude are required');
}

$radius_km = (float)$radius_km;
if (!in_array($radius_km, [1, 3, 5])) {
    $radius_km = 3;
}

$valid_categories = ['all', 'sell_buy', 'roommate', 'job', 'event'];
if (!in_array($category, $valid_categories)) {
    $category = 'all';
}

$pdo = db_conn();
$user_id = current_user_id();

// ブロックしているユーザーを除外
$blocked_ids = get_blocked_user_ids($pdo, $user_id);

// Bounding Box取得
$bbox = get_bounding_box($lat, $lng, $radius_km);

// SQL構築
$sql = "
    SELECT 
        p.id, p.category, p.title, p.body, p.lat, p.lng, 
        p.created_at, p.expires_at,
        u.id as user_id, u.display_name
    FROM posts p
    INNER JOIN users u ON p.user_id = u.id
    WHERE p.deleted_at IS NULL
    AND p.expires_at >= NOW()
    AND p.lat BETWEEN ? AND ?
    AND p.lng BETWEEN ? AND ?
";

$params = [$bbox['min_lat'], $bbox['max_lat'], $bbox['min_lng'], $bbox['max_lng']];

// カテゴリフィルタ
if ($category !== 'all') {
    $sql .= " AND p.category = ?";
    $params[] = $category;
}

// ブロック除外
if (!empty($blocked_ids)) {
    $placeholders = implode(',', array_fill(0, count($blocked_ids), '?'));
    $sql .= " AND p.user_id NOT IN ($placeholders)";
    $params = array_merge($params, $blocked_ids);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// 距離計算とフィルタリング
$results = [];
foreach ($posts as $post) {
    $distance = haversine_km($lat, $lng, $post['lat'], $post['lng']);
    
    if ($distance <= $radius_km) {
        $results[] = [
            'id' => $post['id'],
            'category' => $post['category'],
            'title' => $post['title'],
            'lat' => (float)$post['lat'],
            'lng' => (float)$post['lng'],
            'created_at' => $post['created_at'],
            'expires_at' => $post['expires_at'],
            'distance_km' => round($distance, 2),
            'user' => [
                'id' => $post['user_id'],
                'display_name' => $post['display_name']
            ]
        ];
    }
}

// 距離でソート
usort($results, function($a, $b) {
    return $a['distance_km'] <=> $b['distance_km'];
});

json_ok($results);

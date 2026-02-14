<?php
// funcs.phpを読み込み（h()、db_conn()、ensure_session()、is_logged_in()などを使用）
require_once __DIR__ . '/../funcs.php';

// JSON成功レスポンス
function json_ok($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

// JSONエラーレスポンス
function json_error($code, $message, $status = 400) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ]
    ]);
    exit;
}

// Haversine距離計算（km）
function haversine_km($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

// Bounding Box計算（緯度・経度の範囲）
function get_bounding_box($lat, $lng, $radius_km) {
    $lat_range = $radius_km / 111.0; // 緯度1度 ≒ 111km
    $lng_range = $radius_km / (111.0 * cos(deg2rad($lat)));
    
    return [
        'min_lat' => $lat - $lat_range,
        'max_lat' => $lat + $lat_range,
        'min_lng' => $lng - $lng_range,
        'max_lng' => $lng + $lng_range
    ];
}

// ブロックしているユーザーID配列を取得
function get_blocked_user_ids($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT blocked_id FROM blocks WHERE blocker_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


// ログイン必須チェック（API用）
function require_login() {
    if (!is_logged_in()) {
        json_error('UNAUTHORIZED', 'Login required', 401);
    }
}

// 現在のユーザーID取得
function current_user_id() {
    ensure_session();
    return $_SESSION['user_id'] ?? null;
}

// 現在のユーザー情報取得
function current_user($pdo) {
    $user_id = current_user_id();
    if (!$user_id) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, email, display_name, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// ログイン処理
function login_user($user_id) {
    ensure_session();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['logged_in'] = true;
    session_regenerate_id(true);
    $_SESSION['ssid'] = session_id(); // regenerate後に設定
}

// ログアウト処理
function logout_user() {
    ensure_session();
    $_SESSION = [];
    session_destroy();
}

// パス生成ヘルパー
function base_url($path = "") {
    return BASE_PATH . ltrim($path, "/");
}

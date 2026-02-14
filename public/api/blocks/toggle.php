<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('METHOD_NOT_ALLOWED', 'POST method required', 405);
}

require_login();

$input = json_decode(file_get_contents('php://input'), true);
$target_user_id = $input['user_id'] ?? null;

if (!$target_user_id || !is_numeric($target_user_id)) {
    json_error('VALIDATION_ERROR', 'User ID is required');
}

$pdo = db_conn();
$user_id = current_user_id();

// 自分自身はブロックできない
if ($target_user_id == $user_id) {
    json_error('INVALID_REQUEST', 'Cannot block yourself');
}

// 既存のブロックをチェック
$stmt = $pdo->prepare("SELECT id FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
$stmt->execute([$user_id, $target_user_id]);
$existing = $stmt->fetch();

if ($existing) {
    // ブロック解除
    $stmt = $pdo->prepare("DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->execute([$user_id, $target_user_id]);
    
    json_ok(['action' => 'unblocked', 'user_id' => $target_user_id]);
} else {
    // ブロック追加
    $stmt = $pdo->prepare("INSERT INTO blocks (blocker_id, blocked_id) VALUES (?, ?)");
    try {
        $stmt->execute([$user_id, $target_user_id]);
        json_ok(['action' => 'blocked', 'user_id' => $target_user_id]);
    } catch (PDOException $e) {
        error_log("Block error: " . $e->getMessage());
        json_error('DB_ERROR', 'Failed to block user', 500);
    }
}

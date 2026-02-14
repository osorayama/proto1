<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('METHOD_NOT_ALLOWED', 'POST method required', 405);
}

require_login();

$input = json_decode(file_get_contents('php://input'), true);

$target_user_id = $input['target_user_id'] ?? null;
$target_post_id = $input['target_post_id'] ?? null;
$reason = $input['reason'] ?? '';
$detail = $input['detail'] ?? '';

// バリデーション
$valid_reasons = ['spam', 'harassment', 'scam', 'other'];
if (!in_array($reason, $valid_reasons)) {
    json_error('VALIDATION_ERROR', 'Invalid reason');
}

if (!$target_user_id && !$target_post_id) {
    json_error('VALIDATION_ERROR', 'Either target_user_id or target_post_id is required');
}

if (strlen($detail) > 500) {
    json_error('VALIDATION_ERROR', 'Detail max 500 chars');
}

$pdo = db_conn();
$user_id = current_user_id();

$stmt = $pdo->prepare("
    INSERT INTO reports (reporter_id, target_user_id, target_post_id, reason, detail)
    VALUES (?, ?, ?, ?, ?)
");

try {
    $stmt->execute([
        $user_id,
        $target_user_id ?: null,
        $target_post_id ?: null,
        $reason,
        $detail ?: null
    ]);
    
    $report_id = $pdo->lastInsertId();
    
    json_ok([
        'id' => $report_id,
        'message' => 'Report submitted successfully'
    ]);
} catch (PDOException $e) {
    error_log("Report error: " . $e->getMessage());
    json_error('DB_ERROR', 'Failed to submit report', 500);
}

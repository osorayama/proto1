<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('METHOD_NOT_ALLOWED', 'POST method required', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$display_name = $input['display_name'] ?? '';

// バリデーション
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('VALIDATION_ERROR', 'Valid email is required');
}

if (empty($password) || strlen($password) < 6) {
    json_error('VALIDATION_ERROR', 'Password must be at least 6 characters');
}

if (empty($display_name) || strlen($display_name) > 40) {
    json_error('VALIDATION_ERROR', 'Display name is required (max 40 chars)');
}

$pdo = db_conn();

// メール重複チェック
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_error('EMAIL_EXISTS', 'Email already registered');
}

// ユーザー作成
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (email, password_hash, display_name) 
    VALUES (?, ?, ?)
");

try {
    $stmt->execute([$email, $password_hash, $display_name]);
    $user_id = $pdo->lastInsertId();
    
    // 自動ログイン
    login_user($user_id);
    
    json_ok([
        'id' => $user_id,
        'email' => $email,
        'display_name' => $display_name
    ]);
} catch (PDOException $e) {
    error_log("Register error: " . $e->getMessage());
    json_error('DB_ERROR', 'Registration failed', 500);
}

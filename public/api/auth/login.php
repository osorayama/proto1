<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('METHOD_NOT_ALLOWED', 'POST method required', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    json_error('VALIDATION_ERROR', 'Email and password are required');
}

$pdo = db_conn();

// ユーザー取得
$stmt = $pdo->prepare("SELECT id, password_hash, email, display_name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error('INVALID_CREDENTIALS', 'Invalid email or password', 401);
}

// ログイン
login_user($user['id']);

json_ok([
    'id' => $user['id'],
    'email' => $user['email'],
    'display_name' => $user['display_name']
]);

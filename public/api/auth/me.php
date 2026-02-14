<?php
require_once __DIR__ . '/../../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('METHOD_NOT_ALLOWED', 'GET method required', 405);
}

require_login();

$pdo = db_conn();
$user = current_user($pdo);

if (!$user) {
    json_error('USER_NOT_FOUND', 'User not found', 404);
}

json_ok($user);

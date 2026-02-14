<?php
// 環境設定を読み込む
require_once __DIR__ . '/config.php';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db_conn() {
    $dbName = getenv('DB_NAME');
    $dbHost = getenv('DB_HOST');
    $dbCharset = getenv('DB_CHARSET');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');

    $dsn = "mysql:dbname={$dbName};charset={$dbCharset};host={$dbHost}";
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        exit('DBConnectError:' . $e->getMessage());
    }
}

function sql_error($stmt) {
    $error = $stmt->errorInfo();
    exit('SQLError:' . $error[2]);
}

function redirect($file) {
    header('Location: ' . $file);
    exit();
}

function ensure_session() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // セッションCookieのパスを設定（環境変数で切り替え）
        $basePath = getenv('BASE_PATH') ?: '/gs_code/proto1/';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $basePath,
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function csrf_token() {
    ensure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    ensure_session();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// --- Auth helpers ---
function is_logged_in() {
    ensure_session();
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['ssid']) && $_SESSION['ssid'] === session_id();
}

function loginCheck() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function current_user_name() {
    ensure_session();
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
}
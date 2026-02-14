<?php
// 環境設定ファイルの読み込み

// 本番環境の判定（さくらサーバーかどうか）
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'sottra.sakura.ne.jp') !== false;

if ($isProduction) {
    // 本番環境の設定
    
} else {
    // ローカル環境の設定
    define('BASE_PATH', '/gs_code/proto1/');
    putenv('BASE_PATH=/gs_code/proto1/');
    putenv('DB_NAME=gs_db_proto1');
    putenv('DB_HOST=localhost');
    putenv('DB_CHARSET=utf8mb4');
    putenv('DB_USER=root');
    putenv('DB_PASS=');
}

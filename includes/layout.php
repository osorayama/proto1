<?php
require_once __DIR__ . '/../funcs.php';

// BASE_PATHはconfig.phpで定義済み

function render_header($title = 'Community Map', $current_page = '') {
    $is_logged_in = is_logged_in();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= h($title) ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>public/assets/css/app.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
<?php
}

function render_footer($current_page = '') {
    $is_logged_in = is_logged_in();
    if ($is_logged_in):
?>
    <nav class="bottom-tabs">
        <a href="<?= BASE_PATH ?>public/index.php" class="tab-item <?= $current_page === 'map' ? 'active' : '' ?>">
            <span class="tab-icon">📍</span>
            <span class="tab-label">Map</span>
        </a>
        <a href="<?= BASE_PATH ?>public/post_new.php" class="tab-item <?= $current_page === 'post' ? 'active' : '' ?>">
            <span class="tab-icon">➕</span>
            <span class="tab-label">Post</span>
        </a>
        <a href="<?= BASE_PATH ?>public/chats.php" class="tab-item <?= $current_page === 'chats' ? 'active' : '' ?>">
            <span class="tab-icon">💬</span>
            <span class="tab-label">Chats</span>
        </a>
        <a href="<?= BASE_PATH ?>public/me.php" class="tab-item <?= $current_page === 'me' ? 'active' : '' ?>">
            <span class="tab-icon">👤</span>
            <span class="tab-label">Me</span>
        </a>
    </nav>
<?php
    endif;
?>
</body>
</html>
<?php
}
?>

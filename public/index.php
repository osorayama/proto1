<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_PATH . 'public/login.php');
    exit;
}

render_header('Map', 'map');
?>

<div class="map-container">
    <!-- フィルタ -->
    <div class="map-filters">
        <select id="radiusFilter" class="filter-select">
            <option value="1">半径 1km</option>
            <option value="3" selected>半径 3km</option>
            <option value="5">半径 5km</option>
        </select>
        
        <select id="categoryFilter" class="filter-select">
            <option value="all">すべて</option>
            <option value="sell_buy">売買・譲渡</option>
            <option value="roommate">ルームメイト</option>
            <option value="job">求人・求職</option>
            <option value="event">イベント</option>
        </select>
    </div>
    
    <!-- 地図 -->
    <div id="map"></div>
    
    <!-- 選択された投稿カード -->
    <div id="postCard" class="post-card" style="display:none;">
        <div class="post-card-content">
            <div class="post-card-header">
                <span id="cardCategory" class="post-category"></span>
                <span id="cardDistance" class="post-distance"></span>
            </div>
            <h3 id="cardTitle" class="post-card-title"></h3>
            <p id="cardAuthor" class="post-card-author"></p>
            <button id="viewDetailBtn" class="btn btn-primary btn-sm">詳細を見る</button>
        </div>
    </div>
</div>

<script>
const BASE_PATH = '<?= BASE_PATH ?>';
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_PATH ?>public/assets/js/map.js"></script>

<?php render_footer('map'); ?>

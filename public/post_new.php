<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_PATH . 'public/login.php');
    exit;
}

render_header('新規投稿', 'post');
?>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">新しい投稿</h1>
    </div>
    
    <form id="postForm" class="form-container">
        <div class="form-group">
            <label for="category">カテゴリ</label>
            <select id="category" name="category" required>
                <option value="sell_buy">売買・譲渡</option>
                <option value="roommate">ルームメイト募集</option>
                <option value="job">求人・求職</option>
                <option value="event">イベント・集まり</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="title">タイトル（80文字以内）</label>
            <input type="text" id="title" name="title" maxlength="80" required>
        </div>
        
        <div class="form-group">
            <label for="body">本文（800文字以内）</label>
            <textarea id="body" name="body" rows="6" maxlength="800" required></textarea>
        </div>
        
        <div class="form-group">
            <label>場所を選択</label>
            <button type="button" id="getLocationBtn" class="btn btn-secondary">現在地を取得</button>
            <div id="locationInfo" style="margin-top:10px; display:none;">
                <p>緯度: <span id="latValue"></span></p>
                <p>経度: <span id="lngValue"></span></p>
            </div>
            <div id="map" style="height: 250px; margin-top: 10px; border-radius: 14px; overflow: hidden;"></div>
        </div>
        
        <div class="form-group">
            <label for="expires_days">掲載期間</label>
            <select id="expires_days" name="expires_days">
                <option value="7">7日間</option>
                <option value="14">14日間</option>
                <option value="30" selected>30日間</option>
                <option value="60">60日間</option>
            </select>
        </div>
        
        <div id="errorMessage" class="error-message" style="display:none;"></div>
        
        <button type="submit" class="btn btn-primary btn-block">投稿する</button>
    </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const BASE_PATH = '<?= BASE_PATH ?>';
let selectedLat = null;
let selectedLng = null;
let map = null;
let marker = null;

// 地図初期化（デフォルト位置: ロンドン）
function initMap(lat = 51.505, lng = -0.09) {
    if (map) {
        map.setView([lat, lng], 13);
    } else {
        map = L.map('map').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
    }
    
    // マーカー設置
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', function(e) {
            const pos = marker.getLatLng();
            updateLocation(pos.lat, pos.lng);
        });
    }
    
    updateLocation(lat, lng);
}

function updateLocation(lat, lng) {
    selectedLat = lat;
    selectedLng = lng;
    document.getElementById('latValue').textContent = lat.toFixed(6);
    document.getElementById('lngValue').textContent = lng.toFixed(6);
    document.getElementById('locationInfo').style.display = 'block';
}

// 現在地取得
document.getElementById('getLocationBtn').addEventListener('click', () => {
    if (!navigator.geolocation) {
        alert('位置情報がサポートされていません');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            initMap(lat, lng);
        },
        (error) => {
            alert('位置情報の取得に失敗しました: ' + error.message);
            initMap(); // デフォルト位置で初期化
        }
    );
});

// 投稿フォーム送信
document.getElementById('postForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (!selectedLat || !selectedLng) {
        alert('場所を選択してください');
        return;
    }
    
    const category = document.getElementById('category').value;
    const title = document.getElementById('title').value;
    const body = document.getElementById('body').value;
    const expires_days = parseInt(document.getElementById('expires_days').value);
    const errorDiv = document.getElementById('errorMessage');
    
    try {
        const res = await fetch(BASE_PATH + 'public/api/posts/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                category,
                title,
                body,
                lat: selectedLat,
                lng: selectedLng,
                expires_days
            })
        });
        
        const data = await res.json();
        
        if (data.ok) {
            alert('投稿しました！');
            window.location.href = BASE_PATH + 'public/index.php';
        } else {
            errorDiv.textContent = data.error.message;
            errorDiv.style.display = 'block';
        }
    } catch (err) {
        errorDiv.textContent = '投稿に失敗しました';
        errorDiv.style.display = 'block';
    }
});

// 初期化（デフォルト位置で地図表示）
initMap();
</script>

<?php render_footer('post'); ?>

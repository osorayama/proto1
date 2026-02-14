let map = null;
let currentMarkers = [];
let currentLocation = { lat: 51.505, lng: -0.09 }; // デフォルト: ロンドン
let selectedPostId = null;

const categoryLabels = {
    'sell_buy': '売買・譲渡',
    'roommate': 'ルームメイト',
    'job': '求人・求職',
    'event': 'イベント'
};

const categoryColors = {
    'sell_buy': '#3AAFE6',
    'roommate': '#FF6B6B',
    'job': '#4ECDC4',
    'event': '#FFD93D'
};

// 地図初期化
function initMap() {
    map = L.map('map').setView([currentLocation.lat, currentLocation.lng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // 現在地取得
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                currentLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setView([currentLocation.lat, currentLocation.lng], 13);
                
                // 現在地マーカー
                L.marker([currentLocation.lat, currentLocation.lng], {
                    icon: L.icon({
                        iconUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iOCIgZmlsbD0iIzNBQUZFNiIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIzIi8+Cjwvc3ZnPg==',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    })
                }).addTo(map).bindPopup('現在地');
                
                loadPosts();
            },
            (error) => {
                console.error('位置情報取得エラー:', error);
                loadPosts();
            }
        );
    } else {
        loadPosts();
    }
}

// 投稿取得
async function loadPosts() {
    const radius = document.getElementById('radiusFilter').value;
    const category = document.getElementById('categoryFilter').value;
    
    try {
        const res = await fetch(
            `${BASE_PATH}public/api/posts/list.php?lat=${currentLocation.lat}&lng=${currentLocation.lng}&radius_km=${radius}&category=${category}`
        );
        const data = await res.json();
        
        if (data.ok) {
            displayPosts(data.data);
        } else {
            console.error('投稿取得エラー:', data.error);
        }
    } catch (err) {
        console.error('投稿取得失敗:', err);
    }
}

// 投稿をマーカーで表示
function displayPosts(posts) {
    // 既存マーカー削除
    currentMarkers.forEach(marker => map.removeLayer(marker));
    currentMarkers = [];
    
    posts.forEach(post => {
        const color = categoryColors[post.category] || '#3AAFE6';
        
        const marker = L.marker([post.lat, post.lng], {
            icon: L.divIcon({
                className: 'custom-marker',
                html: `<div style="background-color:${color}; width:32px; height:32px; border-radius:50%; border:3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);"></div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            })
        }).addTo(map);
        
        marker.on('click', () => {
            showPostCard(post);
        });
        
        currentMarkers.push(marker);
    });
}

// 投稿カード表示
function showPostCard(post) {
    selectedPostId = post.id;
    
    document.getElementById('cardCategory').textContent = categoryLabels[post.category];
    document.getElementById('cardCategory').style.backgroundColor = categoryColors[post.category];
    document.getElementById('cardDistance').textContent = `${post.distance_km}km`;
    document.getElementById('cardTitle').textContent = post.title;
    document.getElementById('cardAuthor').textContent = `投稿者: ${post.user.display_name}`;
    
    const card = document.getElementById('postCard');
    card.style.display = 'block';
    
    // アニメーション
    setTimeout(() => {
        card.classList.add('show');
    }, 10);
}

// 詳細表示
document.getElementById('viewDetailBtn').addEventListener('click', () => {
    if (selectedPostId) {
        window.location.href = `${BASE_PATH}public/post.php?id=${selectedPostId}`;
    }
});

// フィルタ変更時
document.getElementById('radiusFilter').addEventListener('change', loadPosts);
document.getElementById('categoryFilter').addEventListener('change', loadPosts);

// カードを閉じる（地図タップ時）
map?.on('click', (e) => {
    if (e.originalEvent.target.classList.contains('leaflet-container')) {
        const card = document.getElementById('postCard');
        card.classList.remove('show');
        setTimeout(() => {
            card.style.display = 'none';
        }, 300);
    }
});

// 初期化
initMap();

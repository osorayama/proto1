<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_PATH . 'public/login.php');
    exit;
}

render_header('チャット一覧', 'chats');
?>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">チャット</h1>
    </div>
    
    <div id="chatsList"></div>
    <div id="emptyMessage" style="display:none; text-align:center; padding:40px; color:#999;">
        まだチャットがありません
    </div>
</div>

<script>
const BASE_PATH = '<?= BASE_PATH ?>';
async function loadChats() {
    try {
        const res = await fetch(BASE_PATH + 'public/api/chats/list.php');
        const data = await res.json();
        
        if (data.ok) {
            renderChats(data.data);
        } else {
            console.error('チャット取得エラー:', data.error);
        }
    } catch (err) {
        console.error('チャット取得失敗:', err);
    }
}

function renderChats(chats) {
    const container = document.getElementById('chatsList');
    const emptyMsg = document.getElementById('emptyMessage');
    
    if (chats.length === 0) {
        container.innerHTML = '';
        emptyMsg.style.display = 'block';
        return;
    }
    
    emptyMsg.style.display = 'none';
    
    const html = chats.map(chat => `
        <a href="${BASE_PATH}public/chat.php?cid=${chat.id}" class="chat-item">
            <div class="chat-item-content">
                <div class="chat-item-header">
                    <h3 class="chat-partner">${escapeHtml(chat.partner.display_name)}</h3>
                    <span class="chat-time">${formatTime(chat.updated_at)}</span>
                </div>
                <p class="chat-post-title">投稿: ${escapeHtml(chat.post.title)}</p>
                <p class="chat-last-message">${chat.last_message ? escapeHtml(chat.last_message) : 'メッセージなし'}</p>
            </div>
        </a>
    `).join('');
    
    container.innerHTML = html;
}

function formatTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'たった今';
    if (minutes < 60) return `${minutes}分前`;
    if (hours < 24) return `${hours}時間前`;
    if (days < 7) return `${days}日前`;
    
    return date.toLocaleDateString('ja-JP');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadChats();
</script>

<?php render_footer('chats'); ?>

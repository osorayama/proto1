<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_PATH . 'public/login.php');
    exit;
}

$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    header('Location: ' . BASE_PATH . 'public/index.php');
    exit;
}

render_header('投稿詳細');
?>

<div class="main-content">
    <div id="postDetail"></div>
    <div id="errorMessage" class="error-message" style="display:none;"></div>
</div>

<!-- ブロック確認モーダル -->
<div id="blockModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>ユーザーをブロックしますか？</h3>
        <p>ブロックすると、このユーザーの投稿やチャットが表示されなくなります。</p>
        <div class="modal-actions">
            <button id="confirmBlockBtn" class="btn btn-danger">ブロックする</button>
            <button id="cancelBlockBtn" class="btn btn-secondary">キャンセル</button>
        </div>
    </div>
</div>

<!-- 通報モーダル -->
<div id="reportModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>投稿を通報</h3>
        <div class="form-group">
            <label>理由</label>
            <select id="reportReason">
                <option value="spam">スパム</option>
                <option value="harassment">嫌がらせ</option>
                <option value="scam">詐欺</option>
                <option value="other">その他</option>
            </select>
        </div>
        <div class="form-group">
            <label>詳細（任意）</label>
            <textarea id="reportDetail" rows="3" maxlength="500"></textarea>
        </div>
        <div class="modal-actions">
            <button id="confirmReportBtn" class="btn btn-danger">通報する</button>
            <button id="cancelReportBtn" class="btn btn-secondary">キャンセル</button>
        </div>
    </div>
</div>

<script>
const BASE_PATH = '<?= BASE_PATH ?>';
let currentPost = null;
let targetUserId = null;

const categoryLabels = {
    'sell_buy': '売買・譲渡',
    'roommate': 'ルームメイト',
    'job': '求人・求職',
    'event': 'イベント'
};

async function loadPost() {
    try {
        const res = await fetch(`${BASE_PATH}public/api/posts/get.php?id=<?= h($post_id) ?>`);
        const data = await res.json();
        
        if (!data.ok) {
            document.getElementById('errorMessage').textContent = data.error.message;
            document.getElementById('errorMessage').style.display = 'block';
            return;
        }
        
        currentPost = data.data;
        targetUserId = currentPost.user.id;
        renderPost(currentPost);
    } catch (err) {
        document.getElementById('errorMessage').textContent = '投稿の取得に失敗しました';
        document.getElementById('errorMessage').style.display = 'block';
    }
}

function renderPost(post) {
    const html = `
        <div class="post-detail-card">
            <div class="post-header">
                <span class="post-category">${categoryLabels[post.category]}</span>
                <span class="post-date">${new Date(post.created_at).toLocaleDateString('ja-JP')}</span>
            </div>
            
            <h1 class="post-title">${escapeHtml(post.title)}</h1>
            
            <div class="post-author">
                投稿者: ${escapeHtml(post.user.display_name)}
            </div>
            
            <div class="post-body">
                ${escapeHtml(post.body).replace(/\n/g, '<br>')}
            </div>
            
            <div class="post-actions">
                ${post.is_mine ? `
                    <button id="deletePostBtn" class="btn btn-danger">削除</button>
                ` : `
                    <button id="startChatBtn" class="btn btn-primary">チャットを開始</button>
                    <button id="blockUserBtn" class="btn btn-secondary">ブロック</button>
                    <button id="reportPostBtn" class="btn btn-secondary">通報</button>
                `}
            </div>
        </div>
    `;
    
    document.getElementById('postDetail').innerHTML = html;
    
    // イベントリスナー
    if (post.is_mine) {
        document.getElementById('deletePostBtn')?.addEventListener('click', deletePost);
    } else {
        document.getElementById('startChatBtn')?.addEventListener('click', startChat);
        document.getElementById('blockUserBtn')?.addEventListener('click', () => {
            document.getElementById('blockModal').style.display = 'flex';
        });
        document.getElementById('reportPostBtn')?.addEventListener('click', () => {
            document.getElementById('reportModal').style.display = 'flex';
        });
    }
}

async function deletePost() {
    if (!confirm('本当に削除しますか？')) return;
    
    try {
        const res = await fetch(`${BASE_PATH}public/api/posts/delete.php?id=<?= h($post_id) ?>`, {
            method: 'POST'
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('削除しました');
            window.location.href = BASE_PATH + 'public/index.php';
        } else {
            alert(data.error.message);
        }
    } catch (err) {
        alert('削除に失敗しました');
    }
}

async function startChat() {
    try {
        const res = await fetch(BASE_PATH + 'public/api/chats/start.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: <?= h($post_id) ?> })
        });
        const data = await res.json();
        
        if (data.ok) {
            window.location.href = `${BASE_PATH}public/chat.php?cid=${data.data.chat_id}`;
        } else {
            alert(data.error.message);
        }
    } catch (err) {
        alert('チャット開始に失敗しました');
    }
}

// ブロック確認
document.getElementById('confirmBlockBtn').addEventListener('click', async () => {
    try {
        const res = await fetch(BASE_PATH + 'public/api/blocks/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: targetUserId })
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('ブロックしました');
            window.location.href = BASE_PATH + 'public/index.php';
        } else {
            alert(data.error.message);
        }
    } catch (err) {
        alert('ブロックに失敗しました');
    }
});

document.getElementById('cancelBlockBtn').addEventListener('click', () => {
    document.getElementById('blockModal').style.display = 'none';
});

// 通報確認
document.getElementById('confirmReportBtn').addEventListener('click', async () => {
    const reason = document.getElementById('reportReason').value;
    const detail = document.getElementById('reportDetail').value;
    
    try {
        const res = await fetch(BASE_PATH + 'public/api/reports/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                target_post_id: <?= h($post_id) ?>,
                target_user_id: targetUserId,
                reason,
                detail
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('通報しました');
            document.getElementById('reportModal').style.display = 'none';
        } else {
            alert(data.error.message);
        }
    } catch (err) {
        alert('通報に失敗しました');
    }
});

document.getElementById('cancelReportBtn').addEventListener('click', () => {
    document.getElementById('reportModal').style.display = 'none';
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadPost();
</script>

<?php render_footer(); ?>

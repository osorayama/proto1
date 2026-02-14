<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_PATH . 'public/login.php');
    exit;
}

$pdo = db_conn();
$user = current_user($pdo);

// 表示名更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $new_name = trim($_POST['display_name'] ?? '');
    
    if (!empty($new_name) && strlen($new_name) <= 40) {
        $stmt = $pdo->prepare("UPDATE users SET display_name = ? WHERE id = ?");
        $stmt->execute([$new_name, $user['id']]);
        
        header('Location: ' . BASE_PATH . 'public/me.php?updated=1');
        exit;
    }
}

render_header('マイページ', 'me');
?>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">マイページ</h1>
    </div>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="success-message">表示名を更新しました</div>
    <?php endif; ?>
    
    <!-- ユーザー情報 -->
    <div class="card">
        <h2 class="card-title">アカウント情報</h2>
        <div class="info-row">
            <span class="info-label">メール:</span>
            <span class="info-value"><?= h($user['email']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">登録日:</span>
            <span class="info-value"><?= date('Y年m月d日', strtotime($user['created_at'])) ?></span>
        </div>
    </div>
    
    <!-- 表示名変更 -->
    <div class="card">
        <h2 class="card-title">表示名変更</h2>
        <form method="POST" class="form-inline">
            <div class="form-group">
                <input type="text" name="display_name" value="<?= h($user['display_name']) ?>" maxlength="40" required>
            </div>
            <button type="submit" name="update_name" class="btn btn-primary">更新</button>
        </form>
    </div>
    
    <!-- ブロックリスト -->
    <div class="card">
        <h2 class="card-title">ブロックリスト</h2>
        <div id="blocksList"></div>
        <div id="emptyBlocks" style="display:none; color:#999; text-align:center; padding:20px;">
            ブロックしているユーザーはいません
        </div>
    </div>
    
    <!-- ログアウト -->
    <div class="card">
        <button id="logoutBtn" class="btn btn-danger btn-block">ログアウト</button>
    </div>
</div>

<script>
const BASE_PATH = '<?= BASE_PATH ?>';
async function loadBlocks() {
    try {
        const res = await fetch(BASE_PATH + 'public/api/blocks/list.php');
        const data = await res.json();
        
        if (data.ok) {
            renderBlocks(data.data);
        }
    } catch (err) {
        console.error('ブロックリスト取得失敗:', err);
    }
}

function renderBlocks(blocks) {
    const container = document.getElementById('blocksList');
    const emptyMsg = document.getElementById('emptyBlocks');
    
    if (blocks.length === 0) {
        container.innerHTML = '';
        emptyMsg.style.display = 'block';
        return;
    }
    
    emptyMsg.style.display = 'none';
    
    const html = blocks.map(block => `
        <div class="block-item">
            <div class="block-info">
                <strong>${escapeHtml(block.display_name)}</strong>
                <span class="block-date">${formatDate(block.created_at)}</span>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="unblockUser(${block.user_id})">解除</button>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

async function unblockUser(userId) {
    if (!confirm('ブロックを解除しますか？')) return;
    
    try {
        const res = await fetch(BASE_PATH + 'public/api/blocks/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        
        const data = await res.json();
        
        if (data.ok) {
            loadBlocks();
        } else {
            alert(data.error.message);
        }
    } catch (err) {
        alert('ブロック解除に失敗しました');
    }
}

document.getElementById('logoutBtn').addEventListener('click', async () => {
    if (!confirm('ログアウトしますか？')) return;
    
    try {
        await fetch(BASE_PATH + 'public/api/auth/logout.php', { method: 'POST' });
        window.location.href = BASE_PATH + 'public/login.php';
    } catch (err) {
        alert('ログアウトに失敗しました');
    }
});

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('ja-JP');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadBlocks();
</script>

<?php render_footer('me'); ?>

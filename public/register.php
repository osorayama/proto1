<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

// すでにログイン済みならMapへ
if (is_logged_in()) {
    header('Location: ' . BASE_PATH . 'public/index.php');
    exit;
}

render_header('新規登録');
?>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">🗺️ Community Map</h1>
        <p class="auth-subtitle">新規アカウント作成</p>
        
        <form id="registerForm" class="auth-form">
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password">パスワード（6文字以上）</label>
                <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
            </div>
            
            <div class="form-group">
                <label for="display_name">表示名（40文字以内）</label>
                <input type="text" id="display_name" name="display_name" required maxlength="40" autocomplete="name">
            </div>
            
            <div id="errorMessage" class="error-message" style="display:none;"></div>
            
            <button type="submit" class="btn btn-primary btn-block">登録</button>
        </form>
        
        <div class="auth-footer">
            <p>すでにアカウントをお持ちの方は<a href="login.php">ログイン</a></p>
        </div>
    </div>
</div>

<script>
const BASE_PATH = '<?= BASE_PATH ?>';

document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const display_name = document.getElementById('display_name').value;
    const errorDiv = document.getElementById('errorMessage');
    
    try {
        const url = BASE_PATH + 'public/api/auth/register.php';
        console.log('Requesting:', url);
        
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ email, password, display_name })
        });
        
        console.log('Response status:', res.status);
        
        const contentType = res.headers.get('content-type');
        console.log('Content-Type:', contentType);
        
        const text = await res.text();
        console.log('Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            errorDiv.textContent = 'サーバーエラー: ' + text.substring(0, 100);
            errorDiv.style.display = 'block';
            return;
        }
        
        if (data.ok) {
            window.location.href = BASE_PATH + 'public/index.php';
        } else {
            errorDiv.textContent = data.error?.message || 'エラーが発生しました';
            errorDiv.style.display = 'block';
        }
    } catch (err) {
        console.error('Fetch error:', err);
        errorDiv.textContent = '登録に失敗しました: ' + err.message;
        errorDiv.style.display = 'block';
    }
});
</script>

<?php render_footer(); ?>

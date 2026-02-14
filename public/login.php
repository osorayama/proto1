<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

// すでにログイン済みならMapへ
if (is_logged_in()) {
    header('Location: ' . BASE_PATH . 'public/index.php');
    exit;
}

render_header('ログイン');
?>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">🗺️ Community Map</h1>
        <p class="auth-subtitle">海外滞在中の日本人向けコミュニティ</p>
        
        <form id="loginForm" class="auth-form">
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <div id="errorMessage" class="error-message" style="display:none;"></div>
            
            <button type="submit" class="btn btn-primary btn-block">ログイン</button>
        </form>
        
        <div class="auth-footer">
            <p>アカウントをお持ちでない方は<a href="register.php">新規登録</a></p>
        </div>
    </div>
</div>

<script>
const BASE_PATH = '<?= BASE_PATH ?>';

document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('errorMessage');
    
    try {
        const url = BASE_PATH + 'public/api/auth/login.php';
        console.log('Requesting:', url);
        
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ email, password })
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
        errorDiv.textContent = 'ログインに失敗しました: ' + err.message;
        errorDiv.style.display = 'block';
    }
});
</script>

<?php render_footer(); ?>

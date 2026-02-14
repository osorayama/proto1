<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_PATH . 'public/login.php');
    exit;
}

$chat_id = $_GET['cid'] ?? null;
if (!$chat_id) {
    header('Location: ' . BASE_PATH . 'public/chats.php');
    exit;
}

render_header('チャット');
?>

<div class="chat-container">
    <div class="chat-header">
        <a href="<?= BASE_PATH ?>public/chats.php" class="back-btn">← 戻る</a>
        <h2 id="partnerName">読み込み中...</h2>
    </div>
    
    <div id="messagesContainer" class="messages-container"></div>
    
    <div class="message-input-container">
        <textarea id="messageInput" placeholder="メッセージを入力..." rows="2" maxlength="1000"></textarea>
        <button id="sendBtn" class="btn btn-primary">送信</button>
    </div>
</div>

<script>
const BASE_PATH = '<?= BASE_PATH ?>';
</script>
<script src="<?= BASE_PATH ?>public/assets/js/chat.js"></script>
<script>
// chat.jsを初期化
initChat(<?= h($chat_id) ?>);
</script>

<?php render_footer(); ?>

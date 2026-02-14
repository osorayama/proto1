let chatId = null;
let lastMessageId = 0;
let pollingInterval = null;
let isLoading = false;
let isInitialized = false;

function initChat(id) {
    chatId = id;
    loadMessages();
    startPolling();
    
    // 送信ボタン
    document.getElementById('sendBtn').addEventListener('click', sendMessage);
    
    // Enterで送信（Shift+Enterで改行）
    document.getElementById('messageInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
}

async function loadMessages() {
    if (isLoading) return;
    isLoading = true;
    
    try {
        const res = await fetch(
            `${BASE_PATH}public/api/messages/list.php?chat_id=${chatId}&after_id=${lastMessageId}`
        );
        const data = await res.json();
        
        if (data.ok) {
            // 初回読み込み時にパートナー名を設定
            if (!isInitialized && data.data.chat_info) {
                document.getElementById('partnerName').textContent = data.data.chat_info.partner_name;
                isInitialized = true;
            }
            
            const messages = data.data.messages || [];
            if (messages.length > 0) {
                appendMessages(messages);
                lastMessageId = messages[messages.length - 1].id;
            }
        } else {
            console.error('メッセージ取得エラー:', data.error);
        }
    } catch (err) {
        console.error('メッセージ取得失敗:', err);
    } finally {
        isLoading = false;
    }
}

function appendMessages(messages) {
    const container = document.getElementById('messagesContainer');
    const shouldScroll = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
    
    messages.forEach(msg => {
        const div = document.createElement('div');
        div.className = `message ${msg.is_mine ? 'message-mine' : 'message-other'}`;
        
        div.innerHTML = `
            <div class="message-content">
                ${!msg.is_mine ? `<div class="message-sender">${escapeHtml(msg.sender_name)}</div>` : ''}
                <div class="message-body">${escapeHtml(msg.body).replace(/\n/g, '<br>')}</div>
                <div class="message-time">${formatTime(msg.created_at)}</div>
            </div>
        `;
        
        container.appendChild(div);
    });
    
    if (shouldScroll) {
        container.scrollTop = container.scrollHeight;
    }
}

async function sendMessage() {
    const input = document.getElementById('messageInput');
    const body = input.value.trim();
    
    if (!body) return;
    
    try {
        const res = await fetch(BASE_PATH + 'public/api/messages/send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ chat_id: chatId, body })
        });
        
        const data = await res.json();
        
        if (data.ok) {
            input.value = '';
            // すぐに反映
            loadMessages();
        } else {
            alert(data.error.message);
        }
    } catch (err) {
        alert('メッセージ送信に失敗しました');
    }
}

function startPolling() {
    // 5秒ごとにポーリング
    pollingInterval = setInterval(() => {
        loadMessages();
    }, 5000);
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
}

function formatTime(dateStr) {
    const date = new Date(dateStr);
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ページ離脱時にポーリング停止
window.addEventListener('beforeunload', stopPolling);

<?php
/**
 * Floating Chat Widget - Facebook Style (Fully Functional)
 *
 * Wired to the SAME backend endpoints used by admin/top-bar.php's private
 * chat tab: search_users.php, fetch_recent_chats.php, load_chat.php,
 * send_message.php, delete_message.php. These files are identical copies
 * in admin/, staff/, and students/, so relative fetch() calls resolve
 * correctly no matter which role directory the including page lives in.
 *
 * Add this just before </body>:
 * <?php include(__DIR__ . '/../includes/floating-chat-widget.php'); ?>
 *
 * The widget only renders for a logged-in user (requires $_SESSION['user_id']
 * and $_SESSION['user_type']); on public pages with no session it renders nothing.
 */

// Do NOT start a new session here: this include sits near the end of the
// page, after HTML has already been output, so calling session_start() for
// a page that never started one would trigger "headers already sent"
// warnings. If the including page never started a session, there is no
// logged-in user, so we simply render nothing.
if (session_status() !== PHP_SESSION_ACTIVE) {
    return;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    return;
}

$current_user_id = $_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'];
?>
<!-- Floating Chat Widget -->
<div id="floatingChatWidget" class="floating-chat-widget" data-user-id="<?php echo htmlspecialchars($current_user_id); ?>" data-user-type="<?php echo htmlspecialchars($current_user_type); ?>">
    <!-- Chat Bubble Button -->
    <button class="chat-bubble" id="chatBubbleBtn" onclick="fcwToggleWindow()" title="Open Chat">
        <svg class="chat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span class="unread-badge" id="fcwUnreadBadge" style="display: none;">0</span>
    </button>

    <!-- Chat Window (list) -->
    <div class="chat-window" id="fcwChatWindow">
        <div class="chat-header">
            <div class="chat-header-content">
                <h3>Messaging</h3>
                <p class="chat-status">Search users or continue a conversation</p>
            </div>
            <button class="close-btn" onclick="fcwToggleWindow()" title="Close">✕</button>
        </div>

        <div class="search-box">
            <input type="text" id="fcwSearchInput" placeholder="Search people..." oninput="fcwHandleSearchInput(this.value)" autocomplete="off">
        </div>

        <!-- Recent conversations (default view) -->
        <div class="chats-list" id="fcwRecentList">
            <div class="empty-state"><p>Loading conversations...</p></div>
        </div>

        <!-- Search results (shown while typing) -->
        <div class="chats-list" id="fcwSearchResultsList" style="display:none;"></div>
    </div>

    <!-- Conversation Modal -->
    <div class="chat-modal" id="fcwChatModal">
        <div class="chat-modal-content">
            <div class="modal-header">
                <div class="modal-header-info">
                    <button class="icon-btn back-btn" onclick="fcwBackToList()" title="Back">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <h3 id="fcwModalChatName">Chat</h3>
                </div>
                <div class="modal-actions">
                    <button class="close-btn" onclick="fcwCloseModal()" title="Close">✕</button>
                </div>
            </div>

            <div class="messages-container" id="fcwMessagesContainer">
                <div class="empty-state"><p>No messages yet.</p></div>
            </div>

            <div class="message-input-area">
                <div class="input-wrapper">
                    <input type="text" id="fcwMessageInput" placeholder="Aa" onkeypress="fcwHandleKeyPress(event)">
                </div>
                <button class="send-btn" id="fcwSendBtn" onclick="fcwSendMessage()" title="Send">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .floating-chat-widget {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    .chat-bubble {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #061E4E 0%, #10177a 100%);
        border: none;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(6, 30, 78, 0.4);
        transition: all 0.3s ease;
        position: relative;
    }

    .chat-bubble:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(6, 30, 78, 0.5);
    }

    .chat-bubble:active {
        transform: scale(0.95);
    }

    .chat-icon {
        width: 28px;
        height: 28px;
    }

    .unread-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #e92b2d;
        color: white;
        border-radius: 50%;
        min-width: 22px;
        height: 22px;
        padding: 0 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        border: 2px solid white;
    }

    .chat-window {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 340px;
        height: 460px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 40px rgba(0, 0, 0, 0.2);
        display: none;
        flex-direction: column;
        opacity: 0;
        transform: scale(0.8) translateY(20px);
        transition: all 0.25s ease;
        overflow: hidden;
    }

    .chat-window.active {
        display: flex;
        opacity: 1;
        transform: scale(1) translateY(0);
    }

    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        border-bottom: 1px solid #e0e0e0;
        background: white;
    }

    .chat-header-content h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #000;
    }

    .chat-status {
        margin: 4px 0 0 0;
        font-size: 12px;
        color: #65676b;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 18px;
        color: #65676b;
        cursor: pointer;
        padding: 4px 8px;
        transition: color 0.2s;
        line-height: 1;
    }

    .close-btn:hover {
        color: #000;
    }

    .search-box {
        padding: 8px 12px;
        border-bottom: 1px solid #e0e0e0;
    }

    .search-box input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 20px;
        font-size: 13px;
        outline: none;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .search-box input:focus {
        border-color: #061E4E;
    }

    .chats-list {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .empty-state {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        min-height: 80px;
        color: #65676b;
        font-size: 13px;
        text-align: center;
        padding: 12px;
    }

    .chat-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
        display: flex;
        gap: 12px;
        align-items: center;
        background: none;
        border-left: none;
        border-right: none;
        border-top: none;
        width: 100%;
        text-align: left;
    }

    .chat-item:hover {
        background: #f0f0f0;
    }

    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #061E4E, #10177a);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        flex-shrink: 0;
    }

    .chat-info {
        flex: 1;
        min-width: 0;
    }

    .chat-name {
        font-size: 13px;
        font-weight: 500;
        margin: 0;
        color: #000;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .chat-name.unread {
        font-weight: 700;
    }

    .chat-preview {
        font-size: 12px;
        color: #65676b;
        margin: 2px 0 0 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-time {
        font-size: 11px;
        color: #999;
        flex-shrink: 0;
    }

    .new-badge {
        background: #e92b2d;
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 1px 6px;
        border-radius: 10px;
    }

    .chat-type-tag {
        font-size: 10px;
        color: #999;
        font-weight: 400;
        text-transform: capitalize;
    }

    /* Chat Modal */
    .chat-modal {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 360px;
        height: 540px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 40px rgba(0, 0, 0, 0.2);
        display: none;
        flex-direction: column;
        opacity: 0;
        transform: scale(0.9) translateY(40px);
        transition: all 0.25s ease;
        z-index: 1001;
    }

    .chat-modal.active {
        display: flex;
        opacity: 1;
        transform: scale(1) translateY(0);
    }

    .chat-modal-content {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #e0e0e0;
    }

    .modal-header-info {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .modal-header-info h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #000;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .icon-btn {
        background: none;
        border: none;
        color: #061E4E;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
        flex-shrink: 0;
    }

    .icon-btn:hover {
        color: #10177a;
    }

    .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 16px 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .message-row {
        display: flex;
        flex-direction: column;
        max-width: 78%;
    }

    .message-row.me {
        align-self: flex-end;
        align-items: flex-end;
    }

    .message-row.them {
        align-self: flex-start;
        align-items: flex-start;
    }

    .message-bubble-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .message {
        padding: 8px 12px;
        border-radius: 16px;
        font-size: 13px;
        line-height: 1.4;
        word-wrap: break-word;
        white-space: pre-wrap;
    }

    .message.them {
        background: #e4e6eb;
        color: #000;
    }

    .message.me {
        background: #061E4E;
        color: white;
    }

    .message.sending {
        background: #e9ecef;
        color: #6c757d;
        font-style: italic;
    }

    .fcw-system-notice {
        align-self: center;
        max-width: 90%;
        text-align: center;
        background: #fff3cd;
        color: #664d03;
        border: 1px solid #ffe69c;
        border-radius: 10px;
        padding: 8px 14px;
        font-size: 12px;
        margin: 4px 0;
    }

    .input-wrapper input:disabled {
        color: #999;
        cursor: not-allowed;
    }

    .send-btn:disabled {
        color: #ccc;
        cursor: not-allowed;
    }

    .send-btn:disabled:hover {
        transform: none;
    }

    .message-meta {
        font-size: 10px;
        color: #999;
        margin-top: 3px;
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .delete-msg-btn {
        background: none;
        border: none;
        color: #e92b2d;
        cursor: pointer;
        padding: 0;
        font-size: 12px;
        opacity: 0.6;
        transition: opacity 0.2s;
    }

    .delete-msg-btn:hover {
        opacity: 1;
    }

    .message-input-area {
        padding: 12px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .input-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        background: #f0f0f0;
        border-radius: 20px;
        padding: 0 12px;
    }

    .input-wrapper input {
        flex: 1;
        border: none;
        background: none;
        outline: none;
        padding: 10px 0;
        font-size: 13px;
        min-width: 0;
    }

    .send-btn {
        background: none;
        border: none;
        color: #061E4E;
        cursor: pointer;
        padding: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
        flex-shrink: 0;
    }

    .send-btn:hover {
        transform: scale(1.1);
    }

    .send-btn:active {
        transform: scale(0.95);
    }

    .chats-list::-webkit-scrollbar,
    .messages-container::-webkit-scrollbar {
        width: 6px;
    }

    .chats-list::-webkit-scrollbar-track,
    .messages-container::-webkit-scrollbar-track {
        background: transparent;
    }

    .chats-list::-webkit-scrollbar-thumb,
    .messages-container::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .chats-list::-webkit-scrollbar-thumb:hover,
    .messages-container::-webkit-scrollbar-thumb:hover {
        background: #999;
    }

    @media (max-width: 480px) {
        .floating-chat-widget {
            bottom: 10px;
            right: 10px;
        }

        .chat-window, .chat-modal {
            width: calc(100vw - 20px);
            height: calc(100vh - 100px);
            bottom: 70px;
            right: 10px;
            max-width: 100%;
        }

        .chat-bubble {
            width: 48px;
            height: 48px;
        }

        .chat-icon {
            width: 24px;
            height: 24px;
        }
    }
</style>

<script>
(function() {
    const widget = document.getElementById('floatingChatWidget');
    const CURRENT_USER_ID = widget.dataset.userId;
    const CURRENT_USER_TYPE = widget.dataset.userType;

    let currentChat = null; // { receiverId, receiverType, receiverName }
    let lastMessageId = 0;
    let convoPollInterval = null;
    let recentPollInterval = null;
    let searchDebounce = null;
    let conversationDisabled = false;

    window.fcwToggleWindow = function() {
        const win = document.getElementById('fcwChatWindow');
        const willOpen = !win.classList.contains('active');
        win.classList.toggle('active');
        if (willOpen) {
            fcwLoadRecentChats();
        }
    };

    function fcwCloseWindow() {
        document.getElementById('fcwChatWindow').classList.remove('active');
    }

    window.fcwHandleSearchInput = function(value) {
        clearTimeout(searchDebounce);
        const query = value.trim();
        const recentList = document.getElementById('fcwRecentList');
        const resultsList = document.getElementById('fcwSearchResultsList');

        if (query.length < 2) {
            resultsList.style.display = 'none';
            recentList.style.display = 'flex';
            return;
        }

        searchDebounce = setTimeout(() => {
            fetch(`search_users.php?query=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(users => {
                    recentList.style.display = 'none';
                    resultsList.style.display = 'flex';
                    resultsList.innerHTML = '';

                    if (!users || users.length === 0 || users.error) {
                        resultsList.innerHTML = '<div class="empty-state"><p>No users found.</p></div>';
                        return;
                    }

                    users.forEach(user => {
                        if (String(user.id) === String(CURRENT_USER_ID) && user.user_type === CURRENT_USER_TYPE) return;
                        const item = document.createElement('button');
                        item.className = 'chat-item';
                        item.innerHTML = `
                            <div class="chat-avatar">${fcwInitial(user.fullName)}</div>
                            <div class="chat-info">
                                <p class="chat-name">${fcwEscape(user.fullName || 'Unknown User')}</p>
                                <p class="chat-preview chat-type-tag">${fcwEscape(user.user_type || '')}</p>
                            </div>
                        `;
                        item.onclick = () => fcwOpenChat(user.id, user.user_type, user.fullName || 'Unknown User');
                        resultsList.appendChild(item);
                    });
                })
                .catch(() => {
                    resultsList.style.display = 'flex';
                    recentList.style.display = 'none';
                    resultsList.innerHTML = '<div class="empty-state"><p>Error searching users.</p></div>';
                });
        }, 300);
    };

    function fcwLoadRecentChats() {
        fetch('fetch_recent_chats.php?t=' + Date.now())
            .then(r => r.json())
            .then(chats => {
                const recentList = document.getElementById('fcwRecentList');
                recentList.innerHTML = '';

                if (!chats || chats.error || chats.length === 0) {
                    recentList.innerHTML = '<div class="empty-state"><p>No conversations yet. Search for someone to start chatting.</p></div>';
                    fcwUpdateBadge(0);
                    return;
                }

                let unreadCount = 0;
                chats.forEach(chat => {
                    if (chat.is_new) unreadCount++;

                    const item = document.createElement('button');
                    item.className = 'chat-item';
                    item.innerHTML = `
                        <div class="chat-avatar">${fcwInitial(chat.full_name)}</div>
                        <div class="chat-info">
                            <p class="chat-name ${chat.is_new ? 'unread' : ''}">
                                ${fcwEscape(chat.full_name || 'Unknown User')}
                                ${chat.is_new ? '<span class="new-badge">New</span>' : ''}
                            </p>
                            <p class="chat-preview">${fcwEscape(chat.last_message || 'No messages yet')}</p>
                        </div>
                        <span class="chat-time">${fcwEscape(chat.time || '')}</span>
                    `;
                    item.onclick = () => fcwOpenChat(chat.chat_partner, chat.chat_partner_type, chat.full_name || 'Unknown User');
                    recentList.appendChild(item);
                });

                fcwUpdateBadge(unreadCount);
            })
            .catch(() => {
                document.getElementById('fcwRecentList').innerHTML = '<div class="empty-state"><p>Error loading conversations.</p></div>';
            });
    }

    function fcwUpdateBadge(count) {
        const badge = document.getElementById('fcwUnreadBadge');
        if (count > 0) {
            badge.style.display = 'flex';
            badge.textContent = count > 9 ? '9+' : count;
        } else {
            badge.style.display = 'none';
        }
    }

    window.fcwOpenChat = function(receiverId, receiverType, receiverName) {
        currentChat = { receiverId, receiverType, receiverName };
        lastMessageId = 0;
        fcwSetConversationDisabled(false);

        document.getElementById('fcwModalChatName').textContent = receiverName;
        document.getElementById('fcwMessagesContainer').innerHTML = '<div class="empty-state"><p>Loading messages...</p></div>';
        document.getElementById('fcwChatModal').classList.add('active');
        fcwCloseWindow();

        fcwLoadConversation(true);
        fcwStartConvoPolling();
    };

    function fcwSetConversationDisabled(disabled, reason) {
        conversationDisabled = disabled;
        const input = document.getElementById('fcwMessageInput');
        const sendBtn = document.getElementById('fcwSendBtn');
        input.disabled = disabled;
        sendBtn.disabled = disabled;
        input.placeholder = disabled ? (reason || 'This conversation is unavailable') : 'Aa';
    }

    window.fcwCloseModal = function() {
        document.getElementById('fcwChatModal').classList.remove('active');
        fcwStopConvoPolling();
        currentChat = null;
    };

    window.fcwBackToList = function() {
        fcwCloseModal();
        document.getElementById('fcwChatWindow').classList.add('active');
        fcwLoadRecentChats();
    };

    function fcwLoadConversation(isInitialLoad) {
        if (!currentChat) return;

        fetch(`load_chat.php?receiver_id=${encodeURIComponent(currentChat.receiverId)}&receiver_type=${encodeURIComponent(currentChat.receiverType)}&t=${Date.now()}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('fcwMessagesContainer').innerHTML = `<div class="empty-state"><p>Error: ${fcwEscape(data.error)}</p></div>`;
                    return;
                }

                if (!Array.isArray(data)) return;

                if (isInitialLoad) {
                    const box = document.getElementById('fcwMessagesContainer');
                    box.innerHTML = '';
                    if (data.length === 0) {
                        box.innerHTML = '<div class="empty-state"><p>No messages yet. Say hello!</p></div>';
                    }
                }

                data.forEach(msg => {
                    const isMe = String(msg.sender_id) === String(CURRENT_USER_ID) && msg.sender_type === CURRENT_USER_TYPE;
                    fcwAppendMessage(msg.message, isMe, msg.timestamp, msg.id, msg.status);
                    if (Number(msg.id) > lastMessageId) lastMessageId = Number(msg.id);
                });
            })
            .catch(() => {
                if (isInitialLoad) {
                    document.getElementById('fcwMessagesContainer').innerHTML = '<div class="empty-state"><p>Error loading messages.</p></div>';
                }
            });
    }

    function fcwStartConvoPolling() {
        fcwStopConvoPolling();
        convoPollInterval = setInterval(() => fcwLoadConversation(false), 2000);
    }

    function fcwStopConvoPolling() {
        if (convoPollInterval) {
            clearInterval(convoPollInterval);
            convoPollInterval = null;
        }
    }

    function fcwAppendSystemNotice(text) {
        const box = document.getElementById('fcwMessagesContainer');
        const emptyState = box.querySelector('.empty-state');
        if (emptyState) emptyState.remove();

        const notice = document.createElement('div');
        notice.className = 'fcw-system-notice';
        notice.textContent = text;
        box.appendChild(notice);
        box.scrollTop = box.scrollHeight;
    }

    function fcwAppendMessage(message, isMe, timestamp, id, status) {
        const box = document.getElementById('fcwMessagesContainer');
        if (document.querySelector(`[data-fcw-id="${id}"]`)) return;

        const emptyState = box.querySelector('.empty-state');
        if (emptyState) emptyState.remove();

        const row = document.createElement('div');
        row.className = `message-row ${isMe ? 'me' : 'them'}`;
        row.dataset.fcwId = id;

        const date = new Date(timestamp);
        const timeString = isNaN(date.getTime()) ? '' : date.toLocaleTimeString('en-PH', {
            hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'Asia/Manila'
        });

        const bubbleRow = document.createElement('div');
        bubbleRow.className = 'message-bubble-row';

        const bubble = document.createElement('div');
        bubble.className = `message ${status === 'sending' ? 'sending' : (isMe ? 'me' : 'them')}`;
        bubble.textContent = message;
        bubbleRow.appendChild(bubble);

        if (isMe && status !== 'sending') {
            const del = document.createElement('button');
            del.className = 'delete-msg-btn';
            del.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>';
            del.title = 'Delete message';
            del.onclick = () => fcwDeleteMessage(id, row);
            bubbleRow.appendChild(del);
        }

        row.appendChild(bubbleRow);

        const meta = document.createElement('div');
        meta.className = 'message-meta';
        let metaText = timeString;
        if (isMe && status && status !== 'sending') {
            metaText += status === 'read' ? ' · Seen' : ' · Sent';
        }
        meta.textContent = metaText;
        row.appendChild(meta);

        box.appendChild(row);
        box.scrollTop = box.scrollHeight;
    }

    window.fcwSendMessage = function() {
        if (conversationDisabled) return;

        const input = document.getElementById('fcwMessageInput');
        const message = input.value.trim();
        if (!message || !currentChat) return;

        const tempId = 'temp-' + Date.now();
        fcwAppendMessage(message, true, new Date().toISOString(), tempId, 'sending');
        input.value = '';

        const formData = new FormData();
        formData.append('receiver_id', currentChat.receiverId);
        formData.append('receiver_type', currentChat.receiverType);
        formData.append('message', message);

        fetch('send_message.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(result => {
                const tempEl = document.querySelector(`[data-fcw-id="${tempId}"]`);
                if (tempEl) tempEl.remove();

                if (result.success) {
                    fcwLoadConversation(false);
                    return;
                }

                console.error('Send failed:', result.error);

                if (result.code === 'receiver_not_found' || result.code === 'sender_not_found') {
                    // Unrecoverable for this conversation: either the recipient's
                    // account no longer exists, or the current session's own
                    // account was deleted/recreated since login. Retrying sends
                    // the exact same failure every time, so show ONE notice and
                    // lock the input instead of letting it pile up.
                    fcwAppendSystemNotice(result.error || 'This conversation is unavailable.');
                    fcwSetConversationDisabled(true, result.code === 'sender_not_found' ? 'Please log in again' : 'Contact no longer exists');
                    fcwStopConvoPolling();

                    if (result.code === 'sender_not_found') {
                        fcwStopRecentPolling();
                    }
                } else {
                    fcwAppendSystemNotice(result.error || 'Failed to send message.');
                }
            })
            .catch(error => {
                const tempEl = document.querySelector(`[data-fcw-id="${tempId}"]`);
                if (tempEl) tempEl.remove();
                fcwAppendSystemNotice('Network error sending message.');
                console.error('Send error:', error);
            });
    };

    window.fcwDeleteMessage = function(id, rowEl) {
        if (!confirm('Delete this message?')) return;

        fetch('delete_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(id)}`
        })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                rowEl.remove();
            } else {
                alert('Failed to delete message: ' + (result.error || result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Network error while deleting message.');
            console.error('Delete error:', error);
        });
    };

    window.fcwHandleKeyPress = function(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            fcwSendMessage();
        }
    };

    function fcwInitial(name) {
        if (!name) return '?';
        return name.trim().charAt(0).toUpperCase();
    }

    function fcwEscape(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    // Close chat window when clicking outside (but not while conversation modal is open)
    document.addEventListener('click', function(event) {
        const win = document.getElementById('fcwChatWindow');
        const modal = document.getElementById('fcwChatModal');
        if (!widget.contains(event.target) && !modal.classList.contains('active')) {
            win.classList.remove('active');
        }
    });

    function fcwStopRecentPolling() {
        if (recentPollInterval) {
            clearInterval(recentPollInterval);
            recentPollInterval = null;
        }
    }

    // Initial badge load + periodic refresh of recent chats badge
    document.addEventListener('DOMContentLoaded', function() {
        fcwLoadRecentChats();
        recentPollInterval = setInterval(fcwLoadRecentChats, 15000);
    });
})();
</script>

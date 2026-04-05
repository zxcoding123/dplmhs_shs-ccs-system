<?php
require 'processes/server/conn.php';

$userId = $_SESSION['user_id'] ?? null;

$queryAll = "SELECT * FROM staff_notifications WHERE user_id = :user_id  ORDER BY id DESC";
$stmtAll = $pdo->prepare($queryAll);
$stmtAll->execute([':user_id' => $userId]);
$notifications = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($notifications);


if ($userId) {
	// Fetch unread notifications for the current user, limited to 4
	$queryUnread = "SELECT * FROM staff_notifications WHERE status = 'unread' AND user_id = :user_id ORDER BY id DESC LIMIT 4";
	$stmtUnread = $pdo->prepare($queryUnread);
	$stmtUnread->execute([':user_id' => $userId]);
	$notificationsUnread = $stmtUnread->fetchAll(PDO::FETCH_ASSOC);
	$notificationCount = count($notificationsUnread); // Total number of unread notifications
} else {
	// Handle case when `user_id` is not set
	$notificationsUnread = [];
	$notificationCount = 0;
}

$query = "SELECT * FROM messages WHERE status = 'unread' AND receiver_id = :user_id ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $userId); // Fix here
$stmt->execute();
$messagesUnread = $stmt->fetchAll(PDO::FETCH_ASSOC);
$messagesCount = count($messagesUnread);
?>



<ul class="navbar-nav navbar-align">
	<li class="nav-item dropdown">
		<a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
			<div class="position-relative">
				<i class="align-middle" data-feather="bell"></i>

				<?php if ($notificationCount > 0) { ?>
					<span class="indicator"><?php echo $notificationCount; ?></span>
				<?php } ?>
			</div>
		</a>
		<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
			<div class="dropdown-menu-header">
				<?php if ($notificationCount > 0) { ?>
					<?php echo $notificationCount; ?> New Notifications
					<div class="dropdown-item text-center">
						<a href="processes/teachers/notifications/read_all.php"><button id="readAll"
								class="btn btn-link">Read All</button></a>
						<a href="processes/teachers/notifications/delete_all.php">
							<button id="deleteAll" class="btn btn-link text-danger">Delete All</button>
						</a>
					</div>
				<?php } else { ?>
					<?php echo "No new notifications" ?>
				<?php } ?>
			</div>
			<div class="list-group">
				<?php foreach ($notificationsUnread as $notification): ?>
					<div class="list-group-item">
						<div class="row g-0 align-items-center">
							<div class="col-2">
								<h1 class="bi bi-info-circle-fill"></h1>
							</div>

							<div class="col-8">
								<a href="<?php echo $notification['link'] ?>">
									<div class="text-dark"><?php echo htmlspecialchars($notification['title']); ?></div>
									<div class="text-muted small mt-1">
										<?php echo htmlspecialchars($notification['description']); ?>
									</div>
									<div class="text-muted small mt-1">
										<?php echo htmlspecialchars($notification['date']); ?>
									</div>
								</a>
							</div>

							<div class="col-2 text-end">
								<form action="processes/teachers/notifications/read.php" method="POST" class="d-inline">
									<input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
									<button type="submit" class="btn btn-link p-0">Read</button>
								</form>
								<form action="processes/teachers/notifications/delete.php" method="POST" class="d-inline">
									<input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
									<button type="submit" class="btn btn-link text-danger p-0">Delete</button>
								</form>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ($notifCount > 0) { ?>
				<div class="dropdown-menu-footer">
					<a href="#" class="text-muted" data-bs-toggle="modal" data-bs-target="#notificationsModal">Show all
						notifications</a>
				</div>
			<?php } ?>
		</div>
	</li>


	<li class="nav-item dropdown">
		<a class="nav-icon dropdown-toggle" href="#" data-bs-toggle="modal" data-bs-target="#messagesModal">
			<div class="position-relative">
				<i class="align-middle" data-feather="message-square"></i>
				<?php
				if ($messagesCount > 0) { ?>
					<span class="indicator"><?php echo $messagesCount; ?></span>
				<?php } ?>
			</div>
		</a>
	</li>


	<div class="modal fade" id="notificationsModal" tabindex="-1" aria-labelledby="notificationsModalLabel"
		aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="notificationsModalLabel">All Notifications</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="list-group">
						<?php foreach ($notifications as $notification): ?>
							<div class="list-group-item list-group-item-action">
							<a href="<?php echo $notification['link'] ?>" style="text-decoration: none">
									<div class="row d-flex justify-content-center align-items-center">
										<div class="col text-center ">
											<h1 class="bi bi-info-circle-fill"></h1>
										</div>
										<div class="col-8">
											<div class="text-dark"><?php echo htmlspecialchars($notification['title']); ?>
											</div>
											<div class="text-muted small mt-1">
												<?php echo htmlspecialchars($notification['description']); ?>
											</div>
											<div class="text-muted small mt-1">
												<?php echo htmlspecialchars($notification['date']); ?>
											</div>
										</div>
										<div class="col-2 text-end">
											<?php if ($notification['status'] == 'unread') { ?>
												<form action="processes/teachers/notifications/read.php" method="POST"
													class="d-inline">
									  
													<input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
													<button type="submit" class="btn btn-link p-0">Read</button>
												</form>
											<?php } ?>
											<form action=""processes/teachers/notifications/delete.php" method="POST"
												class="d-inline">
												<input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
												<button type="submit" class="btn btn-link p-0">Delete</button>
											</form>
										</div>
									</div>
								</a>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

					<a href="processes/teachers/notifications/delete_all.php"><button type="button"
							class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">Delete
							All</button></a>
					<a href="processes/teachers/notifications/read_all.php"><button type="button" class="btn btn-info"
							data-bs-toggle="modal" data-bs-target="#readAllModal">Read
							All</button></a>

				</div>
			</div>
		</div>
	</div>

	<script>
		// Function to fetch unread messages count and update the UI
		async function updateUnreadMessagesCount() {
			try {
				const response = await fetch('messaging.php?action=getCount'); // The file that returns the count
				const data = await response.json();
				const unreadCount = data.unreadCount;

				// Update the message count indicator
				const indicator = document.querySelector('.indicator');
				if (unreadCount > 0) {
					indicator.textContent = unreadCount;
					indicator.style.display = 'inline'; // Make sure indicator is visible
				} else {
					indicator.style.display = 'none'; // Hide the indicator if no unread messages
				}
			} catch (error) {
				console.error('Error fetching unread messages count:', error);
			}
		}

		// Periodically update unread message count every 3 seconds
		setInterval(updateUnreadMessagesCount, 500);

	</script>

    <style>
        .linkism{
            border: 1px solid grey;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            margin-right: 5px;
            color: black;
        }
        .active{
        color: grey !important;
        }
      
    </style>
    
<div class="modal fade" id="messagesModal" tabindex="-1" aria-labelledby="messagesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Messages</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body" id="modalBody">
                <!-- Tabs for switching between private and group chats -->
                <ul class="nav nav-tabs mb-3" id="chatTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="linkism active " id="private-tab" data-bs-toggle="tab" 
                                data-bs-target="#private-chat" type="button" role="tab">
                            Private Chats
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class=" linkism" id="group-tab" data-bs-toggle="tab" 
                                data-bs-target="#group-chat" type="button" role="tab">
                            Group Chats
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Private Chat Tab (existing content) -->
                    <div class="tab-pane fade show active" id="private-chat" role="tabpanel">
                        <!-- Search Bar -->
                        <div id="searchSection">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="userSearch" placeholder="Search users..."
                                    aria-label="Search users">
                                <button class="btn btn-outline-secondary" type="button" id="searchButton">Search</button>
                            </div>

                            <!-- Recent Conversations -->
                            <div id="recentConversations">
                                <h6>Recent Conversations</h6>
                                <div class="list-group" id="recentConvoList">
                                    <!-- Dynamic content will be here -->
                                </div>
                            </div>

                            <!-- Search Results -->
                            <div id="searchResults" style="display: none;">
                                <h6>Search Results</h6>
                                <div class="list-group" id="searchResultList">
                                    <p class="text-muted text-center">Type to search for users.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Conversation Section (Hidden Initially) -->
                        <div id="conversationSection" style="display: none;">
                            <div id="conversationContent" class="flex-grow-1 overflow-auto p-3"
                                style="background-color: #f8f9fa; border-radius: 0.25rem;">
                                <p class="text-muted text-center">No messages yet.</p>
                            </div>
                            <div class="input-group mt-3 sticky-bottom" style="border-radius: 20px;">
                                <input type="text" id="messageInput" class="form-control" placeholder="Type a message"
                                    aria-label="Type a message">
                                <button class="btn btn-primary" type="button" onclick="sendMessage()">Send</button>
                            </div>
                        </div>
                    </div>

                    <!-- Group Chat Tab -->
                    <div class="tab-pane fade" id="group-chat" role="tabpanel">
                        <!-- Create Group Button -->
                        <button class="btn btn-primary mb-3" id="createGroupBtn" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                            <i class="bi bi-plus-circle"></i> Create New Group
                        </button>
                        
                        <!-- Group Search -->
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="groupSearch" placeholder="Search groups...">
                            <button class="btn btn-outline-secondary" type="button" id="searchGroupBtn">Search</button>
                        </div>
                        
                        <!-- Group List -->
                        <div id="groupList">
                            <h6>Your Groups</h6>
                            <div class="list-group" id="userGroupsList">
                                <p class="text-muted text-center">Loading groups...</p>
                            </div>
                        </div>
                        
                        <!-- Group Conversation Section (hidden initially) -->
                        <div id="groupConversationSection" style="display: none;">
                            <div id="groupConversationContent" class="flex-grow-1 overflow-auto p-3"
                                style="background-color: #f8f9fa; border-radius: 0.25rem; max-height: 400px;">
                                <p class="text-muted text-center">No group messages yet.</p>
                            </div>
                            <div class="input-group mt-3">
                                <input type="text" id="groupMessageInput" class="form-control" 
                                       placeholder="Type a message">
                                <button class="btn btn-primary" type="button" onclick="sendGroupMessage()">Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="backButton" style="display: none;"
                    onclick="goBackToMessages()">Back to Chats</button>
                <button type="button" class="btn btn-secondary" id="backToGroupsBtn" style="display: none;"
                    onclick="goBackToGroups()">Back to Groups</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Group</h5>
                <button type="button" class="btn-close" data-bs-toggle="modal" data-bs-target="#messagesModal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createGroupForm">
                    <div class="mb-3">
                        <label for="groupName" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="groupName" required>
                    </div>
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="groupDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Add Members</label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="memberSearch" placeholder="Search users...">
                            <button class="btn btn-outline-secondary" type="button" id="searchMemberBtn">Search</button>
                        </div>
                        <div id="memberSearchResults" class="list-group mb-3" style="max-height: 200px; overflow-y: auto;"></div>
                        <div id="selectedMembers" class="mb-3"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createGroup()">Create Group</button>
            </div>
        </div>
    </div>
</div>

<!-- Group Info Modal -->
<div class="modal fade" id="groupInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupInfoTitle">Group Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="groupInfoContent">
                <!-- Group info will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="leaveGroup()">Leave Group</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Message bubbles */
    .message-bubble {
        max-width: 70%;
        word-wrap: break-word;
    }
    
    /* Group message styling */
    .group-message {
        border-left: 3px solid #0d6efd;
        padding-left: 10px;
    }
    
    /* Selected members chips */
    .member-chip {
        display: inline-flex;
        align-items: center;
        background-color: #0d6efd;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        margin-right: 5px;
        margin-bottom: 5px;
    }
    
    /* Tab content styling */
    .tab-content {
        padding: 15px 0;
    }
    
    /* Conversation content area */
    #conversationContent, #groupConversationContent {
        min-height: 300px;
        max-height: 400px;
        overflow-y: auto;
    }
</style>

<style>
    .message-bubble {
        word-wrap: break-word;
        max-width: 80%;
    }

    #conversationContent {
        height: 400px;
        overflow-y: auto;
    }

    .sending-bubble {
        background-color: #e9ecef;
        color: #6c757d;
        font-style: italic;
    }
</style>



<script>
// Initial variables
const name = '<?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'null'; ?>';
const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
const userType = '<?php echo isset($_SESSION['user_type']) ? $_SESSION['user_type'] : ''; ?>';
let receiverNameGlobal = "Unknown Receiver";
let lastMessageId = 0;
let pollInterval;

// Enhanced user search functionality
function searchUsers(query) {
    if (query.length < 2) {
        document.getElementById("searchResults").style.display = "none";
        return;
    }

    fetch(`search_users.php?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(users => {
            let resultList = document.getElementById("searchResultList");
            resultList.innerHTML = "";

            if (!users || users.length === 0) {
                resultList.innerHTML = "<p class='text-muted text-center'>No users found.</p>";
            } else {
                users.forEach(user => {
                    let item = document.createElement("button");
                    item.className = "list-group-item list-group-item-action";
                    item.innerText = user.fullName || "Unknown User";
                    item.onclick = () => openChat(user.id, user.user_type, user.fullName || "Unknown User");
                    resultList.appendChild(item);
                });
            }
            document.getElementById("searchResults").style.display = "block";
        })
        .catch(error => {
            console.error("Search error:", error);
            let resultList = document.getElementById("searchResultList");
            resultList.innerHTML = "<p class='text-muted text-center'>Error searching users.</p>";
            document.getElementById("searchResults").style.display = "block";
        });
}

// Attach event listeners for search
document.getElementById("userSearch").addEventListener("input", function() {
    searchUsers(this.value.trim());
});

document.getElementById("searchButton").addEventListener("click", function() {
    searchUsers(document.getElementById("userSearch").value.trim());
});

function openChat(receiverId, receiverType, receiverName) {
    document.getElementById("modalTitle").innerText = "Chatting with " + receiverName;
    document.getElementById("conversationSection").style.display = "block";
    document.getElementById("searchSection").style.display = "none";
    document.getElementById("backButton").style.display = "block";

    window.currentChat = {
        receiverId,
        receiverType,
        receiverName
    };
    receiverNameGlobal = receiverName;

    lastMessageId = 0;
    loadChat();
    startPolling();
}

function goBackToMessages() {
    document.getElementById("modalTitle").innerText = "Messages";
    document.getElementById("conversationSection").style.display = "none";
    document.getElementById("searchSection").style.display = "block";
    document.getElementById("backButton").style.display = "none";
    document.getElementById("conversationContent").innerHTML = "<p class='text-muted text-center'>No messages yet.</p>";
    window.currentChat = null;
    stopPolling();
    loadRecentChats();
}

function sendMessage() {
    let messageInput = document.getElementById("messageInput");
    let message = messageInput.value.trim();

    if (!message || !window.currentChat) return;

    let tempId = 'temp-' + Date.now();
    appendMessage("You", message, true, new Date().toISOString(), tempId, 'sending');

    let formData = new FormData();
    formData.append("receiver_id", window.currentChat.receiverId);
    formData.append("receiver_type", window.currentChat.receiverType);
    formData.append("message", message);

    fetch("send_message.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            messageInput.value = "";
            let tempElement = document.querySelector(`[data-message-id="${tempId}"]`);
            if (tempElement) tempElement.remove();
            loadChat(); // Refresh chat after sending
            loadRecentChats();
        } else {
            let tempElement = document.querySelector(`[data-message-id="${tempId}"]`);
            if (tempElement) {
                tempElement.querySelector('.message-bubble').innerText = "Failed to send";
                tempElement.querySelector('.message-bubble').classList.replace('sending-bubble', 'bg-danger');
            }
            console.error("Message sending failed:", result.error);
        }
    })
    .catch(error => {
        let tempElement = document.querySelector(`[data-message-id="${tempId}"]`);
        if (tempElement) {
            tempElement.querySelector('.message-bubble').innerText = "Network error";
            tempElement.querySelector('.message-bubble').classList.replace('sending-bubble', 'bg-danger');
        }
        console.error("Error:", error);
    });
}

function loadChat() {
    let chat = window.currentChat;
    if (!chat) return;

    fetch(`load_chat.php?receiver_id=${chat.receiverId}&receiver_type=${chat.receiverType}&t=${Date.now()}`)
        .then(response => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then(data => {
            let chatBox = document.getElementById("conversationContent");
            chatBox.innerHTML = "";

            if (data.error) {
                chatBox.innerHTML = `<p class='text-muted text-center'>Error: ${data.error}</p>`;
                return;
            }

            if (!Array.isArray(data) || data.length === 0) {
                chatBox.innerHTML = "<p class='text-muted text-center'>No messages yet.</p>";
                return;
            }

            data.forEach(msg => {
                let isMe = msg.sender_id == userId;
                appendMessage(
                    isMe ? "You" : msg.sender_name,
                    msg.message,
                    isMe,
                    msg.timestamp,
                    msg.id,
                    msg.status
                );
                if (msg.id > lastMessageId) lastMessageId = msg.id;
            });
        })
        .catch(error => {
            console.error("Error loading chat:", error);
            document.getElementById("conversationContent").innerHTML = 
                "<p class='text-muted text-center'>Error loading messages.</p>";
        });
}

function pollMessages() {
    let chat = window.currentChat;
    if (!chat) return;

    fetch(`load_chat.php?receiver_id=${chat.receiverId}&receiver_type=${chat.receiverType}&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (!data.error && Array.isArray(data)) {
                let currentMessages = Array.from(document.querySelectorAll('[data-message-id]'))
                    .map(el => el.dataset.messageId)
                    .filter(id => !id.startsWith('temp-')); // Exclude temp IDs

                // Check for new messages
                let newMessages = data.filter(msg => !currentMessages.includes(msg.id.toString()));
                // Check for deleted messages
                let deletedMessages = currentMessages.filter(id => !data.some(msg => msg.id.toString() === id));

                // Append new messages
                if (newMessages.length > 0) {
                    newMessages.forEach(msg => {
                        let isMe = msg.sender_id == userId;
                        appendMessage(
                            isMe ? "You" : msg.sender_name,
                            msg.message,
                            isMe,
                            msg.timestamp,
                            msg.id,
                            msg.status
                        );
                        if (msg.id > lastMessageId) lastMessageId = msg.id;
                    });
                    loadRecentChats();
                }

                // Remove deleted messages
                if (deletedMessages.length > 0) {
                    deletedMessages.forEach(id => {
                        let element = document.querySelector(`[data-message-id="${id}"]`);
                        if (element) {
                            element.remove();
                            console.log(`Deleted message ${id} removed from DOM`);
                        }
                    });
                    loadRecentChats();
                }
            }
        })
        .catch(error => console.error("Polling error:", error));
}

function startPolling() {
    stopPolling();
    pollInterval = setInterval(pollMessages, 2000); // Poll every 2 seconds
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

function appendMessage(sender, message, isMe, timestamp, id, status) {
    let chatBox = document.getElementById("conversationContent");

    // Skip if message already exists (prevents duplicates)
    if (document.querySelector(`[data-message-id="${id}"]`)) return;

    let messageContainer = document.createElement("div");
    messageContainer.className = `d-flex flex-column ${isMe ? "align-items-end" : "align-items-start"} mb-2`;
    messageContainer.dataset.messageId = id;

// Assume timestamp is already in PHT (UTC+8) from the server
    let date = new Date(timestamp); // Treat as UTC to avoid local offset
    let timeString = date.toLocaleTimeString('en-PH', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila' // Display in PHT
    });

    let senderName = document.createElement("small");
    senderName.className = "text-muted font-weight-bold";
    senderName.innerText = sender;
    messageContainer.appendChild(senderName);

    let bubbleContainer = document.createElement("div");
    bubbleContainer.className = "d-flex align-items-center";

    if (isMe && status !== 'sending') {
        let trashIcon = document.createElement("i");
        trashIcon.className = "bi bi-trash me-2";
        trashIcon.style.cursor = "pointer";
        trashIcon.style.color = "red";
        trashIcon.onclick = () => deleteMessage(id);
        bubbleContainer.appendChild(trashIcon);
    }

    let messageBubble = document.createElement("div");
    messageBubble.className = `p-2 rounded text-white ${
        status === 'sending' ? 'sending-bubble' : (isMe ? 'bg-primary' : 'bg-secondary')
    }`;
    messageBubble.style.maxWidth = "100%";
    messageBubble.style.overflowWrap = "break-word";
    messageBubble.innerText = message;
    bubbleContainer.appendChild(messageBubble);

    if (isMe && status && status !== 'sending') {
        let statusIcon = document.createElement("small");
        statusIcon.className = "text-muted ms-2";
        statusIcon.innerText = status === 'read' ? '✓✓' : '✓';
        bubbleContainer.appendChild(statusIcon);
    }

    messageContainer.appendChild(bubbleContainer);

    let timeText = document.createElement("small");
    timeText.className = "text-muted mt-1";
    timeText.innerText = timeString;
    messageContainer.appendChild(timeText);

    chatBox.appendChild(messageContainer);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function deleteMessage(id) {
    if (confirm("Are you sure you want to delete this message?")) {
        fetch("delete_message.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `id=${id}`
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(result => {
            console.log("Delete response:", result); // Debug log
            if (result.success) {
                let element = document.querySelector(`[data-message-id="${id}"]`);
                if (element) {
                    element.remove();
                    console.log(`Message ${id} removed from DOM`);
                }
                loadChat(); // Refresh chat immediately
                loadRecentChats(); // Update recent chats
            } else {
                console.error("Delete failed:", result.error);
                alert("Failed to delete message: " + (result.error || "Unknown error"));
            }
        })
        .catch(error => {
            console.error("Delete error:", error);
            alert("Network error while deleting message: " + error.message);
        });
    }
}

function loadRecentChats() {
    fetch("fetch_recent_chats.php?t=" + Date.now())
        .then(response => response.json())
        .then(chats => {
            let recentList = document.getElementById("recentConvoList");
            recentList.innerHTML = "";

            if (!chats || chats.length === 0) {
                recentList.innerHTML = "<p class='text-muted text-center'>No recent conversations.</p>";
                return;
            }

            chats.forEach(chat => {
                let item = document.createElement("button");
                item.className = "list-group-item list-group-item-action";

                let chatContent = document.createElement("div");
                chatContent.className = "d-flex flex-column";

                let nameContainer = document.createElement("div");
                nameContainer.className = "d-flex justify-content-between align-items-center";

                let nameText = document.createElement("strong");
                nameText.innerText = chat.full_name || "Unknown User";
                nameContainer.appendChild(nameText);

                if (chat.is_new) {
                    let badge = document.createElement("span");
                    badge.className = "badge bg-danger ms-2";
                    badge.innerText = "New";
                    nameContainer.appendChild(badge);
                }

                let messageText = document.createElement("span");
                messageText.className = "text-muted";
                messageText.innerText = chat.last_message || "No messages yet";

                let timeText = document.createElement("small");
                timeText.className = "text-end text-muted";
                timeText.innerText = chat.time || "";

                chatContent.appendChild(nameContainer);
                chatContent.appendChild(messageText);
                chatContent.appendChild(timeText);

                item.appendChild(chatContent);
                item.onclick = () => openChat(chat.chat_partner, chat.chat_partner_type, chat.full_name || "Unknown User");
                recentList.appendChild(item);
            });
        })
        .catch(error => {
            console.error("Error loading recent chats:", error);
            document.getElementById("recentConvoList").innerHTML = 
                "<p class='text-muted text-center'>Error loading conversations.</p>";
        });
}

document.getElementById("messagesModal").addEventListener("hidden.bs.modal", stopPolling);
document.getElementById("messagesModal").addEventListener("shown.bs.modal", loadRecentChats);
</script>

<script>
// Group Chat Variables

const currentUserId = '<?php echo $_SESSION["user_id"] ?? ""; ?>';
const currentUserType = '<?php echo $_SESSION["user_type"] ?? ""; ?>';

let currentGroupChat = null;
let groupPollInterval = null;



// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize event listeners
    document.getElementById('createGroupBtn')?.addEventListener('click', showCreateGroupModal);
    document.getElementById('searchMemberBtn')?.addEventListener('click', searchMembersHandler);
    document.getElementById('memberSearch')?.addEventListener('input', searchMembersHandler);
    
    // Tab switching
    document.getElementById('group-tab')?.addEventListener('shown.bs.tab', loadUserGroups);
});

// When opening a group chat
function openGroupChat(groupId, groupName) {
    // Hide group list and show conversation
    document.getElementById('groupList').style.display = 'none';
    document.getElementById('groupConversationSection').style.display = 'block';
    document.getElementById('backToGroupsBtn').style.display = 'block';
    
    // Update modal title
    document.getElementById('modalTitle').innerText = `Group: ${groupName}`;

    // Set current group chat
    currentGroupChat = {
        id: groupId,
        name: groupName,
        userType: getCurrentUserType() // Get from session
    };

    // Load messages and start polling
    loadGroupChat();
    startGroupPolling();
}

// Get current user type from PHP session
function getCurrentUserType() {
    // This value should be set by PHP when the page is rendered
    return typeof currentUserType !== 'undefined' ? currentUserType : null;
}

// Load messages for the current group
function loadGroupChat() {
    if (!currentGroupChat?.id) return;

    fetch(`fetch_group_messages.php?group_id=${currentGroupChat.id}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(messages => {
            const chatBox = document.getElementById('groupConversationContent');
            chatBox.innerHTML = '';

            if (!messages || messages.length === 0) {
                chatBox.innerHTML = '<p class="text-muted text-center">No messages yet in this group.</p>';
                return;
            }

            messages.forEach(msg => {
                appendGroupMessage(
                    msg.sender_name,
                    msg.message,
                    msg.sender_id == currentUserId,
                    msg.created_at,
                    msg.id
                );
            });
            
            // Scroll to bottom
            chatBox.scrollTop = chatBox.scrollHeight;
        })
        .catch(error => {
            console.error('Error loading group chat:', error);
            document.getElementById('groupConversationContent').innerHTML = 
                '<p class="text-muted text-center">Error loading group messages.</p>';
        });
}

// Append a message to the group chat UI
function appendGroupMessage(sender, message, isMe, timestamp, id) {
    const chatBox = document.getElementById('groupConversationContent');
    
    // Skip if message already exists
    if (document.querySelector(`[data-group-message-id="${id}"]`)) return;

    const messageContainer = document.createElement('div');
    messageContainer.className = `d-flex flex-column ${isMe ? 'align-items-end' : 'align-items-start'} mb-3`;
    messageContainer.dataset.groupMessageId = id;

    // Format timestamp (assuming PHP timezone is already set)
    const date = new Date(timestamp);
    const timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    // Sender name (only show for others' messages)
    if (!isMe) {
        const senderName = document.createElement('small');
        senderName.className = 'text-muted fw-bold mb-1';
        senderName.innerText = sender;
        messageContainer.appendChild(senderName);
    }

    // Message bubble container
    const bubbleContainer = document.createElement('div');
    bubbleContainer.className = 'd-flex align-items-center';

    // Delete button for user's own messages
    if (isMe) {
             const senderName = document.createElement('small');
        senderName.className = 'text-muted  mb-1';
        senderName.innerText = 'You';
        messageContainer.appendChild(senderName);
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-sm btn-link text-danger p-0 me-1';
        deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
        deleteBtn.onclick = () => deleteGroupMessage(id);
        bubbleContainer.appendChild(deleteBtn);
    }

    // Message bubble
    const messageBubble = document.createElement('div');
    messageBubble.className = `p-2 rounded ${isMe ? 'bg-primary text-white' : 'bg-secondary text-white'}`;
    messageBubble.style.maxWidth = '100%';
    messageBubble.style.wordWrap = 'break-word';
    messageBubble.innerText = message;
    bubbleContainer.appendChild(messageBubble);

    messageContainer.appendChild(bubbleContainer);

    // Timestamp
    const timeText = document.createElement('small');
    timeText.className = 'text-muted mt-1';
    timeText.innerText = timeString;
    messageContainer.appendChild(timeText);

    chatBox.appendChild(messageContainer);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Send a message to the current group
function sendGroupMessage() {
    const messageInput = document.getElementById('groupMessageInput');
    const message = messageInput.value.trim();

    if (!message || !currentGroupChat?.id) return;

    // Create temporary message (optimistic UI update)
    const tempId = 'temp-' + Date.now();
    appendGroupMessage(
        "You", 
        message, 
        true, 
        new Date().toISOString(), 
        tempId
    );
    messageInput.value = '';

    // Prepare form data
    const formData = new FormData();
    formData.append('group_id', currentGroupChat.id);
    formData.append('message', message);

    // Send to server
    fetch('send_group_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(result => {
        if (result.success) {
            // Remove temporary message
            const tempElement = document.querySelector(`[data-group-message-id="${tempId}"]`);
            if (tempElement) tempElement.remove();
            
            // Reload messages to get the actual message from server
            loadGroupChat();
        } else {
            throw new Error(result.error || 'Failed to send message');
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        // Update temporary message to show error
        const tempElement = document.querySelector(`[data-group-message-id="${tempId}"]`);
        if (tempElement) {
            const bubble = tempElement.querySelector('.rounded');
            if (bubble) {
                bubble.classList.remove('bg-primary');
                bubble.classList.add('bg-danger');
                bubble.innerHTML = 'Failed to send: ' + error.message;
            }
        }
    });
}

// Delete a group message
function deleteGroupMessage(messageId) {
    if (!confirm('Are you sure you want to delete this message?')) return;

    fetch('delete_group_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `message_id=${messageId}`
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(result => {
        if (result.success) {
            const messageElement = document.querySelector(`[data-group-message-id="${messageId}"]`);
            if (messageElement) messageElement.remove();
        } else {
            throw new Error(result.error || 'Failed to delete message');
        }
    })
    .catch(error => {
        console.error('Error deleting message:', error);
        alert('Error deleting message: ' + error.message);
    });
}

// Poll for new group messages
function pollGroupMessages() {
    if (!currentGroupChat?.id) return;

    fetch(`fetch_group_messages.php?group_id=${currentGroupChat.id}&last_poll=${new Date().toISOString()}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(messages => {
            if (messages && messages.length > 0) {
                messages.forEach(msg => {
                    appendGroupMessage(
                        msg.sender_name,
                        msg.message,
                        msg.sender_id == currentGroupChat.userId,
                        msg.created_at,
                        msg.id
                    );
                });
            }
        })
        .catch(error => console.error('Group poll error:', error));
}

// Start polling for new messages
function startGroupPolling() {
    stopGroupPolling();
    groupPollInterval = setInterval(pollGroupMessages, 2000); // Poll every 2 seconds
}

// Stop polling
function stopGroupPolling() {
    if (groupPollInterval) {
        clearInterval(groupPollInterval);
        groupPollInterval = null;
    }
}

// Return to group list view
function goBackToGroups() {
    document.getElementById('groupList').style.display = 'block';
    document.getElementById('groupConversationSection').style.display = 'none';
    document.getElementById('backToGroupsBtn').style.display = 'none';
    document.getElementById('modalTitle').innerText = 'Messages';
    
    // Clear current chat and stop polling
    currentGroupChat = null;
    stopGroupPolling();
    
    // Reload groups
    loadUserGroups();
}

// Load user's groups
function loadUserGroups() {
    fetch('fetch_user_groups.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(groups => {
            const groupList = document.getElementById('userGroupsList');
            groupList.innerHTML = '';

            if (!groups || groups.length === 0) {
                groupList.innerHTML = '<p class="text-muted text-center">You are not in any groups yet.</p>';
                return;
            }

            groups.forEach(group => {
                const groupItem = document.createElement('button');
                groupItem.className = 'list-group-item list-group-item-action text-start';
                groupItem.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${group.name}</strong>
                        <small class="text-muted">${group.member_count} members</small>
                    </div>
                    <small class="text-muted">${group.last_message || 'No messages yet'}</small>
                `;
                groupItem.onclick = () => openGroupChat(group.id, group.name);
                groupList.appendChild(groupItem);
            });
        })
        .catch(error => {
            console.error('Error loading groups:', error);
            document.getElementById('userGroupsList').innerHTML = 
                '<p class="text-muted text-center">Error loading groups.</p>';
        });
}

/********************
 * GROUP MANAGEMENT *
 ********************/

let selectedMembers = [];

// Show create group modal
function showCreateGroupModal() {
    selectedMembers = []; // Reset selected members
    document.getElementById('createGroupForm').reset();
    document.getElementById('memberSearchResults').innerHTML = '';
    document.getElementById('selectedMembers').innerHTML = '';
    
    const modal = new bootstrap.Modal(document.getElementById('createGroupModal'));
    modal.show();
}

// Handle member search
function searchMembersHandler() {
    const query = document.getElementById('memberSearch').value.trim();
    searchMembers(query);
}

// Search for members to add to group
function searchMembers(query) {
    if (query.length < 2) {
        document.getElementById('memberSearchResults').innerHTML = '';
        return;
    }

    fetch(`search_members.php?query=${encodeURIComponent(query)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(members => {
            const resultsContainer = document.getElementById('memberSearchResults');
            resultsContainer.innerHTML = '';

            if (!members || members.length === 0) {
                resultsContainer.innerHTML = '<p class="text-muted text-center">No users found</p>';
                return;
            }

            members.forEach(member => {
                // Skip if already selected
                if (selectedMembers.some(m => m.id == member.id && m.type == member.type)) return;
                
                const item = document.createElement('button');
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `
                    <div class="form-check d-flex align-items-center">
                        <input class="form-check-input me-2" type="checkbox" 
                               id="member-${member.type}-${member.id}" 
                               value="${member.id}|${member.type}">
                        <label class="form-check-label flex-grow-1" for="member-${member.type}-${member.id}">
                            ${member.fullName} <small class="text-muted">(${member.type})</small>
                        </label>
                    </div>
                `;
                
                const checkbox = item.querySelector('input');
                checkbox.addEventListener('change', function() {
                    toggleMemberSelection(this);
                });
                
                resultsContainer.appendChild(item);
            });
        })
        .catch(error => {
            console.error('Search error:', error);
            document.getElementById('memberSearchResults').innerHTML = 
                '<p class="text-muted text-center">Error searching users</p>';
        });
}

// Toggle member selection
function toggleMemberSelection(checkbox) {
    const value = checkbox.value;
    const [id, type] = value.split('|');
    
    if (checkbox.checked) {
        if (!selectedMembers.some(m => m.id == id && m.type == type)) {
            selectedMembers.push({ id, type });
        }
    } else {
        selectedMembers = selectedMembers.filter(m => !(m.id == id && m.type == type));
    }
    
    updateSelectedMembersDisplay();
}

// Update the display of selected members
function updateSelectedMembersDisplay() {
    const container = document.getElementById('selectedMembers');
    container.innerHTML = '';
    
    selectedMembers.forEach(member => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary me-1 mb-1 d-inline-flex align-items-center';
        badge.innerHTML = `
            ${getMemberTypeIcon(member.type)} 
            ${member.id} 
            <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                    aria-label="Remove" onclick="removeMember('${member.id}', '${member.type}')"></button>
        `;
        container.appendChild(badge);
    });
}

// Remove a member from selection
function removeMember(id, type) {
    selectedMembers = selectedMembers.filter(m => !(m.id == id && m.type == type));
    updateSelectedMembersDisplay();
    
    // Uncheck the checkbox in search results if visible
    const checkbox = document.querySelector(`#member-${type}-${id}`);
    if (checkbox) checkbox.checked = false;
}

// Get icon for member type
function getMemberTypeIcon(type) {
    switch(type) {
        case 'admin': return '<i class="bi bi-person-gear me-1"></i>';
        case 'staff': return '<i class="bi bi-person-badge me-1"></i>';
        case 'student': return '<i class="bi bi-person-vcard me-1"></i>';
        default: return '<i class="bi bi-person me-1"></i>';
    }
}

// Create a new group
function createGroup() {
    const name = document.getElementById('groupName').value.trim();
    const description = document.getElementById('groupDescription').value.trim();
    
    if (!name) {
        alert('Group name is required');
        return;
    }
    
    if (selectedMembers.length === 0) {
        alert('Please add at least one member');
        return;
    }
    
    // Add current user to members if not already added
    const currentUser = { id: getCurrentUserId(), type: getCurrentUserType() };
    if (!selectedMembers.some(m => m.id == currentUser.id && m.type == currentUser.type)) {
        selectedMembers.push(currentUser);
    }
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('description', description);
    formData.append('members', JSON.stringify(selectedMembers));
    
    fetch('create_group.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(result => {
        if (result.success) {
            alert('Group created successfully');
            bootstrap.Modal.getInstance(document.getElementById('createGroupModal')).hide();
            loadUserGroups();
        } else {
            throw new Error(result.error || 'Failed to create group');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating group: ' + error.message);
    });
}

// Get current user ID from PHP session
function getCurrentUserId() {
    // This value should be set by PHP when the page is rendered
    return typeof currentUserId !== 'undefined' ? currentUserId : null;
}

// Stop polling when modal is hidden
document.getElementById('messagesModal')?.addEventListener('hidden.bs.modal', function() {
    stopGroupPolling();
});

// Initialize when group tab is shown
document.getElementById('group-tab')?.addEventListener('shown.bs.tab', function() {
    loadUserGroups();
});

// Search groups on every keystroke with debounce
let searchTimeout;
document.getElementById('groupSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const query = this.value.trim();
        if (query.length >= 2) {  // Only search if at least 2 characters
            searchGroups(query);
        } else {
            // Clear results or show default state if search is too short
          loadUserGroups();
        }
    }, 300); // 300ms debounce delay
});

// Manual search button click
document.getElementById('searchGroupBtn').addEventListener('click', function() {
    const query = document.getElementById('groupSearch').value.trim();
    searchGroups(query);
});

// Enhanced search function with loading state
function searchGroups(query) {
    const groupList = document.getElementById('userGroupsList');
    
    // Show loading state
    groupList.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
    
    fetch(`search_groups.php?query=${encodeURIComponent(query)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(groups => {
            groupList.innerHTML = '';
            
            if (groups.error) {
                groupList.innerHTML = `<div class="alert alert-danger">${groups.error}</div>`;
                return;
            }
            
            if (groups.length === 0) {
                groupList.innerHTML = '<p class="text-muted">No groups found matching "' + query + '"</p>';
                return;
            }
            
            groups.forEach(group => {
                const item = document.createElement('button');
                item.className = 'list-group-item list-group-item-action text-start';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${group.name}</strong>
                        <span class="badge bg-primary rounded-pill">${group.member_count}</span>
                    </div>
                    ${group.description ? `<small class="text-muted d-block mt-1">${group.description}</small>` : ''}
                `;
                item.onclick = () => openGroupChat(group.id, group.name);
                groupList.appendChild(item);
            });
        })
        .catch(error => {
            groupList.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}
</script>

	<li class="nav-item dropdown">
		<a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
			<i class="align-middle" data-feather="settings"></i>
		</a>

		<a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
			<span class="text-light">Staff</span>
		</a>
		<div class="dropdown-menu dropdown-menu-end">


			<a class="dropdown-item" href="processes/teachers/account/logout.php">
				Log out
			</a>
		</div>



	</li>
</ul>

<!-- Add this to your HTML -->
<div class="modal fade" id="createGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createGroupForm">
                    <div class="mb-3">
                        <label for="groupName" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="groupName" required>
                    </div>
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="groupDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Add Members</label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="memberSearch" placeholder="Search users...">
                            <button class="btn btn-outline-secondary" type="button" id="searchMemberBtn">Search</button>
                        </div>
                        <div id="memberSearchResults" class="list-group mb-3" style="max-height: 200px; overflow-y: auto;"></div>
                        <div id="selectedMembers" class="mb-3"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createGroup()">Create Group</button>
            </div>
        </div>
    </div>
</div>
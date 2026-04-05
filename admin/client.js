const ws = new WebSocket("ws://localhost:8080");

ws.onopen = function () {
    console.log("Connected to WebSocket server");
};

// Handle incoming messages
ws.onmessage = function (event) {
    const data = JSON.parse(event.data);
    
    console.log("New message:", data);

    // Append the message to chat UI
    const chatBox = document.getElementById("chat-box");
    const messageElement = document.createElement("div");
    messageElement.innerHTML = `<strong>${data.sender_name}:</strong> ${data.message}`;
    chatBox.appendChild(messageElement);
};

// Function to send a message via WebSocket
function sendMessage() {
    const messageInput = document.getElementById("message-input");
    const message = messageInput.value.trim();

    if (message === "") return;

    const data = {
        sender_id: userId, // Get from session
        sender_type: userType, // Get from session
        receiver_id: selectedReceiverId, // Set based on user selection
        receiver_type: selectedReceiverType, // Set based on user selection
        message: message
    };

    ws.send(JSON.stringify(data));

    // Clear input field
    messageInput.value = "";
}

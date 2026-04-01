// /Gymora/assets/js/chat.js

let currentChatUserId = 0;
let lastMessageId = 0;
let chatInterval = null;

function loadChat(userId, userName) {
    currentChatUserId = userId;
    lastMessageId = 0; // Reset for new chat
    
    // Update UI headers
    document.getElementById('chat-with-name').innerText = userName;
    document.getElementById('chat-box').innerHTML = '<div class="text-center text-muted mt-5">Loading messages...</div>';
    document.getElementById('message-form').style.display = 'block';
    document.getElementById('empty-chat-state').style.display = 'none';

    // Highlight active contact
    document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active', 'bg-primary', 'text-white'));
    document.getElementById('contact-' + userId).classList.add('active', 'bg-primary', 'text-white');

    // Fetch initial messages
    fetchMessages();

    // Clear any existing polling and start a new one (poll every 4 seconds)
    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(fetchMessages, 4000);
}

function fetchMessages() {
    if (currentChatUserId === 0) return;

    fetch(`../api/get_messages.php?other_user_id=${currentChatUserId}&last_message_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                const chatBox = document.getElementById('chat-box');
                
                // Clear loading text if it's the first load
                if (lastMessageId === 0) chatBox.innerHTML = ''; 

                data.messages.forEach(msg => {
                    const isMine = msg.is_mine;
                    const bubbleClass = isMine ? 'bg-primary text-white float-end' : 'bg-light border float-start';
                    const alignClass = isMine ? 'text-end' : 'text-start';

                    const msgHTML = `
                        <div class="clearfix mb-3">
                            <div class="p-3 rounded-3 shadow-sm ${bubbleClass}" style="max-width: 75%;">
                                ${msg.content}
                                <div class="small mt-1 ${isMine ? 'text-white-50' : 'text-muted'}">${msg.time_formatted}</div>
                            </div>
                        </div>
                    `;
                    chatBox.innerHTML += msgHTML;
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });

                // Scroll to bottom
                chatBox.scrollTop = chatBox.scrollHeight;
            } else if (lastMessageId === 0 && data.messages.length === 0) {
                document.getElementById('chat-box').innerHTML = '<div class="text-center text-muted mt-5">No messages yet. Start the conversation!</div>';
            }
        })
        .catch(error => console.error('Error fetching messages:', error));
}

// Handle sending a new message
document.addEventListener("DOMContentLoaded", function() {
    const msgForm = document.getElementById('message-form');
    if(msgForm) {
        msgForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const contentInput = document.getElementById('message-input');
            const content = contentInput.value.trim();
            
            if (content === '' || currentChatUserId === 0) return;
            
            const formData = new FormData();
            formData.append('receiver_id', currentChatUserId);
            formData.append('content', content);
            
            // Clear input immediately for good UX
            contentInput.value = '';
            
            fetch('../api/send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Instantly fetch the message that just sent so it appears on screen
                    fetchMessages();
                }
            });
        });
    }
});
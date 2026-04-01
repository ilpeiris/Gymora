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

  // added a unique timestamp (&t=...) and {cache: 'no-store'} to completely block browser caching
    const timestamp = new Date().getTime();
    fetch(`../api/get_messages.php?other_user_id=${currentChatUserId}&last_message_id=${lastMessageId}&t=${timestamp}`, { cache: "no-store" })

    
        .then(async response => {
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const textError = await response.text();
                throw new Error("API returned non-JSON. Output was: " + textError.substring(0, 100));
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                const chatBox = document.getElementById('chat-box');
                
                if (data.messages && data.messages.length > 0) {
                    if (lastMessageId === 0) chatBox.innerHTML = ''; // Clear loading text

                    data.messages.forEach(msg => {
                        const isMine = msg.is_mine;
                        const bubbleClass = isMine ? 'bg-primary text-white float-end' : 'bg-light border float-start';
                        
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
                    chatBox.scrollTop = chatBox.scrollHeight;
                } else if (lastMessageId === 0) {
                    chatBox.innerHTML = '<div class="text-center text-muted mt-5">No messages yet. Start the conversation!</div>';
                }
            } else {
                document.getElementById('chat-box').innerHTML = `<div class="alert alert-danger m-3">Server Error: ${data.message}</div>`;
            }
        })
        .catch(error => {
            if (lastMessageId === 0) {
                document.getElementById('chat-box').innerHTML = `<div class="alert alert-danger m-3 fw-bold">System Error: Could not load chat.</div><div class="bg-dark text-danger p-2 small font-monospace">${error.message}</div>`;
            }
            console.error('AJAX Parse Error:', error);
        });
}

// Handle sending a new message
document.addEventListener("DOMContentLoaded", function() {
    // FIX 1: Target the actual <form> tag, not the <div> wrapper!
    const msgForm = document.getElementById('message-input-form');
    
    if(msgForm) {
        msgForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop the page from reloading!
            
            const contentInput = document.getElementById('message-input');
            const content = contentInput.value.trim();
            
            if (content === '' || currentChatUserId === 0) return;
            
            const formData = new FormData();
            formData.append('receiver_id', currentChatUserId);
            formData.append('content', content);
            
            // Clear input immediately for good UX
            contentInput.value = '';
            
            // FIX 2: Call send_messages.php (plural) to match your uploaded file!
            fetch('../api/send_messages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Instantly fetch the message that just sent so it appears on screen
                    fetchMessages();
                } else {
                    console.error("Failed to send message:", data.message);
                }
            })
            .catch(err => console.error("Network error on send:", err));
        });
    }
});
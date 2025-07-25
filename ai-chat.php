<?php
include_once 'Config/koneksi.php';
$user_id = $_SESSION['user_id'] ?? null;
$username = 'User';
if ($user_id) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($fetchedUsername);
    if ($stmt->fetch()) {
        $username = htmlspecialchars($fetchedUsername);
    }
    $stmt->close();
}
?>
<!-- Bubble Chat AI Customer Service -->
<!-- Pastikan FontAwesome sudah di-load di <head> -->
<style>
#chatButton {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: linear-gradient(135deg, #ff3737 60%, #ff7b54 100%);
    color: white;
    border: none;
    border-radius: 50%;
    width: 64px;
    height: 64px;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 6px 24px rgba(0,0,0,0.18);
    z-index: 10000;
    transition: box-shadow 0.2s, transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
#chatButton:hover {
    box-shadow: 0 10px 32px rgba(255,55,55,0.25);
    transform: scale(1.08);
    background: linear-gradient(135deg, #ff7b54 60%, #ff3737 100%);
}
#chatButton i {
    font-size: 30px;
    color: #fff;
}
#chatBox {
    display: none;
    position: fixed;
    bottom: 100px;
    right: 32px;
    width: 370px;
    max-height: 540px;
    background: #23272f;
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    overflow: hidden;
    z-index: 9999;
    flex-direction: column;
    color: #fff;
    font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    animation: chatboxIn 0.3s cubic-bezier(.4,2,.6,1) forwards;
}
@keyframes chatboxIn {
    0% { opacity: 0; transform: translateY(40px) scale(0.95); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
#chatHeader {
    background: linear-gradient(90deg, #ff3737 70%, #ff7b54 100%);
    color: white;
    padding: 14px 20px;
    font-weight: 600;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    letter-spacing: 0.5px;
}
#chatHeader span {
    font-size: 20px;
    opacity: 0.7;
    transition: opacity 0.2s;
}
#chatHeader span:hover { opacity: 1; }
#chatMessages {
    flex: 1;
    padding: 18px 14px 12px 14px;
    overflow-y: auto;
    font-size: 15px;
    background: #23272f;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.message {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    margin: 0 0 2px 0;
}
.message.user { flex-direction: row-reverse; }
.message.ai { flex-direction: row; }
.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #ff3737;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    box-shadow: 0 2px 8px rgba(255,55,55,0.10);
    flex-shrink: 0;
}
.message.user .avatar {
    background: linear-gradient(135deg, #ff7b54 60%, #ff3737 100%);
}
.message.ai .avatar {
    background: linear-gradient(135deg, #23272f 60%, #ff3737 100%);
}
.avatar i {
    font-size: 20px;
    color: #fff;
}
.bubble {
    padding: 12px 16px;
    border-radius: 16px;
    max-width: 75%;
    font-size: 15px;
    line-height: 1.6;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    word-break: break-word;
}
.message.user .bubble {
    background: linear-gradient(135deg, #ff7b54 60%, #ff3737 100%);
    color: #fff;
    border-bottom-right-radius: 6px;
    border-bottom-left-radius: 16px;
}
.message.ai .bubble {
    background: #2c323c;
    color: #fff;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 16px;
}
#chatInputArea {
    display: flex;
    border-top: 1px solid #353a45;
    background: #222831;
    padding: 10px 12px;
    gap: 8px;
}
#chatInput {
    flex: 1;
    padding: 10px 14px;
    border: none;
    font-size: 15px;
    background-color: #2c323c;
    color: white;
    border-radius: 10px;
    outline: none;
    transition: box-shadow 0.2s;
}
#chatInput:focus {
    box-shadow: 0 0 0 2px #ff3737;
}
#chatInput::placeholder { color: #bbb; }
#sendButton {
    background: linear-gradient(135deg, #ff3737 60%, #ff7b54 100%);
    color: white;
    border: none;
    padding: 0 18px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(255,55,55,0.10);
}
#sendButton:hover {
    background: linear-gradient(135deg, #ff7b54 60%, #ff3737 100%);
}
.typing {
    display: inline-block;
    font-size: 15px;
    color: #ff7b54;
    margin: 8px 0 0 0;
    padding-left: 8px;
}
@media (max-width: 600px) {
    #chatBox { width: 96%; right: 2%; bottom: 80px; max-height: 70%; }
    #chatButton { width: 52px; height: 52px; font-size: 20px; }
}
</style>
<button id="chatButton"><i class="fa-solid fa-headset"></i></button>
<div id="chatBox">
    <div id="chatHeader">TechAI <span style="cursor:pointer;" onclick="toggleChat()">✖️</span></div>
    <div id="chatMessages"></div>
    <div id="chatInputArea">
        <input type="text" id="chatInput" placeholder="Tulis pesan..." />
        <button id="sendButton">Kirim</button>
    </div>
</div>
<script>
const namaUser = "<?php echo $username; ?>";
const chatButton = document.getElementById('chatButton');
const chatBox = document.getElementById('chatBox');
const chatMessages = document.getElementById('chatMessages');
const chatInput = document.getElementById('chatInput');
const sendButton = document.getElementById('sendButton');
function toggleChat() { chatBox.style.display = chatBox.style.display === 'none' ? 'flex' : 'none'; }
chatButton.onclick = () => { chatBox.style.display = 'flex'; };
function addMessage(text, sender) {
    const msg = document.createElement('div');
    msg.className = 'message ' + sender;
    // Avatar
    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    if(sender === 'ai') {
        avatar.innerHTML = '<i class="fa-solid fa-headset"></i>';
    } else {
        avatar.innerHTML = '<i class="fa fa-user"></i>';
    }
    // Bubble
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.innerHTML = text;
    msg.appendChild(avatar);
    msg.appendChild(bubble);
    chatMessages.appendChild(msg);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
function showTyping() {
    const typing = document.createElement('div');
    typing.className = 'typing ai';
    typing.id = 'typing-indicator';
    typing.innerHTML = 'TechAI sedang mengetik...';
    chatMessages.appendChild(typing);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
function hideTyping() {
    const typing = document.getElementById('typing-indicator');
    if (typing) typing.remove();
}
function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;
    addMessage(text, 'user');
    chatInput.value = '';
    showTyping();
    fetch('gemini_proxy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
    })
    .then(res => res.json())
    .then(data => {
        hideTyping();
        addMessage(data.response || 'Maaf, terjadi gangguan sistem.', 'ai');
    })
    .catch(() => {
        hideTyping();
        addMessage('Maaf, terjadi gangguan sistem.', 'ai');
    });
}
sendButton.onclick = sendMessage;
chatInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') sendMessage(); });
</script>
<!-- End Bubble Chat --> 
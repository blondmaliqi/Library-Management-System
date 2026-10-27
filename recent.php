<?php
require_once 'functions.php';
require_once 'config.php';
require_role(2);

render_header("Recently Read");

$user_id = $_SESSION['user_id'];

/* ----------- CLEAR HISTORY BUTTON HANDLER ----------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_history'])) {
    $stmt = $pdo->prepare("DELETE FROM recently_read WHERE user_id = :u");
    $stmt->execute([':u' => $user_id]);

    header("Location: recent.php");
    exit;
}

/* ----------- GET RECENTLY READ BOOKS ----------- */
$stmt = $pdo->prepare("
    SELECT b.* FROM books b
    JOIN recently_read r ON b.id = r.book_id
    WHERE r.user_id = :u
    ORDER BY r.read_at DESC
    LIMIT 20
");
$stmt->execute([':u'=>$user_id]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group mb-4">
            <a href="user_dashboard.php" class="list-group-item list-group-item-action">📚 Biblioteka</a>
            <a href="favorites.php" class="list-group-item list-group-item-action">⭐ Të preferuarat</a>
            <a href="recent.php" class="list-group-item list-group-item-action active">🕒 Lexuar së fundmi</a>
             <a href="my_loans.php" class="list-group-item list-group-item-action">📚 Librat e huazuar</a>
            <a href="profile.php" class="list-group-item list-group-item-action">👤 Profili</a>
            <a href="process_fine_payment.php" class="list-group-item list-group-item-action">💳 Paguaj Gjobat</a>
            <a href="help_center.php" class="list-group-item list-group-item-action">❓ Qendra e ndihmës</a>
        </div>
    </div>

    <div class="col-md-9">
        <h3>🕒 Lexuar së fundmi</h3>

        <?php if (empty($recent)): ?>
            <p class="text-muted">Nuk ke lexuar ende asnjë libër.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($recent as $book): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($book['title']); ?></h5>
                                <a href="view_book.php?id=<?php echo $book['id']; ?>" class="btn btn-primary w-100">📖 Lexoje Përsëri</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CLEAR HISTORY BUTTON -->
            <form method="POST" class="mt-3">
                <button type="submit" name="clear_history" 
                    class="btn btn-danger w-100"
                    onclick="return confirm('A je i sigurt që dëshiron ta fshish historinë?')">
                    🗑️ Fshi Historinë
                </button>
            </form>

        <?php endif; ?>
    </div>
</div>

<!-- AI Chatbot: paste BEFORE render_footer() -->
<style>
/* CHAT BUTTON */
#chatbot-wrapper {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* MESSAGE BUBBLE */
#chatbot-bubble {
    background: #ffffff;
    color: #333;
    padding: 12px 16px;
    border-radius: 16px;
    font-size: 14px;
    max-width: 220px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    animation: slideIn 0.6s ease, pulse 2s infinite;
    cursor: pointer;
}

/* BUTTON */
#chatbot-button {
    background: #0d6efd;
    color: #fff;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    border: none;
    font-size: 30px;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    animation: pulse 2s infinite;
}

/* CHAT WINDOW */
#chatbox-container {
    position: fixed;
    bottom: 115px;
    right: 25px;
    width: 380px;
    height: 480px;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #ddd;
    display: none;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    z-index: 9999;
}

#chatbox-header {
    background:#0d6efd;
    color:#fff;
    padding:12px;
    text-align:center;
    font-weight:600;
}

#chatbox-messages {
    flex:1;
    padding:12px;
    overflow:auto;
    font-size:14px;
}

.message-user { color:#0d6efd; font-weight:600; }
.message-ai { color:#198754; font-weight:600; }

#chatbox-input {
    display:flex;
    border-top:1px solid #eee;
}

#chatbox-input input {
    flex:1;
    padding:10px;
    border:none;
    outline:none;
    font-size:14px;
}

#chatbox-input button {
    width:90px;
    border:none;
    background:#0d6efd;
    color:#fff;
    cursor:pointer;
}

#chat-status {
    font-size:12px;
    color:#666;
    padding:6px 12px;
}

/* ANIMATIONS */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>

<div id="chatbot-wrapper">
    <div id="chatbot-bubble" onclick="toggleChat()">
        👋 Përshëndetje!  
        <br>Si mund t’ju ndihmoj?
    </div>
    <button id="chatbot-button" onclick="toggleChat()" title="Support Chat">
        💬
    </button>
</div>

<div id="chatbox-container" role="dialog" aria-label="Support chat">
    <div id="chatbox-header">
        📚 Support AI
        <span style="float:right;cursor:pointer;" onclick="toggleChat()">✖</span>
    </div>

    <div id="chatbox-messages" aria-live="polite"></div>

    <div id="chat-status">
        Këshillë: pyet për pagesën e gjobave, huazimin e një libri ose leximin e librave. Inteligjenca artificiale (AI) do t'ju ndihmojë.
    </div>

    <div id="chatbox-input">
        <input id="chat-msg" type="text" placeholder="Shkruaj pyetjen..." aria-label="Ask support">
        <button id="chat-send" onclick="sendChat()">Dërgo</button>
    </div>
</div>

<script>
function toggleChat() {
    const box = document.getElementById('chatbox-container');
    const bubble = document.getElementById('chatbot-bubble');

    if (box.style.display === 'flex') {
        box.style.display = 'none';
    } else {
        box.style.display = 'flex';
        bubble.style.display = 'none'; // hide message bubble
    }
}

async function sendChat() {
    const input = document.getElementById('chat-msg');
    let text = input.value.trim();
    if (!text) return;

    const box = document.getElementById('chatbox-messages');
    box.innerHTML += `<div class="message-user">You:</div><div>${escapeHtml(text)}</div><hr/>`;
    box.scrollTop = box.scrollHeight;

    input.value = '';
    input.disabled = true;
    document.getElementById('chat-send').disabled = true;

    box.innerHTML += `<div class="message-ai">AI:</div><div>Typing...</div><hr/>`;
    box.scrollTop = box.scrollHeight;

    try {
        const res = await fetch('support_ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: text })
        });

        const data = await res.json();
        box.lastChild.remove();

        if (data.answer) {
            box.innerHTML += `<div class="message-ai">AI:</div><div>${escapeHtml(data.answer)}</div><hr/>`;
        } else {
            box.innerHTML += `<div class="message-ai">AI:</div><div>Nuk ka përgjigje.</div><hr/>`;
        }
    } catch {
        box.innerHTML += `<div class="message-ai">AI:</div><div>Gabim rrjeti.</div><hr/>`;
    } finally {
        input.disabled = false;
        document.getElementById('chat-send').disabled = false;
        box.scrollTop = box.scrollHeight;
    }
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;")
        .replace(/\n/g, "<br>");
}
</script>


<?php render_footer(); ?>


<?php render_footer(); ?>

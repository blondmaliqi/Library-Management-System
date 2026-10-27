<?php 
require_once 'functions.php';
require_once 'config.php';
require_role(2);

render_header("Help Center");
?>

<div class="row">
    <!-- LEFT MENU -->
    <div class="col-md-3">
        <div class="list-group mb-4">
            <a href="user_dashboard.php" class="list-group-item list-group-item-action">📚 Biblioteka</a>
            <a href="favorites.php" class="list-group-item list-group-item-action">⭐ Të preferuarat</a>
            <a href="recent.php" class="list-group-item list-group-item-action">🕒 Lexuar së fundmi</a>
             <a href="my_loans.php" class="list-group-item list-group-item-action">📚 Librat e huazuar</a>
            <a href="profile.php" class="list-group-item list-group-item-action">👤 Profili</a>
            <a href="process_fine_payment.php" class="list-group-item list-group-item-action">💳 Paguaj Gjobat</a>
            <a href="help_center.php" class="list-group-item list-group-item-action active">❓ Qendra e ndihmës</a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="col-md-9">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3>❓ Qendra e ndihmës</h3>
                <p class="text-muted">Shikoni përgjigje ose kontaktoni mbështetjen.</p>

                <hr>

                <h5>📘 Si ta përdorni bibliotekën</h5>
                <p>Shfletoni librat, kërkoni sipas titullit, autorit ose fjalëve kyçe, filtroni kategoritë dhe klikoni “Lexo” për të hapur çdo libër që keni huazuar.</p>

                <h5>⭐ Si funksionojnë librat e preferuara</h5>
                <p>Mund të shtoni ose hiqni libra nga lista juaj e të preferuarave në çdo kohë.</p>

                <h5>📚 Procesi i Huazimit</h5>
                <p>🔄 Si funksionon procesi i huazimit?
1. Klikoni "📚 Kërko Huazimin" për librin që dëshironi
2. Prisni miratimin nga administratori (30 minuta-1 orë)
3. Kur huazimi është miratuar, klikoni "✅ Konfirmo Huazimin"
4. Pas konfirmimit, klikoni "📖 Lexoni Librin" për të hapur Librin.</p>
  

                <h5>⏳ Sistemi i Gjobave</h5>
                <p>⚠️ Sistemi i plotë i gjobave:
• Periudha e huazimit: 7 ditë
• Ditët 1-7: Falas (0€)
• Pas 7 ditësh: 5€ për çdo ditë vonesë

📊 Shembuj praktikë:
• Vonesë 5 ditë → 0€ (akoma brenda 7 ditëve)
• Vonesë 7 ditë → 0€ (ditën e fundit falas)
• Vonesë 9 ditë → = 10€
• Vonesë 15 ditë → = 40€.</p>
                
                <h5>💳 Si funksionon pagesa me kartë</h5>
<p>Mund të paguani gjobat tuaja përmes formës së kartës. Shkruani emrin dhe mbiemrin, numrin e kartës, datën e skadencës (MM/YY) dhe CVV. Butoni “Paguaj” aktivizohet vetëm nëse keni gjoba aktive. Nëse gjoba është 0€, mund të shihni formën, por nuk mund të paguani.</p>


                <hr>

                <h4>📬 Kontaktoni mbështetjen</h4>
                <p>Nëse keni nevojë për ndihmë, na dërgoni një mesazh dhe ne do t'ju përgjigjemi së shpejti.</p>

                <form method="post" action="send_support.php">
                    <div class="mb-3">
                        <label>Mesazhi juaj</label>
                        <textarea name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    <button class="btn btn-primary">Dërgo Mesazh</button>
                </form>

            </div>
        </div>
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

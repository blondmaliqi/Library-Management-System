<?php
require_once 'config.php';
require_once 'functions.php';
require_role(2);

$user_id = $_SESSION['user_id'];

render_header("Pagesë me Kartë");

// --- PROCESS PAYMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Merr gjobat aktuale nga DB
    $stmt = $pdo->prepare("SELECT SUM(fine) AS total FROM book_loans WHERE user_id=? AND (fine > 0 OR fine IS NULL)");
    $stmt->execute([$user_id]);
    $totalFine = (float)$stmt->fetchColumn();

    if ($totalFine <= 0) {
        echo "<div class='alert alert-danger text-center'>
                ❌ Nuk keni gjoba aktive për tu paguar.
              </div>";
        render_footer();
        exit;
    }

    // Përditëso të gjitha gjobat në 0
    $stmt = $pdo->prepare("UPDATE book_loans SET fine = 0 WHERE user_id=? AND (fine > 0 OR fine IS NULL)");
    $stmt->execute([$user_id]);

    // Ridrejto tek my_loans.php me mesazh sukses
    header("Location: my_loans.php?fine_paid=1&paid_amount={$totalFine}");
    exit;
}

// --- FORM PAYMENT (GET) ---
$stmt = $pdo->prepare("SELECT SUM(fine) AS total FROM book_loans WHERE user_id=? AND (fine > 0 OR fine IS NULL)");
$stmt->execute([$user_id]);
$totalFine = (float)$stmt->fetchColumn();
?>

<div class="card p-4 shadow" style="max-width:500px; margin:auto;">
    <h4 class="mb-3 text-center">💳 Pagesë me Kartë</h4>

    <p class="text-center">
        Gjobë totale:
        <b><?= number_format($totalFine, 2) ?> €</b>
    </p>

    <?php if ($totalFine <= 0): ?>
        <div class="alert alert-info text-center">
            ℹ️ Nuk keni gjoba aktive.
        </div>
    <?php endif; ?>

    <form method="post" id="paymentForm">
        <div class="mb-3">
            <label>Emri dhe Mbiemri</label>
            <input type="text" name="fullname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Numri i Kartës</label>
            <input type="text" name="card_number" id="cardNumber" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" required>
        </div>
        <div class="row">
            <div class="col">
                <label>MM/YY</label>
                <input type="text" name="expiry" id="expiry" class="form-control" placeholder="MM/YY" maxlength="5" required>
            </div>
            <div class="col">
                <label>CVV</label>
                <input type="text" name="cvv" id="cvv" class="form-control" placeholder="123" maxlength="3" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-4 w-100" <?= ($totalFine <= 0) ? 'disabled' : '' ?>>
            Paguaj <?= number_format($totalFine, 2) ?> €
        </button>
    </form>
</div>

<script>
// Maskë për numrin e kartës (1234 5678 9012 3456)
document.getElementById('cardNumber').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '').slice(0,16); // vetëm numra
    let parts = [];
    for (let i=0; i<value.length; i+=4) {
        parts.push(value.substr(i,4));
    }
    e.target.value = parts.join(' ');
});

// Maskë për MM/YY
document.getElementById('expiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g,'').slice(0,4);
    if(value.length >= 3) {
        e.target.value = value.slice(0,2) + '/' + value.slice(2);
    } else if(value.length >= 1) {
        e.target.value = value;
    }
});

// Siguro CVV të jetë vetëm numër
document.getElementById('cvv').addEventListener('input', function(e){
    e.target.value = e.target.value.replace(/\D/g,'').slice(0,3);
});
</script>




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

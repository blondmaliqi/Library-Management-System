<?php
require_once 'config.php';
require_once 'functions.php';
require_role(2);

$user_id = $_SESSION['user_id'];

// --- PROCESS DELETE IF REQUESTED ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $reservation_id = (int)$_GET['delete'];

    // Sigurohu që kjo rezervim i takon këtij user-i
    $stmt = $pdo->prepare("SELECT * FROM book_reservations WHERE id=? AND user_id=?");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reservation) {
        $delete = $pdo->prepare("DELETE FROM book_reservations WHERE id=?");
        $delete->execute([$reservation_id]);
        $message = "🗑️ Rezervimi është fshirë nga historia juaj.";
    } else {
        $error = "Gabim: nuk mund të fshihet ky rezervim.";
    }
}

render_header("Librat e rezervuar");

// Merr librat që useri ka rezervuar
$resStmt = $pdo->prepare("
    SELECT br.id AS reservation_id, b.*
    FROM book_reservations br
    JOIN books b ON br.book_id = b.id
    WHERE br.user_id=?  -- vetëm rezervimet e këtij user-i
    ORDER BY br.reservation_date DESC
");
$resStmt->execute([$user_id]);
$reservations = $resStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h3>📌 Librat e rezervuar</h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (empty($reservations)): ?>
        <div class="alert alert-info">Nuk keni rezervuar asnjë libër.</div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($reservations as $res): ?>

            <?php
            // Kontrollo nëse libri është i huazuar
            $borrowCheck = $pdo->prepare("SELECT * FROM book_loans WHERE book_id=? AND status='borrowed'");
            $borrowCheck->execute([$res['id']]);
            $isBorrowed = $borrowCheck->fetch(PDO::FETCH_ASSOC);
            ?>

            <div class="col-md-4 mb-3">
                <div class="card h-100 shadow-sm">
                    <?php if (!empty($res['cover_image'])): ?>
                        <img src="uploads/covers/<?php echo htmlspecialchars($res['cover_image']); ?>"
                             class="card-img-top" style="height:220px; object-fit:cover;">
                    <?php else: ?>
                        <div style="height:220px; background:#f0f0f0; display:flex; justify-content:center; align-items:center; color:#aaa;">
                            No Image
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($res['title']); ?></h5>
                        <small class="text-muted"><?php echo htmlspecialchars($res['author']); ?></small><br>
                        <?php if (!empty($res['category'])): ?>
                            <small class="badge bg-secondary mt-2"><?php echo htmlspecialchars($res['category']); ?></small>
                        <?php endif; ?>
                        <hr>

                        <?php if ($isBorrowed): ?>
                            <button class="btn btn-warning w-100" disabled>📚 Libri është i huazuar</button>
                        <?php else: ?>
                            <a href="borrow.php?book_id=<?php echo $res['id']; ?>" class="btn btn-info w-100">📚 Huazo Librin</a>
                        <?php endif; ?>

                        <!-- Delete reservation button -->
                        <a href="my_reservations.php?delete=<?php echo $res['reservation_id']; ?>"
                           class="btn btn-secondary w-100 mt-2"
                           onclick="return confirm('Dëshiron ta fshish këtë rezervim nga historia?');">
                           🗑️ Fshi Historinë
                        </a>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>
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

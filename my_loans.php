<?php
require_once 'config.php';
require_once 'functions.php';
require_role(2);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_id = $_SESSION['user_id'];

// Vendos zona kohore për Kosovën
date_default_timezone_set('Europe/Belgrade');

// --- MESAZHI PAS PAGESËS ---
if (isset($_GET['fine_paid']) && $_GET['fine_paid'] == 1) {
    $paidAmount = isset($_GET['paid_amount']) ? (float)$_GET['paid_amount'] : 0;
    $message = "✅ Pagesa e gjobave u krye me sukses! Shuma e paguar: {$paidAmount} €";
}

// --- PROCESS RETURN IF REQUESTED ---
if (isset($_GET['return']) && is_numeric($_GET['return'])) {
    $loan_id = (int)$_GET['return'];

    $stmt = $pdo->prepare("SELECT * FROM book_loans WHERE id=? AND user_id=? AND status='borrowed'");
    $stmt->execute([$loan_id, $user_id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($loan) {
        $fine = (float)$loan['fine'];

        // Blloko kthimin vetëm nëse ka gjobë
        if ($fine > 0) {
            $error = "📌 Nuk mund të ktheni librin sepse keni gjobë: {$fine} €. Ju lutem paguani gjobën më parë.";
        } else {
            $update = $pdo->prepare("UPDATE book_loans SET status='returned', return_date=NOW() WHERE id=?");
            $update->execute([$loan_id]);
            $message = "📚 Librin e keni kthyer!";
        }
    } else {
        $error = "Gabim: nuk mund të kthehet ky libër.";
    }
}

// --- PROCESS DELETE HISTORY ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $loan_id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("SELECT * FROM book_loans WHERE id=? AND user_id=? AND status='returned'");
    $stmt->execute([$loan_id, $user_id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($loan) {
        $delete = $pdo->prepare("DELETE FROM book_loans WHERE id=?");
        $delete->execute([$loan_id]);
        $message = "🗑️ Huazimi është fshirë nga historia juaj.";
    } else {
        $error = "Gabim: vetëm librat e kthyer mund të fshihen nga historia.";
    }
}

// --- FETCH LOANS (në përdorim) ---
$stmt = $pdo->prepare("
    SELECT bl.id AS loan_id, bl.borrow_date, bl.due_date, bl.status, bl.fine,
           b.title, b.author, b.cover_image
    FROM book_loans bl
    JOIN books b ON bl.book_id = b.id
    WHERE bl.user_id = ? AND bl.status='borrowed'
    ORDER BY bl.borrow_date DESC
");
$stmt->execute([$user_id]);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH LOANS (të kthyer) ---
$stmt_returned = $pdo->prepare("
    SELECT bl.id AS loan_id, bl.borrow_date, bl.due_date, bl.status, bl.fine,
           b.title, b.author, b.cover_image
    FROM book_loans bl
    JOIN books b ON bl.book_id = b.id
    WHERE bl.user_id = ? AND bl.status='returned'
    ORDER BY bl.borrow_date DESC
");
$stmt_returned->execute([$user_id]);
$loans_returned = $stmt_returned->fetchAll(PDO::FETCH_ASSOC);

// --- RENDER HEADER ---
render_header("Librat e Huazuar");

// Funksion për të formatuar datën
function formatKosovaDate($date) {
    if (!empty($date) && $date != '0000-00-00') {
        return date('d-m-Y', strtotime($date));
    }
    return "-";
}
?>

<div class="container mt-4">
    <h3>📚 Librat që i keni huazuar</h3>
    <hr>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Librat në përdorim -->
    <?php if (!empty($loans)): ?>
        <div class="row">
            <?php foreach ($loans as $loan): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($loan['cover_image'])): ?>
                            <img src="uploads/covers/<?php echo htmlspecialchars($loan['cover_image']); ?>"
                                 style="height:220px; object-fit:cover;" class="card-img-top">
                        <?php else: ?>
                            <div style="height:220px; background:#eee; display:flex; 
                                 justify-content:center; align-items:center; color:#aaa;">
                                No Image
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <h5><?php echo htmlspecialchars($loan['title']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($loan['author']); ?></small>
                            <hr>
                            <p><b>Huazuar më:</b> <?php echo formatKosovaDate($loan['borrow_date']); ?></p>
                            <p><b>Afati:</b> <?php echo formatKosovaDate($loan['due_date']); ?></p>
                            <span class="badge bg-warning text-dark">Në përdorim</span>

                            <?php if (!empty($loan['fine']) && $loan['fine'] > 0): ?>
                                <p class="mt-2 text-danger"><b>Gjobë:</b> <?php echo $loan['fine']; ?> €</p>
                                <a href="process_fine_payment.php" class="btn btn-primary w-100 mt-2">
                                    💳 Paguaj Gjobën
                                </a>
                                <button class="btn btn-danger w-100 mt-2" disabled>
                                    🔒 Kthim i bllokuar (Gjobë)
                                </button>
                            <?php else: ?>
                                <a href="my_loans.php?return=<?php echo $loan['loan_id']; ?>" 
                                   class="btn btn-danger w-100 mt-2"
                                   onclick="return confirm('Dëshiron të kthesh këtë libër?');">
                                   🔙 Ktheje Librin
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Nuk keni asnjë libër aktualisht të huazuar.</div>
    <?php endif; ?>

    <!-- Librat e kthyer -->
    <?php if (!empty($loans_returned)): ?>
        <h3 class="mt-5">📖 Librat e Kthyer</h3>
        <div class="row">
            <?php foreach ($loans_returned as $loan): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($loan['cover_image'])): ?>
                            <img src="uploads/covers/<?php echo htmlspecialchars($loan['cover_image']); ?>"
                                 style="height:220px; object-fit:cover;" class="card-img-top">
                        <?php else: ?>
                            <div style="height:220px; background:#eee; display:flex; 
                                 justify-content:center; align-items:center; color:#aaa;">
                                No Image
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <h5><?php echo htmlspecialchars($loan['title']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($loan['author']); ?></small>
                            <hr>
                            <p><b>Huazuar më:</b> <?php echo formatKosovaDate($loan['borrow_date']); ?></p>
                            <p><b>Afati:</b> <?php echo formatKosovaDate($loan['due_date']); ?></p>
                            <span class="badge bg-success">Kthyer</span>

                            <?php if (!empty($loan['fine']) && $loan['fine'] > 0): ?>
                                <p class="mt-2 text-danger"><b>Gjobë:</b> <?php echo $loan['fine']; ?> €</p>
                            <?php endif; ?>

                            <a href="my_loans.php?delete=<?php echo $loan['loan_id']; ?>" 
                               class="btn btn-secondary w-100 mt-2"
                               onclick="return confirm('Dëshiron ta fshish këtë huazim nga historia?');">
                               🗑️ Fshi Historinë
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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

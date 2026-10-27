<?php 
require_once 'functions.php';
require_once 'config.php';
require_role(2);

$user_id = $_SESSION['user_id'];
render_header("Library");

// --- MESAZHET NGA GET PARAMETRAT ---
$messages = [];
if (isset($_GET['loan_success'])) {
    $messages[] = "📚 Kërkesa për huazim u dërgua me sukses! Ju lutem prisni miratimin nga admini.";
}

$totalFine = 0;
if (isset($_GET['return_success'])) {
    $fine = isset($_GET['fine']) ? intval($_GET['fine']) : 0;
    $totalFine = $fine;
    if ($fine > 0) {
        $messages[] = "🔙 Libri u kthye me sukses. Gjobë: ".$fine." €";
    } else {
        $messages[] = "🔙 Libri u kthye me sukses! Nuk keni gjoba.";
    }
}

// --- FILTERS ---
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : "";
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// --- FETCH CATEGORIES ---
$catStmt = $pdo->prepare("SELECT DISTINCT category FROM books ORDER BY category ASC");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// --- FETCH BOOKS ---
$query = "SELECT * FROM books WHERE 1=1";
$params = [];

if ($selectedCategory && $selectedCategory !== "all") {
    $query .= " AND category = ?";
    $params[] = $selectedCategory;
}

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY title ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- UPDATE OVERDUE LOANS & CALCULATE TOTAL FINE ---
$loanStmt = $pdo->prepare("SELECT * FROM book_loans WHERE user_id=? AND (status='borrowed' OR status='approved')");
$loanStmt->execute([$user_id]);
$loans = $loanStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($loans as $loan) {
    $fine = (float)$loan['fine'];

    if ($fine <= 0) {
        $today = new DateTime();
        $due = new DateTime($loan['due_date']);
        if ($today > $due) {
            $daysLate = $today->diff($due)->days;

            if ($daysLate > 7) {
                $fine = ($daysLate - 7) * 5;
            } else {
                $fine = 0;
            }

            if ($fine > 0) {
                $update = $pdo->prepare("UPDATE book_loans SET fine=? WHERE id=?");
                $update->execute([$fine, $loan['id']]);
            }
        }
    }
}

$stmtFine = $pdo->prepare("SELECT SUM(fine) FROM book_loans WHERE user_id=? AND fine > 0");
$stmtFine->execute([$user_id]);
$totalFineDb = $stmtFine->fetchColumn() ?? 0;

if (!isset($_GET['return_success'])) {
    $totalFine = $totalFineDb;
}

?>

<div class="row">
    <!-- LEFT MENU -->
    <div class="col-md-3">
        <div class="list-group mb-4">
            <a href="user_dashboard.php" class="list-group-item list-group-item-action active">📚 Biblioteka</a>
            <a href="favorites.php" class="list-group-item list-group-item-action">⭐ Të preferuarat</a>
            <a href="recent.php" class="list-group-item list-group-item-action">🕒 Lexuar së fundmi</a>
            <a href="my_loans.php" class="list-group-item list-group-item-action">📚 Librat e huazuar</a>
            <a href="profile.php" class="list-group-item list-group-item-action">👤 Profili</a>
            <a href="process_fine_payment.php" class="list-group-item list-group-item-action">💳 Paguaj Gjobat</a>
            <a href="help_center.php" class="list-group-item list-group-item-action">❓ Qendra e ndihmës</a>
        </div>
    </div>

    <!-- BOOKS LIST -->
    <div class="col-md-9">
    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-success auto-dismiss"><?php echo $msg; ?></div>
    <?php endforeach; ?>

    <?php if ($totalFine >= 0): ?>
        <div class="alert alert-<?php echo $totalFine > 0 ? 'danger' : 'success'; ?> mt-2" id="fine-alert">
            💰 Gjobë totale për t'u paguar: <b><?php echo $totalFine; ?> €</b>
            <?php if ($totalFine == 0): ?>
                – Nuk keni gjoba.
            <?php else: ?>
                <br>Ju lutem ktheni librat në kohë për të shmangur gjoba shtesë.
            <?php endif; ?>
        </div>
    <?php endif; ?>

        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <h4>Mirë se vini <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</h4>
                <p>Gjithsej libra: <?php echo count($books); ?></p>
            </div>
        </div>

        <!-- SEARCH & CATEGORY FILTER -->
        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="Kërko sipas titullit, autorit, ISBN-së..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="all">Të gjitha kategoritë</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($selectedCategory == $cat) echo "selected"; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100">Go</button>
                </div>
            </div>
        </form>

        <!-- LIBRAT -->
        <div class="row">
            <?php if (empty($books)): ?>
                <div class="alert alert-warning">Nuk u gjetën libra.</div>
            <?php endif; ?>

            <?php foreach ($books as $book): ?>
                <?php
                // Kontrollo nëse useri aktual ka kërkesë për këtë libër
                $loan = $pdo->prepare("SELECT * FROM book_loans WHERE user_id=? AND book_id=?");
                $loan->execute([$user_id, $book['id']]);
                $loanData = $loan->fetch(PDO::FETCH_ASSOC);
                
                $status = $loanData ? $loanData['status'] : '';
                ?>

                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top" style="height:220px; object-fit:cover;">
                        <?php else: ?>
                            <div style="height:220px; background:#f0f0f0; display:flex; justify-content:center; align-items:center; color:#aaa;">No Image</div>
                        <?php endif; ?>

                        <div class="card-body">
                            <h5><?php echo htmlspecialchars($book['title']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($book['author']); ?></small><br>
                            <small class="badge bg-secondary mt-2"><?php echo htmlspecialchars($book['category']); ?></small>
                            <?php if (!empty($book['isbn'])): ?><br><small class="text-muted">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></small><?php endif; ?>
                            <hr>

                            <?php if ($status == 'pending'): ?>
                                <button class="btn btn-warning w-100 mt-1" disabled>🕒 Në pritje të miratimit nga admini</button>
                                <a href="cancel_loan.php?loan_id=<?php echo $loanData['id']; ?>" class="btn btn-secondary w-100 mt-1" onclick="return confirm('Dëshiron ta anulosh kërkesën për huazim?');">Anulo Kërkesën</a>
                            <?php elseif ($status == 'approved'): ?>
                                <a href="confirm_borrow.php?loan_id=<?php echo $loanData['id']; ?>" class="btn btn-success w-100 mt-1">✅ Konfirmo Huazimin</a>
                            <?php elseif ($status == 'borrowed'): ?>
                                <a href="view_book.php?id=<?php echo $book['id']; ?>" class="btn btn-success w-100 mt-1">📖 Lexoni Librin</a>
                            <?php else: ?>
                                <!-- Për bibliotekë dixhitale, gjithmonë mund të kërkosh huazim -->
                                <a href="borrow.php?book_id=<?php echo $book['id']; ?>" class="btn btn-info w-100 mt-1">📚 Kërko Huazimin</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- JS për zhdukjen e mesazheve pas 4 sekondash -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const alerts = document.querySelectorAll(".alert-success, .alert-warning, .alert-danger");

    alerts.forEach(function(alert) {
        // Kontrollo nëse ky është mesazhi i gjobave
        const isFineAlert = alert.innerHTML.includes("💰 Gjobë totale për t'u paguar");
        
        // Nëse është mesazh i gjobave, mos e zhduk
        if (isFineAlert) {
            return; // Kaloj këtë alert
        }
        
        // Nëse nuk është mesazh i gjobave, zhduk pas 4 sekondash
        setTimeout(function() {
            alert.style.transition = "opacity 0.5s";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });
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
        <br>Si mund t'ju ndihmoj?
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
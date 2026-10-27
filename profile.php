<?php
require_once 'functions.php';
require_once 'config.php';
require_role(2);

$user_id = $_SESSION['user_id'];

// Fetch current profile data
$stmt = $pdo->prepare("SELECT username, email, password FROM users WHERE id = :id");
$stmt->execute([':id'=>$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = $success = "";
$pw_error = $pw_success = "";

// ----------------------------
// UPDATE PROFILE (username/email)
// ----------------------------
if (isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("UPDATE users SET username=:u, email=:e WHERE id=:id");
    if ($stmt->execute([':u'=>$username, ':e'=>$email, ':id'=>$user_id])) {
        $_SESSION['username'] = $username;
        $success = "Profile updated!";
    } else {
        $error = "Update failed.";
    }
}

// ----------------------------
// CHANGE PASSWORD
// ----------------------------
if (isset($_POST['change_password'])) {
    $old_pw = $_POST['old_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];

    if ($old_pw !== $user['password']) {
        $pw_error = "Old password is incorrect!";
    } else if ($new_pw !== $confirm_pw) {
        $pw_error = "New passwords do not match!";
    } else if (strlen($new_pw) < 3) {
        $pw_error = "New password must be at least 3 characters.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password=:p WHERE id=:id");
        $stmt->execute([':p'=>$new_pw, ':id'=>$user_id]);
        $pw_success = "Password changed successfully!";
    }
}

// ----------------------------
// DELETE ACCOUNT
// ----------------------------
if (isset($_POST['delete_account'])) {
    $confirm_pw = $_POST['confirm_delete_password'];

    // Compare plaintext as your system uses plain passwords
    if ($confirm_pw !== $user['password']) {
        $error = "Fjalëkalimi është i pasaktë. Llogaria NUK është fshirë.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=:id");
        $stmt->execute([':id' => $user_id]);

        session_destroy();
        header("Location: goodbye.php");
        exit;
    }
}

render_header("Profile");
?>

<h2>Profili im</h2>

<?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>

<!-- PROFILE UPDATE -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">Update Profile</div>
    <div class="card-body">

        <form method="post">
            <div class="mb-3">
                <label>Username</label>
                <input class="form-control" name="username"
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input class="form-control" name="email" type="email"
                       value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <button class="btn btn-primary" name="update_profile">Save Changes</button>
        </form>

    </div>
</div>

<!-- CHANGE PASSWORD -->
<div class="card">
    <div class="card-header bg-warning">Ndrysho fjalëkalimin</div>
    <div class="card-body">

        <?php if ($pw_error) echo "<div class='alert alert-danger'>$pw_error</div>"; ?>
        <?php if ($pw_success) echo "<div class='alert alert-success'>$pw_success</div>"; ?>

        <form method="post">

            <div class="mb-3">
                <label>Fjalëkalimi i vjetër</label>
                <input class="form-control" type="password" name="old_password" required>
            </div>

            <div class="mb-3">
                <label>Fjalëkalim i ri</label>
                <input class="form-control" type="password" name="new_password" required>
            </div>

            <div class="mb-3">
                <label>Fjalëkalim i ri</label>
                <input class="form-control" type="password" name="confirm_password" required>
            </div>

            <button class="btn btn-warning" name="change_password">Update Password</button>
        </form>

    </div>
</div>

<!-- DELETE ACCOUNT -->
<div class="card mt-4">
    <div class="card-header bg-danger text-white">Delete Account</div>
    <div class="card-body">

        <div class="alert alert-warning">
            ⚠ Ky veprim është i përhershëm. Të gjitha të dhënat tuaja do të fshihen.
        </div>

        <form method="post">
            <div class="mb-3">
                <label>Konfirmo fjalëkalimin</label>
                <input class="form-control" type="password" name="confirm_delete_password" required>
            </div>

            <button class="btn btn-danger" name="delete_account"
                    onclick="return confirm('Je i sigurt? Kjo nuk mund të kthehet!');">
                Delete My Account
            </button>
        </form>

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

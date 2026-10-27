<?php
require_once 'functions.php';
require_once 'config.php';

// Only admins can access
require_role(1);

render_header("Manage Users");

// -------------------------------
// ADD USER
// -------------------------------
$add_error = "";
$add_success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Minimum password length
    if (strlen($password) < 5) {
        $add_error = "⚠ Password must be at least 5 characters long!";
    } else {
        // Check duplicates
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
        $check->execute([':u' => $username, ':e' => $email]);

        if ($check->rowCount() > 0) {
            $add_error = "⚠ Username or Email already exists!";
        } else {
            // Insert new user - role auto-set to 2
            $insert = $pdo->prepare("
                INSERT INTO users (username, email, password, role, created_at)
                VALUES (:u, :e, :p, 2, NOW())
            ");
            $ok = $insert->execute([
                ':u' => $username,
                ':e' => $email,
                ':p' => $password // plain password
            ]);

            if ($ok) {
                $add_success = "✅ User added successfully!";
            } else {
                $add_error = "❌ Error adding user.";
            }
        }
    }
}

// -------------------------------
// DELETE USER
// -------------------------------
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);

    if ($user_id != 1) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        echo "<div class='alert alert-success'>User deleted successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Cannot delete the main admin!</div>";
    }
}

// -------------------------------
// FETCH USERS
// -------------------------------
$stmt = $pdo->query("SELECT id, username, email FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Menaxho përdoruesit</h2>

<!-- ADD USER FORM -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        Shto përdorues të ri
    </div>
    <div class="card-body">

        <?php if ($add_error): ?>
            <div class="alert alert-danger"><?php echo $add_error; ?></div>
        <?php endif; ?>

        <?php if ($add_success): ?>
            <div class="alert alert-success"><?php echo $add_success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="add_user" value="1">

            <div class="mb-3">
                <label>Username</label>
                <input name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input name="email" type="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input name="password" type="text" class="form-control" required minlength="5">
                <small class="text-muted">Minimum 5 characters</small>
            </div>

            <button class="btn btn-success w-100">➕ Shto përdorues</button>
        </form>
    </div>
</div>

<!-- USER LIST -->
<?php if (empty($users)): ?>
    <div class="alert alert-info">Nuk ka përdorues të disponueshëm.</div>
<?php else: ?>

<table class="table table-bordered">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo $user['id']; ?></td>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>

            <td>
                <?php if ($user['id'] != 1): ?>
                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="manage_users.php?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?');">Delete</a>
                <?php else: ?>
                    <span class="text-muted">Administratori kryesor</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>

    </tbody>
</table>

<?php endif; ?>

<?php render_footer(); ?>

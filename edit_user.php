<?php
require_once 'functions.php';
require_once 'config.php';

// Only admins
require_role(1);

render_header("Edit User");

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No user selected.</div>";
    render_footer();
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div class='alert alert-danger'>User not found.</div>";
        render_footer();
        exit();
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: ".$e->getMessage()."</div>";
    render_footer();
    exit();
}

$error = $success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); // Optional: only change if not empty

    if ($username == "" || $email == "") {
        $error = "Username and email cannot be empty.";
    } elseif ($password != "" && strlen($password) < 5) {
        // Minimum password length
        $error = "Password must be at least 5 characters long.";
    } else {
        try {
            if ($password != "") {
                // Update with password
                $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, password = :password WHERE id = :id");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $password, // plaintext or hashed
                    ':id' => $user_id
                ]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':id' => $user_id
                ]);
            }
            $success = "User updated successfully!";
            // Refresh user data
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Database error: ".$e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Edit User</h2>
        <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>

        <form method="post">
            <div class="mb-3">
                <label>Username</label>
                <input name="username" class="form-control" required value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="mb-3">
                <label>Password <small>(leave blank to keep current password, minimum 5 characters)</small></label>
                <input type="password" name="password" class="form-control" minlength="5">
            </div>
            <button class="btn btn-primary">Update User</button>
            <a href="manage_users.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<?php render_footer(); ?>

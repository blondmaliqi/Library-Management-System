<?php
require_once 'config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in and has the required role.
 * Redirects if access is denied.
 * @param int $required_role 1 for Admin, 2 for User
 */
function require_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] != $required_role) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Checks if user is logged in (for general access pages)
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Helper function to create Bootstrap page headers
 */
function render_header($title) {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library | ' . htmlspecialchars($title) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="user_dashboard.php">📚 Sistemi i menaxhimit të biblotekës</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">';
                
                if (is_logged_in()) {
                    if ($_SESSION['role'] == 1) {
                        echo '<li class="nav-item"><a class="nav-link" href="admin.php">Admin Panel</a></li>';
                    }
                    echo '<li class="nav-item"><a class="nav-link" href="user_dashboard.php">My Books</a></li>';
                    echo '<li class="nav-item"><a class="nav-link btn btn-danger ms-2" href="logout.php">Logout</a></li>';
                } else {
                    echo '<li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="signup.php">Signup</a></li>';
                }

    echo '      </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-5">';
}

function render_footer() {
    echo '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>';
}
?>

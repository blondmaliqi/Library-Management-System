<?php
require_once 'functions.php';
require_once 'config.php';
require_role(2); // Only users

if (!isset($_GET['book_id'])) {
    header("Location: user_dashboard.php");
    exit();
}

$book_id = intval($_GET['book_id']);
$user_id = $_SESSION['user_id'];

// Check if already favorite
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :u AND book_id = :b");
$stmt->execute([':u' => $user_id, ':b' => $book_id]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    // Remove favorite
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :u AND book_id = :b");
    $stmt->execute([':u' => $user_id, ':b' => $book_id]);
} else {
    // Add favorite
    $stmt = $pdo->prepare("INSERT INTO favorites (user_id, book_id, added_at)
                           VALUES (:u, :b, NOW())");
    $stmt->execute([':u' => $user_id, ':b' => $book_id]);
}

// Go back to the book page
header("Location: view_book.php?id=" . $book_id);
exit();
?>

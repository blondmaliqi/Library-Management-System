<?php
require_once 'config.php';
require_once 'functions.php';
require_role(2);

if (!isset($_GET['book_id'])) {
    header("Location: user_dashboard.php");
    exit;
}

$book_id = (int)$_GET['book_id'];
$user_id = $_SESSION['user_id'];

// Kontrollo nëse libri është i huazuar nga dikush tjetër
$checkBorrow = $pdo->prepare("SELECT * FROM book_loans WHERE book_id=? AND status='borrowed'");
$checkBorrow->execute([$book_id]);
$borrow = $checkBorrow->fetch(PDO::FETCH_ASSOC);

if (!$borrow) {
    // Nëse libri nuk është i huazuar, nuk ka nevojë për rezervim
    header("Location: user_dashboard.php?reserve_error=1");
    exit;
}

// Kontrollo nëse useri e ka rezervuar tashmë
$checkReserve = $pdo->prepare("SELECT * FROM book_reservations WHERE user_id=? AND book_id=?");
$checkReserve->execute([$user_id, $book_id]);
if ($checkReserve->fetch()) {
    header("Location: user_dashboard.php?reserve_exists=1");
    exit;
}

// Shto rezervimin
$insert = $pdo->prepare("INSERT INTO book_reservations (user_id, book_id) VALUES (?, ?)");
$insert->execute([$user_id, $book_id]);

// Redirect me mesazh suksesi
header("Location: user_dashboard.php?reserve_success=1");
exit;
?>

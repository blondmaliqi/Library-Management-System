<?php
require_once 'config.php';
require_once 'functions.php';
require_role(2);

// Vendos zona kohore për Kosovën
date_default_timezone_set('Europe/Belgrade');

$user_id = $_SESSION['user_id'];
$book_id = $_GET['book_id'] ?? 0;

// Verifikoni nëse libri ekziston
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    // Nëse libri nuk ekziston, ridrejto në dashboard
    header("Location: user_dashboard.php?error=book_not_found");
    exit;
}

// Kontrolloni nëse përdoruesi tashmë ka një kërkesë aktive për këtë libër
$stmt = $pdo->prepare("SELECT * FROM book_loans WHERE book_id = ? AND user_id = ? AND status IN ('pending', 'approved', 'borrowed')");
$stmt->execute([$book_id, $user_id]);
$existingLoan = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingLoan) {
    // Nëse përdoruesi ka tashmë një kërkesë për këtë libër
    $status = $existingLoan['status'];
    if ($status == 'pending') {
        header("Location: user_dashboard.php?error=loan_already_pending");
    } elseif ($status == 'approved') {
        header("Location: user_dashboard.php?error=loan_already_approved");
    } else {
        header("Location: user_dashboard.php?error=book_already_borrowed");
    }
    exit;
}

// Shtoni huazimin në bazën e të dhënave me status 'pending' (në pritje)
$borrow_date = date('Y-m-d H:i:s'); // Ruaj datën + orën
$due_date = date('Y-m-d H:i:s', strtotime('+7 days')); // Afati 7 ditë
$fine = 0; // Gjoba fillimisht 0

// Regjistrohu huazimin në bazë të të dhënave të reja
$insert = $pdo->prepare("
    INSERT INTO book_loans (user_id, book_id, borrow_date, due_date, status, fine) 
    VALUES (?, ?, ?, ?, 'pending', ?)
");
$insert->execute([$user_id, $book_id, $borrow_date, $due_date, $fine]);

// Ridrejto në dashboard me mesazh që huazimi është në pritje
header("Location: user_dashboard.php?loan_success=1&status=pending");
exit;
?>
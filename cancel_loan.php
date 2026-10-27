<?php
require_once 'functions.php';
require_once 'config.php';
require_role(2);

$user_id = $_SESSION['user_id'];

if (isset($_GET['loan_id']) && is_numeric($_GET['loan_id'])) {
    $loan_id = (int)$_GET['loan_id'];
    
    // Verifiko nëse huazimi i takon userit dhe është ende në pritje
    $stmt = $pdo->prepare("SELECT * FROM book_loans WHERE id=? AND user_id=? AND status='pending'");
    $stmt->execute([$loan_id, $user_id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loan) {
        // Fshi huazimin
        $deleteStmt = $pdo->prepare("DELETE FROM book_loans WHERE id=?");
        $deleteStmt->execute([$loan_id]);
        
        header("Location: user_dashboard.php?loan_cancelled=1");
        exit;
    } else {
        header("Location: user_dashboard.php?error=invalid_loan");
        exit;
    }
}

header("Location: user_dashboard.php");
exit;
?>
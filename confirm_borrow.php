<?php
require_once 'functions.php';
require_once 'config.php';
require_role(2);

$user_id = $_SESSION['user_id'];

if (isset($_GET['loan_id']) && is_numeric($_GET['loan_id'])) {
    $loan_id = (int)$_GET['loan_id'];
    
    // Verifiko nëse huazimi i takon userit dhe është miratuar
    $stmt = $pdo->prepare("SELECT * FROM book_loans WHERE id=? AND user_id=? AND status='approved'");
    $stmt->execute([$loan_id, $user_id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loan) {
        // Ndrysho statusin në 'borrowed' dhe vendos datën aktuale të huazimit
        $updateStmt = $pdo->prepare("UPDATE book_loans SET status='borrowed', borrow_date=NOW() WHERE id=?");
        $updateStmt->execute([$loan_id]);
        
        header("Location: user_dashboard.php?loan_confirmed=1");
        exit;
    } else {
        header("Location: user_dashboard.php?error=invalid_loan");
        exit;
    }
}

header("Location: user_dashboard.php");
exit;
?>
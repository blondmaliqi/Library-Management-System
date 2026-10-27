<?php 
require_once 'config.php';
require_once 'functions.php';
require_role(2);

$user_id = $_SESSION['user_id'] ?? 0;
$loan_id = isset($_GET['loan_id']) ? intval($_GET['loan_id']) : 0;

if ($loan_id <= 0) {
    die("Gabim: ID e huazimit nuk është e vlefshme.");
}

// 1️⃣ Merr huazimin aktiv
$stmt = $pdo->prepare("
    SELECT id, due_date, fine 
    FROM book_loans 
    WHERE id=? AND user_id=? AND status='borrowed'
");
$stmt->execute([$loan_id, $user_id]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    die("Gabim: Huazimi nuk ekziston ose është kthyer tashmë.");
}

// 🔹 Kontrollo nëse përdoruesi ka gjobë aktive për këtë libër
if ($loan['fine'] > 0) {
    die("Gabim: Nuk mund të ktheni librin derisa të paguani gjobën prej {$loan['fine']} €.<br>
         <a href='process_fine_payment.php'>Paguaj gjobën tani</a>");
}

// 2️⃣ Llogarit gjobën (7 ditë falas + 5€/ditë pas ditës së 7)
$today = new DateTime();
$due = new DateTime($loan['due_date']);
$fine = 0;

if ($today > $due) {
    $daysLate = $today->diff($due)->days;

    if ($daysLate > 7) {
        $fine = ($daysLate - 7) * 5; // 5€/ditë pas 7 ditëve falas
    } else {
        $fine = 0; // brenda 7 ditëve, nuk ka gjobë
    }
}

// 3️⃣ Përditëso huazimin (ME return_date)
$update = $pdo->prepare("
    UPDATE book_loans 
    SET status='returned', fine=?, return_date=NOW()
    WHERE id=?
");
$update->execute([$fine, $loan_id]);

// 4️⃣ Redirect me mesazh
header("Location: user_dashboard.php?return_success=1&fine=".$fine);
exit;
?>

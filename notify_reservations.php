<?php
require_once 'config.php';
require_once 'functions.php';
require_role(2);

$user_id = $_SESSION['user_id'];

$resStmt = $pdo->prepare("
    SELECT br.id AS reservation_id, b.title, b.id AS book_id
    FROM book_reservations br
    JOIN books b ON br.book_id = b.id
    WHERE br.user_id=? AND br.notified=0
");
$resStmt->execute([$user_id]);
$reservations = $resStmt->fetchAll(PDO::FETCH_ASSOC);

$notifications = [];

foreach ($reservations as $res) {

    // Kontrollo nëse libri është ende i huazuar
    $loanCheck = $pdo->prepare("
        SELECT 1 FROM book_loans 
        WHERE book_id=? AND status='borrowed'
        LIMIT 1
    ");
    $loanCheck->execute([$res['book_id']]);

    // Nëse NUK është i huazuar → libri u lirua
    if (!$loanCheck->fetch()) {

        $notifications[] = [
            'reservation_id' => $res['reservation_id'],
            'title' => $res['title'],
            'message' => "📚 Libri '{$res['title']}' u lirua dhe është gati për huazim."
        ];

        // Shëno si i njoftuar
        $update = $pdo->prepare("
            UPDATE book_reservations 
            SET notified=1 
            WHERE id=?
        ");
        $update->execute([$res['reservation_id']]);
    }
}

header('Content-Type: application/json');
echo json_encode($notifications);

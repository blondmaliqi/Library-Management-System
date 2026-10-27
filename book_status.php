<?php
require_once 'config.php';
require_once 'functions.php';
require_role(2);

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT br.id AS reservation_id, b.title
    FROM book_reservations br
    JOIN books b ON br.book_id = b.id
    WHERE br.user_id=? AND br.notified=0
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Përditëso notified = 1
foreach ($notifications as $note) {
    $pdo->prepare("UPDATE book_reservations SET notified=1 WHERE id=?")->execute([$note['reservation_id']]);
}

header('Content-Type: application/json');
echo json_encode($notifications);

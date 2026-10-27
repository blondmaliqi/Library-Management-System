<?php
require_once 'config.php';
require_once 'functions.php';
session_start();

require_role(2);

$uid = $_SESSION['user_id'];
$bid = (int)($_GET['book_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT b.pdf_file
    FROM book_loans bl
    JOIN books b ON b.id = bl.book_id
    WHERE bl.user_id=? AND bl.book_id=? AND bl.status='borrowed'
");
$stmt->execute([$uid, $bid]);
$pdf = $stmt->fetchColumn();

if (!$pdf) die('Access denied');

$file = __DIR__ . "/uploads/$pdf";
if (!file_exists($file)) die('Not found');

header('Content-Type: application/pdf');
header('Content-Disposition: inline');
readfile($file);
exit;

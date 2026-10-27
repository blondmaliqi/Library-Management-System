<?php
require_once 'config.php';
require_once 'functions.php';
require_role(2);

if (!isset($_POST['message']) || empty(trim($_POST['message']))) {
    header("Location: help_center.php?error=empty");
    exit();
}

$message = trim($_POST['message']);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("INSERT INTO support_messages (user_id, message) VALUES (?, ?)");
$stmt->execute([$user_id, $message]);

header("Location: help_center.php?success=1");
exit();
?>

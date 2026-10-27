<?php
require_once 'config.php';
require_once 'functions.php';
require_role(1); // vetëm admin

$stmt = $pdo->query("
    SELECT bl.id, u.username, b.title, bl.loan_date, bl.due_date, bl.return_date, bl.status, bl.fine
    FROM book_loans bl
    JOIN users u ON bl.user_id = u.id
    JOIN books b ON bl.book_id = b.id
    ORDER BY bl.loan_date DESC
");
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>📚 Paneli i Huazimeve</h2>
<table class="table table-bordered">
<tr>
    <th>Përdoruesi</th>
    <th>Libri</th>
    <th>Huazuar</th>
    <th>Afarë</th>
    <th>Kthyer</th>
    <th>Statusi</th>
    <th>Gjobë</th>
</tr>

<?php foreach ($loans as $loan): ?>
<tr>
    <td><?php echo $loan['username']; ?></td>
    <td><?php echo $loan['title']; ?></td>
    <td><?php echo $loan['loan_date']; ?></td>
    <td><?php echo $loan['due_date']; ?></td>
    <td><?php echo $loan['return_date'] ?: '-'; ?></td>
    <td><?php echo strtoupper($loan['status']); ?></td>
    <td><?php echo number_format($loan['fine'], 2); ?> €</td>
</tr>
<?php endforeach; ?>
</table>

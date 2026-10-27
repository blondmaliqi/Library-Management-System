<?php 
require_once 'functions.php';
require_once 'config.php';

// Only admins can access
require_role(1);

render_header("Manage Books");

// Handle delete
if (isset($_GET['delete'])) {
    $book_id = intval($_GET['delete']);

    // Get file name
    $stmt = $pdo->prepare("SELECT pdf_file FROM books WHERE id = :id");
    $stmt->execute([':id' => $book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        // Delete file
        $file_path = "uploads/" . $book['pdf_file'];
        if (file_exists($file_path)) unlink($file_path);

        // Delete DB record
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
        $stmt->execute([':id' => $book_id]);

        echo "<div class='alert alert-success'>Book deleted successfully!</div>";
    }
}

// Fetch all books
try {
    $stmt = $pdo->query("SELECT * FROM books ORDER BY uploaded_at DESC");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<h2>Menaxho librat</h2>
<a href="add_book.php" class="btn btn-primary mb-3">Shto libër të ri</a>

<?php if (empty($books)): ?>
    <div class="alert alert-info">Nuk ka libra në dispozicion.</div>
<?php else: ?>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Titulli</th>
                <th>Autori</th>
                <th>ISBN</th> <!-- NEW COLUMN -->
                <th>Përshkrimi</th>
                <th>Ngarkuar më</th>
                <th>Veprimet</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
            <tr>
                <td><?php echo $book['id']; ?></td>
                <td><?php echo htmlspecialchars($book['title']); ?></td>
                <td><?php echo htmlspecialchars($book['author']); ?></td>

                <!-- ISBN DISPLAY -->
                <td>
                    <?php 
                        echo !empty($book['isbn']) 
                            ? htmlspecialchars($book['isbn']) 
                            : "<span class='text-muted'>—</span>";
                    ?>
                </td>

                <td><?php echo htmlspecialchars($book['description']); ?></td>
                <td><?php echo $book['uploaded_at']; ?></td>
                <td>
                    <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="manage_books.php?delete=<?php echo $book['id']; ?>" 
                       class="btn btn-danger btn-sm" 
                       onclick="return confirm('Delete this book?');">
                        Delete
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
render_footer();
?>

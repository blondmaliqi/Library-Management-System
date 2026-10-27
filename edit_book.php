<?php
require_once 'functions.php';
require_once 'config.php';

// Only admins
require_role(1);

render_header("Edit Book");

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No book selected.</div>";
    render_footer();
    exit();
}

$book_id = intval($_GET['id']);
$error = $success = "";

// Fetch existing book
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
$stmt->execute([':id' => $book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    echo "<div class='alert alert-danger'>Book not found.</div>";
    render_footer();
    exit();
}

// Set current values
$title = $book['title'];
$author = $book['author'];
$description = $book['description'];
$isbn = $book['isbn'];
$category = $book['category'];

$categories = [
    "Histori", "Klasik", "Fantazi", "Ekonomi", "Biografi",
    "Poezi", "Për fëmijë", "Romancë", "Dramë", "Roman","Libra në gjuhën angleze"
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $description = trim($_POST['description']);
    $isbn = trim($_POST['isbn']);
    $category = trim($_POST['category']);

    // File upload: book file
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $allowed = ['pdf', 'epub', 'txt', 'docx'];
        $file_name = $_FILES['pdf_file']['name'];
        $file_tmp = $_FILES['pdf_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed)) {
            $error = "Invalid file type for book. Allowed: " . implode(", ", $allowed);
        } else {
            $new_file_name = time() . "_". preg_replace('/\s+/', '_', $file_name);
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                // Delete old file
                if (!empty($book['pdf_file']) && file_exists($upload_dir . $book['pdf_file'])) {
                    unlink($upload_dir . $book['pdf_file']);
                }
                $book['pdf_file'] = $new_file_name;
            } else {
                $error = "Failed to upload new book file.";
            }
        }
    }

    // File upload: cover image
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $allowed_img = ['jpg', 'jpeg', 'png', 'gif'];
        $img_name = $_FILES['cover_image']['name'];
        $img_tmp = $_FILES['cover_image']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));

        if (!in_array($img_ext, $allowed_img)) {
            $error = "Invalid cover image type. Allowed: " . implode(", ", $allowed_img);
        } else {
            $new_img_name = time() . "_". preg_replace('/\s+/', '_', $img_name);
            $cover_dir = "uploads/covers/";
            if (!is_dir($cover_dir)) mkdir($cover_dir, 0777, true);

            if (move_uploaded_file($img_tmp, $cover_dir . $new_img_name)) {
                // Delete old cover
                if (!empty($book['cover_image']) && file_exists($cover_dir . $book['cover_image'])) {
                    unlink($cover_dir . $book['cover_image']);
                }
                $book['cover_image'] = $new_img_name;
            } else {
                $error = "Failed to upload cover image.";
            }
        }
    }

    // Update database
    if (!$error) {
        $stmt = $pdo->prepare("
            UPDATE books 
            SET title = :title, author = :author, description = :description,
                isbn = :isbn, category = :category, pdf_file = :pdf_file, cover_image = :cover_image
            WHERE id = :id
        ");
        if ($stmt->execute([
            ':title' => $title,
            ':author' => $author,
            ':description' => $description,
            ':isbn' => $isbn,
            ':category' => $category,
            ':pdf_file' => $book['pdf_file'],
            ':cover_image' => $book['cover_image'] ?? null,
            ':id' => $book_id
        ])) {
            $success = "Book updated successfully!";
        } else {
            $error = "Database error while updating.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Edit Book</h2>

        <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>

        <form method="post" enctype="multipart/form-data">

            <div class="mb-3">
                <label>Titulli</label>
                <input name="title" class="form-control" required value="<?php echo htmlspecialchars($title); ?>">
            </div>

            <div class="mb-3">
                <label>Autori</label>
                <input name="author" class="form-control" required value="<?php echo htmlspecialchars($author); ?>">
            </div>

            <div class="mb-3">
                <label>ISBN</label>
                <input name="isbn" class="form-control" value="<?php echo htmlspecialchars($isbn); ?>">
                <small class="text-muted">ISBN-10 or ISBN-13 (optional)</small>
            </div>

            <div class="mb-3">
                <label>Kategoria</label>
                <select name="category" class="form-control" required>
                    <option value="">-- Select category --</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php if($category==$cat) echo "selected"; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Përshkrimi</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="mb-3">
                <label>Upload New Book File (optional)</label>
                <input type="file" name="pdf_file" class="form-control" accept=".pdf,.epub,.txt,.docx">
                <small>Current file: <?php echo htmlspecialchars($book['pdf_file']); ?></small>
            </div>

            <div class="mb-3">
                <label>Upload Cover Image (optional)</label>
                <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
                <?php if(!empty($book['cover_image'])): ?>
                    <small>Current cover: <?php echo htmlspecialchars($book['cover_image']); ?></small>
                <?php endif; ?>
            </div>

            <button class="btn btn-primary w-100">Update Book</button>
        </form>
    </div>
</div>

<?php render_footer(); ?>

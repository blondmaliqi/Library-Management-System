<?php
require_once 'functions.php';
require_once 'config.php';
require_role(1); // Only admin can upload books
render_header("Add New Book");

// Pre-set variables
$title = $author = $description = $category = $isbn = "";
$error = $success = "";

// Admin-defined categories
$categories = [
    "Histori", "Klasik", "Fantazi", "Ekonomi", "Biografi",
    "Poezi", "Për fëmijë", "Romancë", "Dramë", "Roman","Libra në gjuhën angleze"
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $isbn = trim($_POST['isbn']);

    // Check book file
    if (!isset($_FILES['book_file']) || $_FILES['book_file']['error'] != 0) {
        $error = "Please select a book file to upload.";
    } else {

        $allowed_files = ['pdf','epub','doc','docx','txt','mobi']; 
        $file_name = $_FILES['book_file']['name'];
        $file_tmp = $_FILES['book_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_files)) {
            $error = "Invalid file type. Allowed types: " . implode(", ", $allowed_files);
        } else {

            $new_file_name = time() . "_" . preg_replace('/\s+/', '_', $file_name);
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (!move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $error = "Failed to upload book file.";
            }
        }
    }

    // Cover image upload (optional)
    $cover_file_name = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $allowed_img = ['jpg','jpeg','png','gif'];
        $img_name = $_FILES['cover_image']['name'];
        $img_tmp = $_FILES['cover_image']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));

        if (!in_array($img_ext, $allowed_img)) {
            $error = "Invalid cover image type. Allowed: " . implode(", ", $allowed_img);
        } else {
            $new_img_name = time() . "_" . preg_replace('/\s+/', '_', $img_name);
            $cover_dir = "uploads/covers/";
            if (!is_dir($cover_dir)) mkdir($cover_dir, 0777, true);

            if (move_uploaded_file($img_tmp, $cover_dir . $new_img_name)) {
                $cover_file_name = $new_img_name;
            } else {
                $error = "Failed to upload cover image.";
            }
        }
    }

    // Insert into database
    if (!$error) {
        try {
            $sql = "INSERT INTO books (title, author, pdf_file, cover_image, description, category, isbn, uploaded_by, uploaded_at)
                    VALUES (:title, :author, :pdf_file, :cover_image, :description, :category, :isbn, :uploaded_by, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':pdf_file' => $new_file_name,
                ':cover_image' => $cover_file_name,
                ':description' => $description,
                ':category' => $category,
                ':isbn' => $isbn,
                ':uploaded_by' => $_SESSION['user_id']
            ]);

            $success = "Book uploaded successfully!";
            $title = $author = $description = $category = $isbn = "";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Shto Libër / File</h2>

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
                <small class="text-muted">Optional — Enter ISBN-10 or ISBN-13.</small>
            </div>

            <div class="mb-3">
                <label>Kategoria</label>
                <select name="category" class="form-control" required>
                    <option value="">-- Zgjidhni kategorinë --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php if ($category==$cat) echo "selected"; ?>>
                            <?php echo $cat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Përshkrimi</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="mb-3">
                <label>File</label>
                <input type="file" name="book_file" class="form-control" required>
                <small class="text-muted">Allowed: pdf, epub, doc, docx, txt, mobi</small>
            </div>

            <div class="mb-3">
                <label>Cover Image (optional)</label>
                <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
            </div>

            <button class="btn btn-primary w-100">Ngarkoni Librin</button>

        </form>
    </div>
</div>

<?php render_footer(); ?>

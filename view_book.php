<?php
require_once 'functions.php';
require_once 'config.php';
require_role(2); // Vetëm përdorues të kyçur

$user_id = $_SESSION['user_id'] ?? 0;

// ----------------- Kontroll ID libri -----------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    render_header("Gabim");
    echo "<div class='alert alert-danger'>Libri nuk u zgjodh.</div>";
    render_footer();
    exit();
}

$book_id = intval($_GET['id']);

// ----------------- Kontroll huazimi -----------------
$loanCheck = $pdo->prepare("
    SELECT id, due_date 
    FROM book_loans 
    WHERE user_id=? AND book_id=? AND status='borrowed'
");
$loanCheck->execute([$user_id, $book_id]);
$loan = $loanCheck->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    render_header("Nuk lejohet");
    echo "
        <div class='alert alert-warning'>
            📕 Duhet ta huazoni librin përpara se ta lexoni.
        </div>
        <a href='user_dashboard.php' class='btn btn-primary'>⬅️ Kthehu</a>
    ";
    render_footer();
    exit();
}

// ----------------- Merr librin -----------------
$stmt = $pdo->prepare("SELECT * FROM books WHERE id=?");
$stmt->execute([$book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    render_header("Gabim");
    echo "<div class='alert alert-danger'>Libri nuk u gjet.</div>";
    render_footer();
    exit();
}

// ----------------- Recently read -----------------
$stmt = $pdo->prepare("
    INSERT INTO recently_read (user_id, book_id, read_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE read_at = NOW()
");
$stmt->execute([$user_id, $book_id]);

// ----------------- Favorite -----------------
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id=? AND book_id=?");
$stmt->execute([$user_id, $book_id]);
$is_favorite = $stmt->rowCount() > 0;

// ----------------- HEADER -----------------
render_header(htmlspecialchars($book['title']));
?>

<div class="container mt-4">

    <h2><?php echo htmlspecialchars($book['title']); ?></h2>
    <p><strong>Autor:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
    <p><strong>Afati i kthimit:</strong> <?php echo htmlspecialchars($loan['due_date']); ?></p>

    <!-- Favoritet -->
    <a href="toggle_favorite.php?book_id=<?php echo $book_id; ?>"
       class="btn btn-<?php echo $is_favorite ? 'warning' : 'outline-warning'; ?> mb-2">
        <?php echo $is_favorite ? '★ Hiqe nga Favoritet' : '☆ Shto në Favoritet'; ?>
    </a>

    <!-- Full screen -->
    <button class="btn btn-dark mb-2" onclick="openFullscreen()">⛶ Full Screen</button>

    <hr>

    <!-- ================= PDF VIEWER ================= -->
    <?php if (!empty($book['pdf_file'])): ?>

        <div id="pdfContainer"
             style="width:100%; height:650px; overflow:auto; background:#f4f4f4; padding:10px;">
        </div>

        <!-- PDF.js nga CDN i besueshëm -->
        <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.9.179/build/pdf.min.js"></script>
        <script>
            // URL relative — punon kudo (localhost, subfolder, ose server)
const url = 'uploads/<?php echo htmlspecialchars($book['pdf_file']); ?>';

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.9.179/build/pdf.worker.min.js';

            const container = document.getElementById('pdfContainer');
            const scale = 1.2;

            pdfjsLib.getDocument(url).promise.then(pdf => {
                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    pdf.getPage(pageNum).then(page => {
                        const viewport = page.getViewport({ scale: scale });
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');

                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        canvas.style.display = "block";
                        canvas.style.margin = "0 auto 20px";

                        container.appendChild(canvas);

                        page.render({
                            canvasContext: context,
                            viewport: viewport
                        });
                    });
                }
            }).catch(err => {
                console.error(err);
                container.innerHTML =
                    "<div class='alert alert-danger'>Gabim në ngarkimin e PDF-it: " + err.message + "</div>";
            });

            function openFullscreen() {
                const elem = document.getElementById("pdfContainer");
                if (elem.requestFullscreen) elem.requestFullscreen();
                else if (elem.webkitRequestFullscreen) elem.webkitRequestFullscreen();
                else if (elem.msRequestFullscreen) elem.msRequestFullscreen();
            }

            // Çaktivizo right click për mbrojtje bazike
            document.addEventListener('contextmenu', e => e.preventDefault());
        </script>

    <?php else: ?>
        <div class="alert alert-warning">Nuk ka PDF për këtë libër.</div>
    <?php endif; ?>

</div>

<?php render_footer(); ?>

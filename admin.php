<?php
// --- DEBUG ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- INCLUDES & ADMIN CHECK ---
require_once 'functions.php';
require_role(1); // vetëm admin

// --- CONNECT TO DATABASE ---
require_once 'config.php';

// --- TIMEZONE ---
date_default_timezone_set('Europe/Belgrade');

// --- HANDLE APPROVE LOAN ---
if (isset($_GET['approve_loan']) && is_numeric($_GET['approve_loan'])) {
    $loan_id = (int)$_GET['approve_loan'];
    // Ndrysho statusin në 'approved' kur admini miraton huazimin
    $stmt = $pdo->prepare("UPDATE book_loans SET status='approved' WHERE id=?");
    $stmt->execute([$loan_id]);
    header("Location: admin.php");
    exit;
}

// --- HANDLE REMOVE BORROW OR RESERVATION ---
if (isset($_GET['remove_borrow']) && is_numeric($_GET['remove_borrow'])) {
    $loan_id = (int)$_GET['remove_borrow'];
    $stmt = $pdo->prepare("DELETE FROM book_loans WHERE id=?");
    $stmt->execute([$loan_id]);
    header("Location: admin.php");
    exit;
}

if (isset($_GET['remove_reserve']) && is_numeric($_GET['remove_reserve'])) {
    $reserve_id = (int)$_GET['remove_reserve'];
    $stmt = $pdo->prepare("DELETE FROM book_reservations WHERE id=?");
    $stmt->execute([$reserve_id]);
    header("Location: admin.php");
    exit;
}

// --- HANDLE RESET FINE ---
if (isset($_GET['reset_fine']) && is_numeric($_GET['reset_fine'])) {
    $loan_id = (int)$_GET['reset_fine'];
    $stmt = $pdo->prepare("UPDATE book_loans SET fine=0 WHERE id=?");
    $stmt->execute([$loan_id]);
    header("Location: admin.php");
    exit;
}

// --- FETCH PENDING LOANS (for approval) ---
// Merre huazimet që janë në pritje OSE kanë status NULL/bosh
$pending_stmt = $pdo->prepare("
    SELECT bl.id AS loan_id, bl.user_id, u.username, bl.book_id, b.title, 
           bl.borrow_date, bl.due_date, bl.fine, 
           COALESCE(bl.status, 'pending') AS status
    FROM book_loans bl
    JOIN users u ON bl.user_id = u.id
    JOIN books b ON bl.book_id = b.id
    WHERE bl.status IS NULL OR bl.status = '' OR bl.status = 'pending'
    ORDER BY bl.borrow_date DESC
");
$pending_stmt->execute();
$pending_loans = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH BORROWED BOOKS ---
$borrow_stmt = $pdo->prepare("
    SELECT bl.id AS loan_id, bl.user_id, u.username, bl.book_id, b.title, 
           bl.borrow_date, bl.due_date, bl.fine
    FROM book_loans bl
    JOIN users u ON bl.user_id = u.id
    JOIN books b ON bl.book_id = b.id
    WHERE bl.status = 'borrowed'
    ORDER BY bl.borrow_date DESC
");
$borrow_stmt->execute();
$borrowed_books = $borrow_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH RESERVED BOOKS ---
$reserve_stmt = $pdo->prepare("
    SELECT r.id AS reserve_id, r.user_id, u.username, r.book_id, b.title, r.reservation_date
    FROM book_reservations r
    JOIN users u ON r.user_id = u.id
    JOIN books b ON r.book_id = b.id
    ORDER BY r.reservation_date DESC
");
$reserve_stmt->execute();
$reserved_books = $reserve_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- RENDER HEADER ---
render_header("Admin Dashboard");
?>

<!-- ADMIN DASHBOARD -->
<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Mirë se vini Admin!</h2>
        <p>Kjo është qendra e kontrollit për sistemin e bibliotekës.</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-center bg-primary text-white mb-3">
            <div class="card-body">
                <h4 class="card-title">Shto libër të ri</h4>
                <p class="card-text">Ngarko skedarë PDF dhe detaje të librit.</p>
                <a href="add_book.php" class="btn btn-light">Shkoni te Ngarkimi</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-success text-white mb-3">
            <div class="card-body">
                <h4 class="card-title">Menaxho librat</h4>
                <p class="card-text">Editoni ose fshini sendet të librit.</p>
                <a href="manage_books.php" class="btn btn-light">Shkoni te Menaxhimi</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-info text-white mb-3">
            <div class="card-body">
                <h4 class="card-title">Menaxho përdoruesit</h4>
                <p class="card-text">Shikoni, editoni ose hiqni llogaritë e përdoruesve.</p>
                <a href="manage_users.php" class="btn btn-light">Shkoni te Lista e Përdoruesve</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-warning text-white mb-3">
            <div class="card-body">
                <h4 class="card-title">Menaxho Huazimet</h4>
                <p class="card-text">Miratoni ose fshini kërkesat e huazimit.</p>
                <a href="#pending-loans-section" class="btn btn-light">Shiko Huazimet</a>
            </div>
        </div>
    </div>
</div>

<!-- DEBUG INFO (mund ta fshish më vonë) -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5>📊 Informacionet</h5>
            </div>
            <div class="card-body">
                <p><strong>Huazime në pritje:</strong> <?php echo count($pending_loans); ?></p>
                <p><strong>Huazime aktive:</strong> <?php echo count($borrowed_books); ?></p>
               
                <?php if (count($pending_loans) > 0): ?>
                    <p><strong>Shembull huazimi:</strong> ID <?php echo $pending_loans[0]['loan_id']; ?>, 
                    Përdoruesi: <?php echo htmlspecialchars($pending_loans[0]['username']); ?>, 
                    Libri: <?php echo htmlspecialchars($pending_loans[0]['title']); ?>, 
                    Status: <?php echo $pending_loans[0]['status']; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- PENDING LOANS SECTION -->
<div class="row mt-5" id="pending-loans-section">
    <div class="col-12">
        <h3>📋 Huazimet në Pritje për Miratim (<?php echo count($pending_loans); ?> total)</h3>
        <?php if (count($pending_loans) > 0): ?>
        <table class="table table-bordered table-hover">
            <thead>
                <tr class="table-dark">
                    <th class="text-dark"></th>
                    <th class="text-dark">Përdoruesi</th>
                    <th class="text-dark">Libri</th>
                    <th class="text-dark">Data e Kërkesës</th>
                    <th class="text-dark">Afati i Propozuar</th>
                    <th class="text-dark">Statusi</th>
                    <th class="text-dark">Veprime</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pending_loans as $index => $p): ?>
                    <tr>
                        <td><?php echo $index+1; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($p['username']); ?></strong>
                            <br><small class="text-muted">ID: <?php echo $p['user_id']; ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                            <br><small class="text-muted">Libri ID: <?php echo $p['book_id']; ?></small>
                        </td>
                        <td><?php echo date('d-m-Y H:i', strtotime($p['borrow_date'])); ?></td>
                        <td>
                            <?php echo date('d-m-Y', strtotime($p['due_date'])); ?>
                            <br><small class="text-muted">
                                <?php 
                                    $days_left = floor((strtotime($p['due_date']) - time()) / (60 * 60 * 24));
                                    if ($days_left > 0) {
                                        echo "($days_left ditë të mbetura)";
                                    } else {
                                        echo "(Ka skaduar!)";
                                    }
                                ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($p['status'] == 'pending' || $p['status'] == '' || $p['status'] == null): ?>
                                <span class="badge bg-warning">Për Miratim</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($p['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="admin.php?approve_loan=<?php echo $p['loan_id']; ?>" 
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Dëshiron ta miratosh këtë huazim? Libri do të bëhet i disponueshëm për përdoruesin.')"
                                   title="Mirato huazimin">
                                    <i class="bi bi-check-circle"></i> Mirato
                                </a>
                                <a href="admin.php?remove_borrow=<?php echo $p['loan_id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Dëshiron ta fshish këtë kërkesë për huazim?')"
                                   title="Fshi kërkesën">
                                    <i class="bi bi-trash"></i> Fshi
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="alert alert-info">
                <h5>🎉 Nuk ka huazime në pritje për miratim!</h5>
                <p>Të gjitha kërkesat janë përpunuar ose nuk ka kërkesa të reja.</p>
                <p>Kur një përdorues dërgon një kërkesë për huazim, ajo do të shfaqet këtu.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Borrowed Books with fines -->
<div class="row mt-5">
    <div class="col-12">
        <h3>📖 Librat e Huazuar (<?php echo count($borrowed_books); ?> total)</h3>
        <?php if (count($borrowed_books) > 0): ?>
        <table class="table table-bordered table-hover">
            <thead class="bg-black text-white">
                <tr>
                    <th>#</th>
                    <th>Përdoruesi</th>
                    <th>Libri</th>
                    <th>Huazuar më</th>
                    <th>Afati</th>
                    <th>Gjobë (€)</th>
                    <th>Veprime</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($borrowed_books as $index => $b): ?>
                    <?php
                        // Llogarit gjobën aktuale (7 ditë falas + 5€/ditë)
                        $today = new DateTime();
                        $due_date = new DateTime($b['due_date']);
                        $fine = 0;
                        if ($today > $due_date) {
                            $daysLate = $today->diff($due_date)->days;
                            $lateDays = max(0, $daysLate - 7); // 7 ditë falas
                            $fine = $lateDays * 5;
                        }

                        // Nëse gjoba në DB është më e madhe, përdor atë
                        if (isset($b['fine']) && $b['fine'] > $fine) {
                            $fine = $b['fine'];
                        }
                        
                        // Llogarit ditët e mbetura
                        $days_remaining = floor((strtotime($b['due_date']) - time()) / (60 * 60 * 24));
                        $due_class = ($days_remaining < 0) ? 'text-danger' : (($days_remaining < 3) ? 'text-warning' : 'text-success');
                    ?>
                    <tr>
                        <td><?php echo $index+1; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($b['username']); ?></strong>
                            <br><small class="text-muted">ID: <?php echo $b['user_id']; ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($b['title']); ?></strong>
                            <br><small class="text-muted">Libri ID: <?php echo $b['book_id']; ?></small>
                        </td>
                        <td><?php echo date('d-m-Y', strtotime($b['borrow_date'])); ?></td>
                        <td class="<?php echo $due_class; ?>">
                            <?php echo date('d-m-Y', strtotime($b['due_date'])); ?>
                            <br><small>
                                <?php 
                                    if ($days_remaining > 0) {
                                        echo "($days_remaining ditë të mbetura)";
                                    } else {
                                        echo "(<strong>" . abs($days_remaining) . " ditë vonesë</strong>)";
                                    }
                                ?>
                            </small>
                        </td>
                        <td class="<?php echo $fine > 0 ? 'text-danger fw-bold' : 'text-success'; ?>">
                            <?php echo $fine; ?> €
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="admin.php?remove_borrow=<?php echo $b['loan_id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Dëshiron ta fshish këtë huazim? Kjo do të shënojë librin si të kthyer.')"
                                   title="Shëno si të kthyer">
                                    <i class="bi bi-check-circle"></i> Ktheje
                                </a>
                                <?php if ($fine > 0): ?>
                                    <a href="admin.php?reset_fine=<?php echo $b['loan_id']; ?>" 
                                       class="btn btn-sm btn-warning"
                                       onclick="return confirm('Dëshiron ta rivendosësh gjobën në 0?')"
                                       title="Rivendos gjobën">
                                        <i class="bi bi-arrow-clockwise"></i> Rivendos
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="alert alert-info">
                <h5>📚 Nuk ka libra të huazuar aktualisht</h5>
                <p>Asnjë përdorues nuk ka libra të huazuar në këtë moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>



<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h5 class="card-title">Huazime të Gjitha</h5>
                <h2><?php 
                    $total_loans = $pdo->query("SELECT COUNT(*) FROM book_loans")->fetchColumn();
                    echo $total_loans;
                ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <h5 class="card-title">Në Pritje</h5>
                <h2><?php echo count($pending_loans); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger">
            <div class="card-body text-center">
                <h5 class="card-title">Me Gjobë</h5>
                <h2><?php 
                    $fines = $pdo->query("SELECT COUNT(*) FROM book_loans WHERE fine > 0")->fetchColumn();
                    echo $fines;
                ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

<style>
    .table th {
        background-color: #f8f9fa;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.4em 0.8em;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .btn-group .btn {
        margin-right: 5px;
    }
    .btn-group .btn:last-child {
        margin-right: 0;
    }
</style>

<?php
render_footer();
?>
<?php
require_once 'functions.php';
require_once 'config.php';
render_header("Sign Up");

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST['terms'])) {
        $error = "Ju duhet të pranoni Kushtet dhe Rregullat për të regjistruar llogarinë.";
    } else {

        $username = trim($_POST["username"]);
        $email    = trim($_POST["email"]);
        $password = trim($_POST["password"]);

        if (empty($username) || empty($email) || empty($password)) {
            $error = "Të gjitha fushat janë të detyrueshme!";
        } elseif (strlen($password) < 5) {
            $error = "Fjalëkalimi duhet të jetë të paktën 5 karaktere!";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->rowCount() > 0) {
                $error = "Username ose Email ekziston!";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, role, created_at)
                    VALUES (?, ?, ?, 2, NOW())
                ");
                if ($stmt->execute([$username, $email, $password])) {
                    header("Location: login.php?registered=1");
                    exit;
                } else {
                    $error = "Diçka shkoi gabim.";
                }
            }
        }
    }
}
?>

<!-- Bootstrap Icons (eye icon) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background: url('blond.jpg') no-repeat center center fixed;
    background-size: cover;
}

.signup-box {
    background: rgba(255, 255, 255, 0.90);
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 0 18px rgba(0,0,0,0.3);
    backdrop-filter: blur(4px);
}

.eye-icon {
    cursor: pointer;
}
</style>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 signup-box">
        <h2 class="text-center mb-4">Sign Up</h2>

        <?php if ($error): ?>
            <div class='alert alert-danger'><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label>Username</label>
                <input name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input name="email" type="email" class="form-control" required>
            </div>

            <!-- ======================= -->
            <!-- PASSWORD WITH EYE ICON -->
            <!-- ======================= -->
            <div class="mb-3">
                <label>Password</label>
                <div class="input-group">
                    <input id="password" name="password" type="password" class="form-control" required minlength="5">
                    <span class="input-group-text eye-icon" id="togglePassword">
                        <i class="bi bi-eye-fill"></i>
                    </span>
                </div>
                <small class="text-muted">Minimum 5 karaktere</small>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    Kam lexuar dhe pranoj 
                    <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Kushtet dhe Rregullat</a>
                </label>
            </div>

            <button class="btn btn-primary w-25" type="submit">Sign Up</button>
        </form>
    </div>
</div>

<!-- Modal Kushtet dhe Rregullat -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Kushtet dhe Rregullat e Përdorimit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
       <h6>1. Pranimi i kushteve</h6>
        <p>Duke përdorur këtë faqe, ju pranoni këto rregulla.</p>

        <h6>2. Qëllimi</h6>
        <p>Faqja përdoret për kërkimin, huazimin dhe menaxhimin e librave të bibliotekës.</p>

        <h6>3.  Llogaritë e Përdoruesve</h6>
        <p>Duhet të jepni të dhëna të sakta gjatë regjistrimit. Jeni përgjegjës për ruajtjen e fjalëkalimit dhe llogarisë suaj.</p>

        <h6>4. Privatësia</h6>
        <p>Të dhënat tuaja përdoren vetëm për shërbimet e bibliotekës dhe nuk shpërndahen pa leje.</p>

        <h6>5. Huazimi</h6>
        <p>Çdo libër ka afat kthimi. Vonesat mund të sjellin gjoba ose mbyllje të llogarisë.</p>
         
        <h6>6. Ndalohet</h6>
        <p>Përdorimi i sistemit për qëllime të paligjshme, ndryshimi apo fshirja e të dhënave pa autorizim, dëmtimi i sistemit ose publikimi i informacionit të rremë. <p>
          
       <h6>7. Pronësia</h6>
<p>Të gjitha materialet dhe përmbajtja e sistemit janë pronë e bibliotekës.</p>

<h6>8. Ndryshimet</h6>
<p>Këto kushte mund të ndryshojnë në çdo kohë.</p>

<h6>9. Komunikimi me administratorin</h6>
<p>Dërgimi i mesazheve për mbështetje duhet të bëhet në mënyrë të sjellshme. Mesazhet ofenduese, kërcënuese, fyese ose me gjuhë të papërshtatshme mund të sjellin paralajmërim, pezullim, ose mbyllje të llogarisë.</p>

<h6>10. Kontakt</h6>
<p>Për pyetje dhe ndihmë, na dërgo mesazh tek kontaktoni mbështetjen tek qendra e ndihmës.</p>

        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mbyll</button>
      </div>
    </div>
  </div>
</div>

<?php render_footer(); ?>

<!-- =========================== -->
<!-- EYE ICON JAVASCRIPT        -->
<!-- =========================== -->
<script>
document.getElementById("togglePassword").onclick = function () {
    const password = document.getElementById("password");
    const icon = this.querySelector("i");

    if (password.type === "password") {
        password.type = "text";
        icon.classList.replace("bi-eye-fill", "bi-eye-slash-fill");
    } else {
        password.type = "password";
        icon.classList.replace("bi-eye-slash-fill", "bi-eye-fill");
    }
};
</script>

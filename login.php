<?php
require_once 'functions.php';
require_once 'config.php';
render_header("Login");

if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

// Show success alert if redirected from signup
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Regjistrimi u krye me sukses! Mund të hyni tani.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['terms'])) {
        $error = "Ju duhet të pranoni Kushtet dhe Rregullat për të hyrë.";
    } else {

        $username = trim($_POST["username"]);
        $password = $_POST["password"];

        $stmt = $pdo->prepare("
            SELECT id, username, password, role 
            FROM users 
            WHERE username = :u AND password = :p
            LIMIT 1
        ");
        $stmt->bindParam(":u", $username);
        $stmt->bindParam(":p", $password);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 1) {
                header("Location: admin.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            $error = "Username ose password i gabuar.";
        }
    }
}
?>

<!-- ========================= -->
<!--  ADD BOOTSTRAP ICONS      -->
<!-- ========================= -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<!-- ========================= -->
<!--  BACKGROUND IMAGE CSS     -->
<!-- ========================= -->
<style>
body {
    background: url('blond.jpg') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
}

.login-box {
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
    <div class="col-md-6 login-box">
        <h2 class="text-center mb-4">Login</h2>

        <?php if ($success): ?>
            <div class='alert alert-success'><?= $success ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class='alert alert-danger'><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label>Username</label>
                <input name="username" class="form-control" required>
            </div>

            <!-- ========================= -->
            <!--  PASSWORD WITH EYE ICON   -->
            <!-- ========================= -->
            <div class="mb-3">
                <label>Password</label>
                <div class="input-group">
                    <input id="password" name="password" type="password" class="form-control" required>
                    <span class="input-group-text eye-icon" id="togglePassword">
                        <i class="bi bi-eye-fill"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    Kam lexuar dhe pranoj 
                    <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Kushtet dhe Rregullat</a>
                </label>
            </div>

            <button class="btn btn-primary w-25" type="submit">Login</button>
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

<!-- ========================= -->
<!--  EYE ICON JS LOGIC        -->
<!-- ========================= -->
<script>
document.getElementById("togglePassword").onclick = function () {
    const password = document.getElementById("password");
    const icon = this.querySelector("i");

    if (password.type === "password") {
        password.type = "text";
        icon.classList.remove("bi-eye-fill");
        icon.classList.add("bi-eye-slash-fill");
    } else {
        password.type = "password";
        icon.classList.remove("bi-eye-slash-fill");
        icon.classList.add("bi-eye-fill");
    }
};
</script>

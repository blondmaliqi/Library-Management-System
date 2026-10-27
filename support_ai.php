<?php
// support_ai.php
header('Content-Type: application/json; charset=utf-8');

// Shtoni këto në fillim për siguri
error_reporting(0); // Fshini në development
ini_set('display_errors', 0);
set_time_limit(30);

require_once 'config.php';   // duhet të përkufizojë OPENAI_API_KEY dhe $pdo
require_once 'functions.php'; // duhet të startojë sesionin dhe të sigurojë helpers

// Session handling më i sigurt
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Lexo dhe validizo input në mënyrë më të sigurt
$input = json_decode(file_get_contents('php://input'), true);
$question = trim($input['question'] ?? '');

if (!$input || !is_array($input) || $question === '') {
    http_response_code(400);
    echo json_encode(['answer' => 'Ju lutem shkruani një pyetje.']);
    exit;
}

// Kontrollo gjatësinë e pyetjes
if (strlen($question) > 1000) {
    http_response_code(400);
    echo json_encode(['answer' => 'Pyetja është shumë e gjatë. Ju lutem shkruani një pyetje më të shkurtër.']);
    exit;
}

// Rate-limit: 1 request per 0.8 sekonda
$now = microtime(true);
if (!isset($_SESSION['last_ai_time'])) {
    $_SESSION['last_ai_time'] = 0;
}
if ($now - $_SESSION['last_ai_time'] < 0.8) {
    http_response_code(429);
    echo json_encode(['error' => 'Ngadalëso pak, po përpunojmë pyetjen tuaj.']);
    exit;
}
$_SESSION['last_ai_time'] = $now;

// Merr librat e blerë të përdoruesit
try {
    $stmt = $pdo->prepare("
        SELECT b.title 
        FROM user_purchases up 
        JOIN books b ON up.book_id = b.id 
        WHERE up.user_id = ? AND up.paid = 1
    ");
    $stmt->execute([$user_id]);
    $purchased = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("AI Chat - Database error (purchased): " . $e->getMessage());
    $purchased = [];
}

// Merr librat e huazuar të përdoruesit
try {
    $stmt2 = $pdo->prepare("
        SELECT b.title 
        FROM book_loans bl 
        JOIN books b ON bl.book_id = b.id 
        WHERE bl.user_id = ? AND bl.status='borrowed'
    ");
    $stmt2->execute([$user_id]);
    $borrowed = $stmt2->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("AI Chat - Database error (borrowed): " . $e->getMessage());
    $borrowed = [];
}

// Përgatit system prompt me sugjerime të panelit dhe librat e përdoruesit
$systemPrompt = "Ju jeni një asistent miqësor për një faqe Biblioteke Online të përqendruar në librat dixhitalë.
Përgjigjuni vetëm për përdorimin e platformës dhe librave të përdoruesit.
Kur është e mundur, sugjeroni përdoruesit të klikojë butonët ose seksionet specifike në panel.

Tema që mund të mbuloni:

1. SI TË PËRDORET BIBLIOTEKA:
   • Klikoni '📚 Biblioteka' në meny për të parë të gjithë librat
   • Përdorni kërkimin për të gjetur libra sipas titullit, autorit ose ISBN-së
   • Filtroni sipas kategorive për rezultate më të shpejta

2. SISTEMI I HUAZIMEVE (të rëndësishme):
   • Të gjithë mund të kërkojnë huazim për çdo libër (nuk ka kufizime kopjesh)
   • Pas klikimit të \"📚 Kërko Huazimin\", prisni miratimin nga admini (30 minuta-1 orë)
   • Kur admini miraton, do të shihni \"✅ Konfirmo Huazimin\"
   • Pas konfirmimit, klikoni \"📖 Lexoni Librin\" për të hapur Librin

3. SISTEMI I GJOBAVE (të rëndësishme):
   • Periudha e huazimit: 7 ditë TOTAL
   • Ditët 1-7: FALAS (0€ gjobë)
   • Pas 7 ditësh: 5€ për ÇDO DITË vonesë
   • Shembull: 9 ditë = 10€ gjobë
   • 15 ditë = 40€ gjobë
   • Gjoba shfaqet në mesazhin e kuq/verdhë në krye të faqes

4. MENAXHIMI I LIBRAVE:
   • Librat e preferuar: Shtoni/hiqni nga \"⭐ Të preferuarat\"
   • Lexuar së fundmi: Shikoni në \"🕒 Lexuar së fundmi\"
   • Librat aktualisht të huazuar: Shikoni në \"📚 Librat e huazuar\"

5. PROFILI DHE SETTINGS:
   • Ndryshoni të dhënat personale në \"👤 Profili\"
   • Ndryshoni username fjalëkalimin ose email-in

6. PAGESA E GJOBAVE:
   • Paguani gjobat në \"💳 Paguaj Gjobat\"
   • Forma e kartës kërkon: Emër, Numër karte, Datë skadence (MM/YY), CVV
   • Butoni \"Paguaj\" aktivizohet VETËM nëse keni gjoba (> 0€)
   • Nëse gjoba është 0€, mund të shihni formën por NUK mund të paguani

7. LAJMËRIME DHE NDHIMË:
   • Përdorni \"❓ Qendra e ndihmës\" për udhëzime të hollësishme
   • Përdorni butonin 💬 për të biseduar me këtë asistent

8. ÇFARË TË BËNI NËSE:
   • Nuk mund ta lexoni librin: Kontrolloni nëse është miratuar dhe konfirmuar
   • Nuk shfaqet butoni për huazim: Kontrolloni nëse e keni tashmë huazuar
   • Keni gjoba: Shkoni te \"💳 Paguaj Gjobat\" ose kthejeni librat shpejt

**MOS përgjigjuni për:**
• Këshilla mjekësore, ligjore ose financiare
• Ndihmë me fjalëkalime (përdorni \"👤 Profili\")
• Pyetje për librat që nuk janë në bibliotekë
• Temat jashtë bibliotekës dixhitale";

// Konteksti i librave
$booksContext = '';
if (!empty($purchased)) {
    $purchasedTitles = array_map('trim', $purchased);
    $booksContext .= "Librat e blerë nga përdoruesi: " . implode(', ', $purchasedTitles) . ". ";
}
if (!empty($borrowed)) {
    $borrowedTitles = array_map('trim', $borrowed);
    $booksContext .= "Librat e huazuar aktualisht: " . implode(', ', $borrowedTitles) . ".";
}

// Mesazhet për OpenAI
$messages = [
    ["role" => "system", "content" => $systemPrompt . "\n\n" . $booksContext],
    ["role" => "user", "content" => $question]
];

// Përgatit kërkesën
$payload = [
    "model" => "gpt-4o-mini",
    "messages" => $messages,
    "max_tokens" => 700,
    "temperature" => 0.2
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: " . "Bearer " . OPENAI_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Siguri shtesë

$response = curl_exec($ch);
$err = curl_error($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || $httpcode >= 400 || !$response) {
    error_log("AI Chat - OpenAI API Error: HTTP $httpcode - " . substr($err, 0, 200));
    http_response_code(500);
    echo json_encode(['answer' => 'Na vjen keq, shërbimi i AI nuk është i disponueshëm për momentin.']);
    exit;
}

$data = json_decode($response, true);
$answer = $data['choices'][0]['message']['content'] ?? 'Na vjen keq, asnjë përgjigje nga AI.';

// Pastro pak përgjigjen
$answer = trim($answer);
if (empty($answer)) {
    $answer = "Nuk mora asnjë përgjigje. Ju lutem provoni përsëri.";
}

echo json_encode(['answer' => $answer]);
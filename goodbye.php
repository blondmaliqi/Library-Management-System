<?php
// Optional: start session only if needed
// session_start();
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Llogaria u fshi</title>

    <!-- Bootstrap (if your site uses it) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <style>
        body {
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        .goodbye-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 420px;
        }
        .goodbye-box h2 {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .goodbye-box p {
            color: #555;
        }
        .btn-home {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="goodbye-box">
    <h2>✔ Llogaria u fshi me sukses</h2>
    <p>Na vjen keq që po largohesh. Faleminderit që ishe pjesë e platformës sonë!</p>
    
    <a href="index.php" class="btn btn-primary btn-home">Kthehu në faqen kryesore</a>
</div>

</body>
</html>

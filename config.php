<?php
date_default_timezone_set('Europe/Belgrade');


define("DB_SERVER",   "localhost");       // ← ndryshoi
define("DB_USERNAME", "root");            // ← ndryshoi
define("DB_PASSWORD", "2009");                // ← ndryshoi (bosh)
define("DB_NAME",     "library_db");





try {
    $pdo = new PDO(
        "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>

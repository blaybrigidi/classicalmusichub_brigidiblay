<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'brigidi.blay');
define('DB_PASS', 'TheRizzlyBear98');
define('DB_NAME', 'webtech_fall2024_brigidi_blay');

try {
    // PDO connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // mysqli connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<?php
$config = require '../config/config.php';

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
                   $config['database']['username'], 
                   $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database. Error: " . $e->getMessage());
}

function getOriginalUrl($shortCode, $pdo) {
    $stmt = $pdo->prepare("SELECT original_url FROM shortlinks WHERE short_code = ?");
    $stmt->execute([$shortCode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['original_url'] : null;
}

// Redirect if accessing the root path
if ($_SERVER['REQUEST_URI'] === '/') {
    header("Location: https://masdzub.com");
    exit(); // Ensure no further code is executed after the redirect
}

// Redirect if accessing the short link directly
if (isset($_SERVER['REQUEST_URI'])) {
    $parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $shortCode = end($parts); // Get the last segment of the URL

    if ($shortCode) {
        $originalUrl = getOriginalUrl($shortCode, $pdo);
        if ($originalUrl) {
            header("Location: " . $originalUrl);
            exit();
        } else {
            // Redirect to the 404 page
            header("Location: /404.shtml");
            exit();
        }
    }
}
?>
<?php
session_start();
$config = require './config/config.php';

// Session timeout set to 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
}
$_SESSION['last_activity'] = time(); // Update last activity time

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
                   $config['database']['username'], 
                   $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database. Error: " . $e->getMessage());
}

// Register user function with bcrypt password hashing
function registerUser($username, $password, $pdo) {
    // Check if the username already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        return false; // Username already exists
    }

    // Hash the password using bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert user with hashed password
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hashedPassword]);

    return true; // Registration successful
}

// Verify user function using bcrypt password verification
function verifyUser($username, $password, $pdo) {
    // Get the user's hashed password from the database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $hashedPassword = $stmt->fetchColumn();

    if ($hashedPassword && password_verify($password, $hashedPassword)) {
        // Set session on successful verification
        $_SESSION['username'] = $username;
        return true; // Login successful
    }

    return false; // Invalid username or password
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
}

// Generate short link function
function generateShortLink($longUrl, $pdo) {
    // Generate a random short code
    $shortCode = substr(md5(uniqid()), 0, 6); // Change length as needed
    $shortUrl = "http://s1s.uk/" . $shortCode; // Change to your short link domain

    // Insert the long URL and short URL into the database
    $stmt = $pdo->prepare("INSERT INTO links (long_url, short_url) VALUES (?, ?)");
    $stmt->execute([$longUrl, $shortUrl]);

    return $shortUrl; // Return the generated short URL
}

// Get all short links function
function getShortLinks($pdo) {
    $stmt = $pdo->prepare("SELECT long_url, short_url FROM links");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
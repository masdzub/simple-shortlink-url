<?php
require 'functions.php';

// Check for login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (verifyUser($username, $password, $pdo)) {
        header("Location: dashboard.php"); // Redirect to dashboard after login
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

// Check for registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (registerUser($username, $password, $pdo)) {
        header("Location: login.php"); // Redirect to login after registration
        exit;
    } else {
        $error = "Username already exists.";
    }
}

// Check for logout
if (isset($_GET['logout'])) {
    logout();
    header("Location: index.php"); // Redirect to login page after logout
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-sm">

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" class="mb-6">
            <h2 class="text-xl font-semibold mb-4">Login</h2>
            <input type="text" name="username" placeholder="Username" required class="w-full p-3 mb-3 border rounded">
            <input type="password" name="password" placeholder="Password" required class="w-full p-3 mb-3 border rounded">
            <button type="submit" name="login" class="bg-blue-500 text-white w-full p-3 rounded">Login</button>
        </form>

        <!-- Register Form 
        <form method="POST">
            <h2 class="text-xl font-semibold mb-4">Register</h2>
            <input type="text" name="username" placeholder="Username" required class="w-full p-3 mb-3 border rounded">
            <input type="password" name="password" placeholder="Password" required class="w-full p-3 mb-3 border rounded">
            <button type="submit" name="register" class="bg-green-500 text-white w-full p-3 rounded">Register</button>
        </form>
        -->

    </div>
</div>
</body>
</html>
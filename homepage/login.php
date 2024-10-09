<?php
session_start();
require './config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use password_verify() to check the input password against the hashed password in config
    if ($username === $config['admin']['username'] && password_verify($password, $config['admin']['password'])) {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-sm">
            <h1 class="text-2xl text-center">Login</h1>
            <?php if (isset($error)) : ?>
                <div class="text-red-600"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" class="w-full p-2 mt-4 border-2" required>
                <input type="password" name="password" placeholder="Password" class="w-full p-2 mt-4 border-2" required>
                <button type="submit" class="w-full bg-blue-600 text-white p-2 mt-4">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
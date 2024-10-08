<?php
session_start(); // Start session to hold short link temporarily
$config = require './config/config.php';

// Set session timeout to 30 minutes
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

function createShortLink($originalUrl, $shortCode, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shortlinks WHERE short_code = ?");
    $stmt->execute([$shortCode]);
    $exists = $stmt->fetchColumn();

    if ($exists > 0) {
        return false; // Slug already exists
    }

    $stmt = $pdo->prepare("INSERT INTO shortlinks (original_url, short_code, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$originalUrl, $shortCode]);
    return true; // Successful insertion
}

// Check if custom slug option is enabled
$isCustom = isset($_GET['custom']) && $_GET['custom'] === 'true';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $originalUrl = $_POST['url'];
    $customSlug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $shortCode = !empty($customSlug) ? $customSlug : substr(md5(uniqid(rand(), true)), 0, 6);

    if (!createShortLink($originalUrl, $shortCode, $pdo)) {
        $_SESSION['error'] = "The custom slug already exists. Please choose a different one.";
        header("Location: /?custom=" . ($isCustom ? 'true' : 'false')); // Redirect to the same page with custom parameter
        exit; // Ensure no further code is executed after the redirect
    }

    $_SESSION['shortLink'] = htmlspecialchars($config['domains']['shortlink'] . "/" . $shortCode);
    
    // Retrieve previous links from session if exists
    $previousLinks = isset($_SESSION['previousLinks']) ? $_SESSION['previousLinks'] : [];
    $previousLinks[] = $_SESSION['shortLink']; // Add the newly generated link to the history

    // Keep only the last 5 links
    if (count($previousLinks) > 5) {
        $previousLinks = array_slice($previousLinks, -5); // Retain only the last 5 links
    }

    $_SESSION['previousLinks'] = $previousLinks; // Update the session with the limited history
    header("Location: /?custom=" . ($isCustom ? 'true' : 'false')); // Redirect to the same page with custom parameter
    exit; // Ensure no further code is executed after the redirect
}

// Retrieve previous links from session
$previousLinks = isset($_SESSION['previousLinks']) ? $_SESSION['previousLinks'] : [];

// Get error message from session
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']); // Clear error message after displaying it
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Short Link Generator</title>
    <link rel="icon" type="image/x-icon" href="https://ucarecdn.com/90cb273d-ec6c-4516-adc4-cfc07442805f/-/preview/100x100/">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.10/clipboard.min.js"></script>
    <style>
        body {
            background-color: #f7f9fc;
        }
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.5s;
        }
        .fade-enter, .fade-leave-to {
            opacity: 0;
        }
    </style>
</head>
<body>
    <div id="app" class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-lg transition-transform transform hover:scale-105 duration-300">
            <h1 @click="resetShortLink" class="text-3xl text-center text-blue-600 cursor-pointer hover:text-blue-800 transition-colors duration-300">Create a Short Link</h1>

            <form @submit.prevent="generateShortLink">
                <input type="url" v-model="url" placeholder="Enter your URL" required class="mt-6 w-full p-4 border-2 border-gray-300 rounded-md focus:border-blue-600 focus:ring-1 focus:ring-blue-500 transition duration-200">

                <!-- Show custom slug input only if ?custom=true is in the URL -->
                <transition name="fade">
                    <div v-if="isCustom" class="mt-4">
                        <input type="text" v-model="slug" placeholder="Custom Slug (optional)" class="w-full p-4 border-2 border-gray-300 rounded-md focus:border-blue-600 focus:ring-1 focus:ring-blue-500 transition duration-200">
                    </div>
                </transition>

                <button type="submit" class="mt-6 w-full bg-blue-600 text-white p-4 rounded-md hover:bg-blue-500 transition-colors duration-300 shadow-md hover:shadow-lg">Generate Short Link</button>
            </form>

            <transition name="fade">
                <div v-if="error" class="mt-4 p-4 border-2 border-red-600 rounded-md bg-red-50 text-red-600">
                    {{ error }}
                </div>
            </transition>

            <transition name="fade">
                <div v-if="shortLink" class="mt-4 p-4 border-2 border-blue-600 rounded-md bg-blue-50 flex justify-between items-center shadow-md">
                    <strong class="text-blue-800">Short link generated:</strong>
                    <span class="text-blue-800 font-semibold">{{ shortLink }}</span>
                    <button class="bg-green-600 text-white p-2 rounded-md hover:bg-green-500 transition-colors duration-300 copy-btn" :data-clipboard-text="shortLink">
                        <i class="fas fa-copy"></i> {{ copyButtonText }}
                    </button>
                </div>
            </transition>

            <transition name="fade">
                <div v-if="previousLinks.length > 0" class="mt-6">
                    <h2 class="text-lg font-semibold text-gray-800">Generated Short Links History:</h2>
                    <ul class="mt-2 space-y-2">
                        <li v-for="(link, index) in previousLinks" :key="index" class="p-4 border-2 border-gray-300 rounded-md mt-2 flex justify-between items-center bg-gray-50 hover:bg-gray-100 transition duration-200 shadow-md">
                            <span class="text-gray-800">{{ link }}</span>
                            <button class="bg-green-600 text-white p-2 rounded-md hover:bg-green-500 transition-colors duration-300 copy-btn" :data-clipboard-text="link">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </li>
                    </ul>
                </div>
            </transition>
        </div>
    </div>

    <script>
        new Vue({
            el: '#app',
            data: {
                url: '',
                slug: '',
                shortLink: '<?= isset($_SESSION['shortLink']) ? $_SESSION['shortLink'] : ''; ?>', // Prepopulate with PHP session value
                previousLinks: <?= json_encode(array_reverse($previousLinks)); ?>, // Get previous links from PHP in reverse order
                copyButtonText: 'Copy', // Initial button text
                error: '<?= addslashes($error); ?>', // Get error message from PHP
                isCustom: <?= $isCustom ? 'true' : 'false'; ?> // Determine if custom slug option is active
            },
            methods: {
                generateShortLink() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/'; // Adjust to your action URL
                    const urlInput = document.createElement('input');
                    urlInput.type = 'hidden';
                    urlInput.name = 'url';
                    urlInput.value = this.url;
                    form.appendChild(urlInput);
                    const slugInput = document.createElement('input');
                    slugInput.type = 'hidden';
                    slugInput.name = 'slug';
                    slugInput.value = this.slug;
                    form.appendChild(slugInput);
                    document.body.appendChild(form);
                    form.submit(); // Submit the form programmatically
                },
                resetShortLink() {
                    this.shortLink = ''; // Reset short link
                }
            }
        });

        // Initialize Clipboard.js for copying links
        new ClipboardJS('.copy-btn').on('success', function(e) {
            const button = e.trigger;
            const app = document.querySelector('#app').__vue__;
            app.copyButtonText = 'Copied!'; // Change button text
            setTimeout(() => {
                app.copyButtonText = 'Copy'; // Reset after 2 seconds
            }, 2000);
            e.clearSelection();
        });
    </script>
</body>
</html>
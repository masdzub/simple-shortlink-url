<?php
session_start();
$config = require './config/config.php';

// Session timeout set to 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
}
$_SESSION['last_activity'] = time();

try {
    $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4", 
                   $config['database']['username'], 
                   $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database. Error: " . $e->getMessage());
}

// Limit history to 5 short links
function getShortLinks($pdo) {
    $stmt = $pdo->prepare("SELECT long_url, short_url, uploaded_file FROM links ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate short link function
function generateShortLink($longUrl, $pdo, $config, $customSlug = null, $uploadedFileUrl = null) {
    $shortCode = $customSlug ?: substr(md5(uniqid()), 0, 6);
    $shortUrl = "{$config['domains']['shortlink']}/" . $shortCode;

    $stmt = $pdo->prepare("INSERT INTO links (long_url, short_url, uploaded_file) VALUES (?, ?, ?)");
    $stmt->execute([$longUrl, $shortUrl, $uploadedFileUrl]);

    return $shortUrl;
}

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Handle short link creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shorten'])) {
    $longUrl = $_POST['long_url'];
    $customSlug = isset($_POST['custom_slug']) ? $_POST['custom_slug'] : null;
    $uploadedFileUrl = isset($_POST['uploaded_file']) ? $_POST['uploaded_file'] : null;

    $shortUrl = generateShortLink($longUrl, $pdo, $config, $customSlug, $uploadedFileUrl);
}

$shortLinks = getShortLinks($pdo);
// Fetch uploaded files (Assuming you have a separate table for uploads)
function getUploadedFiles($pdo) {
    $stmt = $pdo->prepare("SELECT uploaded_file FROM links WHERE uploaded_file IS NOT NULL ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$uploadedFiles = getUploadedFiles($pdo);
?>
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://ucarecdn.com/libs/widget/3.x/uploadcare.full.min.js"></script>
    <script>
        UPLOADCARE_PUBLIC_KEY = "<?= $config['uploadcare']['public_key']; ?>"; // Set Uploadcare public key
    </script>
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.10/dist/clipboard.min.js"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-2xl">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>

            <!-- Tab Navigation -->
            <div class="mb-4">
                <ul class="flex space-x-4">
                    <li>
                        <button id="shortLinkTab" class="px-4 py-2 text-blue-500 font-semibold border-b-2 border-blue-500 focus:outline-none">Create Short Link</button>
                    </li>
                    <li>
                        <button id="uploadTab" class="px-4 py-2 text-gray-600 font-semibold hover:text-blue-500 focus:outline-none">Upload Image</button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div id="shortLinkContent" class="tab-content">
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Create a Short Link</h3>
                    <div class="p-6 border border-gray-300 rounded-md bg-gray-50">
                        <form method="POST" class="space-y-6">
                            <!-- Long URL Input -->
                            <div>
                                <label for="long_url" class="block text-gray-700 font-semibold mb-2">Enter Long URL</label>
                                <input type="url" id="long_url" name="long_url" placeholder="https://example.com" required class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Custom Slug Input -->
                            <div>
                                <label for="custom_slug" class="block text-gray-700 font-semibold mb-2">Custom Slug (optional)</label>
                                <input type="text" id="custom_slug" name="custom_slug" placeholder="Enter custom slug" class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <button type="submit" name="shorten" class="w-full bg-blue-500 text-white py-3 rounded-md hover:bg-blue-600 transition">
                                Shorten URL
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div id="uploadContent" class="tab-content hidden">
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Optional File Upload</h3>
                    <div class="p-6 border border-gray-300 rounded-md bg-gray-50">
                        <button type="button" id="uploadcare_widget" class="w-full bg-green-500 text-white py-3 rounded-md hover:bg-green-600 transition">Upload Image</button>
                        
                        <!-- Hidden input to store the uploaded file URL -->
                        <input type="hidden" id="uploaded_file" name="uploaded_file">

                        <!-- Area to display uploaded file URL -->
                        <div id="uploaded_file_url" class="mt-4 text-sm text-gray-600"></div>
                    </div>
                </div>
            </div>

                        <!-- Recent Short Links Section -->
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Short Links</h3>
            <ul class="space-y-3">
                <?php foreach ($shortLinks as $link): ?>
                    <li class="flex justify-between items-center bg-gray-50 p-3 rounded-md">
                        <a href="<?= htmlspecialchars($link['short_url']) ?>" target="_blank" class="text-blue-500 hover:underline"><?= htmlspecialchars($link['short_url']) ?></a>
                        <span class="text-gray-600"><?= htmlspecialchars($link['long_url']) ?></span>
                        <button class="copy-btn bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded-md ml-2" data-clipboard-text="<?= htmlspecialchars($link['short_url']) ?>">Copy</button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Recent Uploaded Files Section -->
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Uploaded Files</h3>
            <ul class="space-y-3">
                <?php foreach ($uploadedFiles as $file): ?>
                    <li class="flex justify-between items-center bg-gray-50 p-3 rounded-md">
                        <a href="<?= htmlspecialchars($file['uploaded_file']) ?>" target="_blank" class="text-blue-500 hover:underline"><?= htmlspecialchars($file['uploaded_file']) ?></a>
                        <button class="copy-btn bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded-md ml-2" data-clipboard-text="<?= htmlspecialchars($file['uploaded_file']) ?>">Copy</button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <a href="index.php?logout" class="block text-red-500 mt-6 text-center font-semibold">Logout</a>
        </div>
    </div>

    <!-- Script for clipboard functionality -->
    <script>
        var clipboard = new ClipboardJS('.copy-btn');

        clipboard.on('success', function(e) {
            var originalText = e.trigger.innerHTML;
            e.trigger.innerHTML = 'Copied!';
            e.trigger.classList.add('bg-green-500', 'text-white');
            e.trigger.classList.remove('bg-gray-200', 'text-gray-800');

            setTimeout(function() {
                e.trigger.innerHTML = originalText;
                e.trigger.classList.add('bg-gray-200', 'text-gray-800');
                e.trigger.classList.remove('bg-green-500', 'text-white');
            }, 2000);
        });

        // Handle the Uploadcare widget
        document.getElementById('uploadcare_widget').onclick = function() {
            uploadcare.openDialog(null, { publicKey: '<?= $config['uploadcare']['public_key'] ?>' })
                .done(function(file) {
                    file.done(function(fileInfo) {
                        // Update the hidden input field and display the uploaded file URL
                        document.getElementById('uploaded_file').value = fileInfo.cdnUrl;
                        document.getElementById('uploaded_file_url').innerHTML = 'Uploaded File URL: <a href="' + fileInfo.cdnUrl + '" target="_blank" class="text-blue-500">' + fileInfo.cdnUrl + '</a>';
                    });
                });
        };

        // Tab functionality
        document.getElementById('shortLinkTab').onclick = function() {
            document.getElementById('shortLinkContent').classList.remove('hidden');
            document.getElementById('uploadContent').classList.add('hidden');
            this.classList.add('border-blue-500', 'text-blue-500');
            document.getElementById('uploadTab').classList.remove('border-blue-500', 'text-blue-500');
            document.getElementById('uploadTab').classList.add('text-gray-600');
        };

        document.getElementById('uploadTab').onclick = function() {
            document.getElementById('uploadContent').classList.remove('hidden');
            document.getElementById('shortLinkContent').classList.add('hidden');
            this.classList.add('border-blue-500', 'text-blue-500');
            document.getElementById('shortLinkTab').classList.remove('border-blue-500', 'text-blue-500');
            document.getElementById('shortLinkTab').classList.add('text-gray-600');
        };
    </script>
</body>

</html>
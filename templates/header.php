<?php
// Define base path if needed, assuming relative paths work from including file
// Or use a configuration file later
// Example: define('BASE_URL', '/simple-pos-project/');

// Default title if not set by the including page
$pageTitle = $pageTitle ?? 'Simple POS System';

// Include helper for sanitizing output
require_once __DIR__ . '/../modules/utils/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitizeOutput($pageTitle); ?> - Grocery POS</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="assets/css/custom.css">

    <style>
        /* Add minor base styles or overrides if needed */
        body {
            font-family: sans-serif;
        }
        /* Style for Alpine.js cloak to prevent FOUC */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">

<header class="bg-blue-600 text-white shadow-md">
    <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
        <a href="index.php" class="text-xl font-bold hover:text-blue-200">
             <i class="fas fa-store mr-2"></i>Grocery POS
        </a>
        <div>
            <a href="pos.php" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out"><i class="fas fa-cash-register mr-1"></i> POS</a>
            <a href="products.php" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out"><i class="fas fa-box mr-1"></i> Products</a>
            <a href="sales_history.php" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out"><i class="fas fa-history mr-1"></i> Sales History</a>
            <?php // Add Login/Logout link here later if implementing auth ?>
        </div>
    </nav>
</header>

<main class="container mx-auto p-4 md:p-6 lg:p-8">
    ```

Next, here is the code for `templates/footer.php`:

```php
<?php
// Get current year for footer
$currentYear = date('Y');
?>

    </main> <?php /* End of main container */ ?>

<footer class="bg-gray-200 text-gray-600 text-center p-4 mt-8">
    <div class="container mx-auto">
        &copy; <?php echo $currentYear; ?> Simple Grocery POS. All rights reserved.
        <p class="text-xs mt-1">Developed for demonstration purposes.</p>
        <?php
            // Optional: Display PHP execution time or memory usage for debugging
            // $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
            // echo "<p class='text-xs'>Page loaded in: " . round($time, 4) . "s</p>";
        ?>
    </div>
</footer>

<script defer src="assets/js/main.js"></script>

</body>
</html>
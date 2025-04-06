<?php
// Start session handling AT THE VERY TOP
session_start();

// Set the page title for the header
$pageTitle = 'Product Management';

// Include necessary files
require_once 'modules/products/product_functions.php'; // Also includes helpers via product_functions
require_once 'modules/utils/helpers.php'; // Include specifically for redirect() if not already pulled in

// --- Initialize Variables ---
$products = [];      // To store the list of products
$message = '';       // Success message from session
$error = '';         // Error message from session
$editProduct = null; // Holds product data when editing

// --- Retrieve Session Messages ---
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear message after retrieving
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']); // Clear error after retrieving
}

// --- Handle Form Submissions (POST Requests) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use filter_input for better security/clarity
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $productId = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
    $productName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    // FILTER_VALIDATE_FLOAT allows decimals
    $productPrice = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $productSku = filter_input(INPUT_POST, 'sku', FILTER_SANITIZE_STRING);

    try { // Wrap DB operations in try-catch for potential future exceptions
        if ($action === 'add' && $productName && $productPrice !== false && $productPrice >= 0) {
            $result = addProduct($productName, $productPrice, $productSku ?? '');
            if ($result) {
                $_SESSION['message'] = "Product '{$result['name']}' added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add product.";
            }
        } elseif ($action === 'update' && $productId && $productName && $productPrice !== false && $productPrice >= 0) {
            $result = updateProduct($productId, $productName, $productPrice, $productSku ?? '');
            if ($result) {
                $_SESSION['message'] = "Product updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update product (maybe it was deleted?)";
            }
        } elseif ($action === 'delete' && $productId) {
            $result = deleteProduct($productId);
             if ($result) {
                $_SESSION['message'] = "Product deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete product (maybe it was already deleted?)";
            }
        } else {
            // Invalid action or missing required fields for add/update
             $_SESSION['error'] = "Invalid action or missing required data (Name, valid Price).";
        }
    } catch (Exception $e) {
        // Catch any unexpected errors during product operations
        error_log("Error processing product action '{$action}': " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred. Please try again.";
    }

    // Redirect back to the products page to prevent form resubmission (PRG pattern)
    redirect('products.php');
    exit; // Ensure script stops after redirect header

} // End of POST handling

// --- Handle Edit Request (GET Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_id'])) {
    $editId = filter_input(INPUT_GET, 'edit_id', FILTER_SANITIZE_STRING);
    if ($editId) {
        $editProduct = getProductById($editId);
        if (!$editProduct) {
            // Product not found or inactive, set error message for display
            $error = "Product with ID '{$editId}' not found or is inactive.";
            // Don't keep $editProduct set if not found
            $editProduct = null;
        }
    }
}

// --- Fetch All Active Products for Display ---
// Always fetch fresh list after potential POST actions or for initial load
try {
    $products = getAllProducts();
} catch (Exception $e) {
     error_log("Error fetching products: " . $e->getMessage());
     $error = "Could not load product list.";
     $products = []; // Ensure products is an array
}

// --- Include Header ---
require_once 'templates/header.php';
?>

<h1 class="text-2xl font-semibold mb-6 text-gray-800">Product Management</h1>

<?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i><?php echo sanitizeOutput($message); ?></span>
    </div>
<?php endif; ?>
<?php if ($error): ?>
     <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i><?php echo sanitizeOutput($error); ?></span>
    </div>
<?php endif; ?>


<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">
        <?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?>
    </h2>
    <form action="products.php" method="POST" class="space-y-4">

        <?php if ($editProduct): ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?php echo sanitizeOutput($editProduct['id']); ?>">
        <?php else: ?>
            <input type="hidden" name="action" value="add">
        <?php endif; ?>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name:</label>
            <input type="text" id="name" name="name" required
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                   value="<?php echo $editProduct ? sanitizeOutput($editProduct['name']) : ''; ?>">
        </div>

        <div>
            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price:</label>
            <input type="number" id="price" name="price" required step="0.01" min="0"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                   value="<?php echo $editProduct ? sanitizeOutput($editProduct['price']) : ''; ?>">
        </div>

        <div>
            <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU (Optional):</label>
            <input type="text" id="sku" name="sku"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    value="<?php echo $editProduct ? sanitizeOutput($editProduct['sku']) : ''; ?>">
        </div>

        <div class="flex items-center space-x-4">
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                <i class="fas <?php echo $editProduct ? 'fa-save' : 'fa-plus'; ?> mr-2"></i>
                <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
            </button>
            <?php if ($editProduct): ?>
                <a href="products.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                    Cancel Edit
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>


<div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Product List</h2>
    <?php if (!empty($products)): ?>
        <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeOutput($product['name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">Rs <?php echo number_format($product['price'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo sanitizeOutput($product['sku'] ?: '-'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                            <a href="products.php?edit_id=<?php echo sanitizeOutput($product['id']); ?>"
                               class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="products.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo sanitizeOutput($product['id']); ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-gray-500">No products found. Add some using the form above!</p>
    <?php endif; ?>
</div>

<?php
// --- Include Footer ---
require_once 'templates/footer.php';
?>
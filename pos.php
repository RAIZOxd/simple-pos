<?php
// Start session handling AT THE VERY TOP
session_start();

// Set the page title
$pageTitle = 'Point of Sale';

// Include necessary files
require_once 'modules/products/product_functions.php';
require_once 'modules/sales/sale_functions.php'; // Includes helpers
require_once 'modules/utils/helpers.php'; // Include specifically if needed

// --- Initialize Variables ---
$cart = $_SESSION['cart'] ?? []; // Retrieve cart from session or initialize empty
$message = '';       // Success message from session
$error = '';         // Error message from session
$allProducts = getAllProducts(); // Get products for the dropdown

// --- Retrieve Session Messages ---
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// --- Helper Function to Calculate Cart Total ---
function calculateCartTotal(array $cart): float {
    $total = 0.0;
    foreach ($cart as $item) {
        if (isset($item['price']) && isset($item['quantity'])) {
            $total += (float)$item['price'] * (int)$item['quantity'];
        }
    }
    return $total;
}

// --- Handle Actions (POST Requests) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_STRING);

    // --- Add Item Action ---
    if ($action === 'add_item' && $productId) {
        $product = getProductById($productId);
        if ($product) {
            // Use product ID as the key in the cart array for easy access/update
            if (isset($cart[$productId])) {
                // Increment quantity if item already exists
                $cart[$productId]['quantity']++;
            } else {
                // Add new item to cart
                $cart[$productId] = [
                    'id'        => $product['id'],
                    'name'      => $product['name'],
                    'price'     => (float)$product['price'], // Price at time of adding
                    'quantity'  => 1
                ];
            }
             $_SESSION['message'] = "'" . sanitizeOutput($product['name']) . "' added to cart.";
        } else {
            $_SESSION['error'] = "Product not found or invalid ID.";
        }
         $_SESSION['cart'] = $cart; // Update session cart
         redirect('pos.php'); // PRG Pattern
    }
    // --- Update Item Quantity Action ---
    elseif ($action === 'update_item' && $productId) {
        $newQuantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        if ($newQuantity !== false && $newQuantity >= 1) {
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] = $newQuantity;
                $_SESSION['message'] = "'" . sanitizeOutput($cart[$productId]['name']) . "' quantity updated.";
            } else {
                 $_SESSION['error'] = "Item not found in cart for update.";
            }
        } elseif ($newQuantity !== false && $newQuantity <= 0) {
             // If quantity is 0 or less, treat as remove
             if (isset($cart[$productId])) {
                 $_SESSION['message'] = "'" . sanitizeOutput($cart[$productId]['name']) . "' removed from cart.";
                 unset($cart[$productId]);
             }
        } else {
             $_SESSION['error'] = "Invalid quantity specified.";
        }
        $_SESSION['cart'] = $cart;
        redirect('pos.php');
    }
    // --- Remove Item Action ---
    elseif ($action === 'remove_item' && $productId) {
         if (isset($cart[$productId])) {
             $removedName = $cart[$productId]['name'];
             unset($cart[$productId]);
             $_SESSION['message'] = "'" . sanitizeOutput($removedName) . "' removed from cart.";
         } else {
             $_SESSION['error'] = "Item not found in cart for removal.";
         }
         $_SESSION['cart'] = $cart;
         redirect('pos.php');
    }
    // --- Clear Cart Action ---
    elseif ($action === 'clear_cart') {
        unset($_SESSION['cart']);
        $_SESSION['message'] = "Cart cleared.";
        redirect('pos.php');
    }
    // --- Process Payment Action ---
    elseif ($action === 'process_payment') {
        $amountTendered = filter_input(INPUT_POST, 'amount_tendered', FILTER_VALIDATE_FLOAT);
        $currentTotal = calculateCartTotal($cart);

        if ($currentTotal <= 0) {
             $_SESSION['error'] = "Cannot process payment: Cart is empty.";
        } elseif ($amountTendered === false || $amountTendered < $currentTotal) {
            $_SESSION['error'] = "Amount tendered is invalid or less than the total amount (Rs " . number_format($currentTotal, 2) . ").";
        } else {
            // Process the sale
            $changeGiven = $amountTendered - $currentTotal;
            $saleRecord = createSale($cart, $currentTotal, $amountTendered, $changeGiven);

            if ($saleRecord) {
                unset($_SESSION['cart']); // Clear cart on successful sale
                $_SESSION['message'] = "Sale completed successfully! Total: Rs " . number_format($currentTotal, 2) . ". Change Given: Rs " . number_format($changeGiven, 2) . ". (Sale ID: " . sanitizeOutput($saleRecord['saleId']) . ")";
                // Optionally redirect to a receipt page: redirect('receipt.php?id='.$saleRecord['saleId']);
            } else {
                 $_SESSION['error'] = "Failed to save the sale transaction. Please try again.";
            }
        }
        redirect('pos.php'); // Redirect back anyway to show message/updated state
    }
    else {
         // Catch unknown actions or errors
         $_SESSION['error'] = "Invalid action requested.";
         redirect('pos.php');
    }

    exit; // Exit after processing POST action and redirecting

} // End of POST handling


// --- Calculate current cart total for display ---
$currentCartTotal = calculateCartTotal($cart);


// --- Include Header ---
require_once 'templates/header.php';
?>

<h1 class="text-2xl font-semibold mb-6 text-gray-800">Point of Sale</h1>

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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Add Product</h2>
        <?php if (!empty($allProducts)): ?>
            <form action="pos.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_item">
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Select Product:</label>
                    <select id="product_id" name="product_id" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="" disabled selected>-- Choose a product --</option>
                        <?php foreach ($allProducts as $product): ?>
                            <option value="<?php echo sanitizeOutput($product['id']); ?>">
                                <?php echo sanitizeOutput($product['name']); ?> (Rs <?php echo number_format($product['price'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit"
                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                    <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                </button>
            </form>
        <?php else: ?>
            <p class="text-gray-500">No products available. Please add products in the <a href="products.php" class="text-blue-600 hover:underline">Product Management</a> section.</p>
        <?php endif; ?>
    </div>

    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Current Sale</h2>

        <div class="overflow-x-auto mb-6 max-h-96 overflow-y-auto border border-gray-200 rounded">
             <?php if (!empty($cart)): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                         <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($cart as $productId => $item): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeOutput($item['name']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">Rs <?php echo number_format($item['price'], 2); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                    <form action="pos.php" method="POST" class="inline-flex items-center">
                                        <input type="hidden" name="action" value="update_item">
                                        <input type="hidden" name="product_id" value="<?php echo sanitizeOutput($productId); ?>">
                                        <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1"
                                               class="w-16 px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <button type="submit" class="ml-1 text-blue-600 hover:text-blue-900 text-xs p-1" title="Update Quantity"><i class="fas fa-sync-alt"></i></button>
                                    </form>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 text-right">Rs <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-medium">
                                    <form action="pos.php" method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="product_id" value="<?php echo sanitizeOutput($productId); ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Remove Item">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                 <p class="text-gray-500 p-4 text-center">Cart is empty. Add products using the form on the left.</p>
            <?php endif; ?>
        </div>


        <?php if (!empty($cart)): ?>
            <div class="text-right mb-6 border-t pt-4">
                <p class="text-lg font-semibold text-gray-800">
                    Total: <span class="text-blue-600">Rs <?php echo number_format($currentCartTotal, 2); ?></span>
                </p>
            </div>

            <form action="pos.php" method="POST" class="space-y-4 border-t pt-6">
                <input type="hidden" name="action" value="process_payment">
                 <h3 class="text-lg font-semibold mb-2 text-gray-700">Process Payment (Cash)</h3>
                 <div>
                    <label for="amount_tendered" class="block text-sm font-medium text-gray-700 mb-1">Amount Tendered (Rs):</label>
                    <input type="number" id="amount_tendered" name="amount_tendered" required step="0.01" min="<?php echo $currentCartTotal; ?>"
                           class="mt-1 block w-full md:w-1/2 lg:w-1/3 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="e.g., <?php echo ceil($currentCartTotal / 100) * 100; // Suggest next round figure ?>">
                 </div>
                 <div class="flex flex-wrap items-center gap-4">
                     <button type="submit"
                            class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        <i class="fas fa-check mr-2"></i> Finalize Sale
                    </button>
                     <form action="pos.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to clear the current cart?');">
                        <input type="hidden" name="action" value="clear_cart">
                        <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                             <i class="fas fa-times mr-2"></i> Clear Cart
                        </button>
                    </form>
                 </div>
            </form>
        <?php endif; ?>

    </div> </div> <?php
// --- Include Footer ---
require_once 'templates/footer.php';
?>
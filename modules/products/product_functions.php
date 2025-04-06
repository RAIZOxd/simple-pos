<?php
/**
 * Functions for managing products (CRUD operations).
 * Interacts with the products.json data file.
 */

// Ensure helper functions are available
require_once __DIR__ . '/../utils/json_helpers.php';
require_once __DIR__ . '/../utils/helpers.php';

// Define the path to the products data file relative to this file's directory
define('PRODUCTS_FILE', __DIR__ . '/../../data/products.json');

/**
 * Retrieves all active products from the data file.
 *
 * @return array An array of active product arrays, or an empty array if none found or error.
 */
function getAllProducts(): array {
    $products = readJsonFile(PRODUCTS_FILE);

    if ($products === null) {
        // Error reading file (already logged by readJsonFile)
        return [];
    }

    // Filter out inactive products (where 'active' is explicitly false)
    $activeProducts = array_filter($products, function ($product) {
        return isset($product['active']) ? $product['active'] === true : true; // Default to active if flag not set
    });

    // Re-index array numerically if needed, though associative might be fine
    return array_values($activeProducts);
}

/**
 * Retrieves a single active product by its ID.
 *
 * @param string $id The ID of the product to retrieve.
 * @return array|null The product array if found and active, null otherwise.
 */
function getProductById(string $id): ?array {
    $products = readJsonFile(PRODUCTS_FILE);

    if ($products === null) {
        return null; // Error reading file
    }

    foreach ($products as $product) {
        if (isset($product['id']) && $product['id'] === $id) {
            // Check if active (default to active if 'active' key doesn't exist)
            $isActive = isset($product['active']) ? $product['active'] === true : true;
            return $isActive ? $product : null; // Return product only if active
        }
    }

    return null; // Not found
}

/**
 * Adds a new product to the data file.
 *
 * @param string $name The name of the product.
 * @param float $price The price of the product.
 * @param string $sku The SKU of the product (optional).
 * @return array|false The newly added product array on success, false on failure.
 */
function addProduct(string $name, float $price, string $sku = '') {
    if (empty(trim($name)) || $price < 0) {
        error_log("Invalid input for addProduct: Name cannot be empty, price cannot be negative.");
        return false; // Basic validation
    }

    $products = readJsonFile(PRODUCTS_FILE);
    if ($products === null) {
        error_log("Failed to read products file before adding.");
        return false; // Error reading file
    }

    $newProduct = [
        'id' => generateUniqueID('prod_'), // Generate unique ID
        'name' => trim($name),
        'price' => (float) $price, // Ensure price is float
        'sku' => trim($sku),
        'active' => true // New products are active by default
    ];

    $products[] = $newProduct; // Add the new product to the array

    if (writeJsonFile(PRODUCTS_FILE, $products)) {
        return $newProduct; // Return the added product data on success
    } else {
        error_log("Failed to write products file after adding product ID: " . $newProduct['id']);
        return false; // Error writing file
    }
}

/**
 * Updates an existing product's details.
 * Finds the product by ID and updates its name, price, and SKU.
 * Does not change the 'active' status here.
 *
 * @param string $id The ID of the product to update.
 * @param string $name The new name.
 * @param float $price The new price.
 * @param string $sku The new SKU (optional).
 * @return bool True on success, false if product not found or write fails.
 */
function updateProduct(string $id, string $name, float $price, string $sku = ''): bool {
     if (empty(trim($name)) || $price < 0) {
        error_log("Invalid input for updateProduct: Name cannot be empty, price cannot be negative.");
        return false; // Basic validation
    }

    $products = readJsonFile(PRODUCTS_FILE);
    if ($products === null) {
        error_log("Failed to read products file before updating product ID: " . $id);
        return false; // Error reading file
    }

    $productIndex = -1;
    foreach ($products as $index => $product) {
        if (isset($product['id']) && $product['id'] === $id) {
            $productIndex = $index;
            break;
        }
    }

    if ($productIndex === -1) {
        error_log("Product not found for update with ID: " . $id);
        return false; // Product not found
    }

    // Update the product details IN PLACE
    $products[$productIndex]['name'] = trim($name);
    $products[$productIndex]['price'] = (float) $price;
    $products[$productIndex]['sku'] = trim($sku);
    // Preserve other fields like 'active' status

    if (writeJsonFile(PRODUCTS_FILE, $products)) {
        return true; // Success
    } else {
        error_log("Failed to write products file after updating product ID: " . $id);
        return false; // Error writing file
    }
}

/**
 * Deletes a product by marking it as inactive (soft delete).
 *
 * @param string $id The ID of the product to delete.
 * @return bool True on success, false if product not found or write fails.
 */
function deleteProduct(string $id): bool {
    $products = readJsonFile(PRODUCTS_FILE);
    if ($products === null) {
        error_log("Failed to read products file before deleting product ID: " . $id);
        return false; // Error reading file
    }

    $productIndex = -1;
    foreach ($products as $index => $product) {
        // Find active product to delete
        $isActive = isset($product['active']) ? $product['active'] === true : true;
        if (isset($product['id']) && $product['id'] === $id && $isActive) {
            $productIndex = $index;
            break;
        }
    }

    if ($productIndex === -1) {
        error_log("Active product not found for deletion with ID: " . $id);
        return false; // Product not found or already inactive
    }

    // Mark the product as inactive
    $products[$productIndex]['active'] = false;

    if (writeJsonFile(PRODUCTS_FILE, $products)) {
        return true; // Success
    } else {
        error_log("Failed to write products file after deleting product ID: " . $id);
        return false; // Error writing file
    }
}

?>
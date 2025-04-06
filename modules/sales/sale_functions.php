<?php
/**
 * Functions for managing sales transactions.
 * Interacts with the sales.json data file.
 */

// Ensure helper functions are available
require_once __DIR__ . '/../utils/json_helpers.php';
require_once __DIR__ . '/../utils/helpers.php';

// Define the path to the sales data file relative to this file's directory
define('SALES_FILE', __DIR__ . '/../../data/sales.json');

/**
 * Creates a new sale record and saves it to the data file.
 *
 * @param array $cartItems Array of items in the cart (should include productId, name, price, quantity).
 * @param float $totalAmount The final total amount for the sale.
 * @param float $amountTendered The amount of cash received from the customer.
 * @param float $changeGiven The amount of change returned to the customer.
 * @param string $paymentMethod The method of payment (default: 'Cash').
 * @return array|false The newly created sale record array on success, false on failure.
 */
function createSale(array $cartItems, float $totalAmount, float $amountTendered, float $changeGiven, string $paymentMethod = 'Cash') {
    if (empty($cartItems) || $totalAmount < 0 || $amountTendered < $totalAmount) {
        error_log("Invalid input for createSale: Cart empty, negative total, or insufficient amount tendered.");
        return false; // Basic validation
    }

    $sales = readJsonFile(SALES_FILE);
    if ($sales === null) {
        error_log("Failed to read sales file before creating sale.");
        return false; // Error reading file
    }

    // Prepare items array for storage (ensure correct fields)
    $saleItems = [];
    foreach ($cartItems as $productId => $item) {
        if (isset($item['id']) && isset($item['name']) && isset($item['price']) && isset($item['quantity'])) {
             $saleItems[] = [
                'productId' => $item['id'],
                'name'      => $item['name'], // Store name at time of sale
                'price'     => (float)$item['price'], // Store price at time of sale
                'quantity'  => (int)$item['quantity']
            ];
        } else {
             error_log("Skipping invalid item structure in cart during sale creation: " . $productId);
        }

    }

    // Double check if any valid items were processed
     if (empty($saleItems)) {
        error_log("Cannot create sale: No valid items found in the processed cart.");
        return false;
    }


    $newSale = [
        'saleId'         => generateUniqueID('sale_'), // Generate unique Sale ID
        'timestamp'      => date('c'), // ISO 8601 format timestamp (e.g., 2025-04-07T14:30:00+05:30)
        'items'          => $saleItems,
        'totalAmount'    => (float) $totalAmount,
        'paymentMethod'  => $paymentMethod,
        'amountTendered' => (float) $amountTendered,
        'changeGiven'    => (float) $changeGiven
    ];

    $sales[] = $newSale; // Add the new sale to the array

    if (writeJsonFile(SALES_FILE, $sales)) {
        return $newSale; // Return the created sale data on success
    } else {
        error_log("Failed to write sales file after creating sale ID: " . $newSale['saleId']);
        return false; // Error writing file
    }
}


/**
 * Retrieves all sales records from the data file.
 * Optionally filters by a date range.
 *
 * @param string|null $startDate Start date string (YYYY-MM-DD) or null.
 * @param string|null $endDate End date string (YYYY-MM-DD) or null.
 * @return array An array of sale records, potentially filtered, or empty array on error.
 */
function getAllSales(?string $startDate = null, ?string $endDate = null): array {
    $sales = readJsonFile(SALES_FILE);

    if ($sales === null) {
        return []; // Error reading file
    }

    // Apply date filtering if start or end dates are provided
    if ($startDate || $endDate) {
        $filteredSales = [];
        // Set default start/end times for comparison
        $startTs = $startDate ? strtotime($startDate . ' 00:00:00') : null;
        $endTs = $endDate ? strtotime($endDate . ' 23:59:59') : null;

        foreach ($sales as $sale) {
            if (isset($sale['timestamp'])) {
                 $saleTs = strtotime($sale['timestamp']);
                 if ($saleTs) {
                    $include = true;
                    if ($startTs && $saleTs < $startTs) {
                        $include = false;
                    }
                    if ($endTs && $saleTs > $endTs) {
                         $include = false;
                    }
                    if ($include) {
                        $filteredSales[] = $sale;
                    }
                 }
            }
        }
         // Sort by timestamp descending (most recent first) before returning
        usort($filteredSales, function($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });
        return $filteredSales;
    }

    // Sort by timestamp descending if no filter applied
    usort($sales, function($a, $b) {
        return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
    });

    return $sales;
}

/**
 * Retrieves a single sale record by its ID.
 *
 * @param string $saleId The ID of the sale to retrieve.
 * @return array|null The sale array if found, null otherwise.
 */
function getSaleById(string $saleId): ?array {
    $sales = readJsonFile(SALES_FILE);

    if ($sales === null) {
        return null; // Error reading file
    }

    foreach ($sales as $sale) {
        if (isset($sale['saleId']) && $sale['saleId'] === $saleId) {
            return $sale;
        }
    }

    return null; // Not found
}


?>
<?php
// No session needed usually for just displaying a receipt, but start if auth might be added later
// session_start();

// Include necessary files
require_once 'modules/sales/sale_functions.php'; // Includes helpers
require_once 'modules/utils/helpers.php'; // Include specifically if needed

// --- Configuration ---
// You could move this to a config.json or config.php file later
define('STORE_NAME', 'My Simple Grocery');
define('STORE_ADDRESS', '123 Main Street, Colombo'); // Optional
define('STORE_PHONE', '011-1234567'); // Optional

// --- Initialize Variables ---
$saleData = null;
$error = '';
$saleId = filter_input(INPUT_GET, 'sale_id', FILTER_SANITIZE_STRING);

// --- Fetch Sale Data ---
if (!$saleId) {
    $error = "No Sale ID provided.";
} else {
    try {
        $saleData = getSaleById($saleId);
        if (!$saleData) {
            $error = "Receipt not found for Sale ID: " . sanitizeOutput($saleId);
        }
    } catch (Exception $e) {
         error_log("Error fetching sale data for receipt (ID: {$saleId}): " . $e->getMessage());
         $error = "An error occurred while retrieving the receipt.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $saleData ? sanitizeOutput($saleData['saleId']) : 'Error'; ?></title>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact; /* Ensures background colors print in Chrome/Safari */
                print-color-adjust: exact; /* Standard */
                margin: 0;
                padding: 0;
                background-color: #fff; /* Force white background for printing */
            }
            .no-print {
                display: none !important; /* Hide elements marked with .no-print */
            }
            /* Adjust receipt width and remove shadow/border for printing */
            .receipt-container {
                 max-width: 100% !important;
                 width: 280px !important; /* Adjust typical thermal receipt width */
                 box-shadow: none !important;
                 border: none !important;
                 margin: 0 auto; /* Center if needed */
                 font-size: 10pt; /* Smaller font for receipts */
            }
            /* Reduce padding/margins for print */
             .receipt-container .p-4 { padding: 5px !important; }
             .receipt-container .py-1 { padding-top: 2px !important; padding-bottom: 2px !important; }
             .receipt-container .py-2 { padding-top: 4px !important; padding-bottom: 4px !important; }
             .receipt-container .my-1 { margin-top: 2px !important; margin-bottom: 2px !important; }
             .receipt-container .my-2 { margin-top: 4px !important; margin-bottom: 4px !important; }
             .receipt-container hr { margin-top: 4px !important; margin-bottom: 4px !important; }
        }
        /* Default body style */
        body {
            background-color: #f3f4f6; /* Light gray background for screen view */
        }
    </style>
</head>
<body class="p-4">

    <?php if ($error): ?>
        <div class="max-w-md mx-auto bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative my-4" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline"><?php echo sanitizeOutput($error); ?></span>
            <br><br>
            <a href="sales_history.php" class="text-blue-600 hover:underline no-print">&larr; Back to Sales History</a>
        </div>
    <?php elseif ($saleData): ?>
        <?php
            // Format timestamp nicely
            $dateTime = 'N/A';
            if (isset($saleData['timestamp'])) {
                try {
                    $dt = new DateTime($saleData['timestamp']);
                    // Adjust timezone if needed, e.g., for Sri Lanka:
                     try { $dt->setTimezone(new DateTimeZone('Asia/Colombo')); } catch(Exception $tzErr) { error_log("TZ Error: ".$tzErr->getMessage()); }
                    $dateTime = $dt->format('Y-m-d H:i:s');
                } catch (Exception $e) { $dateTime = 'Invalid Date'; }
            }
        ?>
        <div class="receipt-container max-w-sm mx-auto bg-white p-4 border border-gray-300 shadow-md text-gray-800 text-sm">

            <div class="text-center mb-2">
                <h1 class="text-lg font-bold"><?php echo sanitizeOutput(STORE_NAME); ?></h1>
                <?php if (defined('STORE_ADDRESS') && STORE_ADDRESS): ?>
                    <p class="text-xs"><?php echo sanitizeOutput(STORE_ADDRESS); ?></p>
                <?php endif; ?>
                 <?php if (defined('STORE_PHONE') && STORE_PHONE): ?>
                    <p class="text-xs">Tel: <?php echo sanitizeOutput(STORE_PHONE); ?></p>
                <?php endif; ?>
            </div>

            <div class="text-xs my-2">
                <p>Sale ID: <?php echo sanitizeOutput($saleData['saleId']); ?></p>
                <p>Date: <?php echo $dateTime; ?></p>
            </div>

            <hr class="my-1 border-t border-dashed border-gray-400">

            <div class="my-2">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-dashed border-gray-400 text-xs">
                            <th class="text-left font-semibold pb-1">Item</th>
                            <th class="text-center font-semibold pb-1">Qty</th>
                            <th class="text-right font-semibold pb-1">Price</th>
                            <th class="text-right font-semibold pb-1">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saleData['items'] as $item): ?>
                            <tr class="text-xs">
                                <td class="py-1 text-left"><?php echo sanitizeOutput($item['name']); ?></td>
                                <td class="py-1 text-center"><?php echo (int)$item['quantity']; ?></td>
                                <td class="py-1 text-right">Rs <?php echo number_format($item['price'], 2); ?></td>
                                <td class="py-1 text-right">Rs <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

             <hr class="my-1 border-t border-dashed border-gray-400">

             <div class="my-2 text-right">
                <p class="font-bold">Total: Rs <?php echo number_format($saleData['totalAmount'], 2); ?></p>
            </div>

             <hr class="my-1 border-t border-dashed border-gray-400">

             <div class="my-2 text-xs">
                 <p>Payment Method: <?php echo sanitizeOutput($saleData['paymentMethod']); ?></p>
                 <p>Amount Tendered: Rs <?php echo number_format($saleData['amountTendered'], 2); ?></p>
                 <p>Change Given: Rs <?php echo number_format($saleData['changeGiven'], 2); ?></p>
            </div>

             <hr class="my-1 border-t border-dashed border-gray-400">

            <div class="text-center text-xs mt-2">
                <p>Thank you for shopping with us!</p>
            </div>

        </div>

        <div class="text-center mt-4 no-print">
             <button onclick="window.print();"
                    class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                 <i class="fas fa-print mr-2"></i> Print Receipt
            </button>
             <a href="sales_history.php" class="ml-4 text-blue-600 hover:underline">&larr; Back to Sales History</a>
        </div>

    <?php endif; ?>

</body>
</html>
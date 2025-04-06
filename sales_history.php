<?php
// Start session handling
session_start();

// Set the page title
$pageTitle = 'Sales History';

// Include necessary files
require_once 'modules/sales/sale_functions.php'; // Includes helpers via sale_functions
require_once 'modules/utils/helpers.php'; // Include specifically if needed

// --- Initialize Variables ---
$sales = [];
$error = '';
$startDate = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING);
$endDate = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING);

// --- Validate Dates (Basic) ---
// You might add more robust date validation if needed
$startDate = ($startDate && strtotime($startDate)) ? $startDate : null;
$endDate = ($endDate && strtotime($endDate)) ? $endDate : null;

// --- Fetch Sales Data ---
try {
    // Pass validated dates to the function
    $sales = getAllSales($startDate, $endDate);
} catch (Exception $e) {
    error_log("Error fetching sales history: " . $e->getMessage());
    $error = "Could not load sales history. Please try again later.";
    $sales = []; // Ensure sales is an array
}


// --- Include Header ---
require_once 'templates/header.php';
?>

<h1 class="text-2xl font-semibold mb-6 text-gray-800">Sales History</h1>

<?php if ($error): ?>
     <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i><?php echo sanitizeOutput($error); ?></span>
    </div>
<?php endif; ?>

<div class="bg-white p-4 rounded-lg shadow-md mb-8">
    <form action="sales_history.php" method="GET" class="flex flex-wrap items-end gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date:</label>
            <input type="date" id="start_date" name="start_date"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                   value="<?php echo sanitizeOutput($startDate ?? ''); ?>">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date:</label>
            <input type="date" id="end_date" name="end_date"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                   value="<?php echo sanitizeOutput($endDate ?? ''); ?>">
        </div>
        <button type="submit"
                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
            <i class="fas fa-filter mr-2"></i> Filter
        </button>
        <a href="sales_history.php"
           class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
             Reset Filter
        </a>
    </form>
</div>


<div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">
        Sales Transactions <?php echo ($startDate || $endDate) ? '(Filtered)' : ''; ?>
    </h2>
    <?php if (!empty($sales)): ?>
        <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                     <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($sales as $sale): ?>
                    <?php
                        // Format timestamp nicely
                        $dateTime = 'N/A';
                        if (isset($sale['timestamp'])) {
                            try {
                                // Attempt to create DateTime object (handles ISO 8601)
                                $dt = new DateTime($sale['timestamp']);
                                // You can adjust the timezone if needed, e.g., $dt->setTimezone(new DateTimeZone('Asia/Colombo'));
                                $dateTime = $dt->format('Y-m-d H:i:s'); // Example format
                            } catch (Exception $e) {
                                $dateTime = 'Invalid Date'; // Handle parsing errors
                            }
                        }
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitizeOutput($sale['saleId'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $dateTime; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">Rs <?php echo number_format($sale['totalAmount'] ?? 0, 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center"><?php echo isset($sale['items']) ? count($sale['items']) : 0; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo sanitizeOutput($sale['paymentMethod'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="receipt.php?sale_id=<?php echo urlencode(sanitizeOutput($sale['saleId'] ?? '')); ?>"
                               target="_blank" <?php /* Open in new tab */ ?>
                               class="text-blue-600 hover:text-blue-900" title="View Receipt">
                                <i class="fas fa-receipt"></i> View
                            </a>
                            <?php // Add other actions like 'Refund' later if needed ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-gray-500">No sales transactions found<?php echo ($startDate || $endDate) ? ' matching the specified date range' : ''; ?>.</p>
    <?php endif; ?>
</div>


<?php
// --- Include Footer ---
require_once 'templates/footer.php';
?>
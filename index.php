<?php
/**
 * Main entry point for the Simple POS System.
 *
 * For this simple application, it directly redirects to the main POS interface.
 * If authentication or a dashboard were added, this file would handle that logic first.
 */

// Start session (good practice, especially if auth is added later)
session_start();

// Include helper functions (specifically for the redirect function)
require_once 'modules/utils/helpers.php';

// Redirect the user immediately to the Point of Sale page
redirect('pos.php');

// Exit to ensure no further code is executed after the redirect header is sent
exit;

?>
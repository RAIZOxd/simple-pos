<?php
/**
 * General utility helper functions for the POS system.
 */

/**
 * Generates a reasonably unique ID string.
 * Combines a prefix, high-resolution time, and random bytes.
 *
 * @param string $prefix Optional prefix for the ID (e.g., 'prod_', 'sale_').
 * @return string A unique ID string.
 */
function generateUniqueID(string $prefix = ''): string {
    // Get microseconds as a string (removes the decimal point)
    $microtime = str_replace('.', '', microtime(true));

    // Generate a few random bytes and convert to hex
    // random_bytes is cryptographically secure
    try {
        $randomPart = bin2hex(random_bytes(4)); // 4 bytes = 8 hex characters
    } catch (Exception $e) {
        // Fallback if random_bytes fails (less secure)
        $randomPart = substr(md5(mt_rand()), 0, 8);
        error_log("random_bytes failed, using fallback for ID generation: " . $e->getMessage());
    }

    return $prefix . $microtime . '_' . $randomPart;
}

/**
 * Sanitizes output data to prevent XSS attacks when displaying in HTML.
 *
 * @param mixed $data The data to sanitize (string or array).
 * @return mixed Sanitized data.
 */
function sanitizeOutput($data) {
    if (is_array($data)) {
        // Recursively sanitize arrays
        return array_map('sanitizeOutput', $data);
    } elseif (is_string($data)) {
        // Sanitize strings
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } else {
        // Return other types as is (int, float, bool, null)
        return $data;
    }
}

/**
 * Redirects the browser to a different URL.
 * Ensures that previously sent headers are respected and exits script execution.
 *
 * @param string $url The URL to redirect to.
 * @return void
 */
function redirect(string $url): void {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        // Fallback if headers already sent (less clean)
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
        // Still exit to prevent further script execution
        exit;
    }
}

// Add any other general helper functions here as needed.

?>
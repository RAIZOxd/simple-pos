<?php
/**
 * Functions for user authentication and session management.
 */

// Ensure helper functions are available (needed for readJsonFile)
require_once __DIR__ . '/../utils/json_helpers.php';
require_once __DIR__ . '/../utils/helpers.php'; // Needed for redirect

// Define the path to the users data file
define('USERS_FILE', __DIR__ . '/../../data/users.json');

/**
 * Checks if a user is currently logged in based on session data.
 *
 * @return bool True if logged in, false otherwise.
 */
function isUserLoggedIn(): bool {
    // Ensure session is started before checking session variables
    if (session_status() === PHP_SESSION_NONE) {
        session_start(); // Start session if not already started
    }
    return isset($_SESSION['user_id']); // Check if our user identifier is set
}

/**
 * If the user is not logged in, redirects them to the login page and exits.
 * Call this at the beginning of any protected page.
 *
 * @return void
 */
function requireLogin(): void {
    if (!isUserLoggedIn()) {
        // Store the intended URL in session to redirect back after login (optional enhancement)
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('login.php'); // Redirect to login page
        exit; // Stop script execution
    }
    // If logged in, execution continues...
}

/**
 * Attempts to log in a user with the given credentials.
 * Verifies username and password against stored hashes.
 * Starts a session and sets user ID if successful.
 *
 * @param string $username The username attempt.
 * @param string $password The password attempt.
 * @return bool True on successful login, false otherwise.
 */
function attemptLogin(string $username, string $password): bool {
    $users = readJsonFile(USERS_FILE);

    if ($users === null || empty($users)) {
        error_log("Users file not found, empty, or unreadable.");
        return false; // Cannot log in if users file has issues
    }

    foreach ($users as $user) {
        // Case-sensitive username comparison recommended
        if (isset($user['username']) && $user['username'] === $username) {
            // Verify the provided password against the stored hash
            if (isset($user['passwordHash']) && password_verify($password, $user['passwordHash'])) {
                // Password is correct!

                // Start or resume session
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // Regenerate session ID for security (prevents session fixation)
                session_regenerate_id(true);

                // Store user identifier in session
                $_SESSION['user_id'] = $user['username']; // Store username as identifier
                // Could store other non-sensitive info like user role if needed

                return true; // Login successful
            } else {
                // Password incorrect for this user, stop checking
                return false;
            }
        }
    }

    return false; // Username not found
}

/**
 * Logs the current user out by destroying the session.
 * Redirects to the login page.
 *
 * @return void
 */
function logoutUser(): void {
     // Start or resume session to manage it
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Unset all session variables
    $_SESSION = [];

    // If using session cookies, delete the cookie as well
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();

    // Redirect to login page after logout
    redirect('login.php');
    exit; // Stop script execution
}

?>
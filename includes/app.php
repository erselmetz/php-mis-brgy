<?php
/**
 * Application Bootstrap File
 * 
 * This file initializes the application by:
 * 1. Starting the session for user authentication
 * 2. Loading utility functions
 * 3. Loading authentication helpers
 * 4. Establishing database connection
 * 
 * Include this file at the top of all PHP pages that need
 * database access or authentication.
 */

  // Start session for user authentication and state management
  session_start();

// Configure secure session settings before starting session
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    ini_set('session.cookie_httponly', '1'); // Prevent JavaScript access to session cookie
    ini_set('session.cookie_secure', '0'); // Set to 1 in production with HTTPS
    ini_set('session.use_strict_mode', '1'); // Prevent session fixation attacks
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Load utility functions (asset loading, validation, etc.)
include_once 'function.php';

// Load authentication and authorization functions
include_once 'auth.php';

// Load database connection
include_once 'db.php';
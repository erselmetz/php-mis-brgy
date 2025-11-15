<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the test environment for MIS Barangay
 */

// Define test environment
define('TEST_ENV', true);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the application bootstrap
require_once __DIR__ . '/../includes/app.php';

// Create test database connection if needed
// You may want to use a separate test database
if (!defined('DB_NAME_TEST')) {
    define('DB_NAME_TEST', DB_NAME . '_test');
}

echo "Test environment initialized.\n";


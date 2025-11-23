<?php
/**
 * Database Connection Handler
 * 
 * This file establishes and configures the MySQL database connection.
 * It uses mysqli with prepared statements for security.
 * 
 * IMPORTANT: Never manually close this connection using $conn->close()
 * as it's shared across the application. Let PHP handle cleanup.
 */

include_once __DIR__ . '/../config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection and handle errors gracefully
if ($conn->connect_error) {
    // Log error for debugging (don't expose credentials)
    error_log("Database connection failed: " . $conn->connect_error);
    // Show user-friendly error message
    die("Database connection failed. Please contact the administrator.");
}

// Set charset to UTF-8 to prevent character encoding issues
// utf8mb4 supports full Unicode including emojis
$conn->set_charset("utf8mb4");

// Enable error reporting for mysqli (useful for debugging)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

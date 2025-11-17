<?php
/**
 * Database Connection
 * MIS Barangay - Database Layer
 * 
 * This file handles database connections.
 */

include_once __DIR__ . '/../config.php';

/**
 * Create database connection
 * @return mysqli Database connection object
 */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to prevent character encoding issues
$conn->set_charset("utf8mb4");

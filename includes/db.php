<?php
/**
 * Database Connection Handler (Safe)
 *
 * - Does NOT die/exit on failure
 * - Exposes:
 *    $conn (mysqli|null)
 *    $db_error (string|null)
 */

include_once __DIR__ . '/../config.php';

// IMPORTANT: set mysqli reporting BEFORE creating connection
// OFF = no exceptions, we'll handle connect_errno ourselves
mysqli_report(MYSQLI_REPORT_OFF);

$conn = null;
$db_error = null;

try {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_errno) {
        $db_error = $conn->connect_error;
        error_log("Database connection failed: " . $db_error);
        $conn = null;
    } else {
        $conn->set_charset("utf8mb4");
    }
} catch (Throwable $e) {
    $db_error = $e->getMessage();
    error_log("Database connection exception: " . $db_error);
    $conn = null;
}
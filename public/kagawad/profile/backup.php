<?php
/**
 * Backup API Endpoint
 * 
 * Generates a full SQL database backup and triggers a download.
 * Logs each backup to the `backups` table for history tracking.
 * 
 * Access: Secretary only
 * Method: POST (CSRF-protected)
 */

require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid security token.');
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Unknown';
$description = sanitizeString($_POST['description'] ?? 'Manual Backup');
if (empty($description)) {
    $description = 'Manual Backup';
}

// ─── Database credentials (from config) ───────────────────────────────────────
$host   = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$dbUser = defined('DB_USER') ? DB_USER : 'root';
$dbPass = defined('DB_PASS') ? DB_PASS : '';
$dbName = defined('DB_NAME') ? DB_NAME : 'php_mis_brgy';

// ─── Generate SQL dump via PHP (no exec/shell) ────────────────────────────────

$sqlDump  = "-- MIS Barangay Database Backup\n";
$sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sqlDump .= "-- By: " . htmlspecialchars($user_name) . "\n";
$sqlDump .= "-- Database: " . $dbName . "\n";
$sqlDump .= "-- -----------------------------------------------\n\n";
$sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Get all tables
$tablesResult = $conn->query("SHOW TABLES");
if (!$tablesResult) {
    http_response_code(500);
    exit('Failed to retrieve tables.');
}

while ($tableRow = $tablesResult->fetch_row()) {
    $table = $tableRow[0];

    // ── CREATE TABLE statement ──
    $createResult = $conn->query("SHOW CREATE TABLE `$table`");
    if ($createResult) {
        $createRow = $createResult->fetch_row();
        $sqlDump .= "-- Table: `$table`\n";
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlDump .= $createRow[1] . ";\n\n";
    }

    // ── INSERT rows ──
    $rowsResult = $conn->query("SELECT * FROM `$table`");
    if ($rowsResult && $rowsResult->num_rows > 0) {
        $sqlDump .= "INSERT INTO `$table` VALUES\n";
        $rows = [];
        while ($row = $rowsResult->fetch_row()) {
            $escapedValues = array_map(function ($val) use ($conn) {
                if ($val === null) return 'NULL';
                return "'" . $conn->real_escape_string($val) . "'";
            }, $row);
            $rows[] = '(' . implode(', ', $escapedValues) . ')';
        }
        $sqlDump .= implode(",\n", $rows) . ";\n\n";
    }
}

$sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

// ─── Save backup file to disk ─────────────────────────────────────────────────
$backupDir = __DIR__ . '/../../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$filename  = 'backup_' . date('Y-m-d_H-i-s') . '_' . $user_id . '.sql';
$filepath  = $backupDir . $filename;

$bytesWritten = file_put_contents($filepath, $sqlDump);
if ($bytesWritten === false) {
    http_response_code(500);
    exit('Failed to write backup file.');
}

$fileSize = $bytesWritten; // in bytes

// ─── Log to backups table ─────────────────────────────────────────────────────
$stmt = $conn->prepare(
    "INSERT INTO backups (filename, file_size, description, performed_by, performed_by_name) VALUES (?, ?, ?, ?, ?)"
);
if ($stmt) {
    $stmt->bind_param("sisss", $filename, $fileSize, $description, $user_id, $user_name);
    $stmt->execute();
    $stmt->close();
}

// ─── Stream file download to browser ─────────────────────────────────────────
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filepath);
exit;
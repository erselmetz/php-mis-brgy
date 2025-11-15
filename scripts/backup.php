<?php
/**
 * Automated Database Backup Script
 * MIS Barangay - Backup Utility
 * 
 * Usage:
 *   php scripts/backup.php
 *   php scripts/backup.php --compress
 *   php scripts/backup.php --output=/path/to/backups
 */

// Load configuration
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config.php';

// Configuration
$backupDir = __DIR__ . '/../backups';
$compress = in_array('--compress', $argv);
$customOutput = null;

// Parse command line arguments
foreach ($argv as $arg) {
    if (strpos($arg, '--output=') === 0) {
        $customOutput = substr($arg, 9);
    }
}

if ($customOutput) {
    $backupDir = $customOutput;
}

// Create backup directory if it doesn't exist
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Generate backup filename
$timestamp = date('Y-m-d_H-i-s');
$filename = "mis_barangay_backup_{$timestamp}.sql";
$filepath = $backupDir . '/' . $filename;

echo "Starting database backup...\n";
echo "Database: " . DB_NAME . "\n";
echo "Output: $filepath\n";

// Open file for writing
$file = fopen($filepath, 'w');

if (!$file) {
    die("Error: Cannot create backup file.\n");
}

// Write SQL header
fwrite($file, "-- MIS Barangay Database Backup\n");
fwrite($file, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
fwrite($file, "-- Database: " . DB_NAME . "\n\n");
fwrite($file, "SET FOREIGN_KEY_CHECKS=0;\n\n");

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

// Backup each table
foreach ($tables as $table) {
    echo "Backing up table: $table\n";
    
    // Get table structure
    fwrite($file, "-- Table structure for `$table`\n");
    fwrite($file, "DROP TABLE IF EXISTS `$table`;\n");
    
    $createTable = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $createTable->fetch_array();
    fwrite($file, $row[1] . ";\n\n");
    
    // Get table data
    fwrite($file, "-- Data for table `$table`\n");
    $data = $conn->query("SELECT * FROM `$table`");
    
    if ($data->num_rows > 0) {
        fwrite($file, "INSERT INTO `$table` VALUES\n");
        $rows = [];
        
        while ($row = $data->fetch_assoc()) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . $conn->real_escape_string($value) . "'";
                }
            }
            $rows[] = "(" . implode(",", $values) . ")";
        }
        
        fwrite($file, implode(",\n", $rows) . ";\n\n");
    }
}

fwrite($file, "SET FOREIGN_KEY_CHECKS=1;\n");

fclose($file);

echo "Backup completed: $filepath\n";

// Compress if requested
if ($compress) {
    echo "Compressing backup...\n";
    $zipFile = $filepath . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($filepath, $filename);
        $zip->close();
        unlink($filepath); // Delete uncompressed file
        echo "Compressed backup: $zipFile\n";
    } else {
        echo "Warning: Could not compress backup.\n";
    }
}

// Clean old backups (keep last 30 days)
echo "Cleaning old backups...\n";
$files = glob($backupDir . '/mis_barangay_backup_*.sql*');
$cutoff = time() - (30 * 24 * 60 * 60); // 30 days

foreach ($files as $file) {
    if (filemtime($file) < $cutoff) {
        unlink($file);
        echo "Deleted old backup: " . basename($file) . "\n";
    }
}

echo "Backup process completed successfully!\n";


<?php
/**
 * Create Migrations Tracking Table
 * MIS Barangay - Migration Versioning System
 * 
 * This script creates a table to track which migrations have been executed.
 */

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS migrations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time DECIMAL(10, 4) NULL,
    status ENUM('success', 'failed', 'skipped') DEFAULT 'success',
    error_message TEXT NULL,
    INDEX idx_migration_name (migration_name),
    INDEX idx_executed_at (executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Migrations tracking table created successfully.\n";
} else {
    echo "❌ Error creating migrations table: " . $conn->error . "\n";
}

?>


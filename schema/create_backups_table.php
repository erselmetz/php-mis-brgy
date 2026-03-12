<?php
/**
 * Create Backups Table
 * 
 * Tracks all database backup history including:
 * - Date and time of backup
 * - File size
 * - Performed by which user
 * - Description/notes
 */

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `backups` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `description` VARCHAR(255) DEFAULT 'Manual Backup',
  `performed_by` INT(11) NOT NULL,
  `performed_by_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_performed_by` (`performed_by`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'backups' created successfully.\n";
} else {
    echo "❌ Error creating table 'backups': " . $conn->error . "\n";
}

$conn->close();
?>
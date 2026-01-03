<?php
/**
 * Create Inventory Audit Trail Table
 * 
 * Tracks all changes and usage of inventory items including:
 * - Asset assignments/returns
 * - Location changes
 * - Condition updates
 * - Quantity changes
 * - User actions
 */

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `inventory_audit_trail` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inventory_id` INT UNSIGNED NOT NULL,
  `asset_code` VARCHAR(50) NOT NULL,
  `action_type` ENUM('created', 'updated', 'deleted', 'assigned', 'returned', 'location_changed', 'condition_changed', 'quantity_changed') NOT NULL,
  `user_id` INT(11) NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `user_role` VARCHAR(50) DEFAULT NULL,
  `personnel_name` VARCHAR(255) DEFAULT NULL,
  `personnel_role` VARCHAR(100) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `purpose` TEXT DEFAULT NULL,
  `start_time` DATETIME DEFAULT NULL,
  `end_time` DATETIME DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_inventory_id` (`inventory_id`),
  INDEX `idx_asset_code` (`asset_code`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'inventory_audit_trail' created successfully.\n";
} else {
    echo "❌ Error creating table 'inventory_audit_trail': " . $conn->error . "\n";
}

$conn->close();
?>


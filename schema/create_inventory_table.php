<?php
/**
 * Create Inventory Table
 * Stores all barangay assets and equipment
 */

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `asset_code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `quantity` INT DEFAULT 1,
    `location` VARCHAR(255) DEFAULT NULL,
    `cond` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('available','in_use','maintenance','damaged','retired') 
        DEFAULT 'available',
    `description` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP 
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_asset_code` (`asset_code`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`),

    CONSTRAINT `fk_inventory_created_by`
        FOREIGN KEY (`created_by`) 
        REFERENCES `users`(`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql)) {
    echo "✅ Table `inventory` created successfully\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();

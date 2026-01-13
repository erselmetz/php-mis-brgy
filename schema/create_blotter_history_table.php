<?php
/**
 * Create Blotter History Table
 * 
 * Tracks all status changes and updates to blotter cases including:
 * - Status changes (pending, under_investigation, resolved, dismissed)
 * - Field updates
 * - User actions
 */

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `blotter_history` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `blotter_id` INT(11) NOT NULL,
  `case_number` VARCHAR(50) NOT NULL,
  `action_type` ENUM('status_changed', 'updated', 'created', 'archived', 'restored') NOT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) DEFAULT NULL,
  `changed_field` VARCHAR(100) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `user_id` INT(11) NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_blotter_id` (`blotter_id`),
  INDEX `idx_case_number` (`case_number`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`blotter_id`) REFERENCES `blotter`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'blotter_history' created successfully.\n";
} else {
    echo "❌ Error creating table 'blotter_history': " . $conn->error . "\n";
}

$conn->close();
?>

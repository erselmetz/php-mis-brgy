<?php
/**
 * Create Term History Table
 * 
 * Tracks all term changes and updates for officers including:
 * - Term start/end changes
 * - Status changes (Active/Inactive)
 * - Position changes
 * - User actions
 */

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `term_history` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `officer_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `action_type` ENUM('term_started', 'term_ended', 'term_updated', 'status_changed', 'position_changed', 'archived', 'restored') NOT NULL,
  `old_position` VARCHAR(150) DEFAULT NULL,
  `new_position` VARCHAR(150) DEFAULT NULL,
  `old_term_start` DATE DEFAULT NULL,
  `new_term_start` DATE DEFAULT NULL,
  `old_term_end` DATE DEFAULT NULL,
  `new_term_end` DATE DEFAULT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) DEFAULT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_officer_id` (`officer_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`officer_id`) REFERENCES `officers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'term_history' created successfully.\n";
} else {
    echo "❌ Error creating table 'term_history': " . $conn->error . "\n";
}

$conn->close();
?>

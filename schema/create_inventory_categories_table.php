<?php
// schema/create_inventory_categories_table.php
// Creates the `inventory_category_list` table

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `inventory_category_list` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'inventory_category_list' created successfully.";
} else {
    echo "❌ Error creating table 'inventory_category_list': " . $conn->error;
}

$conn->close();
?>

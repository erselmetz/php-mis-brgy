<?php
// schema/create_medicine_categories_table.php

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS medicine_categories (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_name (name),
    KEY idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'medicine_categories' created successfully.\n";
} else {
    echo "❌ Error creating table 'medicine_categories': " . $conn->error . "\n";
}

$conn->close();
?>

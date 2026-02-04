<?php
// schema/create_medicines_table.php

include '../includes/db.php';

/**
 * IMPORTANT:
 * residents.id is INT(11) UNSIGNED in your schema.
 * We'll keep everything UNSIGNED for FK compatibility.
 */

$sql = "
CREATE TABLE IF NOT EXISTS medicines (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11) UNSIGNED DEFAULT NULL,

    name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,

    stock_qty INT(11) NOT NULL DEFAULT 0,
    reorder_level INT(11) NOT NULL DEFAULT 10,

    unit VARCHAR(50) DEFAULT 'pcs',
    expiration_date DATE DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_name (name),
    KEY idx_category_id (category_id),
    KEY idx_stock_qty (stock_qty),
    KEY idx_reorder_level (reorder_level),
    KEY idx_expiration_date (expiration_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) !== TRUE) {
    echo "❌ Error creating table 'medicines': " . $conn->error . "\n";
    $conn->close();
    exit;
}

echo "✅ Table 'medicines' created successfully.\n";
?>

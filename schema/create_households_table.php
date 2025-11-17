<?php
// schema/create_households_table.php
// Creates the `households` table for MIS Barangay (Pure PHP)

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS households (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    household_no VARCHAR(100) NOT NULL UNIQUE,
    address VARCHAR(255) NOT NULL,
    head_name VARCHAR(150) NOT NULL,
    total_members INT(3) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'households' created successfully.";
} else {
    echo "❌ Error creating table 'households': " . $conn->error;
}

?>

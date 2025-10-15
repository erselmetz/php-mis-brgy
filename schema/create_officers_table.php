<?php
// schema/create_officers_table.php
// Creates the `officers` table for MIS Barangay (Pure PHP)
// Merged with: add_resident_id_to_officers_table.php

include './includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS officers (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NULL,
    position VARCHAR(150) NOT NULL,
    term_start DATE NOT NULL,
    term_end DATE NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_officer_resident FOREIGN KEY (resident_id) REFERENCES residents(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'officers' created successfully (with resident_id column).";
} else {
    echo "❌ Error creating table 'officers': " . $conn->error;
}

$conn->close();
?>

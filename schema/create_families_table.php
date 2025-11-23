<?php
// schema/create_families_table.php
// Creates the `families` table for MIS Barangay (Pure PHP)

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS families (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    household_id INT(11) UNSIGNED NULL,
    family_name VARCHAR(150) NOT NULL,
    total_members INT(3) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_family_household FOREIGN KEY (household_id) REFERENCES households(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'families' created successfully.";
} else {
    echo "❌ Error creating table 'families': " . $conn->error;
}

$conn->close();
?>

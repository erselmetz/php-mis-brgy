<?php
// schema/create_residents_table.php
// Creates the `residents` table for MIS Barangay (Pure PHP)
// Fixed: removed generated column for age

include './includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS residents (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    household_id INT(11) UNSIGNED NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(10) NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    birthdate DATE NOT NULL,
    birthplace VARCHAR(255) NULL,
    civil_status ENUM('Single', 'Married', 'Widowed', 'Separated') DEFAULT 'Single',
    religion VARCHAR(100) NULL,
    occupation VARCHAR(150) NULL,
    citizenship VARCHAR(100) DEFAULT 'Filipino',
    contact_no VARCHAR(20) NULL,
    address VARCHAR(100) NULL,
    voter_status ENUM('Yes', 'No') DEFAULT 'No',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_resident_household FOREIGN KEY (household_id) REFERENCES households(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'residents' created successfully (with household_id column).";
} else {
    echo "❌ Error creating table 'residents': " . $conn->error;
}

$conn->close();
?>

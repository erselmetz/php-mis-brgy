<?php
// schema/create_immunizations_table.php

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS immunizations (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,

    vaccine_name VARCHAR(100) NOT NULL,
    dose VARCHAR(50) DEFAULT NULL,

    date_given DATE NOT NULL,
    next_schedule DATE DEFAULT NULL,

    administered_by VARCHAR(100) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_resident_id (resident_id),
    KEY idx_date_given (date_given),
    KEY idx_next_schedule (next_schedule),

    CONSTRAINT fk_immunizations_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'immunizations' created successfully.\n";
} else {
    echo "❌ Error creating table 'immunizations': " . $conn->error . "\n";
}

$conn->close();
?>

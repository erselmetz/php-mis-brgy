<?php
// schema/create_medicine_dispense_table.php

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS medicine_dispense (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,
    medicine_id INT(11) UNSIGNED NOT NULL,

    quantity INT(11) NOT NULL,
    dispense_date DATE NOT NULL,
    notes TEXT DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_resident_id (resident_id),
    KEY idx_medicine_id (medicine_id),
    KEY idx_dispense_date (dispense_date),

    CONSTRAINT fk_dispense_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_dispense_medicine
        FOREIGN KEY (medicine_id)
        REFERENCES medicines(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'medicine_dispense' created successfully.\n";
} else {
    echo "❌ Error creating table 'medicine_dispense': " . $conn->error . "\n";
}

$conn->close();
?>

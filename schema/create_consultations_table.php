<?php
// schema/create_consultations_table.php

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS consultations (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,

    complaint TEXT NOT NULL,
    diagnosis TEXT DEFAULT NULL,
    treatment TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,

    consultation_date DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_resident_id (resident_id),
    KEY idx_consultation_date (consultation_date),

    CONSTRAINT fk_consultations_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'consultations' created successfully.\n";
} else {
    echo "❌ Error creating table 'consultations': " . $conn->error . "\n";
}

$conn->close();
?>

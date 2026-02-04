<?php
// schema/create_health_metrics_table.php

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS health_metrics (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,

    weight DECIMAL(5,2) DEFAULT NULL,
    height DECIMAL(5,2) DEFAULT NULL,
    blood_pressure VARCHAR(20) DEFAULT NULL,
    temperature DECIMAL(4,1) DEFAULT NULL,

    recorded_at DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_resident_id (resident_id),
    KEY idx_recorded_at (recorded_at),

    CONSTRAINT fk_health_metrics_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'health_metrics' created successfully.\n";
} else {
    echo "❌ Error creating table 'health_metrics': " . $conn->error . "\n";
}

$conn->close();
?>

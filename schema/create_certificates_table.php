<?php
include './includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS certificates (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,
    certificate_type VARCHAR(100) NOT NULL,
    purpose TEXT NULL,
    issued_by VARCHAR(100) NULL,
    date_issued DATETIME DEFAULT CURRENT_TIMESTAMP, -- ✅ fixed: use DATETIME instead of DATE with CURRENT_DATE
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_certificate_resident FOREIGN KEY (resident_id) REFERENCES residents(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'certificates' created successfully.";
} else {
    echo "❌ Error creating table 'certificates': " . $conn->error;
}

$conn->close();
?>

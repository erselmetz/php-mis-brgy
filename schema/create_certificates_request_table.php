<?php
/**
 * Create certificate_request table
 * MIS Barangay - Certificate Request System
 */

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS certificate_request (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resident_id INT(11) UNSIGNED NOT NULL,
  certificate_type VARCHAR(100) NOT NULL,
  purpose VARCHAR(255) NOT NULL,
  issued_by INT(11) NOT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'certificate_request' created successfully.\n";
} else {
    echo "❌ Error creating table 'certificate_request': " . $conn->error . "\n";
}

?>


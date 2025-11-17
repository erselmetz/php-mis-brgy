<?php
// schema/create_blotter_table.php
// Creates the `blotter` table for MIS Barangay Blotter System

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS blotter (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(50) UNIQUE NOT NULL,
    complainant_name VARCHAR(255) NOT NULL,
    complainant_address TEXT,
    complainant_contact VARCHAR(20),
    respondent_name VARCHAR(255) NOT NULL,
    respondent_address TEXT,
    respondent_contact VARCHAR(20),
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_location TEXT NOT NULL,
    incident_description TEXT NOT NULL,
    status ENUM('pending', 'under_investigation', 'resolved', 'dismissed') DEFAULT 'pending',
    resolution TEXT,
    resolved_date DATE,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_case_number (case_number),
    INDEX idx_status (status),
    INDEX idx_incident_date (incident_date),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'blotter' created successfully.\n";
} else {
    echo "❌ Error creating table 'blotter': " . $conn->error . "\n";
}

// Function to generate case number
function generateCaseNumber($conn) {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blotter WHERE case_number LIKE ?");
    $pattern = "BLT-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'] + 1;
    return "BLT-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

?>


<?php
// schema/create_tanod_duty_schedule_table.php
// Creates the `tanod_duty_schedule` table for MIS Barangay (Pure PHP)

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS tanod_duty_schedule (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    duty_code       VARCHAR(20)  NOT NULL UNIQUE,
    tanod_name      VARCHAR(150) NOT NULL,
    duty_date       DATE         NOT NULL,
    shift           ENUM('morning','afternoon','night') NOT NULL DEFAULT 'morning',
    post_location   VARCHAR(200) DEFAULT NULL,
    notes           TEXT         DEFAULT NULL,
    status          ENUM('active','cancelled','completed') NOT NULL DEFAULT 'active',
    created_by      INT          DEFAULT NULL,
    updated_by      INT          DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_duty_date  (duty_date),
    INDEX idx_tanod_name (tanod_name),
    INDEX idx_shift      (shift),
    INDEX idx_status     (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'tanod_duty_schedule' created successfully.";
} else {
    echo "❌ Error creating table 'tanod_duty_schedule': " . $conn->error;
}

$conn->close();
?>

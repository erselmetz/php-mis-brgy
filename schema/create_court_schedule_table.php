<?php
// schema/create_court_schedule_table.php
// Creates the `court_schedule` table for MIS Barangay (Pure PHP)

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS court_schedule (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reservation_code VARCHAR(20) NOT NULL UNIQUE,
    facility        ENUM('basketball_court','multipurpose_area','gym') NOT NULL DEFAULT 'basketball_court',
    borrower_name   VARCHAR(150) NOT NULL,
    borrower_contact VARCHAR(20)  DEFAULT NULL,
    organization    VARCHAR(150)  DEFAULT NULL,
    purpose         VARCHAR(250)  NOT NULL,
    reservation_date DATE         NOT NULL,
    time_start      TIME          NOT NULL,
    time_end        TIME          NOT NULL,
    status          ENUM('pending','approved','denied','completed','cancelled') NOT NULL DEFAULT 'pending',
    remarks         TEXT          DEFAULT NULL,
    approved_by     INT           DEFAULT NULL,
    created_by      INT           DEFAULT NULL,
    updated_by      INT           DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_res_date  (reservation_date),
    INDEX idx_facility  (facility),
    INDEX idx_status    (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'court_schedule' created successfully.";
} else {
    echo "❌ Error creating table 'court_schedule': " . $conn->error;
}

$conn->close();
?>

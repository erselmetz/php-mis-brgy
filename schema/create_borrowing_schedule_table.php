<?php
// schema/create_borrowing_schedule_table.php
// Creates the `borrowing_schedule` table for MIS Barangay (Pure PHP)
include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS borrowing_schedule (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    borrow_code     VARCHAR(20)  NOT NULL UNIQUE,
    borrower_name   VARCHAR(150) NOT NULL,
    borrower_contact VARCHAR(20) DEFAULT NULL,
    item_name       VARCHAR(200) NOT NULL,
    inventory_id    INT          DEFAULT NULL,   -- FK to inventory table (optional)
    quantity        INT          NOT NULL DEFAULT 1,
    borrow_date     DATE         NOT NULL,
    return_date     DATE         NOT NULL,
    actual_return   DATE         DEFAULT NULL,
    purpose         VARCHAR(250) DEFAULT NULL,
    status          ENUM('borrowed','returned','overdue','cancelled') NOT NULL DEFAULT 'borrowed',
    condition_out   VARCHAR(100) DEFAULT NULL,
    condition_in    VARCHAR(100) DEFAULT NULL,
    notes           TEXT         DEFAULT NULL,
    created_by      INT          DEFAULT NULL,
    updated_by      INT          DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_borrow_date  (borrow_date),
    INDEX idx_return_date  (return_date),
    INDEX idx_borrower     (borrower_name),
    INDEX idx_status       (status),
    INDEX idx_inventory_id (inventory_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'borrowing_schedule' created successfully.";
} else {
    echo "❌ Error creating table 'borrowing_schedule': " . $conn->error;
}

$conn->close();
?>
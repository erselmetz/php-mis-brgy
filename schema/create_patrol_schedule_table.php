<?php
// schema/create_patrol_schedule_table.php
// Creates the `patrol_schedule` table for MIS Barangay (Pure PHP)

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS patrol_schedule (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    patrol_code     VARCHAR(20)  NOT NULL UNIQUE,
    team_name       VARCHAR(150) NOT NULL,
    patrol_date     DATE         NOT NULL,
    time_start      TIME         NOT NULL,
    time_end        TIME         NOT NULL,
    patrol_route    VARCHAR(300) DEFAULT NULL,
    area_covered    VARCHAR(300) DEFAULT NULL,
    is_weekly       TINYINT(1)   NOT NULL DEFAULT 0,
    week_day        TINYINT(1)   DEFAULT NULL, -- 0=Sun, 1=Mon ... 6=Sat (used if is_weekly=1)
    tanod_members   TEXT         DEFAULT NULL, -- comma-separated names
    notes           TEXT         DEFAULT NULL,
    status          ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_by      INT          DEFAULT NULL,
    updated_by      INT          DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patrol_date (patrol_date),
    INDEX idx_status      (status),
    INDEX idx_is_weekly   (is_weekly)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'patrol_schedule' created successfully.";
} else {
    echo "❌ Error creating table 'patrol_schedule': " . $conn->error;
}

$conn->close();
?>
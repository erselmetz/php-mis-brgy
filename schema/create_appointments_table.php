<?php
/**
 * Create appointments table
 * Run once: php schema/create_appointments_table.php
 */
include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS appointments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appt_code       VARCHAR(30)  NOT NULL UNIQUE,
    resident_id     INT          NOT NULL,
    appt_date       DATE         NOT NULL,
    appt_time       TIME         NOT NULL,
    appt_type       ENUM(
                        'general','maternal','family_planning',
                        'prenatal','postnatal','child_nutrition',
                        'immunization','dental','other'
                    ) NOT NULL DEFAULT 'general',
    purpose         VARCHAR(300) NOT NULL,
    health_worker   VARCHAR(100) DEFAULT NULL,
    status          ENUM('scheduled','completed','cancelled','no_show')
                    NOT NULL DEFAULT 'scheduled',
    notes           TEXT         DEFAULT NULL,
    created_by      INT          DEFAULT NULL,
    updated_by      INT          DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_appt_date    (appt_date),
    INDEX idx_resident_id  (resident_id),
    INDEX idx_status       (status),
    INDEX idx_appt_type    (appt_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql)) {
    echo "✅ Table `appointments` created.\n";
} else {
    echo "❌ " . $conn->error . "\n";
}
$conn->close();
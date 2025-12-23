<?php
// schema/create_events_table.php
// Creates the `events` table for MIS Barangay Events & Scheduling

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS events (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,

    event_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,

    event_date DATE NOT NULL,
    event_time TIME,

    location VARCHAR(255),

    priority ENUM('normal', 'important', 'urgent') DEFAULT 'normal',
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',

    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_event_code (event_code),
    INDEX idx_event_date (event_date),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'events' created successfully.\n";
} else {
    echo "❌ Error creating table 'events': " . $conn->error . "\n";
}

/**
 * Generate unique event code
 * Example: EVT-2025-0001
 */
function generateEventCode(mysqli $conn): string
{
    $year = date('Y');

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS count 
        FROM events 
        WHERE event_code LIKE ?
    ");

    $pattern = "EVT-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $count = ($row['count'] ?? 0) + 1;

    return "EVT-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

$conn->close();
?>

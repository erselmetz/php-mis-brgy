<?php
// schema/create_users_table.php
// Creates the `users` table for MIS Barangay (Pure PHP)

include '../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(244) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'users' created successfully.\n";
} else {
    echo "❌ Error creating table 'users': " . $conn->error . "\n";
}
?>

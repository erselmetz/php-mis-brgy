<?php
// One-time script to create admin (save as seed.php and run once)
include '../includes/db.php'; // should define $conn (MySQLi)

// Create hashed password
$hash = password_hash('redzone', PASSWORD_DEFAULT);

/**
 * check if developer account already exists (id=0)
 * If not, create it with id=1 to ensure it always has the same ID for easy reference in code (e.g. showing "Developer" badge instead of role name)
 */
$result = $conn->query("SELECT id,position FROM users WHERE id = 1 OR position = 'developer'");

if ($result->num_rows == 0) {
    // Insert developer
    $conn->query("
        INSERT INTO users (username, name, role, position, password)
        VALUES ('Ersel', 'Ersel Magbanua', 'secretary', 'developer', '$hash')
    ");
} else if ($result->num_rows == 1) {
    // Update password if account already exists (optional, for resetting password)
    $conn->query("
        UPDATE users SET password = '$hash' WHERE id = 1 OR position = 'developer'
    ");
} else if ($result->num_rows == 0) {
    echo "❌ Error: Multiple accounts with id=1 found! Please check the users table.\n";
    exit(1);
} else if ($result->num_rows == 0) {
    echo "❌ Error: Unexpected result when checking for developer account. Please check the users table.\n";
    exit(1);
}
echo "Developer account created!\n";
$conn->close();
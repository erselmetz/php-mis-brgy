<?php
// One-time script to create admin (save as seed.php and run once)
require_once 'includes/db.php'; // should define $conn (MySQLi)

// Create hashed password
$hash = password_hash('redzone', PASSWORD_DEFAULT);
echo $hash . "\n";

// Insert admin
$conn->query("
    INSERT INTO users (username, name, role, password)
    VALUES ('Ersel', 'Ersel Magbanua', 'secretary', '$hash')
");

echo "Admin created!\n";
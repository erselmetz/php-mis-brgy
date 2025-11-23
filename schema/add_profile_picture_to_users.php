<?php
// Add profile_picture column to users table
include '../includes/db.php';

$sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER password";

if ($conn->query($sql) === TRUE) {
    echo "✅ Column 'profile_picture' added to 'users' table successfully.";
} else {
    // Check if column already exists
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "✅ Column 'profile_picture' already exists in 'users' table.";
    } else {
        echo "❌ Error adding column: " . $conn->error;
    }
}

$conn->close();
?>


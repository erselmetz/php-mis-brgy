<?php
// schema/add_profile_picture_to_users.php
// Adds profile_picture column to users table

include '../includes/db.php';

// Check if column already exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
if ($checkColumn->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER password";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Column 'profile_picture' added to 'users' table successfully.\n";
    } else {
        echo "❌ Error adding column 'profile_picture': " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ Column 'profile_picture' already exists in 'users' table.\n";
}

$conn->close();
?>


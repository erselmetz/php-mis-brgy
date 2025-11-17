<?php
/**
 * Add profile_picture column to users table
 * MIS Barangay - Profile Picture Feature
 */

include '../includes/db.php';

// Helper function to check if column exists
if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $result->num_rows > 0;
    }
}

// Check if column already exists
if (columnExists($conn, 'users', 'profile_picture')) {
    echo "✅ Column 'profile_picture' already exists in 'users' table.\n";
} else {
    $sql = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER password";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Column 'profile_picture' added to 'users' table successfully.\n";
    } else {
        echo "❌ Error adding column: " . $conn->error . "\n";
    }
}

?>


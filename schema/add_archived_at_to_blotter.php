<?php
// schema/add_archived_at_to_blotter.php
// Adds archived_at field to the blotter table

include '../includes/db.php';

// Check if column already exists
$checkColumn = $conn->query("SHOW COLUMNS FROM blotter LIKE 'archived_at'");
if ($checkColumn->num_rows == 0) {
    $sql = "
    ALTER TABLE blotter 
    ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
    ADD INDEX idx_archived_at (archived_at);
    ";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Column 'archived_at' added to 'blotter' table successfully.\n";
    } else {
        echo "❌ Error adding column 'archived_at': " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ Column 'archived_at' already exists in 'blotter' table.\n";
}

$conn->close();
?>

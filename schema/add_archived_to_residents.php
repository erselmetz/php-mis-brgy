<?php
// schema/add_archived_to_residents.php
// Adds soft delete functionality to residents table

include '../includes/db.php';

// Check if column already exists
$checkColumn = $conn->query("SHOW COLUMNS FROM residents LIKE 'deleted_at'");
if ($checkColumn->num_rows == 0) {
    $sql = "
    ALTER TABLE residents
    ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
    ADD INDEX idx_deleted_at (deleted_at);
    ";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Added archived functionality to 'residents' table successfully.\n";
    } else {
        echo "❌ Error adding archived functionality to 'residents': " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ Column 'deleted_at' already exists in 'residents' table.\n";
}

$conn->close();
?>
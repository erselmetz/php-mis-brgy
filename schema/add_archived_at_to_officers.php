<?php
// schema/add_archived_at_to_officers.php
// Adds archived_at field to the officers table

include '../includes/db.php';

$checkColumn = $conn->query("SHOW COLUMNS FROM officers LIKE 'archived_at'");

if ($checkColumn->num_rows == 0) {
    $sql = "
    ALTER TABLE officers 
    ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
    ADD INDEX idx_archived_at (archived_at);
    ";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Column 'archived_at' added to 'officers' table successfully.\n";
    } else {
        echo "❌ Error adding column 'archived_at': " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ Column 'archived_at' already exists in 'officers' table.\n";
}

$conn->close();
?>

<?php
// schema/add_archived_to_residents.php
// Adds soft delete functionality to residents table

include '../includes/db.php';

$sql = "
ALTER TABLE residents
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
ADD INDEX idx_deleted_at (deleted_at);
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Added archived functionality to 'residents' table successfully.";
} else {
    echo "❌ Error adding archived functionality to 'residents': " . $conn->error;
}

$conn->close();
?>
<?php
/**
 * Migration: add archived_at to households table
 * Run once: php schema/add_archived_at_to_households.php
 */
include '../includes/db.php';

$check = $conn->query("SHOW COLUMNS FROM households LIKE 'archived_at'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE households
            ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
            ADD INDEX idx_archived_at (archived_at)";
    if ($conn->query($sql)) {
        echo "✅ Column 'archived_at' added to 'households' table.\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ Column 'archived_at' already exists.\n";
}
$conn->close();
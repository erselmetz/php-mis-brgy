<?php
/**
 * Fix: Add missing columns to immunizations table
 * Run once: php schema/fix_immunizations_columns.php
 *
 * The previous migration failed because MySQL's AFTER clause
 * references a column that hasn't been added yet in the same statement.
 * This script adds each column individually with existence checks.
 */

include '../includes/db.php';

$columns = [
    // column_name => definition (no AFTER clause)
    'route'            => "ENUM('IM','SC','ID','Oral','Nasal') DEFAULT 'IM'",
    'adverse_reaction' => "TEXT NULL",
    'is_defaulter'     => "TINYINT(1) DEFAULT 0",
    'catch_up'         => "TINYINT(1) DEFAULT 0 COMMENT 'Given outside normal schedule'",
    'care_visit_id'    => "INT NULL",
];

foreach ($columns as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM `immunizations` LIKE '{$col}'");
    if ($check && $check->num_rows > 0) {
        echo "ℹ️  Column `{$col}` already exists — skipped.\n";
        continue;
    }
    $sql = "ALTER TABLE `immunizations` ADD COLUMN `{$col}` {$def}";
    if ($conn->query($sql)) {
        echo "✅ Added column `{$col}`\n";
    } else {
        echo "❌ Failed to add `{$col}`: {$conn->error}\n";
    }
}

echo "\nDone.\n";
$conn->close();
<?php
/**
 * Migration: NIP Card Editing Support
 * Adds fields needed for editable immunization card per child.
 *
 * Run once: php schema/add_nip_card_fields.php
 */
include '../includes/db.php';

$log = [];

function safe_col(mysqli $c, string $tbl, string $col, string $def, array &$log): void {
    $r = $c->query("SHOW COLUMNS FROM `{$tbl}` LIKE '{$col}'");
    if ($r && $r->num_rows > 0) { $log[] = "ℹ️  `{$col}` already exists"; return; }
    if ($c->query("ALTER TABLE `{$tbl}` ADD COLUMN `{$col}` {$def}"))
        $log[] = "✅ Added `{$col}`";
    else
        $log[] = "❌ `{$col}`: {$c->error}";
}

// immunizations: ensure all NIP-card columns exist
$cols = [
    'schedule_id'        => "INT UNSIGNED NULL COMMENT 'FK to immunization_schedule'",
    'batch_number'       => "VARCHAR(50) NULL",
    'expiry_date'        => "DATE NULL",
    'site_given'         => "VARCHAR(100) NULL",
    'route'              => "ENUM('IM','SC','ID','Oral','Nasal') DEFAULT 'IM'",
    'adverse_reaction'   => "TEXT NULL",
    'is_defaulter'       => "TINYINT(1) DEFAULT 0",
    'catch_up'           => "TINYINT(1) DEFAULT 0",
    'care_visit_id'      => "INT NULL",
    'given_at_facility'  => "VARCHAR(150) NULL COMMENT 'Facility where given if not here'",
    'lot_number'         => "VARCHAR(50) NULL",
    'vvm_status'         => "ENUM('OK','WARN','DISCARD') DEFAULT 'OK' COMMENT 'Vaccine vial monitor status'",
    'weight_at_vaccination' => "DECIMAL(5,2) NULL COMMENT 'kg'",
    'temperature_at_vaccination' => "DECIMAL(4,1) NULL COMMENT 'Cold chain temp °C'",
];

foreach ($cols as $col => $def) {
    safe_col($conn, 'immunizations', $col, $def, $log);
}

// immunization_schedule: add editing-support columns
$schedCols = [
    'is_active'          => "TINYINT(1) DEFAULT 1",
    'notes'              => "TEXT NULL COMMENT 'Clinical notes / contraindications'",
    'min_age_days'       => "SMALLINT UNSIGNED NULL COMMENT 'Minimum age in days (for eligibility)'",
    'max_age_days'       => "SMALLINT UNSIGNED NULL COMMENT 'Maximum recommended age in days'",
    'catch_up_allowed'   => "TINYINT(1) DEFAULT 1",
];
foreach ($schedCols as $col => $def) {
    safe_col($conn, 'immunization_schedule', $col, $def, $log);
}

// Index to speed up NIP card queries
$indexes = [
    'idx_imm_schedule_id' => "ALTER TABLE `immunizations` ADD INDEX `idx_imm_schedule_id` (`schedule_id`)",
    'idx_imm_resident_date' => "ALTER TABLE `immunizations` ADD INDEX `idx_imm_resident_date` (`resident_id`, `date_given`)",
];
foreach ($indexes as $name => $sql) {
    $r = $conn->query("SHOW INDEX FROM immunizations WHERE Key_name='{$name}'");
    if ($r && $r->num_rows === 0) {
        if ($conn->query($sql)) $log[] = "✅ Index `{$name}` added";
        else $log[] = "⚠️  Index `{$name}`: " . $conn->error;
    } else {
        $log[] = "ℹ️  Index `{$name}` already exists";
    }
}

foreach ($log as $l) echo $l . "\n";
echo "\nDone.\n";
$conn->close();
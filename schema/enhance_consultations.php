<?php
/**
 * Enhance Consultations
 * - Adds vital signs + linking columns to consultations table
 * - Creates consultation_detail table for full clinical record
 * - Zero data loss: all existing rows are preserved, new columns are nullable
 *
 * Run once: php schema/enhance_consultations.php
 */

include '../includes/db.php';

/* ── helper ── */
function colExists(mysqli $c, string $tbl, string $col): bool {
    return (bool)$c->query("SHOW COLUMNS FROM `{$tbl}` LIKE '{$col}'")->num_rows;
}
function addCol(mysqli $c, string $tbl, string $col, string $def, string &$log): void {
    if (colExists($c, $tbl, $col)) {
        $log .= "  ℹ️  `{$col}` already exists\n";
        return;
    }
    if ($c->query("ALTER TABLE `{$tbl}` ADD COLUMN `{$col}` {$def}")) {
        $log .= "  ✅ Added `{$col}`\n";
    } else {
        $log .= "  ❌ `{$col}`: {$c->error}\n";
    }
}

$log = '';

/* ════════════════════════════════════════════════════
   STEP 1 — Add columns to existing consultations table
   All nullable → zero breakage on old rows
════════════════════════════════════════════════════ */
echo "📋 Enhancing `consultations` table...\n";

$cols = [
    /* Consult type — replaces the notes JSON program field */
    'consult_type'      => "ENUM('general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','other') DEFAULT 'general'",

    /* Care visit link — nullable, set when consult is part of a care programme */
    'care_visit_id'     => "INT NULL",

    /* Vital signs — stored directly for quick access */
    'temp_celsius'      => "DECIMAL(4,1) NULL COMMENT 'Temperature °C'",
    'bp_systolic'       => "SMALLINT UNSIGNED NULL",
    'bp_diastolic'      => "SMALLINT UNSIGNED NULL",
    'pulse_rate'        => "SMALLINT UNSIGNED NULL COMMENT 'bpm'",
    'respiratory_rate'  => "TINYINT UNSIGNED NULL COMMENT 'breaths/min'",
    'o2_saturation'     => "TINYINT UNSIGNED NULL COMMENT 'SpO2 %'",

    /* Body measurements */
    'weight_kg'         => "DECIMAL(5,2) NULL",
    'height_cm'         => "DECIMAL(5,2) NULL",
    'bmi'               => "DECIMAL(4,1) NULL COMMENT 'Auto-computed'",
    'waist_cm'          => "DECIMAL(5,1) NULL",

    /* Clinical assessment */
    'health_advice'     => "TEXT NULL COMMENT 'Health education / advice given'",
    'risk_level'        => "ENUM('Low','Moderate','High') DEFAULT 'Low'",
    'is_referred'       => "TINYINT(1) DEFAULT 0",
    'referred_to'       => "VARCHAR(150) NULL",
    'follow_up_date'    => "DATE NULL",

    /* Health worker (promoted from notes JSON) */
    'health_worker'     => "VARCHAR(100) NULL",

    /* Consult status (promoted from notes JSON) */
    'consult_status'    => "ENUM('Ongoing','Completed','Follow-up','Dismissed') DEFAULT 'Ongoing'",

    /* Indexes later */
];

foreach ($cols as $col => $def) {
    addCol($conn, 'consultations', $col, $def, $log);
}
echo $log;

/* Add indexes if not present */
$indexes = [
    'idx_consult_type'     => "ALTER TABLE consultations ADD INDEX idx_consult_type (consult_type)",
    'idx_care_visit_id'    => "ALTER TABLE consultations ADD INDEX idx_care_visit_id (care_visit_id)",
    'idx_risk_level'       => "ALTER TABLE consultations ADD INDEX idx_risk_level (risk_level)",
    'idx_follow_up_date'   => "ALTER TABLE consultations ADD INDEX idx_follow_up_date (follow_up_date)",
];
foreach ($indexes as $name => $sql) {
    $check = $conn->query("SHOW INDEX FROM consultations WHERE Key_name = '{$name}'");
    if ($check && $check->num_rows === 0) {
        $conn->query($sql);
        echo "  ✅ Index `{$name}` added\n";
    }
}

/* ════════════════════════════════════════════════════
   STEP 2 — consultation_detail: full clinical record
════════════════════════════════════════════════════ */
echo "\n📋 Creating `consultation_detail` table...\n";

$sql = "
CREATE TABLE IF NOT EXISTS consultation_detail (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consultation_id         INT NOT NULL UNIQUE,

    /* ── Chief complaint (mirrors consultations.complaint but structured) ── */
    chief_complaint         TEXT NULL,
    complaint_duration      VARCHAR(100) NULL COMMENT 'e.g. 3 days, 2 weeks',
    complaint_onset         ENUM('Sudden','Gradual','Chronic') DEFAULT 'Sudden',

    /* ── Diagnosis ── */
    primary_diagnosis       TEXT NULL,
    secondary_diagnosis     TEXT NULL,
    icd_code                VARCHAR(20) NULL COMMENT 'ICD-10 code if known',

    /* ── Treatment & prescription ── */
    treatment               TEXT NULL,
    medicines_prescribed    TEXT NULL,
    procedures_done         TEXT NULL,

    /* ── Health advice & education ── */
    health_advice           TEXT NULL,
    lifestyle_advice        TEXT NULL COMMENT 'Diet, exercise, smoking cessation etc.',
    patient_education       TEXT NULL COMMENT 'Topics explained to patient',

    /* ── Health potential / risk profile ── */
    smoking_status          ENUM('Never','Former','Current','NA') DEFAULT 'NA',
    alcohol_use             ENUM('None','Occasional','Regular','Heavy','NA') DEFAULT 'NA',
    physical_activity       ENUM('Sedentary','Light','Moderate','Active','NA') DEFAULT 'NA',
    nutritional_status      ENUM('Normal','Underweight','Overweight','Obese','NA') DEFAULT 'NA',
    mental_health_screen    ENUM('Not screened','Normal','Needs follow-up','Referred') DEFAULT 'Not screened',

    /* ── Complete medical history ── */
    past_medical_history    TEXT NULL COMMENT 'Previous illnesses, surgeries, hospitalizations',
    family_history          TEXT NULL COMMENT 'Hereditary conditions in family',
    current_medications     TEXT NULL COMMENT 'Maintenance meds at time of visit',
    known_allergies         TEXT NULL,
    immunization_history    TEXT NULL,

    /* ── Social history ── */
    occupation              VARCHAR(150) NULL,
    civil_status            VARCHAR(50) NULL,
    educational_attainment  VARCHAR(100) NULL,
    living_conditions       TEXT NULL COMMENT 'Housing, number of household members',

    /* ── Assessment & plan ── */
    assessment              TEXT NULL,
    plan                    TEXT NULL,
    prognosis               ENUM('Good','Fair','Poor','NA') DEFAULT 'NA',

    /* ── Timestamps ── */
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cd_consult_id (consultation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if ($conn->query($sql)) {
    echo "✅ `consultation_detail` created\n";
} else {
    echo "❌ {$conn->error}\n";
}

/* ════════════════════════════════════════════════════
   STEP 3 — Back-fill: migrate notes JSON → new columns
   Only for existing rows that have a notes JSON blob
════════════════════════════════════════════════════ */
echo "\n📋 Back-filling notes JSON → new columns...\n";

$rows = $conn->query("
    SELECT id, notes FROM consultations
    WHERE notes IS NOT NULL AND notes LIKE '{%'
    AND (consult_type = 'general' OR consult_type IS NULL)
");

$updated = 0;
$skipped = 0;
if ($rows) {
    while ($r = $rows->fetch_assoc()) {
        $meta = json_decode($r['notes'], true);
        if (!is_array($meta)) { $skipped++; continue; }

        $program     = $meta['program']       ?? null;
        $status      = $meta['status']        ?? null;
        $worker      = $meta['health_worker'] ?? null;

        // Map program to consult_type enum
        $allowed = ['general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','other'];
        $ctype   = in_array($program, $allowed, true) ? $program : 'general';

        $statusMap = [
            'Completed' => 'Completed', 'Ongoing' => 'Ongoing',
            'Follow-up' => 'Follow-up', 'Dismissed' => 'Dismissed',
        ];
        $cstatus = $statusMap[$status] ?? 'Ongoing';

        $upd = $conn->prepare("
            UPDATE consultations
            SET consult_type = ?, consult_status = ?, health_worker = ?
            WHERE id = ? AND (consult_type = 'general' OR consult_type IS NULL)
        ");
        $upd->bind_param('sssi', $ctype, $cstatus, $worker, $r['id']);
        $upd->execute();
        $updated++;
    }
}
echo "  ✅ Migrated {$updated} existing rows from notes JSON\n";
echo "  ℹ️  Skipped {$skipped} rows (not JSON or already migrated)\n";

echo "\n═══════════════════════════════════════\n";
echo "✅ Enhancement complete. Zero data loss.\n";
echo "   All old consultation records still accessible.\n";
echo "   New columns are nullable → no form breaks.\n";

$conn->close();
<?php
/**
 * Database SQL Generator — Fixed Version
 *
 * Reads all schema PHP files and generates database_complete.sql.
 * Fixed: ensures every statement ends with a semicolon + blank line,
 * so phpMyAdmin / MariaDB imports work without delimiter errors.
 */

$schemaDir  = __DIR__;
$outputFile = $schemaDir . '/database_complete.sql';

// ── Migration order & categories ─────────────────────────────────────────────
$migrations = [
    'Initial Setup' => [
        'description' => 'Creates all base tables required for the system',
        'files' => [
            'create_users_table.php',
            'create_households_table.php',
            'create_families_table.php',
            'create_residents_table.php',
            'create_officers_table.php',
            'create_certificates_request_table.php',
            'create_blotter_table.php',
            'create_events_scheduling.php',
            'create_inventory_table.php',
            'create_inventory_categories_table.php',
            'create_inventory_audit_trail_table.php',
            'create_medicines_table.php',
            'create_medicine_categories_table.php',
            'create_medicine_dispense_table.php',
            'create_health_metrics_table.php',
            'create_immunizations_table.php',
            'create_consultations_table.php',
            'create_backups_table.php',
            'create_patrol_schedule_table.php',
            'create_tanod_duty_schedule_table.php',
            'create_court_schedule_table.php',
            'create_borrowing_schedule_table.php',
            'create_appointments_table.php',
        ]
    ],
    'Feature Additions' => [
        'description' => 'Adds new features and columns to existing tables',
        'files' => [
            'add_profile_picture_to_users.php',
            'add_archived_to_residents.php',
            'add_archived_at_to_blotter.php',
            'create_blotter_history_table.php',
            'add_archived_at_to_officers.php',
            'create_term_history_table.php',
            'add_archived_at_to_households.php',
            'create_care_visits_table.php',
        ]
    ],
    'Structural Changes' => [
        'description' => 'Major schema changes and refactoring',
        'files' => [
            'merge_staff_officers.php',
            'make_officer_term_nullable.php',
            'enhance_consultations.php',
        ]
    ]
];

// ── SQL blocks that cannot be auto-extracted (dynamic or multi-statement) ────
// Written here verbatim so the output is always correct.
$manualSql = [

    // fix_immunizations_columns.php — dynamic loop, extract manually
    'fix_immunizations_columns.php' => <<<'SQL'
-- Add extra columns to immunizations (safe: skips if already present)
ALTER TABLE `immunizations`
    ADD COLUMN IF NOT EXISTS `route`            ENUM('IM','SC','ID','Oral','Nasal') DEFAULT 'IM',
    ADD COLUMN IF NOT EXISTS `adverse_reaction` TEXT NULL,
    ADD COLUMN IF NOT EXISTS `is_defaulter`     TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `catch_up`         TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `care_visit_id`    INT NULL;
SQL,

    // add_nip_card_field.php — dynamic loop, extract manually
    'add_nip_card_field.php' => <<<'SQL'
-- NIP card editing support columns on immunizations
ALTER TABLE `immunizations`
    ADD COLUMN IF NOT EXISTS `schedule_id`               INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `batch_number`              VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS `expiry_date`               DATE NULL,
    ADD COLUMN IF NOT EXISTS `site_given`                VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS `route`                     ENUM('IM','SC','ID','Oral','Nasal') DEFAULT 'IM',
    ADD COLUMN IF NOT EXISTS `adverse_reaction`          TEXT NULL,
    ADD COLUMN IF NOT EXISTS `is_defaulter`              TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `catch_up`                  TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `care_visit_id`             INT NULL,
    ADD COLUMN IF NOT EXISTS `given_at_facility`         VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS `lot_number`                VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS `vvm_status`                ENUM('OK','WARN','DISCARD') DEFAULT 'OK',
    ADD COLUMN IF NOT EXISTS `weight_at_vaccination`     DECIMAL(5,2) NULL,
    ADD COLUMN IF NOT EXISTS `temperature_at_vaccination` DECIMAL(4,1) NULL;

ALTER TABLE `immunization_schedule`
    ADD COLUMN IF NOT EXISTS `is_active`        TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `notes`            TEXT NULL,
    ADD COLUMN IF NOT EXISTS `min_age_days`     SMALLINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `max_age_days`     SMALLINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `catch_up_allowed` TINYINT(1) DEFAULT 1;

-- Indexes (ignore errors if already exist)
ALTER TABLE `immunizations`
    ADD INDEX IF NOT EXISTS `idx_imm_schedule_id`  (`schedule_id`),
    ADD INDEX IF NOT EXISTS `idx_imm_resident_date` (`resident_id`, `date_given`);
SQL,

    // create_care_visits_enhanced.php — huge multi-block, write inline
    'create_care_visits_enhanced.php' => <<<'SQL'
CREATE TABLE IF NOT EXISTS care_visits (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    care_type   ENUM('maternal','family_planning','prenatal','postnatal','child_nutrition','immunization') NOT NULL,
    visit_date  DATE NOT NULL,
    details     LONGTEXT NULL,
    notes       TEXT NULL,
    created_by  INT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_care_resident  (resident_id),
    INDEX idx_care_type_date (care_type, visit_date),
    INDEX idx_visit_date     (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS maternal_profile (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id         INT NOT NULL UNIQUE,
    gravida             TINYINT UNSIGNED DEFAULT 0,
    term                TINYINT UNSIGNED DEFAULT 0,
    preterm             TINYINT UNSIGNED DEFAULT 0,
    abortions           TINYINT UNSIGNED DEFAULT 0,
    living_children     TINYINT UNSIGNED DEFAULT 0,
    hx_pre_eclampsia    TINYINT(1) DEFAULT 0,
    hx_pph              TINYINT(1) DEFAULT 0,
    hx_cesarean         TINYINT(1) DEFAULT 0,
    hx_ectopic          TINYINT(1) DEFAULT 0,
    hx_stillbirth       TINYINT(1) DEFAULT 0,
    has_diabetes        TINYINT(1) DEFAULT 0,
    has_hypertension    TINYINT(1) DEFAULT 0,
    has_hiv             TINYINT(1) DEFAULT 0,
    has_anemia          TINYINT(1) DEFAULT 0,
    other_conditions    TEXT NULL,
    blood_type          ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown') DEFAULT 'Unknown',
    notes               TEXT NULL,
    updated_by          INT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mp_resident (resident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS family_planning_record (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id         INT NOT NULL,
    care_visit_id       INT NULL,
    method              ENUM('Pills','Injectable','IUD','Implant','Condom','LAM','BTL','Vasectomy','NFP','SDM','Abstinence','Other') NOT NULL,
    method_other        VARCHAR(100) NULL,
    method_start_date   DATE NULL,
    next_supply_date    DATE NULL,
    next_checkup_date   DATE NULL,
    is_new_acceptor     TINYINT(1) DEFAULT 0,
    is_method_switch    TINYINT(1) DEFAULT 0,
    prev_method         VARCHAR(100) NULL,
    side_effects        TEXT NULL,
    counseling_notes    TEXT NULL,
    pills_given         TINYINT UNSIGNED DEFAULT 0,
    injectables_given   TINYINT UNSIGNED DEFAULT 0,
    health_worker       VARCHAR(100) NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fp_resident  (resident_id),
    INDEX idx_fp_next_date (next_supply_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prenatal_visit (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id         INT NOT NULL,
    care_visit_id       INT NULL,
    lmp_date            DATE NULL,
    edd_date            DATE NULL,
    aog_weeks           TINYINT UNSIGNED NULL,
    visit_number        TINYINT UNSIGNED DEFAULT 1,
    weight_kg           DECIMAL(5,2) NULL,
    bp_systolic         SMALLINT UNSIGNED NULL,
    bp_diastolic        SMALLINT UNSIGNED NULL,
    fundal_height_cm    DECIMAL(4,1) NULL,
    fetal_heart_rate    SMALLINT UNSIGNED NULL,
    fetal_presentation  ENUM('Cephalic','Breech','Transverse','Unknown') DEFAULT 'Unknown',
    folic_acid_given    TINYINT(1) DEFAULT 0,
    iron_given          TINYINT(1) DEFAULT 0,
    iron_tablets_qty    TINYINT UNSIGNED DEFAULT 0,
    calcium_given       TINYINT(1) DEFAULT 0,
    iodine_given        TINYINT(1) DEFAULT 0,
    tt_dose             ENUM('None','TT1','TT2','TT3','TT4','TT5','TD') DEFAULT 'None',
    tt_date             DATE NULL,
    hgb_result          DECIMAL(4,1) NULL,
    urinalysis_done     TINYINT(1) DEFAULT 0,
    blood_type_done     TINYINT(1) DEFAULT 0,
    hiv_test_done       TINYINT(1) DEFAULT 0,
    hiv_result          ENUM('Not done','Negative','Positive','Referred') DEFAULT 'Not done',
    syphilis_done       TINYINT(1) DEFAULT 0,
    syphilis_result     ENUM('Not done','Non-reactive','Reactive','Referred') DEFAULT 'Not done',
    risk_level          ENUM('Low','Moderate','High') DEFAULT 'Low',
    risk_notes          TEXT NULL,
    chief_complaint     TEXT NULL,
    assessment          TEXT NULL,
    plan                TEXT NULL,
    health_worker       VARCHAR(100) NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pv_resident (resident_id),
    INDEX idx_pv_lmp      (lmp_date),
    INDEX idx_pv_edd      (edd_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS postnatal_visit (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id             INT NOT NULL,
    care_visit_id           INT NULL,
    delivery_date           DATE NULL,
    delivery_type           ENUM('NSD','CS','Assisted','Unknown') DEFAULT 'Unknown',
    delivery_facility       VARCHAR(150) NULL,
    birth_attendant         VARCHAR(100) NULL,
    visit_number            TINYINT UNSIGNED DEFAULT 1,
    weight_kg               DECIMAL(5,2) NULL,
    bp_systolic             SMALLINT UNSIGNED NULL,
    bp_diastolic            SMALLINT UNSIGNED NULL,
    lochia_type             ENUM('Rubra','Serosa','Alba','Abnormal','Not checked') DEFAULT 'Not checked',
    fundal_involution       ENUM('Normal','Subinvolution','Not checked') DEFAULT 'Not checked',
    episiotomy_healing      ENUM('Normal','Infected','NA') DEFAULT 'NA',
    cs_wound_healing        ENUM('Normal','Infected','NA','Not checked') DEFAULT 'NA',
    breastfeeding_status    ENUM('Exclusive','Mixed','Not breastfeeding','NA') DEFAULT 'NA',
    ppd_score               TINYINT UNSIGNED NULL,
    ppd_referred            TINYINT(1) DEFAULT 0,
    newborn_weight_g        SMALLINT UNSIGNED NULL,
    newborn_length_cm       DECIMAL(4,1) NULL,
    apgar_1min              TINYINT UNSIGNED NULL,
    apgar_5min              TINYINT UNSIGNED NULL,
    cord_status             ENUM('Normal','Infected','Healing','NA') DEFAULT 'NA',
    jaundice                TINYINT(1) DEFAULT 0,
    newborn_screening_done  TINYINT(1) DEFAULT 0,
    bcg_given               TINYINT(1) DEFAULT 0,
    hb_vaccine_given        TINYINT(1) DEFAULT 0,
    fp_counseled            TINYINT(1) DEFAULT 0,
    fp_method_chosen        VARCHAR(100) NULL,
    chief_complaint         TEXT NULL,
    assessment              TEXT NULL,
    plan                    TEXT NULL,
    health_worker           VARCHAR(100) NULL,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pnc_resident (resident_id),
    INDEX idx_pnc_delivery (delivery_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS child_nutrition_visit (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id         INT NOT NULL,
    care_visit_id       INT NULL,
    visit_date          DATE NOT NULL,
    age_months          TINYINT UNSIGNED NULL,
    weight_kg           DECIMAL(5,3) NULL,
    height_cm           DECIMAL(5,2) NULL,
    muac_cm             DECIMAL(4,1) NULL,
    waz                 DECIMAL(5,2) NULL,
    haz                 DECIMAL(5,2) NULL,
    whz                 DECIMAL(5,2) NULL,
    stunting_status     ENUM('Normal','Mild','Moderate','Severe','Not assessed') DEFAULT 'Not assessed',
    wasting_status      ENUM('Normal','Mild','Moderate','Severe','Not assessed') DEFAULT 'Not assessed',
    underweight_status  ENUM('Normal','Mild','Moderate','Severe','Not assessed') DEFAULT 'Not assessed',
    breastfeeding       ENUM('Exclusive','Mixed','Complementary','Weaned','NA') DEFAULT 'NA',
    complementary_intro DATE NULL,
    feeding_problems    TEXT NULL,
    vita_supplemented   TINYINT(1) DEFAULT 0,
    vita_dose           ENUM('100000 IU','200000 IU','NA') DEFAULT 'NA',
    vita_date           DATE NULL,
    iron_supplemented   TINYINT(1) DEFAULT 0,
    zinc_given          TINYINT(1) DEFAULT 0,
    deworming_done      TINYINT(1) DEFAULT 0,
    deworming_date      DATE NULL,
    counseling_given    TINYINT(1) DEFAULT 0,
    counseling_notes    TEXT NULL,
    referred            TINYINT(1) DEFAULT 0,
    referral_reason     TEXT NULL,
    health_worker       VARCHAR(100) NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cn_resident   (resident_id),
    INDEX idx_cn_visit_date (visit_date),
    INDEX idx_cn_stunting   (stunting_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS immunization_schedule (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vaccine_name    VARCHAR(100) NOT NULL,
    dose_label      VARCHAR(50)  NOT NULL,
    dose_number     TINYINT UNSIGNED DEFAULT 1,
    target_age_days SMALLINT UNSIGNED NULL,
    target_age_label VARCHAR(50) NULL,
    interval_days   SMALLINT UNSIGNED NULL,
    disease_protected VARCHAR(200) NULL,
    route           ENUM('IM','SC','ID','Oral','Nasal') DEFAULT 'IM',
    site            VARCHAR(100) NULL,
    is_nip          TINYINT(1) DEFAULT 1,
    sort_order      TINYINT UNSIGNED DEFAULT 0,
    UNIQUE KEY uk_vaccine_dose (vaccine_name, dose_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL,
];

// ── Helper: extract the first $sql = "..." block from a PHP file ─────────────
function extractSqlFromPhp(string $filePath): ?string
{
    $content = file_get_contents($filePath);

    // Try double-quoted heredoc / regular string
    $patterns = [
        '/\$sql\s*=\s*"([\s\S]*?)"\s*;/U',      // double-quoted (non-greedy)
        "/\\\$sql\s*=\s*'([\s\S]*?)'\s*;/U",     // single-quoted (non-greedy)
        '/\$sql\s*=\s*<<<SQL\s([\s\S]*?)\nSQL\s*;/', // heredoc SQL
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }
    }

    return null;
}

// ── Helper: ensure a SQL block ends with exactly one semicolon ───────────────
function ensureSemicolon(string $sql): string
{
    $sql = rtrim($sql);
    if ($sql === '') return '';

    // If the last non-whitespace character isn't ; add one
    if (substr($sql, -1) !== ';') {
        $sql .= ';';
    }
    return $sql;
}

// ── Build the output ─────────────────────────────────────────────────────────
$out = '';
$out .= "-- =====================================================\n";
$out .= "-- MIS BARANGAY - Complete Database Schema\n";
$out .= "-- Generated: " . date('F d, Y \a\t H:i:s') . "\n";
$out .= "-- =====================================================\n";
$out .= "-- Usage:\n";
$out .= "--   1. Create a new database (e.g. php_mis_brgy)\n";
$out .= "--   2. Import this file via phpMyAdmin or CLI:\n";
$out .= "--      mysql -u root php_mis_brgy < database_complete.sql\n";
$out .= "-- =====================================================\n\n";

$out .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

$totalFiles     = 0;
$processedFiles = 0;

foreach ($migrations as $category => $config) {
    $out .= "-- =====================================================\n";
    $out .= "-- SECTION: " . strtoupper($category) . "\n";
    $out .= "-- " . $config['description'] . "\n";
    $out .= "-- =====================================================\n\n";

    foreach ($config['files'] as $file) {
        $totalFiles++;
        $filePath = $schemaDir . '/' . $file;

        $out .= "-- File: {$file}\n";

        // Use manual override if defined
        if (isset($manualSql[$file])) {
            // manualSql may contain multiple statements — keep as-is,
            // just ensure each individual statement ends correctly
            $block = trim($manualSql[$file]);
            // Split on semicolons to normalise, then rejoin
            $statements = preg_split('/;\s*\n/', $block);
            $normalized = [];
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt !== '') {
                    $normalized[] = $stmt . ';';
                }
            }
            $out .= implode("\n\n", $normalized) . "\n\n";
            $processedFiles++;
            continue;
        }

        if (!file_exists($filePath)) {
            $out .= "-- WARNING: File not found — skipped\n\n";
            continue;
        }

        $sql = extractSqlFromPhp($filePath);

        if (empty($sql)) {
            $out .= "-- WARNING: No SQL found in this file — skipped\n\n";
            continue;
        }

        $out .= ensureSemicolon($sql) . "\n\n";
        $processedFiles++;
    }
}

// ── Additional manual sections: consultation_detail ─────────────────────────
$out .= "-- =====================================================\n";
$out .= "-- SECTION: ENHANCED CONSULTATIONS\n";
$out .= "-- =====================================================\n\n";

$out .= <<<'SQL'
-- File: enhance_consultations.php (consultation_detail table)
CREATE TABLE IF NOT EXISTS consultation_detail (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consultation_id         INT NOT NULL UNIQUE,
    chief_complaint         TEXT NULL,
    complaint_duration      VARCHAR(100) NULL,
    complaint_onset         ENUM('Sudden','Gradual','Chronic') DEFAULT 'Sudden',
    primary_diagnosis       TEXT NULL,
    secondary_diagnosis     TEXT NULL,
    icd_code                VARCHAR(20) NULL,
    treatment               TEXT NULL,
    medicines_prescribed    TEXT NULL,
    procedures_done         TEXT NULL,
    health_advice           TEXT NULL,
    lifestyle_advice        TEXT NULL,
    patient_education       TEXT NULL,
    smoking_status          ENUM('Never','Former','Current','NA') DEFAULT 'NA',
    alcohol_use             ENUM('None','Occasional','Regular','Heavy','NA') DEFAULT 'NA',
    physical_activity       ENUM('Sedentary','Light','Moderate','Active','NA') DEFAULT 'NA',
    nutritional_status      ENUM('Normal','Underweight','Overweight','Obese','NA') DEFAULT 'NA',
    mental_health_screen    ENUM('Not screened','Normal','Needs follow-up','Referred') DEFAULT 'Not screened',
    past_medical_history    TEXT NULL,
    family_history          TEXT NULL,
    current_medications     TEXT NULL,
    known_allergies         TEXT NULL,
    immunization_history    TEXT NULL,
    occupation              VARCHAR(150) NULL,
    civil_status            VARCHAR(50) NULL,
    educational_attainment  VARCHAR(100) NULL,
    living_conditions       TEXT NULL,
    assessment              TEXT NULL,
    plan                    TEXT NULL,
    prognosis               ENUM('Good','Fair','Poor','NA') DEFAULT 'NA',
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cd_consult_id (consultation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhanced consultations: add extra columns (safe)
ALTER TABLE `consultations`
    ADD COLUMN IF NOT EXISTS `consult_type`     ENUM('general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','other') DEFAULT 'general',
    ADD COLUMN IF NOT EXISTS `care_visit_id`    INT NULL,
    ADD COLUMN IF NOT EXISTS `temp_celsius`     DECIMAL(4,1) NULL,
    ADD COLUMN IF NOT EXISTS `bp_systolic`      SMALLINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `bp_diastolic`     SMALLINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `pulse_rate`       SMALLINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `respiratory_rate` TINYINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `o2_saturation`    TINYINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `weight_kg`        DECIMAL(5,2) NULL,
    ADD COLUMN IF NOT EXISTS `height_cm`        DECIMAL(5,2) NULL,
    ADD COLUMN IF NOT EXISTS `bmi`              DECIMAL(4,1) NULL,
    ADD COLUMN IF NOT EXISTS `waist_cm`         DECIMAL(5,1) NULL,
    ADD COLUMN IF NOT EXISTS `health_advice`    TEXT NULL,
    ADD COLUMN IF NOT EXISTS `risk_level`       ENUM('Low','Moderate','High') DEFAULT 'Low',
    ADD COLUMN IF NOT EXISTS `is_referred`      TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `referred_to`      VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS `follow_up_date`   DATE NULL,
    ADD COLUMN IF NOT EXISTS `health_worker`    VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS `consult_status`   ENUM('Ongoing','Completed','Follow-up','Dismissed') DEFAULT 'Ongoing';

SQL;

$out .= "\n";

// ── Structural changes extracted from PHP files ──────────────────────────────
$out .= "-- =====================================================\n";
$out .= "-- SECTION: STRUCTURAL CHANGES\n";
$out .= "-- =====================================================\n\n";

// merge_staff_officers.php
$out .= <<<'SQL'
-- File: merge_staff_officers.php
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `position` VARCHAR(150) NULL AFTER `role`;

ALTER TABLE `officers`
    ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL AFTER `id`;

-- FK (only add if missing — ignore error if already present)
ALTER TABLE `officers`
    ADD CONSTRAINT `fk_officer_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

SQL;

$out .= "\n";

// make_officer_term_nullable.php
$out .= <<<'SQL'
-- File: make_officer_term_nullable.php
ALTER TABLE `officers`
    MODIFY COLUMN `term_start` DATE NULL DEFAULT NULL,
    MODIFY COLUMN `term_end`   DATE NULL DEFAULT NULL;

SQL;

$out .= "\n";

// ── Re-enable FK checks ───────────────────────────────────────────────────────
$out .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";

$out .= "-- =====================================================\n";
$out .= "-- END OF SCHEMA\n";
$out .= "-- Files processed: {$processedFiles}/{$totalFiles}\n";
$out .= "-- =====================================================\n";

// ── Write file ────────────────────────────────────────────────────────────────
if (file_put_contents($outputFile, $out) !== false) {
    echo "✅ SUCCESS: database_complete.sql generated!\n";
    echo "   Location : {$outputFile}\n";
    echo "   Processed: {$processedFiles}/{$totalFiles} files\n";
    exit(0);
} else {
    echo "❌ ERROR: Could not write to {$outputFile}\n";
    echo "   Check file permissions.\n";
    exit(1);
}
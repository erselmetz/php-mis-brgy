<?php
/**
 * Enhanced Care Visits Schema Migration
 * 
 * Upgrades the care_visits table to support all 6 care modules with
 * structured columns instead of a flat JSON blob.
 * 
 * Run once: php schema/create_care_visits_enhanced.php
 */

include '../includes/db.php';

$migrations = [];

/* ════════════════════════════════════════════════════════════════
   STEP 1 — Create or verify base care_visits table
════════════════════════════════════════════════════════════════ */
$migrations[] = [
    'name' => 'care_visits base table',
    'sql'  => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

/* ════════════════════════════════════════════════════════════════
   STEP 2 — Maternal health: obstetric history & risk profile table
════════════════════════════════════════════════════════════════ */
$migrations[] = [
    'name' => 'maternal_profile table',
    'sql'  => "
        CREATE TABLE IF NOT EXISTS maternal_profile (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resident_id         INT NOT NULL UNIQUE,

            -- GTPAL obstetric history
            gravida             TINYINT UNSIGNED DEFAULT 0 COMMENT 'Total pregnancies',
            term                TINYINT UNSIGNED DEFAULT 0 COMMENT 'Full-term births',
            preterm             TINYINT UNSIGNED DEFAULT 0 COMMENT 'Preterm births',
            abortions           TINYINT UNSIGNED DEFAULT 0 COMMENT 'Abortions/miscarriages',
            living_children     TINYINT UNSIGNED DEFAULT 0 COMMENT 'Living children',

            -- Risk profiling (history of complications)
            hx_pre_eclampsia    TINYINT(1) DEFAULT 0,
            hx_pph              TINYINT(1) DEFAULT 0 COMMENT 'Postpartum hemorrhage',
            hx_cesarean         TINYINT(1) DEFAULT 0,
            hx_ectopic          TINYINT(1) DEFAULT 0,
            hx_stillbirth       TINYINT(1) DEFAULT 0,

            -- Chronic conditions affecting reproductive health
            has_diabetes        TINYINT(1) DEFAULT 0,
            has_hypertension    TINYINT(1) DEFAULT 0,
            has_hiv             TINYINT(1) DEFAULT 0,
            has_anemia          TINYINT(1) DEFAULT 0,
            other_conditions    TEXT NULL,

            -- Blood type
            blood_type          ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown') DEFAULT 'Unknown',

            notes               TEXT NULL,
            updated_by          INT NULL,
            created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_mp_resident (resident_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

/* ════════════════════════════════════════════════════════════════
   STEP 3 — Family planning: method tracking
════════════════════════════════════════════════════════════════ */
$migrations[] = [
    'name' => 'family_planning_record table',
    'sql'  => "
        CREATE TABLE IF NOT EXISTS family_planning_record (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resident_id         INT NOT NULL,
            care_visit_id       INT NULL COMMENT 'FK to care_visits',

            -- Current method
            method              ENUM(
                                    'Pills','Injectable','IUD','Implant',
                                    'Condom','LAM','BTL','Vasectomy',
                                    'NFP','SDM','Abstinence','Other'
                                ) NOT NULL,
            method_other        VARCHAR(100) NULL,
            method_start_date   DATE NULL,
            next_supply_date    DATE NULL,
            next_checkup_date   DATE NULL,

            -- Counseling
            is_new_acceptor     TINYINT(1) DEFAULT 0,
            is_method_switch    TINYINT(1) DEFAULT 0,
            prev_method         VARCHAR(100) NULL,
            side_effects        TEXT NULL,
            counseling_notes    TEXT NULL,

            -- Supplies given
            pills_given         TINYINT UNSIGNED DEFAULT 0 COMMENT 'Packs given',
            injectables_given   TINYINT UNSIGNED DEFAULT 0 COMMENT 'Vials given',

            health_worker       VARCHAR(100) NULL,
            created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_fp_resident  (resident_id),
            INDEX idx_fp_next_date (next_supply_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

/* ════════════════════════════════════════════════════════════════
   STEP 4 — Prenatal (ANC): per-visit monitoring
════════════════════════════════════════════════════════════════ */
$migrations[] = [
    'name' => 'prenatal_visit table',
    'sql'  => "
        CREATE TABLE IF NOT EXISTS prenatal_visit (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resident_id         INT NOT NULL,
            care_visit_id       INT NULL,

            -- Pregnancy dating
            lmp_date            DATE NULL COMMENT 'Last menstrual period',
            edd_date            DATE NULL COMMENT 'Estimated due date (computed)',
            aog_weeks           TINYINT UNSIGNED NULL COMMENT 'Age of gestation in weeks',
            visit_number        TINYINT UNSIGNED DEFAULT 1,

            -- Vitals
            weight_kg           DECIMAL(5,2) NULL,
            bp_systolic         SMALLINT UNSIGNED NULL,
            bp_diastolic        SMALLINT UNSIGNED NULL,
            fundal_height_cm    DECIMAL(4,1) NULL,
            fetal_heart_rate    SMALLINT UNSIGNED NULL COMMENT 'bpm',
            fetal_presentation  ENUM('Cephalic','Breech','Transverse','Unknown') DEFAULT 'Unknown',

            -- Supplementation
            folic_acid_given    TINYINT(1) DEFAULT 0,
            iron_given          TINYINT(1) DEFAULT 0,
            iron_tablets_qty    TINYINT UNSIGNED DEFAULT 0,
            calcium_given       TINYINT(1) DEFAULT 0,
            iodine_given        TINYINT(1) DEFAULT 0,

            -- Tetanus toxoid
            tt_dose             ENUM('None','TT1','TT2','TT3','TT4','TT5','TD') DEFAULT 'None',
            tt_date             DATE NULL,

            -- Labs
            hgb_result          DECIMAL(4,1) NULL COMMENT 'g/dL',
            urinalysis_done     TINYINT(1) DEFAULT 0,
            blood_type_done     TINYINT(1) DEFAULT 0,
            hiv_test_done       TINYINT(1) DEFAULT 0,
            hiv_result          ENUM('Not done','Negative','Positive','Referred') DEFAULT 'Not done',
            syphilis_done       TINYINT(1) DEFAULT 0,
            syphilis_result     ENUM('Not done','Non-reactive','Reactive','Referred') DEFAULT 'Not done',

            -- Assessment
            risk_level          ENUM('Low','Moderate','High') DEFAULT 'Low',
            risk_notes          TEXT NULL,
            chief_complaint     TEXT NULL,
            assessment          TEXT NULL,
            plan                TEXT NULL,

            health_worker       VARCHAR(100) NULL,
            created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_pv_resident    (resident_id),
            INDEX idx_pv_lmp         (lmp_date),
            INDEX idx_pv_edd         (edd_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

/* ════════════════════════════════════════════════════════════════
   STEP 5 — Postnatal (PNC): maternal recovery & newborn check
════════════════════════════════════════════════════════════════ */
$migrations[] = [
    'name' => 'postnatal_visit table',
    'sql'  => "
        CREATE TABLE IF NOT EXISTS postnatal_visit (
            id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resident_id             INT NOT NULL,
            care_visit_id           INT NULL,

            -- Delivery info (recorded once, inherited by subsequent visits)
            delivery_date           DATE NULL,
            delivery_type           ENUM('NSD','CS','Assisted','Unknown') DEFAULT 'Unknown',
            delivery_facility       VARCHAR(150) NULL,
            birth_attendant         VARCHAR(100) NULL,

            -- Maternal recovery
            visit_number            TINYINT UNSIGNED DEFAULT 1,
            weight_kg               DECIMAL(5,2) NULL,
            bp_systolic             SMALLINT UNSIGNED NULL,
            bp_diastolic            SMALLINT UNSIGNED NULL,
            lochia_type             ENUM('Rubra','Serosa','Alba','Abnormal','Not checked') DEFAULT 'Not checked',
            fundal_involution       ENUM('Normal','Subinvolution','Not checked') DEFAULT 'Not checked',
            episiotomy_healing      ENUM('Normal','Infected','NA') DEFAULT 'NA',
            cs_wound_healing        ENUM('Normal','Infected','NA','Not checked') DEFAULT 'NA',
            breastfeeding_status    ENUM('Exclusive','Mixed','Not breastfeeding','NA') DEFAULT 'NA',

            -- PPD screening (Edinburgh Postnatal Depression Scale subset)
            ppd_score               TINYINT UNSIGNED NULL COMMENT 'EPDS score 0–30',
            ppd_referred            TINYINT(1) DEFAULT 0,

            -- Newborn check (recorded at first PNC visit)
            newborn_weight_g        SMALLINT UNSIGNED NULL,
            newborn_length_cm       DECIMAL(4,1) NULL,
            apgar_1min              TINYINT UNSIGNED NULL,
            apgar_5min              TINYINT UNSIGNED NULL,
            cord_status             ENUM('Normal','Infected','Healing','NA') DEFAULT 'NA',
            jaundice                TINYINT(1) DEFAULT 0,
            newborn_screening_done  TINYINT(1) DEFAULT 0,
            bcg_given               TINYINT(1) DEFAULT 0,
            hb_vaccine_given        TINYINT(1) DEFAULT 0 COMMENT 'Hep B birth dose',

            -- Family planning (PNC counseling)
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

/* ════════════════════════════════════════════════════════════════
   STEP 6 — Child nutrition: growth monitoring
════════════════════════════════════════════════════════════════ */
$migrations[] = [
    'name' => 'child_nutrition_visit table',
    'sql'  => "
        CREATE TABLE IF NOT EXISTS child_nutrition_visit (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resident_id         INT NOT NULL COMMENT 'The child',
            care_visit_id       INT NULL,

            visit_date          DATE NOT NULL,
            age_months          TINYINT UNSIGNED NULL COMMENT 'Age at visit in months',

            -- Anthropometrics
            weight_kg           DECIMAL(5,3) NULL,
            height_cm           DECIMAL(5,2) NULL,
            muac_cm             DECIMAL(4,1) NULL COMMENT 'Mid-upper arm circumference',

            -- WHO Z-scores (computed or entered)
            waz                 DECIMAL(5,2) NULL COMMENT 'Weight-for-age Z-score',
            haz                 DECIMAL(5,2) NULL COMMENT 'Height-for-age Z-score (stunting)',
            whz                 DECIMAL(5,2) NULL COMMENT 'Weight-for-height Z-score (wasting)',

            -- Classification
            stunting_status     ENUM('Normal','Mild','Moderate','Severe','Not assessed') DEFAULT 'Not assessed',
            wasting_status      ENUM('Normal','Mild','Moderate','Severe','Not assessed') DEFAULT 'Not assessed',
            underweight_status  ENUM('Normal','Mild','Moderate','Severe','Not assessed') DEFAULT 'Not assessed',

            -- Feeding
            breastfeeding       ENUM('Exclusive','Mixed','Complementary','Weaned','NA') DEFAULT 'NA',
            complementary_intro DATE NULL COMMENT 'Date solid foods introduced',
            feeding_problems    TEXT NULL,

            -- Micronutrients
            vita_supplemented   TINYINT(1) DEFAULT 0,
            vita_dose           ENUM('100000 IU','200000 IU','NA') DEFAULT 'NA',
            vita_date           DATE NULL,
            iron_supplemented   TINYINT(1) DEFAULT 0,
            zinc_given          TINYINT(1) DEFAULT 0,
            deworming_done      TINYINT(1) DEFAULT 0,
            deworming_date      DATE NULL,

            -- Nutritional counseling
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

/* ════════════════════════════════════════════════════════════════
   STEP 7 — Immunization schedule master (NIP checklist)
════════════════════════════════════════════════════════════════ */
$migrations[] = [
    'name' => 'immunization_schedule master table',
    'sql'  => "
        CREATE TABLE IF NOT EXISTS immunization_schedule (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            vaccine_name    VARCHAR(100) NOT NULL,
            dose_label      VARCHAR(50)  NOT NULL COMMENT 'e.g. BCG Dose 1',
            dose_number     TINYINT UNSIGNED DEFAULT 1,
            target_age_days SMALLINT UNSIGNED NULL COMMENT 'Recommended age in days from birth',
            target_age_label VARCHAR(50)  NULL COMMENT 'Display label e.g. At birth, 6 weeks',
            interval_days   SMALLINT UNSIGNED NULL COMMENT 'Days from prev dose',
            disease_protected VARCHAR(200) NULL,
            route           ENUM('IM','SC','ID','Oral','Nasal') DEFAULT 'IM',
            site            VARCHAR(100) NULL,
            is_nip          TINYINT(1) DEFAULT 1 COMMENT 'Part of national immunization program',
            sort_order      TINYINT UNSIGNED DEFAULT 0,
            UNIQUE KEY uk_vaccine_dose (vaccine_name, dose_label)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

/* ════════════════════════════════════════════════════════════════
   STEP 8 — Enhanced immunization records (extends existing table)
════════════════════════════════════════════════════════════════ */
$migrations[] = [
    'name' => 'immunization_record enhanced columns',
    'sql'  => "
        ALTER TABLE immunizations
        ADD COLUMN IF NOT EXISTS schedule_id      INT UNSIGNED NULL AFTER vaccine_name,
        ADD COLUMN IF NOT EXISTS batch_number     VARCHAR(50)  NULL AFTER administered_by,
        ADD COLUMN IF NOT EXISTS expiry_date      DATE         NULL AFTER batch_number,
        ADD COLUMN IF NOT EXISTS site_given       VARCHAR(100) NULL AFTER expiry_date,
        ADD COLUMN IF NOT EXISTS route            ENUM('IM','SC','ID','Oral','Nasal') DEFAULT 'IM' AFTER site_given,
        ADD COLUMN IF NOT EXISTS adverse_reaction TEXT         NULL AFTER route,
        ADD COLUMN IF NOT EXISTS is_defaulter     TINYINT(1)   DEFAULT 0 AFTER adverse_reaction,
        ADD COLUMN IF NOT EXISTS catch_up         TINYINT(1)   DEFAULT 0 COMMENT 'Given outside normal schedule' AFTER is_defaulter,
        ADD COLUMN IF NOT EXISTS care_visit_id    INT          NULL AFTER catch_up
    "
];

/* ════════════════════════════════════════════════════════════════
   STEP 9 — Seed the NIP immunization schedule
════════════════════════════════════════════════════════════════ */
$nipSeeds = [
    // BCG
    ['BCG', 'BCG Dose 1', 1, 0, 'At birth', null, 'Tuberculosis', 'ID', 'Right deltoid', 1, 1],
    // Hepatitis B
    ['Hepatitis B', 'HepB Birth Dose', 1, 0, 'At birth', null, 'Hepatitis B', 'IM', 'Right anterolateral thigh', 1, 2],
    ['Hepatitis B', 'HepB Dose 2', 2, 42, '6 weeks', null, 'Hepatitis B', 'IM', 'Right anterolateral thigh', 1, 3],
    ['Hepatitis B', 'HepB Dose 3', 3, 70, '10 weeks', null, 'Hepatitis B', 'IM', 'Right anterolateral thigh', 1, 4],
    ['Hepatitis B', 'HepB Dose 4', 4, 98, '14 weeks', null, 'Hepatitis B', 'IM', 'Right anterolateral thigh', 1, 5],
    // Pentavalent (DPT-HepB-Hib)
    ['Pentavalent', 'Penta Dose 1', 1, 42, '6 weeks', null, 'Diphtheria, Pertussis, Tetanus, HepB, Hib', 'IM', 'Left anterolateral thigh', 1, 6],
    ['Pentavalent', 'Penta Dose 2', 2, 70, '10 weeks', null, 'Diphtheria, Pertussis, Tetanus, HepB, Hib', 'IM', 'Left anterolateral thigh', 1, 7],
    ['Pentavalent', 'Penta Dose 3', 3, 98, '14 weeks', null, 'Diphtheria, Pertussis, Tetanus, HepB, Hib', 'IM', 'Left anterolateral thigh', 1, 8],
    // OPV
    ['OPV', 'OPV Dose 1', 1, 42, '6 weeks', null, 'Polio', 'Oral', null, 1, 9],
    ['OPV', 'OPV Dose 2', 2, 70, '10 weeks', null, 'Polio', 'Oral', null, 1, 10],
    ['OPV', 'OPV Dose 3', 3, 98, '14 weeks', null, 'Polio', 'Oral', null, 1, 11],
    // IPV
    ['IPV', 'IPV Dose 1', 1, 98, '14 weeks', null, 'Polio', 'IM', 'Right anterolateral thigh', 1, 12],
    // PCV
    ['PCV', 'PCV Dose 1', 1, 42, '6 weeks', null, 'Pneumococcal disease', 'IM', 'Left anterolateral thigh', 1, 13],
    ['PCV', 'PCV Dose 2', 2, 70, '10 weeks', null, 'Pneumococcal disease', 'IM', 'Left anterolateral thigh', 1, 14],
    ['PCV', 'PCV Dose 3', 3, 98, '14 weeks', null, 'Pneumococcal disease', 'IM', 'Left anterolateral thigh', 1, 15],
    // Measles-Rubella
    ['Measles-Rubella', 'MR Dose 1', 1, 274, '9 months', null, 'Measles, Rubella', 'SC', 'Right outer arm', 1, 16],
    ['Measles-Rubella', 'MR Dose 2', 2, 365, '12 months', null, 'Measles, Rubella', 'SC', 'Right outer arm', 1, 17],
];

/* ════════════════════════════════════════════════════════════════
   RUN MIGRATIONS
════════════════════════════════════════════════════════════════ */

$success = 0;
$failed  = 0;

foreach ($migrations as $m) {
    // Support both ALTER (may partially fail if columns exist) and CREATE
    if ($conn->query($m['sql'])) {
        echo "✅ {$m['name']}\n";
        $success++;
    } else {
        // For ALTER TABLE with IF NOT EXISTS — MySQL < 8.0 doesn't support it
        // Try column-by-column as a fallback
        if (str_contains($m['sql'], 'ADD COLUMN IF NOT EXISTS')) {
            echo "⚠️  {$m['name']} — falling back to safe ALTER\n";
            $lines = explode(',', $m['sql']);
            $tbl   = '';
            preg_match('/ALTER TABLE (\w+)/', $m['sql'], $t);
            $tbl = $t[1] ?? '';
            if ($tbl) {
                preg_match_all('/ADD COLUMN IF NOT EXISTS\s+(\w+)\s+([^,]+?)(?=\s*,\s*ADD|\s*$)/s', $m['sql'], $cols, PREG_SET_ORDER);
                foreach ($cols as $col) {
                    $colName = $col[1];
                    $colDef  = trim($col[2]);
                    $check   = $conn->query("SHOW COLUMNS FROM `{$tbl}` LIKE '{$colName}'");
                    if ($check && $check->num_rows === 0) {
                        $alterSql = "ALTER TABLE `{$tbl}` ADD COLUMN `{$colName}` {$colDef}";
                        if (!$conn->query($alterSql)) {
                            echo "   ⚠️  Column {$colName}: {$conn->error}\n";
                        } else {
                            echo "   ✓ Added column {$colName}\n";
                        }
                    } else {
                        echo "   ℹ️  Column {$colName} already exists\n";
                    }
                }
                $success++;
            }
        } else {
            echo "❌ {$m['name']}: {$conn->error}\n";
            $failed++;
        }
    }
}

/* Seed NIP schedule */
echo "\n--- Seeding NIP immunization schedule ---\n";
$seedStmt = $conn->prepare("
    INSERT IGNORE INTO immunization_schedule
        (vaccine_name, dose_label, dose_number, target_age_days, target_age_label,
         interval_days, disease_protected, route, site, is_nip, sort_order)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)
");
foreach ($nipSeeds as $s) {
    $seedStmt->bind_param('ssiisssssii', ...$s);
    $seedStmt->execute();
}
$seeded = $seedStmt->affected_rows;
echo "✅ Seeded {$seeded} NIP schedule entries (duplicates skipped)\n";

echo "\n═══════════════════════════════════\n";
echo "✅ Completed: {$success}   ❌ Failed: {$failed}\n";

$conn->close();
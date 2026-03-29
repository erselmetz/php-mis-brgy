-- =====================================================
-- MIS BARANGAY - Complete Database Schema
-- Generated: March 29, 2026 at 18:42:39
-- =====================================================
-- Usage:
--   1. Create a new database (e.g. php_mis_brgy)
--   2. Import this file via phpMyAdmin or CLI:
--      mysql -u root php_mis_brgy < database_complete.sql
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- SECTION: INITIAL SETUP
-- Creates all base tables required for the system
-- =====================================================

-- File: create_users_table.php
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(244) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'users' created successfully.;

-- File: create_households_table.php
CREATE TABLE IF NOT EXISTS households (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    household_no VARCHAR(100) NOT NULL UNIQUE,
    address VARCHAR(255) NOT NULL,
    head_id INT(11) NOT NULL,
    head_name VARCHAR(150) NOT NULL,
    total_members INT(3) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'households' created successfully.;

-- File: create_families_table.php
CREATE TABLE IF NOT EXISTS families (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    household_id INT(11) UNSIGNED NULL,
    family_name VARCHAR(150) NOT NULL,
    total_members INT(3) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_family_household FOREIGN KEY (household_id) REFERENCES households(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'families' created successfully.;

-- File: create_residents_table.php
CREATE TABLE IF NOT EXISTS residents (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    household_id INT(11) UNSIGNED NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(10) NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    birthdate DATE NOT NULL,
    birthplace VARCHAR(255) NULL,
    civil_status ENUM('Single', 'Married', 'Widowed', 'Separated') DEFAULT 'Single',
    religion VARCHAR(100) NULL,
    occupation VARCHAR(150) NULL,
    citizenship VARCHAR(100) DEFAULT 'Filipino',
    contact_no VARCHAR(20) NULL,
    address VARCHAR(100) NULL,
    voter_status ENUM('Yes', 'No') DEFAULT 'No',
    disability_status ENUM('Yes', 'No') DEFAULT 'No',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_resident_household FOREIGN KEY (household_id) REFERENCES households(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'residents' created successfully (with household_id column).;

-- File: create_officers_table.php
CREATE TABLE IF NOT EXISTS officers (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NULL,
    position VARCHAR(150) NOT NULL,
    term_start DATE NOT NULL,
    term_end DATE NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_officer_resident FOREIGN KEY (resident_id) REFERENCES residents(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'officers' created successfully (with resident_id column).;

-- File: create_certificates_request_table.php
CREATE TABLE IF NOT EXISTS certificate_request (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resident_id INT(11) UNSIGNED NOT NULL,
  certificate_type VARCHAR(100) NOT NULL,
  purpose VARCHAR(255) NOT NULL,
  issued_by INT(11) NOT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
);

";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'certificates_requests' created successfully.;

-- File: create_blotter_table.php
CREATE TABLE IF NOT EXISTS blotter (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(50) UNIQUE NOT NULL,
    complainant_name VARCHAR(255) NOT NULL,
    complainant_address TEXT,
    complainant_contact VARCHAR(20),
    respondent_name VARCHAR(255) NOT NULL,
    respondent_address TEXT,
    respondent_contact VARCHAR(20),
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_location TEXT NOT NULL,
    incident_description TEXT NOT NULL,
    status ENUM('pending', 'under_investigation', 'resolved', 'dismissed') DEFAULT 'pending',
    resolution TEXT,
    resolved_date DATE,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_case_number (case_number),
    INDEX idx_status (status),
    INDEX idx_incident_date (incident_date),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'blotter' created successfully.\n";
} else {
    echo "❌ Error creating table 'blotter': " . $conn->error . "\n";
}

// Function to generate case number
function generateCaseNumber($conn) {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blotter WHERE case_number LIKE ?");
    $pattern = "BLT-$year-%;

-- File: create_events_scheduling.php
CREATE TABLE IF NOT EXISTS events (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,

    event_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,

    event_date DATE NOT NULL,
    event_time TIME,

    location VARCHAR(255),

    priority ENUM('normal', 'important', 'urgent') DEFAULT 'normal',
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',

    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_event_code (event_code),
    INDEX idx_event_date (event_date),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'events' created successfully.\n";
} else {
    echo "❌ Error creating table 'events': " . $conn->error . "\n";
}

/**
 * Generate unique event code
 * Example: EVT-2025-0001
 */
function generateEventCode(mysqli $conn): string
{
    $year = date('Y');

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS count 
        FROM events 
        WHERE event_code LIKE ?
    ");

    $pattern = "EVT-$year-%;

-- File: create_inventory_table.php
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `asset_code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `quantity` INT DEFAULT 1,
    `location` VARCHAR(255) DEFAULT NULL,
    `cond` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('available','in_use','maintenance','damaged','retired') 
        DEFAULT 'available',
    `description` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP 
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_asset_code` (`asset_code`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`),

    CONSTRAINT `fk_inventory_created_by`
        FOREIGN KEY (`created_by`) 
        REFERENCES `users`(`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql)) {
    echo "✅ Table `inventory` created successfully\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n;

-- File: create_inventory_categories_table.php
CREATE TABLE IF NOT EXISTS `inventory_category_list` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'inventory_category_list' created successfully.;

-- File: create_inventory_audit_trail_table.php
CREATE TABLE IF NOT EXISTS `inventory_audit_trail` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inventory_id` INT UNSIGNED NOT NULL,
  `asset_code` VARCHAR(50) NOT NULL,
  `action_type` ENUM('created', 'updated', 'deleted', 'assigned', 'returned', 'location_changed', 'condition_changed', 'quantity_changed') NOT NULL,
  `user_id` INT(11) NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `user_role` VARCHAR(50) DEFAULT NULL,
  `personnel_name` VARCHAR(255) DEFAULT NULL,
  `personnel_role` VARCHAR(100) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `purpose` TEXT DEFAULT NULL,
  `start_time` DATETIME DEFAULT NULL,
  `end_time` DATETIME DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_inventory_id` (`inventory_id`),
  INDEX `idx_asset_code` (`asset_code`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'inventory_audit_trail' created successfully.\n";
} else {
    echo "❌ Error creating table 'inventory_audit_trail': " . $conn->error . "\n;

-- File: create_medicines_table.php
CREATE TABLE IF NOT EXISTS medicines (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11) UNSIGNED DEFAULT NULL,

    name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,

    stock_qty INT(11) NOT NULL DEFAULT 0,
    reorder_level INT(11) NOT NULL DEFAULT 10,

    unit VARCHAR(50) DEFAULT 'pcs',
    expiration_date DATE DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_name (name),
    KEY idx_category_id (category_id),
    KEY idx_stock_qty (stock_qty),
    KEY idx_reorder_level (reorder_level),
    KEY idx_expiration_date (expiration_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) !== TRUE) {
    echo "❌ Error creating table 'medicines': " . $conn->error . "\n";
    $conn->close();
    exit;
}

echo "✅ Table 'medicines' created successfully.\n;

-- File: create_medicine_categories_table.php
CREATE TABLE IF NOT EXISTS medicine_categories (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_name (name),
    KEY idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'medicine_categories' created successfully.\n";
} else {
    echo "❌ Error creating table 'medicine_categories': " . $conn->error . "\n;

-- File: create_medicine_dispense_table.php
CREATE TABLE IF NOT EXISTS medicine_dispense (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,
    medicine_id INT(11) UNSIGNED NOT NULL,

    quantity INT(11) NOT NULL,
    dispense_date DATE NOT NULL,
    notes TEXT DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_resident_id (resident_id),
    KEY idx_medicine_id (medicine_id),
    KEY idx_dispense_date (dispense_date),

    CONSTRAINT fk_dispense_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_dispense_medicine
        FOREIGN KEY (medicine_id)
        REFERENCES medicines(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'medicine_dispense' created successfully.\n";
} else {
    echo "❌ Error creating table 'medicine_dispense': " . $conn->error . "\n;

-- File: create_health_metrics_table.php
CREATE TABLE IF NOT EXISTS health_metrics (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,

    weight DECIMAL(5,2) DEFAULT NULL,
    height DECIMAL(5,2) DEFAULT NULL,
    blood_pressure VARCHAR(20) DEFAULT NULL,
    temperature DECIMAL(4,1) DEFAULT NULL,

    recorded_at DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_resident_id (resident_id),
    KEY idx_recorded_at (recorded_at),

    CONSTRAINT fk_health_metrics_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'health_metrics' created successfully.\n";
} else {
    echo "❌ Error creating table 'health_metrics': " . $conn->error . "\n;

-- File: create_immunizations_table.php
CREATE TABLE IF NOT EXISTS immunizations (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,

    vaccine_name VARCHAR(100) NOT NULL,
    dose VARCHAR(50) DEFAULT NULL,
    schedule_id      INT UNSIGNED NULL,

    date_given DATE NOT NULL,
    next_schedule DATE DEFAULT NULL,

    administered_by VARCHAR(100) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    batch_number     VARCHAR(50)  NULL,
    expiry_date      DATE         NULL,
    site_given       VARCHAR(100) NULL,
    
    route            ENUM('IM','SC','ID','Oral','Nasal') DEFAULT 'IM',
    adverse_reaction TEXT         NULL,
    is_defaulter     TINYINT(1)   DEFAULT 0,
    catch_up         TINYINT(1)   DEFAULT 0 COMMENT 'Given outside normal schedule',
    care_visit_id    INT          NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_resident_id (resident_id),
    KEY idx_date_given (date_given),
    KEY idx_next_schedule (next_schedule),

    CONSTRAINT fk_immunizations_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'immunizations' created successfully.\n";
} else {
    echo "❌ Error creating table 'immunizations': " . $conn->error . "\n;

-- File: create_consultations_table.php
CREATE TABLE IF NOT EXISTS consultations (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT(11) UNSIGNED NOT NULL,

    complaint TEXT NOT NULL,
    diagnosis TEXT DEFAULT NULL,
    treatment TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,

    consultation_date DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_resident_id (resident_id),
    KEY idx_consultation_date (consultation_date),

    CONSTRAINT fk_consultations_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'consultations' created successfully.\n";
} else {
    echo "❌ Error creating table 'consultations': " . $conn->error . "\n;

-- File: create_backups_table.php
CREATE TABLE IF NOT EXISTS `backups` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `description` VARCHAR(255) DEFAULT 'Manual Backup',
  `performed_by` INT(11) NOT NULL,
  `performed_by_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_performed_by` (`performed_by`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'backups' created successfully.\n";
} else {
    echo "❌ Error creating table 'backups': " . $conn->error . "\n;

-- File: create_patrol_schedule_table.php
CREATE TABLE IF NOT EXISTS patrol_schedule (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    patrol_code     VARCHAR(20)  NOT NULL UNIQUE,
    team_name       VARCHAR(150) NOT NULL,
    patrol_date     DATE         NOT NULL,
    time_start      TIME         NOT NULL,
    time_end        TIME         NOT NULL,
    patrol_route    VARCHAR(300) DEFAULT NULL,
    area_covered    VARCHAR(300) DEFAULT NULL,
    is_weekly       TINYINT(1)   NOT NULL DEFAULT 0,
    week_day        TINYINT(1)   DEFAULT NULL, -- 0=Sun, 1=Mon ... 6=Sat (used if is_weekly=1)
    tanod_members   TEXT         DEFAULT NULL, -- comma-separated names
    notes           TEXT         DEFAULT NULL,
    status          ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_by      INT          DEFAULT NULL,
    updated_by      INT          DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patrol_date (patrol_date),
    INDEX idx_status      (status),
    INDEX idx_is_weekly   (is_weekly)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'patrol_schedule' created successfully.;

-- File: create_tanod_duty_schedule_table.php
CREATE TABLE IF NOT EXISTS tanod_duty_schedule (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    duty_code       VARCHAR(20)  NOT NULL UNIQUE,
    tanod_name      VARCHAR(150) NOT NULL,
    duty_date       DATE         NOT NULL,
    shift           ENUM('morning','afternoon','night') NOT NULL DEFAULT 'morning',
    post_location   VARCHAR(200) DEFAULT NULL,
    notes           TEXT         DEFAULT NULL,
    status          ENUM('active','cancelled','completed') NOT NULL DEFAULT 'active',
    created_by      INT          DEFAULT NULL,
    updated_by      INT          DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_duty_date  (duty_date),
    INDEX idx_tanod_name (tanod_name),
    INDEX idx_shift      (shift),
    INDEX idx_status     (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'tanod_duty_schedule' created successfully.;

-- File: create_court_schedule_table.php
CREATE TABLE IF NOT EXISTS court_schedule (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reservation_code VARCHAR(20) NOT NULL UNIQUE,
    facility        ENUM('basketball_court','multipurpose_area','gym') NOT NULL DEFAULT 'basketball_court',
    borrower_name   VARCHAR(150) NOT NULL,
    borrower_contact VARCHAR(20)  DEFAULT NULL,
    organization    VARCHAR(150)  DEFAULT NULL,
    purpose         VARCHAR(250)  NOT NULL,
    reservation_date DATE         NOT NULL,
    time_start      TIME          NOT NULL,
    time_end        TIME          NOT NULL,
    status          ENUM('pending','approved','denied','completed','cancelled') NOT NULL DEFAULT 'pending',
    remarks         TEXT          DEFAULT NULL,
    approved_by     INT           DEFAULT NULL,
    created_by      INT           DEFAULT NULL,
    updated_by      INT           DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_res_date  (reservation_date),
    INDEX idx_facility  (facility),
    INDEX idx_status    (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'court_schedule' created successfully.;

-- File: create_borrowing_schedule_table.php
CREATE TABLE IF NOT EXISTS borrowing_schedule (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    borrow_code     VARCHAR(20)  NOT NULL UNIQUE,
    borrower_name   VARCHAR(150) NOT NULL,
    borrower_contact VARCHAR(20) DEFAULT NULL,
    item_name       VARCHAR(200) NOT NULL,
    inventory_id    INT          DEFAULT NULL,   -- FK to inventory table (optional)
    quantity        INT          NOT NULL DEFAULT 1,
    borrow_date     DATE         NOT NULL,
    return_date     DATE         NOT NULL,
    actual_return   DATE         DEFAULT NULL,
    purpose         VARCHAR(250) DEFAULT NULL,
    status          ENUM('borrowed','returned','overdue','cancelled') NOT NULL DEFAULT 'borrowed',
    condition_out   VARCHAR(100) DEFAULT NULL,
    condition_in    VARCHAR(100) DEFAULT NULL,
    notes           TEXT         DEFAULT NULL,
    created_by      INT          DEFAULT NULL,
    updated_by      INT          DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_borrow_date  (borrow_date),
    INDEX idx_return_date  (return_date),
    INDEX idx_borrower     (borrower_name),
    INDEX idx_status       (status),
    INDEX idx_inventory_id (inventory_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'borrowing_schedule' created successfully.;

-- File: create_appointments_table.php
CREATE TABLE IF NOT EXISTS appointments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appt_code       VARCHAR(30)  NOT NULL UNIQUE,
    resident_id     INT          NOT NULL,
    appt_date       DATE         NOT NULL,
    appt_time       TIME         NOT NULL,
    appt_type       ENUM(
                        'general','maternal','family_planning',
                        'prenatal','postnatal','child_nutrition',
                        'immunization','dental','other'
                    ) NOT NULL DEFAULT 'general',
    purpose         VARCHAR(300) NOT NULL,
    health_worker   VARCHAR(100) DEFAULT NULL,
    status          ENUM('scheduled','completed','cancelled','no_show')
                    NOT NULL DEFAULT 'scheduled',
    notes           TEXT         DEFAULT NULL,
    created_by      INT          DEFAULT NULL,
    updated_by      INT          DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_appt_date    (appt_date),
    INDEX idx_resident_id  (resident_id),
    INDEX idx_status       (status),
    INDEX idx_appt_type    (appt_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql)) {
    echo "✅ Table `appointments` created.\n";
} else {
    echo "❌ " . $conn->error . "\n;

-- =====================================================
-- SECTION: FEATURE ADDITIONS
-- Adds new features and columns to existing tables
-- =====================================================

-- File: add_profile_picture_to_users.php
ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER password";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Column 'profile_picture' added to 'users' table successfully.\n";
    } else {
        echo "❌ Error adding column 'profile_picture': " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ Column 'profile_picture' already exists in 'users' table.\n;

-- File: add_archived_to_residents.php
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
    echo "ℹ️ Column 'deleted_at' already exists in 'residents' table.\n;

-- File: add_archived_at_to_blotter.php
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
    echo "ℹ️ Column 'archived_at' already exists in 'blotter' table.\n;

-- File: create_blotter_history_table.php
CREATE TABLE IF NOT EXISTS `blotter_history` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `blotter_id` INT(11) NOT NULL,
  `case_number` VARCHAR(50) NOT NULL,
  `action_type` ENUM('status_changed', 'updated', 'created', 'archived', 'restored') NOT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) DEFAULT NULL,
  `changed_field` VARCHAR(100) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `user_id` INT(11) NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_blotter_id` (`blotter_id`),
  INDEX `idx_case_number` (`case_number`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`blotter_id`) REFERENCES `blotter`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'blotter_history' created successfully.\n";
} else {
    echo "❌ Error creating table 'blotter_history': " . $conn->error . "\n;

-- File: add_archived_at_to_officers.php
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
    echo "ℹ️ Column 'archived_at' already exists in 'officers' table.\n;

-- File: create_term_history_table.php
CREATE TABLE IF NOT EXISTS `term_history` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `officer_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `action_type` ENUM('term_started', 'term_ended', 'term_updated', 'status_changed', 'position_changed', 'archived', 'restored') NOT NULL,
  `old_position` VARCHAR(150) DEFAULT NULL,
  `new_position` VARCHAR(150) DEFAULT NULL,
  `old_term_start` DATE DEFAULT NULL,
  `new_term_start` DATE DEFAULT NULL,
  `old_term_end` DATE DEFAULT NULL,
  `new_term_end` DATE DEFAULT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) DEFAULT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_officer_id` (`officer_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`officer_id`) REFERENCES `officers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'term_history' created successfully.\n";
} else {
    echo "❌ Error creating table 'term_history': " . $conn->error . "\n;

-- File: add_archived_at_to_households.php
ALTER TABLE households
            ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
            ADD INDEX idx_archived_at (archived_at)";
    if ($conn->query($sql)) {
        echo "✅ Column 'archived_at' added to 'households' table.\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ Column 'archived_at' already exists.\n;

-- File: create_care_visits_table.php
CREATE TABLE IF NOT EXISTS care_visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resident_id INT NOT NULL,
  care_type ENUM('maternal','family_planning','prenatal','postnatal','child_nutrition') NOT NULL,
  visit_date DATE NOT NULL,
  details LONGTEXT NULL,   -- JSON string (type-specific data)
  notes TEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_care_resident (resident_id),
  INDEX idx_care_type_date (care_type, visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql)) {
  echo "✅ care_visits table ready!\n";
} else {
  echo "❌ Error: " . $conn->error . "\n;

-- =====================================================
-- SECTION: STRUCTURAL CHANGES
-- Major schema changes and refactoring
-- =====================================================

-- File: merge_staff_officers.php
-- WARNING: No SQL found in this file — skipped

-- File: make_officer_term_nullable.php
-- WARNING: No SQL found in this file — skipped

-- File: enhance_consultations.php
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
echo "   New columns are nullable → no form breaks.\n;

-- =====================================================
-- SECTION: ENHANCED CONSULTATIONS
-- =====================================================

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

-- =====================================================
-- SECTION: STRUCTURAL CHANGES
-- =====================================================

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

-- File: make_officer_term_nullable.php
ALTER TABLE `officers`
    MODIFY COLUMN `term_start` DATE NULL DEFAULT NULL,
    MODIFY COLUMN `term_end`   DATE NULL DEFAULT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- END OF SCHEMA
-- Files processed: 32/34
-- =====================================================

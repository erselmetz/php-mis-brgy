-- MIS Barangay Database Backup
-- Generated: 2026-03-22 08:09:36
-- By: Ersel Magbanua
-- Database: php_mis_brgy
-- -----------------------------------------------

SET FOREIGN_KEY_CHECKS=0;

-- Table: `backups`
DROP TABLE IF EXISTS `backups`;
CREATE TABLE `backups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `description` varchar(255) DEFAULT 'Manual Backup',
  `performed_by` int NOT NULL,
  `performed_by_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `blotter`
DROP TABLE IF EXISTS `blotter`;
CREATE TABLE `blotter` (
  `id` int NOT NULL AUTO_INCREMENT,
  `case_number` varchar(50) NOT NULL,
  `complainant_name` varchar(255) NOT NULL,
  `complainant_address` text,
  `complainant_contact` varchar(20) DEFAULT NULL,
  `respondent_name` varchar(255) NOT NULL,
  `respondent_address` text,
  `respondent_contact` varchar(20) DEFAULT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `incident_location` text NOT NULL,
  `incident_description` text NOT NULL,
  `status` enum('pending','under_investigation','resolved','dismissed') DEFAULT 'pending',
  `resolution` text,
  `resolved_date` date DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `case_number` (`case_number`),
  KEY `idx_case_number` (`case_number`),
  KEY `idx_status` (`status`),
  KEY `idx_incident_date` (`incident_date`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_archived_at` (`archived_at`),
  CONSTRAINT `blotter_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `blotter` VALUES
('1', 'BLT-2026-0001', 'test', '', '', 'test', '', '', '2026-03-22', '00:00:00', 'test', 'test', 'pending', NULL, NULL, '1', '2026-03-22 14:15:06', '2026-03-22 14:15:06', NULL);

-- Table: `blotter_history`
DROP TABLE IF EXISTS `blotter_history`;
CREATE TABLE `blotter_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blotter_id` int NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `action_type` enum('status_changed','updated','created','archived','restored') NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `changed_field` varchar(100) DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `user_id` int NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_blotter_id` (`blotter_id`),
  KEY `idx_case_number` (`case_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `blotter_history_ibfk_1` FOREIGN KEY (`blotter_id`) REFERENCES `blotter` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blotter_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `borrowing_schedule`
DROP TABLE IF EXISTS `borrowing_schedule`;
CREATE TABLE `borrowing_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `borrow_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `borrower_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `borrower_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inventory_id` int DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `borrow_date` date NOT NULL,
  `return_date` date NOT NULL,
  `actual_return` date DEFAULT NULL,
  `purpose` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('borrowed','returned','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrowed',
  `condition_out` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_in` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `borrow_code` (`borrow_code`),
  KEY `idx_borrow_date` (`borrow_date`),
  KEY `idx_return_date` (`return_date`),
  KEY `idx_borrower` (`borrower_name`),
  KEY `idx_status` (`status`),
  KEY `idx_inventory_id` (`inventory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `borrowing_schedule` VALUES
('1', 'BRS-2026-0001', 'test', '', 'test', NULL, '1', '2026-03-22', '2026-03-23', NULL, '', 'borrowed', '', '', '', '1', NULL, '2026-03-22 13:09:16', NULL),
('2', 'BRS-2026-0002', 'Test', '', 'Generator', '1', '1', '2026-03-22', '2026-03-23', NULL, '', 'borrowed', '', '', '', '1', NULL, '2026-03-22 14:24:02', NULL);

-- Table: `certificate_request`
DROP TABLE IF EXISTS `certificate_request`;
CREATE TABLE `certificate_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resident_id` int unsigned NOT NULL,
  `certificate_type` varchar(100) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `issued_by` int NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `requested_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `certificate_request_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `certificate_request` VALUES
('1', '1', 'Barangay Clearance', 'test', '1', 'Printed', '2026-03-22 14:14:13'),
('2', '3', 'Indigency Certificate', 'test', '5', 'Printed', '2026-03-22 14:34:51');

-- Table: `consultations`
DROP TABLE IF EXISTS `consultations`;
CREATE TABLE `consultations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `resident_id` int unsigned NOT NULL,
  `complaint` text NOT NULL,
  `diagnosis` text,
  `treatment` text,
  `notes` text,
  `consultation_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_consultation_date` (`consultation_date`),
  CONSTRAINT `fk_consultations_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `court_schedule`
DROP TABLE IF EXISTS `court_schedule`;
CREATE TABLE `court_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservation_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `facility` enum('basketball_court','multipurpose_area','gym') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basketball_court',
  `borrower_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `borrower_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organization` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reservation_date` date NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `status` enum('pending','approved','denied','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `approved_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_code` (`reservation_code`),
  KEY `idx_res_date` (`reservation_date`),
  KEY `idx_facility` (`facility`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `court_schedule` VALUES
('1', 'CRS-2026-0001', 'basketball_court', 'test', '', '', 'test', '2026-03-22', '13:08:00', '13:09:00', 'approved', '', NULL, '1', NULL, '2026-03-22 13:08:50', NULL);

-- Table: `events`
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `priority` enum('normal','important','urgent') DEFAULT 'normal',
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_code` (`event_code`),
  KEY `idx_event_code` (`event_code`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `events` VALUES
('2', 'EVT-2026-0001', 'Thesis : Final Defense', '', '2026-03-28', '00:00:00', '', 'normal', 'scheduled', '1', '2026-03-22 14:22:30', '2026-03-22 14:22:30'),
('3', 'EVT-2026-0002', 'Holiday', '', '2026-03-20', '00:00:00', '', 'normal', 'completed', '5', '2026-03-22 14:42:14', '2026-03-22 14:42:25');

-- Table: `families`
DROP TABLE IF EXISTS `families`;
CREATE TABLE `families` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `household_id` int unsigned DEFAULT NULL,
  `family_name` varchar(150) NOT NULL,
  `total_members` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_family_household` (`household_id`),
  CONSTRAINT `fk_family_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `health_metrics`
DROP TABLE IF EXISTS `health_metrics`;
CREATE TABLE `health_metrics` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `resident_id` int unsigned NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `recorded_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_recorded_at` (`recorded_at`),
  CONSTRAINT `fk_health_metrics_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `households`
DROP TABLE IF EXISTS `households`;
CREATE TABLE `households` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `household_no` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `head_id` int NOT NULL,
  `head_name` varchar(150) NOT NULL,
  `total_members` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `household_no` (`household_no`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `households` VALUES
('1', 'h-123', 'test', '1', 'John  Cedric', '0', '2026-03-22 14:16:03', '2026-03-22 14:16:03', NULL),
('2', '9678678', 'Testing', '3', 'Maria  Luiz', '0', '2026-03-22 14:16:35', '2026-03-22 14:16:35', NULL),
('3', '3345', 'test', '3', 'Maria  Luiz', '0', '2026-03-22 14:16:46', '2026-03-22 14:27:14', '2026-03-22 14:27:14'),
('4', '546456', 'test', '1', 'John  Cedric', '0', '2026-03-22 14:17:10', '2026-03-22 14:17:10', NULL),
('5', '56456', 'test', '1', 'John  Cedric', '0', '2026-03-22 14:17:19', '2026-03-22 14:17:19', NULL);

-- Table: `immunizations`
DROP TABLE IF EXISTS `immunizations`;
CREATE TABLE `immunizations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `resident_id` int unsigned NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `dose` varchar(50) DEFAULT NULL,
  `date_given` date NOT NULL,
  `next_schedule` date DEFAULT NULL,
  `administered_by` varchar(100) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_date_given` (`date_given`),
  KEY `idx_next_schedule` (`next_schedule`),
  CONSTRAINT `fk_immunizations_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `inventory`
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `location` varchar(255) DEFAULT NULL,
  `cond` varchar(50) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','damaged','retired') DEFAULT 'available',
  `description` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asset_code` (`asset_code`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_inventory_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `inventory` VALUES
('1', 'PROP-2026-0001', 'Generator', 'Equipments', '5', '', 'Good', 'available', '', '1', '2026-03-22 14:23:27', '2026-03-22 14:23:27');

-- Table: `inventory_audit_trail`
DROP TABLE IF EXISTS `inventory_audit_trail`;
CREATE TABLE `inventory_audit_trail` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` int unsigned NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `action_type` enum('created','updated','deleted','assigned','returned','location_changed','condition_changed','quantity_changed') NOT NULL,
  `user_id` int NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `personnel_name` varchar(255) DEFAULT NULL,
  `personnel_role` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `purpose` text,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_id` (`inventory_id`),
  KEY `idx_asset_code` (`asset_code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `inventory_audit_trail_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_audit_trail_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `inventory_audit_trail` VALUES
('1', '1', 'PROP-2026-0001', 'created', '1', 'Ersel', 'secretary', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"name\":\"Generator\",\"category\":\"Equipments\",\"quantity\":5}', 'New inventory item created', '2026-03-22 14:23:27');

-- Table: `inventory_category_list`
DROP TABLE IF EXISTS `inventory_category_list`;
CREATE TABLE `inventory_category_list` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `inventory_category_list` VALUES
('1', 'Furniture', '2026-03-22 14:22:44'),
('2', 'Equipments', '2026-03-22 14:22:58'),
('3', 'Vehicles', '2026-03-22 14:23:05');

-- Table: `medicine_categories`
DROP TABLE IF EXISTS `medicine_categories`;
CREATE TABLE `medicine_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `medicine_dispense`
DROP TABLE IF EXISTS `medicine_dispense`;
CREATE TABLE `medicine_dispense` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `resident_id` int unsigned NOT NULL,
  `medicine_id` int unsigned NOT NULL,
  `quantity` int NOT NULL,
  `dispense_date` date NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_medicine_id` (`medicine_id`),
  KEY `idx_dispense_date` (`dispense_date`),
  CONSTRAINT `fk_dispense_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_dispense_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `medicines`
DROP TABLE IF EXISTS `medicines`;
CREATE TABLE `medicines` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `stock_qty` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '10',
  `unit` varchar(50) DEFAULT 'pcs',
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_stock_qty` (`stock_qty`),
  KEY `idx_reorder_level` (`reorder_level`),
  KEY `idx_expiration_date` (`expiration_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: `officers`
DROP TABLE IF EXISTS `officers`;
CREATE TABLE `officers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `resident_id` int unsigned DEFAULT NULL,
  `position` varchar(150) NOT NULL,
  `term_start` date DEFAULT NULL,
  `term_end` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_officer_resident` (`resident_id`),
  KEY `idx_archived_at` (`archived_at`),
  KEY `fk_officer_user` (`user_id`),
  CONSTRAINT `fk_officer_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_officer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `officers` VALUES
('1', '2', '1', 'Barangay Captain', '2026-03-01', '2026-03-31', 'Active', '2026-03-22 13:12:50', '2026-03-22 14:33:59', NULL),
('2', '3', '2', 'Barangay Kagawad', '2026-03-01', '2026-03-31', 'Inactive', '2026-03-22 13:13:20', '2026-03-22 14:30:28', '2026-03-22 14:30:28'),
('3', '4', '3', 'HC Nurse', NULL, NULL, 'Active', '2026-03-22 13:13:37', '2026-03-22 14:33:41', NULL),
('4', '5', '3', 'Barangay Kagawad', '2026-03-01', '2026-03-31', 'Active', '2026-03-22 14:31:28', '2026-03-22 14:31:28', NULL);

-- Table: `patrol_schedule`
DROP TABLE IF EXISTS `patrol_schedule`;
CREATE TABLE `patrol_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patrol_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `team_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patrol_date` date NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `patrol_route` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_covered` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_weekly` tinyint(1) NOT NULL DEFAULT '0',
  `week_day` tinyint(1) DEFAULT NULL,
  `tanod_members` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('scheduled','ongoing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patrol_code` (`patrol_code`),
  KEY `idx_patrol_date` (`patrol_date`),
  KEY `idx_status` (`status`),
  KEY `idx_is_weekly` (`is_weekly`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `patrol_schedule` VALUES
('1', 'PRS-2026-0001', 'test', '2026-03-22', '13:09:00', '13:11:00', '', '', '0', NULL, '', '', 'scheduled', '1', NULL, '2026-03-22 13:09:58', NULL);

-- Table: `residents`
DROP TABLE IF EXISTS `residents`;
CREATE TABLE `residents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `household_id` int unsigned DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `birthdate` date NOT NULL,
  `birthplace` varchar(255) DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated') DEFAULT 'Single',
  `religion` varchar(100) DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `citizenship` varchar(100) DEFAULT 'Filipino',
  `contact_no` varchar(20) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `voter_status` enum('Yes','No') DEFAULT 'No',
  `disability_status` enum('Yes','No') DEFAULT 'No',
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_resident_household` (`household_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_resident_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `residents` VALUES
('1', '2', 'John', '', 'Cedric', '', 'Male', '2000-01-01', '', 'Single', '', '', 'Filipino', '', '', 'No', 'No', '', '2026-03-22 13:11:29', '2026-03-22 14:27:09', '2026-03-22 14:27:09'),
('2', '2', 'Rizal', '', 'Dela Cruz', '', 'Male', '1990-01-01', '', 'Single', '', '', 'Filipino', '', '', 'No', 'No', '', '2026-03-22 13:11:52', '2026-03-22 14:17:52', NULL),
('3', '2', 'Maria', '', 'Luiz', '', 'Female', '2000-01-01', '', 'Single', '', '', 'Filipino', '', '', 'No', 'No', '', '2026-03-22 13:12:11', '2026-03-22 14:28:43', NULL);

-- Table: `tanod_duty_schedule`
DROP TABLE IF EXISTS `tanod_duty_schedule`;
CREATE TABLE `tanod_duty_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `duty_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanod_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duty_date` date NOT NULL,
  `shift` enum('morning','afternoon','night') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'morning',
  `post_location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `duty_code` (`duty_code`),
  KEY `idx_duty_date` (`duty_date`),
  KEY `idx_tanod_name` (`tanod_name`),
  KEY `idx_shift` (`shift`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tanod_duty_schedule` VALUES
('1', 'TDS-2026-0001', 'test', '2026-03-22', 'morning', '', '', 'active', '1', NULL, '2026-03-22 13:08:07', NULL);

-- Table: `term_history`
DROP TABLE IF EXISTS `term_history`;
CREATE TABLE `term_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `officer_id` int unsigned NOT NULL,
  `user_id` int NOT NULL,
  `action_type` enum('term_started','term_ended','term_updated','status_changed','position_changed','archived','restored') NOT NULL,
  `old_position` varchar(150) DEFAULT NULL,
  `new_position` varchar(150) DEFAULT NULL,
  `old_term_start` date DEFAULT NULL,
  `new_term_start` date DEFAULT NULL,
  `old_term_end` date DEFAULT NULL,
  `new_term_end` date DEFAULT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_officer_id` (`officer_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `term_history_ibfk_1` FOREIGN KEY (`officer_id`) REFERENCES `officers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `term_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `term_history` VALUES
('1', '1', '1', 'archived', NULL, NULL, NULL, NULL, NULL, NULL, 'Active', 'Inactive', 'Ersel Magbanua', 'Archived current term - all officers except latest secretary', '2026-03-22 14:30:28'),
('2', '2', '1', 'archived', NULL, NULL, NULL, NULL, NULL, NULL, 'Active', 'Inactive', 'Ersel Magbanua', 'Archived current term - all officers except latest secretary', '2026-03-22 14:30:28'),
('3', '3', '1', 'archived', NULL, NULL, NULL, NULL, NULL, NULL, 'Active', 'Inactive', 'Ersel Magbanua', 'Archived current term - all officers except latest secretary', '2026-03-22 14:30:28'),
('4', '3', '1', 'restored', NULL, NULL, NULL, NULL, NULL, NULL, 'Inactive', 'Active', 'Ersel Magbanua', 'Officer restored from archive', '2026-03-22 14:33:41'),
('5', '1', '1', 'restored', NULL, NULL, NULL, NULL, NULL, NULL, 'Inactive', 'Active', 'Ersel Magbanua', 'Officer restored from archive', '2026-03-22 14:33:59');

-- Table: `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` varchar(244) NOT NULL,
  `position` varchar(150) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` VALUES
('1', 'Ersel', 'Ersel Magbanua', 'secretary', 'developer', 'active', '$2y$10$zLDTdGwJunKvG4r2A2OQyOqhqwQzXfm1aPZ2YGUEmQfdVrV./Os.m', NULL, '2026-03-22 12:45:40', '2026-03-22 12:45:40'),
('2', 'john', 'John Cedric', 'captain', NULL, 'active', '$2y$10$He0HBNbKrakrFTb15wa5a.xuHIMEmIqdVazdaTA6aDxK4ebCiUxwG', NULL, '2026-03-22 13:12:50', '2026-03-22 14:33:59'),
('3', 'rizal', 'Rizal Dela Cruz', 'kagawad', NULL, 'disabled', '$2y$10$ZdzkWWwuf33Q3hzQGxzmPOTA4JroDBoWIi7AZRVeaVCNmVxYOQk4C', NULL, '2026-03-22 13:13:20', '2026-03-22 14:30:28'),
('4', 'maria', 'Maria Luiz', 'hcnurse', NULL, 'active', '$2y$10$COrQT961fltVs0lJyoN1EudytSLQuXFRwjZXFb534U.4PgkpL22Su', NULL, '2026-03-22 13:13:37', '2026-03-22 14:33:41'),
('5', 'luis', 'Maria Luiz', 'kagawad', NULL, 'active', '$2y$10$qZo8s601UgwjcUqt4R.b0ubd5pUctRxmaJ6qfmt5L5TTvFhCoNgYO', NULL, '2026-03-22 14:31:28', '2026-03-22 14:31:28');

SET FOREIGN_KEY_CHECKS=1;

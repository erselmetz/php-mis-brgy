<?php
/**
 * Database SQL Generator
 * 
 * This script reads all schema PHP files and generates
 * a complete database_complete.sql file that can be
 * imported into any MySQL database management tool.
 * 
 * It automatically extracts SQL from PHP files and
 * organizes them into sections.
 */

// Configuration
$schemaDir = __DIR__;
$outputFile = $schemaDir . '/database_complete.sql';

// Define migration order and categories
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
            'fix_immunizations_columns.php',
            'add_nip_card_field.php',
            'create_care_visits_table.php',
            'create_care_visits_enhanced.php',
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

// Output buffer
$output = '';

// Helper function to extract SQL from PHP file
function extractSqlFromPhp($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    
    $content = file_get_contents($filePath);
    
    // Pattern to match SQL variable
    $patterns = [
        '/\$sql\s*=\s*"([\s\S]*?)"\s*;/',
        "/\\\$sql\s*=\s*'([\s\S]*?)'\s*;/",
        '/\$sql\s*=\s*<<<SQL([\s\S]*?)SQL\s*;/',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return null;
}

// Start building SQL file
$output .= "-- =====================================================\n";
$output .= "-- MIS BARANGAY - Complete Database Schema\n";
$output .= "-- =====================================================\n";
$output .= "-- This SQL file contains all database tables and structures\n";
$output .= "-- for the Management Information System - Barangay Module\n";
$output .= "--\n";
$output .= "-- Usage:\n";
$output .= "-- 1. Open your database management tool (phpMyAdmin, MySQL Workbench, etc.)\n";
$output .= "-- 2. Create a new database named 'mis_brgy' (or your preferred name)\n";
$output .= "-- 3. Copy and paste this entire file into the SQL query editor\n";
$output .= "-- 4. Click \"Execute\" or \"Run\"\n";
$output .= "--\n";
$output .= "-- Generated: " . date('F d, Y \a\t H:i:s') . "\n";
$output .= "-- =====================================================\n\n";

// Process each category
$totalFiles = 0;
$processedFiles = 0;

foreach ($migrations as $category => $config) {
    $output .= "-- =====================================================\n";
    $output .= "-- SECTION: " . strtoupper($category) . "\n";
    $output .= "-- =====================================================\n";
    $output .= "-- " . $config['description'] . "\n";
    $output .= "--\n";
    
    foreach ($config['files'] as $file) {
        $filePath = $schemaDir . '/' . $file;
        $totalFiles++;
        
        if (!file_exists($filePath)) {
            $output .= "-- ⚠️  File not found: {$file}\n";
            continue;
        }
        
        $sql = extractSqlFromPhp($filePath);
        if (!empty($sql)) {
            $output .= "\n-- File: {$file}\n";
            $output .= $sql . "\n";
            $processedFiles++;
        }
    }
    
    $output .= "\n";
}

// Add indexes section
$output .= "-- =====================================================\n";
$output .= "-- SECTION: INDEXES FOR BETTER QUERY PERFORMANCE\n";
$output .= "-- =====================================================\n\n";

$indexes = [
    'CREATE INDEX idx_users_username ON users(username);',
    'CREATE INDEX idx_users_role ON users(role);',
    'CREATE INDEX idx_users_status ON users(status);',
    'CREATE INDEX idx_residents_household ON residents(household_id);',
    'CREATE INDEX idx_residents_status ON residents(status);',
    'CREATE INDEX idx_residents_firstname ON residents(first_name);',
    'CREATE INDEX idx_residents_lastname ON residents(last_name);',
    'CREATE INDEX idx_officers_resident ON officers(resident_id);',
    'CREATE INDEX idx_officers_position ON officers(position);',
    'CREATE INDEX idx_officers_status ON officers(status);',
    'CREATE INDEX idx_blotter_date ON blotter(date_of_complain);',
    'CREATE INDEX idx_blotter_status ON blotter(status);',
    'CREATE INDEX idx_certificates_resident ON certificates_request(resident_id);',
    'CREATE INDEX idx_certificates_status ON certificates_request(status);',
    'CREATE INDEX idx_immunizations_resident ON immunizations(resident_id);',
    'CREATE INDEX idx_immunizations_status ON immunizations(status);',
    'CREATE INDEX idx_immunizations_date ON immunizations(scheduled_date);',
    'CREATE INDEX idx_consultations_resident ON consultations(resident_id);',
    'CREATE INDEX idx_consultations_date ON consultations(consultation_date);',
    'CREATE INDEX idx_appointments_resident ON appointments(resident_id);',
    'CREATE INDEX idx_appointments_date ON appointments(appointment_date);',
    'CREATE INDEX idx_appointments_status ON appointments(status);',
    'CREATE INDEX idx_health_metrics_resident ON health_metrics(resident_id);',
    'CREATE INDEX idx_health_metrics_date ON health_metrics(visit_date);',
    'CREATE INDEX idx_carevisit_resident ON care_visits(resident_id);',
    'CREATE INDEX idx_carevisit_date ON care_visits(visit_date);',
    'CREATE INDEX idx_carevisit_type ON care_visits(visit_type);',
    'CREATE INDEX idx_medicines_name ON medicines(medicine_name);',
    'CREATE INDEX idx_medicines_category ON medicines(category_id);',
    'CREATE INDEX idx_medicines_expiration ON medicines(expiration_date);',
    'CREATE INDEX idx_medicine_dispense_date ON medicine_dispense(date_dispensed);',
    'CREATE INDEX idx_patrol_schedule_date ON patrol_schedule(schedule_date);',
    'CREATE INDEX idx_patrol_schedule_tanod ON patrol_schedule(tanod_id);',
    'CREATE INDEX idx_duty_schedule_date ON tanod_duty_schedule(schedule_date);',
    'CREATE INDEX idx_duty_schedule_tanod ON tanod_duty_schedule(tanod_id);',
    'CREATE INDEX idx_court_schedule_date ON court_schedule(scheduled_date);',
    'CREATE INDEX idx_court_schedule_status ON court_schedule(status);',
    'CREATE INDEX idx_borrowing_date ON borrowing_schedule(borrowed_date);',
    'CREATE INDEX idx_borrowing_status ON borrowing_schedule(status);',
    'CREATE INDEX idx_inventory_status ON inventory(status);',
    'CREATE INDEX idx_inventory_category ON inventory(category_id);',
];

foreach ($indexes as $index) {
    $output .= $index . "\n";
}

// Add footer
$output .= "\n-- =====================================================\n";
$output .= "-- END OF SCHEMA\n";
$output .= "-- =====================================================\n";
$output .= "-- All tables have been created successfully!\n";
$output .= "-- Files processed: {$processedFiles}/{$totalFiles}\n";
$output .= "-- You can now use this database with your application.\n";
$output .= "-- =====================================================\n";

// Write to file
if (file_put_contents($outputFile, $output) !== false) {
    echo "✅ SUCCESS: database_complete.sql has been generated!\n";
    echo "Location: {$outputFile}\n";
    echo "Files processed: {$processedFiles}/{$totalFiles}\n";
    exit(0);
} else {
    echo "❌ ERROR: Could not write to file: {$outputFile}\n";
    echo "Please check file permissions.\n";
    exit(1);
}
?>

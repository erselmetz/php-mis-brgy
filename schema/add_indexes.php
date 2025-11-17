<?php
/**
 * Add Database Indexes for Performance Optimization
 * MIS Barangay - Database Index Optimization
 * 
 * This migration adds indexes to commonly queried columns for better performance.
 */

include '../includes/db.php';

// Helper function to check if index exists
function indexExists($conn, $table, $indexName) {
    $result = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
    return $result->num_rows > 0;
}

// Helper function to check if table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

$indexes = [
    // Users table indexes
    ['table' => 'users', 'name' => 'idx_users_username', 'columns' => 'username'],
    ['table' => 'users', 'name' => 'idx_users_role', 'columns' => 'role'],
    ['table' => 'users', 'name' => 'idx_users_status', 'columns' => 'status'],
    
    // Residents table indexes
    ['table' => 'residents', 'name' => 'idx_residents_household_id', 'columns' => 'household_id'],
    ['table' => 'residents', 'name' => 'idx_residents_last_name', 'columns' => 'last_name'],
    ['table' => 'residents', 'name' => 'idx_residents_first_name', 'columns' => 'first_name'],
    ['table' => 'residents', 'name' => 'idx_residents_gender', 'columns' => 'gender'],
    ['table' => 'residents', 'name' => 'idx_residents_birthdate', 'columns' => 'birthdate'],
    ['table' => 'residents', 'name' => 'idx_residents_voter_status', 'columns' => 'voter_status'],
    ['table' => 'residents', 'name' => 'idx_residents_disability_status', 'columns' => 'disability_status'],
    ['table' => 'residents', 'name' => 'idx_residents_created_at', 'columns' => 'created_at'],
    
    // Certificates request table indexes
    ['table' => 'certificate_request', 'name' => 'idx_certificate_request_resident_id', 'columns' => 'resident_id'],
    ['table' => 'certificate_request', 'name' => 'idx_certificate_request_status', 'columns' => 'status'],
    ['table' => 'certificate_request', 'name' => 'idx_certificate_request_requested_at', 'columns' => 'requested_at'],
    
    // Officers table indexes
    ['table' => 'officers', 'name' => 'idx_officers_user_id', 'columns' => 'user_id'],
    ['table' => 'officers', 'name' => 'idx_officers_resident_id', 'columns' => 'resident_id'],
    ['table' => 'officers', 'name' => 'idx_officers_status', 'columns' => 'status'],
    ['table' => 'officers', 'name' => 'idx_officers_position', 'columns' => 'position'],
];

$successCount = 0;
$errorCount = 0;
$skippedCount = 0;

foreach ($indexes as $index) {
    // Check if table exists
    if (!tableExists($conn, $index['table'])) {
        echo "⚠️  Table '{$index['table']}' does not exist, skipping index '{$index['name']}'\n";
        $skippedCount++;
        continue;
    }
    
    // Check if index already exists
    if (indexExists($conn, $index['table'], $index['name'])) {
        echo "ℹ️  Index '{$index['name']}' already exists on '{$index['table']}' (skipped)\n";
        $skippedCount++;
        continue;
    }
    
    // Create index
    $sql = "CREATE INDEX {$index['name']} ON {$index['table']}({$index['columns']})";
    
    if ($conn->query($sql) === TRUE) {
        $successCount++;
        echo "✅ Index '{$index['name']}' created successfully on '{$index['table']}'\n";
    } else {
        $errorCount++;
        echo "❌ Error creating index '{$index['name']}': " . $conn->error . "\n";
    }
}

echo "\n📊 Summary:\n";
echo "✅ Successful: {$successCount}\n";
if ($skippedCount > 0) {
    echo "ℹ️  Skipped: {$skippedCount}\n";
}
if ($errorCount > 0) {
    echo "❌ Errors: {$errorCount}\n";
} else {
    echo "✅ All indexes processed successfully!\n";
}

?>


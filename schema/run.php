<?php
// schema/run_all.php
// Runs all table creation scripts for MIS Barangay (Pure PHP)

echo "<pre>";
echo "🚀 Starting MIS Barangay database setup...\n\n";

// Order matters because of foreign key dependencies
$schemaFiles = [
    'create_users_table.php',
    'create_households_table.php',
    'create_families_table.php',
    'create_residents_table.php',
    'create_officers_table.php',
    'create_certificates_table.php'
];

foreach ($schemaFiles as $file) {
    echo "-------------------------------------------\n";
    echo "🔧 Running: $file\n";
    echo "-------------------------------------------\n";

    // Include each schema file
    include $file;

    echo "\n\n";
}

echo "✅ All schema files executed successfully!\n";
echo "You may now use the database 'php_mis_brgy'.\n";
echo "</pre>";
?>

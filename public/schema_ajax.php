<?php
/**
 * Schema Migration AJAX Runner
 * Place at: public/schema_ajax.php
 *
 * Called via POST from the Setup Wizard.
 * Uses the SAME migration list as schema/schema.php — no duplication.
 * Reuses schema.php's $migrations array by extracting it cleanly.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['run'])) {
    http_response_code(405);
    exit('Method not allowed');
}

header('Content-Type: text/plain; charset=utf-8');

// ── DB connection (standalone — no app.php) ──────────────────────────────
$cfgFile = dirname(__DIR__) . '/config.php';
if (file_exists($cfgFile)) include_once $cfgFile;

mysqli_report(MYSQLI_REPORT_OFF);
$conn = null;
try {
    $conn = @new mysqli(
        defined('DB_HOST') ? DB_HOST : 'localhost',
        defined('DB_USER') ? DB_USER : 'root',
        defined('DB_PASS') ? DB_PASS : '',
        defined('DB_NAME') ? DB_NAME : 'php_mis_brgy'
    );
    if ($conn->connect_errno) {
        echo "❌ Cannot connect to database: " . $conn->connect_error . "\n";
        echo "   Make sure MySQL is running and the database exists (Step 1).\n";
        exit;
    }
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . "\n";
    exit;
}

// ── Migration list — mirrors schema/schema.php exactly ───────────────────
$schemaDir  = dirname(__DIR__) . '/schema';
$migrations = [
    'Initial Setup' => [
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
        'create_account.php',
        'create_patrol_schedule_table.php',
        'create_tanod_duty_schedule_table.php',
        'create_court_schedule_table.php',
        'create_borrowing_schedule_table.php',
        'create_appointments_table.php',
    ],
    'Feature Additions' => [
        'add_profile_picture_to_users.php',
        'add_archived_to_residents.php',
        'add_archived_at_to_blotter.php',
        'create_blotter_history_table.php',
        'add_archived_at_to_officers.php',
        'create_term_history_table.php',
        'add_archived_at_to_households.php',
        'fix_immunizations_columns.php',
    ],
    'Structural Changes' => [
        'merge_staff_officers.php',
        'make_officer_term_nullable.php',
        'create_care_visits_enhanced.php',
        'enhance_consultations.php',
    ],
];

// ── Runner ────────────────────────────────────────────────────────────────
$totalOk = 0; $totalFail = 0; $totalSkip = 0;
$start   = microtime(true);

echo "🚀 MIS Barangay Migration Runner\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('═', 52) . "\n";

foreach ($migrations as $group => $files) {
    echo "\n📦 {$group}\n";
    echo str_repeat('─', 52) . "\n";
    $gOk = 0; $gFail = 0; $gSkip = 0;

    foreach ($files as $file) {
        $path = $schemaDir . '/' . $file;

        if (!file_exists($path)) {
            echo "⚠️  Skipped (not found): {$file}\n";
            $gSkip++; $totalSkip++;
            continue;
        }

        echo "🔧 {$file}\n";
        $t0 = microtime(true);
        ob_start();
        try {
            include $path;
            // Each migration file calls $conn->close() — reopen if needed
            if (!$conn || $conn->connect_errno) {
                $conn = @new mysqli(
                    defined('DB_HOST') ? DB_HOST : 'localhost',
                    defined('DB_USER') ? DB_USER : 'root',
                    defined('DB_PASS') ? DB_PASS : '',
                    defined('DB_NAME') ? DB_NAME : 'php_mis_brgy'
                );
                if (!$conn->connect_errno) $conn->set_charset('utf8mb4');
            }
        } catch (Throwable $e) {
            ob_end_clean();
            echo "   ❌ Error: " . $e->getMessage() . "\n";
            $gFail++; $totalFail++;
            continue;
        }
        $out  = trim(ob_get_clean());
        $ms   = round((microtime(true) - $t0) * 1000);
        $fail = (stripos($out, 'error') !== false || str_contains($out, '❌'));

        if ($out !== '') echo "   " . $out . "\n";
        echo "   " . ($fail ? "❌ FAILED" : "✅ OK") . " ({$ms}ms)\n";

        if ($fail) { $gFail++; $totalFail++; }
        else       { $gOk++;   $totalOk++;   }
    }

    echo str_repeat('─', 52) . "\n";
    echo "   ✅ {$gOk}  ❌ {$gFail}  ⚠️  {$gSkip}\n";
}

$elapsed = round(microtime(true) - $start, 2);
echo "\n" . str_repeat('═', 52) . "\n";
if ($totalFail === 0) {
    echo "✅ ALL MIGRATIONS COMPLETED SUCCESSFULLY!\n";
} else {
    echo "⚠️  Done with {$totalFail} error(s) — review output above.\n";
}
echo "Total: ✅ {$totalOk}  ❌ {$totalFail}  ⚠️ {$totalSkip}\n";
echo "Time: {$elapsed}s\n";
echo "Completed: " . date('Y-m-d H:i:s') . "\n";
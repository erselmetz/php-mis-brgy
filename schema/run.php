<?php
/**
 * Database Migration Runner
 * 
 * This script organizes and runs database migrations for MIS Barangay.
 * 
 * Usage:
 *   CLI: php schema/run.php
 *   Browser: http://php-mis-brgy.local/schema/run
 * 
 * Migration Types:
 *   1. Initial Setup - Creates all base tables
 *   2. Feature Additions - Adds new features/columns
 *   3. Structural Changes - Major schema changes
 */

// Detect if running from CLI or browser
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Browser mode - include app.php for authentication
    include_once __DIR__ . '/../includes/app.php';
    requireAdmin(); // Only admin can run migrations
    
    echo "<!DOCTYPE html><html><head><title>Database Migrations</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
    echo ".section{margin:20px 0;padding:15px;background:white;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
    echo ".success{color:#10b981;} .error{color:#ef4444;} .warning{color:#f59e0b;}";
    echo ".migration{padding:10px;margin:5px 0;background:#f9fafb;border-left:3px solid #446c3e;}";
    echo "pre{background:#1f2937;color:#f9fafb;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body>";
    echo "<h1>🚀 Database Migration Runner</h1>";
    echo "<p><a href='/admin/account'>← Back to Admin</a></p>";
}

// Migration categories
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
            'create_term_history_table.php'
        ]
    ],
    'Structural Changes' => [
        'description' => 'Major schema changes and refactoring',
        'files' => [
            'merge_staff_officers.php',
        ]
    ]
];

// Function to run migrations
function runMigrations($migrations, $isCLI) {
    $totalSuccess = 0;
    $totalFailed = 0;
    $totalSkipped = 0;
    $categoryStats = [];
    
    foreach ($migrations as $category => $config) {
        $categorySuccess = 0;
        $categoryFailed = 0;
        $categorySkipped = 0;
        $fileCount = count($config['files']);
        $currentFile = 0;
        
        if (!$isCLI) {
            echo "<div class='section'>";
            echo "<h2>📦 {$category}</h2>";
            echo "<p><em>{$config['description']}</em></p>";
            echo "<p><small>Total files: {$fileCount}</small></p>";
        } else {
            echo "\n";
            echo str_repeat("=", 80) . "\n";
            echo "📦 {$category}\n";
            echo str_repeat("=", 80) . "\n";
            echo "Description: {$config['description']}\n";
            echo "Files to process: {$fileCount}\n";
            echo str_repeat("-", 80) . "\n";
        }
        
        foreach ($config['files'] as $index => $file) {
            $currentFile++;
            $filePath = __DIR__ . '/' . $file;
            
            if (!file_exists($filePath)) {
                if (!$isCLI) {
                    echo "<div class='migration'><span class='warning'>⚠️</span> File not found: {$file}</div>";
                } else {
                    echo "\n[{$currentFile}/{$fileCount}] ⚠️  SKIPPED: {$file}\n";
                    echo "   Reason: File not found\n";
                }
                $totalSkipped++;
                $categorySkipped++;
                continue;
            }
            
            if (!$isCLI) {
                echo "<div class='migration'>";
                echo "<strong>🔧 Running: {$file}</strong> <small>({$currentFile}/{$fileCount})</small><br>";
                echo "<pre>";
            } else {
                echo "\n[{$currentFile}/{$fileCount}] 🔧 Running: {$file}\n";
                echo str_repeat("-", 80) . "\n";
            }
            
            // Capture output
            $startTime = microtime(true);
            ob_start();
            try {
                include $filePath;
                $output = ob_get_clean();
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                
                if (!$isCLI) {
                    echo htmlspecialchars($output);
                    echo "</pre>";
                } else {
                    echo $output;
                }
                
                // Check for errors in output
                if (stripos($output, 'error') !== false || stripos($output, '❌') !== false) {
                    $totalFailed++;
                    $categoryFailed++;
                    if (!$isCLI) {
                        echo "<span class='error'>❌ Migration failed or had errors</span>";
                    } else {
                        echo "\n❌ STATUS: FAILED (Execution time: {$executionTime}ms)\n";
                    }
                } else {
                    $totalSuccess++;
                    $categorySuccess++;
                    if (!$isCLI) {
                        echo "<span class='success'>✅ Migration completed</span>";
                    } else {
                        echo "✅ STATUS: SUCCESS (Execution time: {$executionTime}ms)\n";
                    }
                }
            } catch (Exception $e) {
                ob_end_clean();
                $totalFailed++;
                $categoryFailed++;
                $errorMsg = "❌ Error: " . $e->getMessage();
                if (!$isCLI) {
                    echo "<span class='error'>{$errorMsg}</span>";
                } else {
                    echo "\n{$errorMsg}\n";
                    echo "❌ STATUS: FAILED (Exception thrown)\n";
                }
            }
            
            if (!$isCLI) {
                echo "</div>";
            }
        }
        
        // Category summary
        $categoryStats[$category] = [
            'success' => $categorySuccess,
            'failed' => $categoryFailed,
            'skipped' => $categorySkipped,
            'total' => $fileCount
        ];
        
        if (!$isCLI) {
            echo "<div style='margin-top:10px;padding:10px;background:#f0f0f0;border-radius:5px;'>";
            echo "<strong>Category Summary:</strong> ";
            echo "<span class='success'>✅ {$categorySuccess}</span> | ";
            echo "<span class='error'>❌ {$categoryFailed}</span> | ";
            echo "<span class='warning'>⚠️ {$categorySkipped}</span>";
            echo "</div>";
            echo "</div>";
        } else {
            echo "\n" . str_repeat("-", 80) . "\n";
            echo "Category Summary: ✅ {$categorySuccess} | ❌ {$categoryFailed} | ⚠️  {$categorySkipped} / {$fileCount} files\n";
        }
    }
    
    // Summary
    if (!$isCLI) {
        echo "<div class='section'>";
        echo "<h2>📊 Final Summary</h2>";
        echo "<table style='width:100%;border-collapse:collapse;'>";
        echo "<tr style='background:#f9fafb;'><th style='padding:10px;text-align:left;border:1px solid #ddd;'>Category</th>";
        echo "<th style='padding:10px;text-align:center;border:1px solid #ddd;'>✅ Success</th>";
        echo "<th style='padding:10px;text-align:center;border:1px solid #ddd;'>❌ Failed</th>";
        echo "<th style='padding:10px;text-align:center;border:1px solid #ddd;'>⚠️ Skipped</th>";
        echo "<th style='padding:10px;text-align:center;border:1px solid #ddd;'>Total</th></tr>";
        
        foreach ($categoryStats as $cat => $stats) {
            echo "<tr>";
            echo "<td style='padding:10px;border:1px solid #ddd;'><strong>{$cat}</strong></td>";
            echo "<td style='padding:10px;text-align:center;border:1px solid #ddd;'><span class='success'>{$stats['success']}</span></td>";
            echo "<td style='padding:10px;text-align:center;border:1px solid #ddd;'><span class='error'>{$stats['failed']}</span></td>";
            echo "<td style='padding:10px;text-align:center;border:1px solid #ddd;'><span class='warning'>{$stats['skipped']}</span></td>";
            echo "<td style='padding:10px;text-align:center;border:1px solid #ddd;'>{$stats['total']}</td>";
            echo "</tr>";
        }
        
        echo "<tr style='background:#f9fafb;font-weight:bold;'>";
        echo "<td style='padding:10px;border:1px solid #ddd;'>TOTAL</td>";
        echo "<td style='padding:10px;text-align:center;border:1px solid #ddd;'><span class='success'>{$totalSuccess}</span></td>";
        echo "<td style='padding:10px;text-align:center;border:1px solid #ddd;'><span class='error'>{$totalFailed}</span></td>";
        echo "<td style='padding:10px;text-align:center;border:1px solid #ddd;'><span class='warning'>{$totalSkipped}</span></td>";
        $grandTotal = $totalSuccess + $totalFailed + $totalSkipped;
        echo "<td style='padding:10px;text-align:center;border:1px solid #ddd;'>{$grandTotal}</td>";
        echo "</tr>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "\n";
        echo str_repeat("=", 80) . "\n";
        echo "📊 FINAL SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        echo "\n";
        echo str_pad("Category", 25) . str_pad("✅ Success", 12) . str_pad("❌ Failed", 12) . str_pad("⚠️  Skipped", 12) . "Total\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($categoryStats as $cat => $stats) {
            echo str_pad($cat, 25) . 
                 str_pad($stats['success'], 12) . 
                 str_pad($stats['failed'], 12) . 
                 str_pad($stats['skipped'], 12) . 
                 $stats['total'] . "\n";
        }
        
        echo str_repeat("-", 80) . "\n";
        $grandTotal = $totalSuccess + $totalFailed + $totalSkipped;
        echo str_pad("TOTAL", 25) . 
             str_pad($totalSuccess, 12) . 
             str_pad($totalFailed, 12) . 
             str_pad($totalSkipped, 12) . 
             $grandTotal . "\n";
        echo "\n";
    }
    
    return $totalFailed === 0;
}

// Run migrations
$scriptStartTime = microtime(true);

if ($isCLI) {
    echo "\n";
    echo str_repeat("=", 80) . "\n";
    echo "🚀 MIS BARANGAY DATABASE MIGRATION RUNNER\n";
    echo str_repeat("=", 80) . "\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n";
    echo "Running from: " . __DIR__ . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo str_repeat("=", 80) . "\n";
} else {
    echo "<div class='section'>";
    echo "<h2>🚀 Migration Runner</h2>";
    echo "<p><strong>Started at:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Running from:</strong> " . __DIR__ . "</p>";
    echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
    echo "</div>";
}

$success = runMigrations($migrations, $isCLI);

$scriptEndTime = microtime(true);
$totalExecutionTime = round($scriptEndTime - $scriptStartTime, 2);

if ($isCLI) {
    echo "\n";
    echo str_repeat("=", 80) . "\n";
    if ($success) {
        echo "✅ ALL MIGRATIONS COMPLETED SUCCESSFULLY!\n";
    } else {
        echo "⚠️  SOME MIGRATIONS HAD ERRORS - Please review the output above.\n";
    }
    echo str_repeat("=", 80) . "\n";
    echo "Total execution time: {$totalExecutionTime} seconds\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat("=", 80) . "\n";
    
    if (!$success) {
        exit(1);
    }
} else {
    echo "<div class='section'>";
    if ($success) {
        echo "<h2 class='success'>✅ All migrations completed successfully!</h2>";
    } else {
        echo "<h2 class='error'>⚠️ Some migrations had errors. Please review the output above.</h2>";
    }
    echo "<p><strong>Total execution time:</strong> {$totalExecutionTime} seconds</p>";
    echo "<p><strong>Completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "</div>";
    echo "</body></html>";
}
?>

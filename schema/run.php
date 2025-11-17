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
    echo ".migration{padding:10px;margin:5px 0;background:#f9fafb;border-left:3px solid #3b82f6;}";
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
        ]
    ],
    'Feature Additions' => [
        'description' => 'Adds new features and columns to existing tables',
        'files' => [
            'add_profile_picture_to_users.php',
        ]
    ],
    'Database Optimization' => [
        'description' => 'Adds indexes and optimizes database performance',
        'files' => [
            'migrations_table.php',
            'add_indexes.php',
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
    
    foreach ($migrations as $category => $config) {
        if (!$isCLI) {
            echo "<div class='section'>";
            echo "<h2>📦 {$category}</h2>";
            echo "<p><em>{$config['description']}</em></p>";
        } else {
            echo "\n" . str_repeat("=", 70) . "\n";
            echo "📦 {$category}\n";
            echo str_repeat("-", 70) . "\n";
            echo "{$config['description']}\n";
        }
        
        foreach ($config['files'] as $file) {
            $filePath = __DIR__ . '/' . $file;
            
            if (!file_exists($filePath)) {
                if (!$isCLI) {
                    echo "<div class='migration'><span class='warning'>⚠️</span> File not found: {$file}</div>";
                } else {
                    echo "⚠️  File not found: {$file}\n";
                }
                $totalSkipped++;
                continue;
            }
            
            if (!$isCLI) {
                echo "<div class='migration'>";
                echo "<strong>🔧 Running: {$file}</strong><br>";
                echo "<pre>";
            } else {
                echo "\n" . str_repeat("-", 70) . "\n";
                echo "🔧 Running: {$file}\n";
                echo str_repeat("-", 70) . "\n";
            }
            
            // Capture output
            ob_start();
            try {
                // Change to schema directory so relative paths in migration files work
                $originalDir = getcwd();
                chdir(__DIR__);
                include $filePath;
                chdir($originalDir);
                $output = ob_get_clean();
                
                if (!$isCLI) {
                    echo htmlspecialchars($output);
                    echo "</pre>";
                } else {
                    echo $output;
                }
                
                // Check for errors in output
                if (stripos($output, 'error') !== false || stripos($output, '❌') !== false) {
                    $totalFailed++;
                    if (!$isCLI) {
                        echo "<span class='error'>❌ Migration failed or had errors</span>";
                    }
                } else {
                    $totalSuccess++;
                    if (!$isCLI) {
                        echo "<span class='success'>✅ Migration completed</span>";
                    }
                }
            } catch (Exception $e) {
                ob_end_clean();
                $totalFailed++;
                $errorMsg = "❌ Error: " . $e->getMessage();
                if (!$isCLI) {
                    echo "<span class='error'>{$errorMsg}</span>";
                } else {
                    echo $errorMsg . "\n";
                }
            }
            
            if (!$isCLI) {
                echo "</div>";
            }
        }
        
        if (!$isCLI) {
            echo "</div>";
        }
    }
    
    // Summary
    if (!$isCLI) {
        echo "<div class='section'>";
        echo "<h2>📊 Summary</h2>";
        echo "<p><span class='success'>✅ Successful: {$totalSuccess}</span></p>";
        echo "<p><span class='error'>❌ Failed: {$totalFailed}</span></p>";
        if ($totalSkipped > 0) {
            echo "<p><span class='warning'>⚠️ Skipped: {$totalSkipped}</span></p>";
        }
        echo "</div>";
    } else {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📊 Summary\n";
        echo str_repeat("=", 70) . "\n";
        echo "✅ Successful: {$totalSuccess}\n";
        echo "❌ Failed: {$totalFailed}\n";
        if ($totalSkipped > 0) {
            echo "⚠️  Skipped: {$totalSkipped}\n";
        }
    }
    
    return $totalFailed === 0;
}

// Run migrations
if ($isCLI) {
    echo "🚀 Starting MIS Barangay Database Migrations...\n";
    echo "Running from: " . __DIR__ . "\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
} else {
    echo "<p><strong>Running from:</strong> " . __DIR__ . "</p>";
    echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
}

$success = runMigrations($migrations, $isCLI);

if ($isCLI) {
    echo "\n" . str_repeat("=", 70) . "\n";
    if ($success) {
        echo "✅ All migrations completed successfully!\n";
    } else {
        echo "⚠️  Some migrations had errors. Please review the output above.\n";
        exit(1);
    }
} else {
    if ($success) {
        echo "<div class='section'><h2 class='success'>✅ All migrations completed successfully!</h2></div>";
    } else {
        echo "<div class='section'><h2 class='error'>⚠️ Some migrations had errors. Please review the output above.</h2></div>";
    }
    echo "</body></html>";
}
?>

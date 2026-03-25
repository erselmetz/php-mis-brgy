<?php
// One-time script to create admin (save as seed.php and run once)
include '../includes/db.php'; // should define $conn (MySQLi)

// Create hashed password
$hash = password_hash('redzone', PASSWORD_DEFAULT);

/**
 * Check if developer account already exists (id=1)
 * If not, create it with id=1 to ensure it always has the same ID for easy reference in code (e.g. showing "Developer" badge instead of role name)
 */

// Check users table
$userCheck = $conn->query("SELECT id, position FROM users WHERE id = 1 OR position = 'developer'");

if (!$userCheck) {
    die("❌ Error checking users table: " . $conn->error . "\n");
}

$userCount = $userCheck->num_rows;

if ($userCount == 0) {
    // No developer account exists - create new one
    $sql = "INSERT INTO users (id, username, name, role, position, password) 
            VALUES (1, 'Ersel', 'Ersel Magbanua', 'secretary', 'developer', '$hash')";
    
    if ($conn->query($sql)) {
        echo "✅ Developer account created successfully (ID: 1)\n";
    } else {
        die("❌ Error creating developer account: " . $conn->error . "\n");
    }
} elseif ($userCount == 1) {
    // Developer account exists - update password
    $row = $userCheck->fetch_assoc();
    $userId = $row['id'];
    
    $sql = "UPDATE users SET password = '$hash' WHERE id = $userId";
    
    if ($conn->query($sql)) {
        echo "✅ Developer account password updated successfully (ID: $userId)\n";
    } else {
        die("❌ Error updating developer password: " . $conn->error . "\n");
    }
} else {
    // Multiple accounts found - error
    echo "❌ Error: Multiple developer accounts found!\n";
    echo "Please check the users table and ensure only one developer account exists.\n";
    $userCheck->data_seek(0);
    while ($row = $userCheck->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Position: {$row['position']}\n";
    }
    exit(1);
}

// Check officers table for System Developer entry
$officerCheck = $conn->query("SELECT user_id, position FROM officers WHERE user_id = 1 OR position = 'System Developer'");

if (!$officerCheck) {
    echo "⚠️ Warning: Could not check officers table: " . $conn->error . "\n";
} else {
    $officerCount = $officerCheck->num_rows;
    
    if ($officerCount == 0) {
        // No developer entry in officers table - create it
        $sql = "INSERT INTO officers (user_id, position) VALUES (1, 'System Developer')";
        
        if ($conn->query($sql)) {
            echo "✅ System Developer entry created in officers table\n";
        } else {
            echo "⚠️ Warning: Could not create officers entry: " . $conn->error . "\n";
        }
    } elseif ($officerCount == 1) {
        // Check if it's linked to user_id 1
        $row = $officerCheck->fetch_assoc();
        if ($row['user_id'] != 1) {
            echo "⚠️ Warning: System Developer exists but is linked to user_id {$row['user_id']} instead of 1\n";
        } else {
            echo "✅ System Developer entry already exists in officers table\n";
        }
    } else {
        echo "⚠️ Warning: Multiple System Developer entries found in officers table\n";
    }
}

echo "\n✨ Setup completed successfully!\n";
$conn->close();
?>
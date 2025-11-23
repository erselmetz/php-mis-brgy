<?php
// schema/merge_staff_officers.php
// Merges staff and officers management by adding position to users and linking officers to users

include '../includes/db.php';

// Helper function to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result->num_rows > 0;
}

// Step 1: Add position column to users table (for non-officers)
if (!columnExists($conn, 'users', 'position')) {
    $sql1 = "ALTER TABLE users ADD COLUMN position VARCHAR(150) NULL AFTER role";
    
    if ($conn->query($sql1) === TRUE) {
        echo "✅ Column 'position' added to 'users' table successfully.\n";
    } else {
        echo "❌ Error adding column 'position': " . $conn->error . "\n";
    }
} else {
    echo "✅ Column 'position' already exists in 'users' table.\n";
}

// Step 2: Add user_id column to officers table (to link officers to accounts)
if (!columnExists($conn, 'officers', 'user_id')) {
    $sql2 = "ALTER TABLE officers ADD COLUMN user_id INT(11) NULL AFTER id";
    
    if ($conn->query($sql2) === TRUE) {
        echo "✅ Column 'user_id' added to 'officers' table successfully.\n";
    } else {
        echo "❌ Error adding column 'user_id': " . $conn->error . "\n";
    }
} else {
    echo "✅ Column 'user_id' already exists in 'officers' table.\n";
}

// Step 3: Add foreign key constraint for user_id in officers table
// First, check if the foreign key already exists
$checkFK = "SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'officers' 
            AND CONSTRAINT_NAME = 'fk_officer_user'";

$result = $conn->query($checkFK);
if ($result && $result->num_rows == 0) {
    $sql3 = "ALTER TABLE officers 
             ADD CONSTRAINT fk_officer_user 
             FOREIGN KEY (user_id) REFERENCES users(id) 
             ON DELETE SET NULL 
             ON UPDATE CASCADE";
    
    if ($conn->query($sql3) === TRUE) {
        echo "✅ Foreign key constraint 'fk_officer_user' added successfully.\n";
    } else {
        echo "⚠️ Warning: Could not add foreign key constraint (may already exist or table has data): " . $conn->error . "\n";
    }
} else {
    echo "✅ Foreign key constraint 'fk_officer_user' already exists.\n";
}

$conn->close();
?>

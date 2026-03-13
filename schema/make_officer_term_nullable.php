<?php
// schema/make_officer_term_nullable.php
// Makes term_start and term_end nullable in officers table
// Required to support hcnurse role which has no elected term

include '../includes/db.php';

$changes = [];

$res = $conn->query("SHOW COLUMNS FROM officers LIKE 'term_start'");
$col = $res->fetch_assoc();
if ($col && strtolower($col['Null']) !== 'yes') {
    if ($conn->query("ALTER TABLE officers MODIFY COLUMN term_start DATE NULL DEFAULT NULL")) {
        $changes[] = "✅ term_start is now nullable.";
    } else {
        $changes[] = "❌ Failed to alter term_start: " . $conn->error;
    }
} else {
    $changes[] = "ℹ️ term_start is already nullable.";
}

$res = $conn->query("SHOW COLUMNS FROM officers LIKE 'term_end'");
$col = $res->fetch_assoc();
if ($col && strtolower($col['Null']) !== 'yes') {
    if ($conn->query("ALTER TABLE officers MODIFY COLUMN term_end DATE NULL DEFAULT NULL")) {
        $changes[] = "✅ term_end is now nullable.";
    } else {
        $changes[] = "❌ Failed to alter term_end: " . $conn->error;
    }
} else {
    $changes[] = "ℹ️ term_end is already nullable.";
}

foreach ($changes as $msg) echo $msg . "\n";

$conn->close();
?>
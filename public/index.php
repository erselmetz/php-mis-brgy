<?php
require_once __DIR__ . '/../includes/db.php';

if (!$conn) {
  $setup_error = $db_error ?? 'Cannot connect to database.';
  include __DIR__ . '/setup_wizard.php';
  exit;
}

/* Stay on setup wizard until migrations have created core tables (not only empty DB). */
$setup_tables_ok = false;
$r = $conn->query("SHOW TABLES LIKE 'users'");
if ($r && $r->num_rows > 0) {
  $setup_tables_ok = true;
}

if (!$setup_tables_ok) {
  include __DIR__ . '/setup_wizard.php';
  exit;
}

header('Location: navigator.php');
exit;
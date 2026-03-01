<?php
require_once __DIR__ . '/../includes/db.php';

if (!$conn) {
  // optional: pass error message to wizard
  $setup_error = $db_error ?? 'Cannot connect to database.';
  include __DIR__ . '/setup_wizard.php';
  exit;
}

header("Location: navigator.php");
exit;
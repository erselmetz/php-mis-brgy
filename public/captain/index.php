<?php
require_once '../auth.php';

// Only the 'captain' can access this page
require_role(['captain']);
?>
<!DOCTYPE html>
<h1>Welcome, Captain!</h1>
<p>You have view-only access to the system.</p>
<!-- Read-only views of data go here. Forms and action buttons should be hidden or disabled. -->
<a href="/public/logout.php">Logout</a>
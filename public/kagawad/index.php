<?php
require_once '../auth.php';

// Only the 'kagawad' can access this page
require_role(['kagawad']);
?>
<!DOCTYPE html>
<h1>Welcome, Kagawad!</h1>
<p>You have access to specific modules.</p>
<!-- Placeholders for limited-access modules go here -->
<a href="/public/logout.php">Logout</a>
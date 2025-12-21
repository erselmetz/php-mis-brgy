<?php
require_once '../auth.php';

// Only the 'hcnurse' can access this page
require_role(['hcnurse']);
?>
<?php
// Redirect hcnurse landing page to the dashboard
header('Location: dashboard/');
exit;
?>
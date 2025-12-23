<?php
require_once __DIR__ . '/../includes/app.php';

// Ensure user is logged in
if (!isset($_SESSION['role'])) {
    header('Location: /login/');
    exit;
}

$role = strtolower($_SESSION['role']);

$routes = [
    'captain' => '/captain/dashboard/',
    'hcnurse'  => '/hcnurse/dashboard/',
    'kagawad'  => '/kagawad/dashboard/',
    'secretary'=> '/secretary/dashboard/',
    'admin'    => '/captain/dashboard/',
    'staff'    => '/captain/dashboard/',
];

if (isset($routes[$role])) {
    header('Location: ' . $routes[$role]);
    exit;
}

// Fallback: try a conventional path for the role
$fallback = '/' . $role . '/dashboard/';
header('Location: ' . $fallback);
exit;

?>
<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: /public/login.php");
    exit();
}

$role = $_SESSION['role'];

// Route user based on their role
switch ($role) {
    case 'secretary':
        header("Location: /public/secretary/");
        break;
    case 'captain':
        header("Location: /public/captain/");
        break;
    case 'kagawad':
        header("Location: /public/kagawad/");
        break;
    case 'hcnurse':
        header("Location: /public/hcnurse/");
        break;
    default:
        // If role is unknown or not set, send back to login
        session_destroy();
        header("Location: /public/login.php?error=invalid_role");
        break;
}
exit();
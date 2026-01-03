<?php
require_once __DIR__ . '/../includes/app.php';

// If user is already logged in, redirect to their dashboard (even if on login page)
if (isset($_SESSION['role'])) {
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
}

// If not logged in, check if we're on login page
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($currentPath, '/login/') !== false) {
    return; // Allow login page to display
}

// Not logged in and not on login page - redirect to login
header('Location: /login/');
exit;

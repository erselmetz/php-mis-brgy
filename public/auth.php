<?php
session_start();

/**
 * Authorization check.
 *
 * @param array $allowedRoles An array of roles allowed to access the page.
 */
function require_role(array $allowedRoles) {
    // 1. Check if user is logged in
    if (!isset($_SESSION['role'])) {
        header("Location: /public/login.php?error=unauthorized");
        exit();
    }

    $userRole = $_SESSION['role'];

    // 2. Check if the user's role is in the list of allowed roles
    if (!in_array($userRole, $allowedRoles)) {
        // Forbid access and provide a generic error message
        http_response_code(403);
        echo "<h1>403 Forbidden</h1>";
        echo "You do not have permission to access this page.";
        
        // Optionally, you can redirect them to their own dashboard
        // echo '<p>Redirecting to your dashboard...</p>';
        // echo '<meta http-equiv="refresh" content="3;url=/public/navigator.php">';
        
        exit();
    }
}

?>
<?php
/**
 * Authentication and Authorization Functions
 * 
 * These functions handle user authentication and role-based access control.
 * They check session data and redirect unauthorized users appropriately.
 * 
 * Usage:
 *   requireLogin() - Ensures user is logged in
 *   requireAdmin() - Ensures user is admin
 *   requireStaff() - Ensures user is staff or admin
 *   requireTanod() - Ensures user is tanod or admin
 */

/**
 * Require user to be logged in
 * Redirects to login page if not authenticated
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login/");
        exit();
    }
}

/**
 * Require admin role
 * Admins have full access to all features
 * Other roles are redirected to dashboard
 */
function requireAdmin() {
    requireLogin(); // First check if user is logged in
    
    if ($_SESSION['role'] !== 'admin') {
        // Non-admin users are redirected to dashboard
        header("Location: /dashboard");
        exit();
    }
}

/**
 * Require Tanod role (or admin)
 * Tanod can access blotter management features
 * Admin can access everything, so they're allowed
 */
function requireTanod() {
    requireLogin(); // First check if user is logged in
    
    if ($_SESSION['role'] !== 'tanod' && $_SESSION['role'] !== 'admin') {
        // Only tanod and admin can access tanod features
        header("Location: /dashboard");
        exit();
    }
}

/**
 * Require Staff role (or admin)
 * Staff can access resident and certificate management
 * Admin can access everything, so they're allowed
 */
function requireStaff() {
    requireLogin(); // First check if user is logged in
    
    if ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin') {
        // Only staff and admin can access staff features
        header("Location: /dashboard");
        exit();
    }
}

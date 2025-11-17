<?php
/**
 * Authentication Functions
 * MIS Barangay - Access Control
 * 
 * This file contains functions for handling user authentication and authorization.
 */

/**
 * Redirect to a URL and exit
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirectTo(string $url): void
{
    header("Location: $url");
    exit();
}

/**
 * Require user to be logged in
 * Redirects to login page if user is not authenticated
 * @return void
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirectTo('/login');
    }
}

/**
 * Require user to have admin role
 * Redirects to dashboard if user is not an admin
 * @return void
 */
function requireAdmin(): void
{
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        redirectTo('/dashboard');
    }
}

/**
 * Require user to have tanod role
 * Admins can also access tanod features
 * @return void
 */
function requireTanod(): void
{
    requireLogin();
    if ($_SESSION['role'] !== 'tanod') {
        if ($_SESSION['role'] === 'admin') {
            // Admin can access everything, so allow
            return;
        } else {
            // Staff or other roles cannot access Tanod features
            redirectTo('/dashboard');
        }
    }
}

/**
 * Require user to have staff or admin role
 * Redirects to dashboard if user doesn't have required role
 * @return void
 */
function requireStaff(): void
{
    requireLogin();
    if ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin') {
        redirectTo('/dashboard');
    }
}

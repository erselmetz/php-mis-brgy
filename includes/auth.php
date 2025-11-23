<?php

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        if($_SESSION['role'] === 'staff') {
            header("Location: /dashboard");
            exit();
        } elseif($_SESSION['role'] === 'tanod') {
            header("Location: /dashboard");
            exit();
        }
    }
}

function requireTanod() {
    requireLogin();
    if ($_SESSION['role'] !== 'tanod') {
        if($_SESSION['role'] === 'admin') {
            // Admin can access everything, so allow
            return;
        } else {
            // Staff or other roles cannot access Tanod features
            header("Location: /dashboard");
            exit();
        }
    }
}

function requireStaff() {
    requireLogin();
    if ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin') {
        if($_SESSION['role'] === 'tanod') {
            // Tanod cannot access staff features
            header("Location: /dashboard");
            exit();
        } else {
            header("Location: /dashboard");
            exit();
        }
    }
}

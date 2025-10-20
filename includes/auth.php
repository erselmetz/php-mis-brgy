<?php

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /index.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        if($_SESSION['role'] === 'staff') {
            header("Location: /dashboard.php");
            exit();
        }
    }
}

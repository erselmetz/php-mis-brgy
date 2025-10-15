<?php

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /index.php");
        exit();
    }
}

function requireAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        header("Location: /index.php");
        exit();
    }
}

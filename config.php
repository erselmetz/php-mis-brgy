<?php
/**
 * Main Configuration File
 * MIS Barangay - Configuration
 * 
 * This file contains the main configuration settings for the application.
 */

// Version
define('VERSION', '1.4.0');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'admin');
define('DB_PASS', 'phpmisbrgy');
define('DB_NAME', 'php_mis_brgy');

// Base path of the project
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('PUBLIC_PATH', BASE_PATH . '/public');
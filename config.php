<?php
/**
 * Configuration File
 * 
 * Contains database connection settings and application paths.
 * 
 * SECURITY NOTE: In production, consider moving sensitive credentials
 * to environment variables or a file outside the web root.
 * 
 * @var string DB_HOST Database server hostname or IP address
 * @var string DB_USER Database username
 * @var string DB_PASS Database password
 * @var string DB_NAME Database name
 */

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'admin');
define('DB_PASS', 'phpmisbrgy');
define('DB_NAME', 'php_mis_brgy');

/**
 * Application Paths
 * These constants define the base paths for the application
 * Used for including files and loading assets
 */
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('PUBLIC_PATH', BASE_PATH . '/public');
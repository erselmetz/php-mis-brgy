<?php

/**
 * Utility Functions for MIS Barangay System
 * 
 * This file contains helper functions used throughout the application
 * for asset loading, input validation, sanitization, and UI components.
 */

/**
 * Load a single asset (CSS or JS file)
 * 
 * @param string $type Asset type: 'css', 'js', 'node_css', 'node_js'
 * @param string $path Path to the asset file
 * @return string HTML tag for the asset
 */
function loadAsset($type, $path)
{
    $basePaths = [
        'css' => '/assets/css/',
        'js' => '/assets/js/',
        'vendor_css' => '/assets/vendor/',
        'vendor_js' => '/assets/vendor/',
    ];

    switch ($type) {
        case 'css':
        case 'vendor_css':
            return "<link rel='stylesheet' href='{$basePaths[$type]}$path'>\n";
        case 'js':
        case 'vendor_js':
            return "<script src='{$basePaths[$type]}$path'></script>\n";
        default:
            return '';
    }
}

/**
 * Load multiple assets at once
 * 
 * @param array $assets Associative array where keys are asset types and values are arrays of file paths
 */
function loadAssets(array $assets)
{
    foreach ($assets as $type => $files) {
        foreach ($files as $file) {
            echo loadAsset($type, $file);
        }
    }
}

/* ---- Grouped Asset Loaders ---- */

/**
 * Load all CSS stylesheets required by the application
 * Includes custom styles and third-party library styles
 */
function loadAllStyles()
{
    loadAssets([
        'vendor_css' => [
            'jquery-ui/jquery-ui.css',
            'datatables/dataTables.jqueryui.css',
        ],
        'css' => ['style.css'],
    ]);
}

/**
 * Load all JavaScript files required by the application
 * Includes jQuery, jQuery UI, DataTables, and custom scripts
 */
function loadAllScripts()
{
    loadAssets([
        'vendor_js' => [
            'jquery/jquery.js',
            'jquery-ui/jquery-ui.js',
            'datatables/dataTables.js',
            'chartjs/chart.umd.min.js',
        ],
        'js' => ['tailwindcss.js', 'app.js', 'theme.js'],
    ]);
}

/**
 * Load all assets (CSS and JS) in one call
 * Convenience function for pages that need everything
 */
function loadAllAssets()
{
    loadAllStyles();
    loadAllScripts();
}


/**
 * Display an alert message using jQuery UI dialog
 * Automatically sanitizes output to prevent XSS attacks
 * 
 * @param string $message The message to display
 * @param string $title The dialog title (default: "Alert")
 * @return string HTML and JavaScript for the dialog
 */
function AlertMessage($message, $title = "Alert")
{
    // Sanitize input to prevent XSS attacks
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $dialogId = 'dialog_' . uniqid();

    return "<div id='$dialogId' title='$safeTitle' style='display:none;'>
                <div class='p-4'>
                    <p class='text-gray-700'>$safeMessage</p>
                </div>
            </div>
            <script>
                $(function() {
                    $('#$dialogId').dialog({
                        modal: true,
                        width: 500,
                        resizable: false,
                        classes: {
                            'ui-dialog': 'rounded-lg shadow-lg',
                            'ui-dialog-titlebar': 'bg-green-600 text-white rounded-t-lg',
                            'ui-dialog-title': 'font-semibold',
                            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                        },
                        buttons: {
                            Ok: function() {
                                $(this).dialog('close');
                                $(this).remove();
                            }
                        },
                            open: function() {
                            $('.ui-dialog-buttonpane button').addClass('bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded');
                        }
                    });
                });
            </script>";
}

/**
 * Display a dialog message using jQuery UI dialog
 * Similar to AlertMessage but with different default title
 * Automatically sanitizes output to prevent XSS attacks
 * 
 * @param string $message The message to display
 * @param string $title The dialog title (default: "Message")
 * @return string HTML and JavaScript for the dialog
 */
function DialogMessage($message, $title = "Message")
{
    // Sanitize input to prevent XSS attacks
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $dialogId = 'dialog_' . uniqid();

    return "<div id='$dialogId' title='$safeTitle' style='display:none;'>
                <div class='p-4'>
                    <p class='text-gray-700'>$safeMessage</p>
                </div>
            </div>
            <script>
                $(function() {
                    $('#$dialogId').dialog({
                        modal: true,
                        width: 500,
                        resizable: false,
                        classes: {
                            'ui-dialog': 'rounded-lg shadow-lg',
                            'ui-dialog-titlebar': 'bg-green-600 text-white rounded-t-lg',
                            'ui-dialog-title': 'font-semibold',
                            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                        },
                        buttons: {
                            Ok: function() {
                                $(this).dialog('close');
                                $(this).remove();
                            }
                        },
                            open: function() {
                    $('.ui-dialog-buttonpane button').addClass('bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded');
                }
                    });
                });
            </script>";
}

/**
 * Calculate age from birthdate
 * 
 * @param string $birthdate Birthdate in YYYY-MM-DD format
 * @return int Age in years
 */
function AutoComputeAge($birthdate)
{
    // Validate input format
    if (empty($birthdate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        return 0; // Return 0 for invalid dates
    }

    try {
        $birthDateObj = new DateTime($birthdate);
        $today = new DateTime();
        return $birthDateObj->diff($today)->y;
    } catch (Exception $e) {
        // Log error for debugging
        error_log("Error calculating age for birthdate: $birthdate - " . $e->getMessage());
        return 0;
    }
}

/**
 * Generate JavaScript function for dialog that reloads page after closing
 * Used for operations that require a page refresh to show updated data
 * 
 * @return string JavaScript code for showDialogReload function
 */
function showDialogReloadScript()
{
    return "<script>
        /**
         * Show a dialog message and reload the page when closed
         * Useful for operations that modify data and need a refresh
         * 
         * @param {string} title Dialog title
         * @param {string} message Dialog message
         */
        function showDialogReload(title, message) {
            const dialogId = 'dialog_' + Date.now();
            // Escape quotes to prevent XSS
            const safeTitle = title.replace(/\"/g, '&quot;').replace(/'/g, '&#039;');
            const safeMessage = message.replace(/\"/g, '&quot;').replace(/'/g, '&#039;');
            const dialog = $('<div id=\"' + dialogId + '\" title=\"' + safeTitle + '\" style=\"display:none;\"><div class=\"p-4\"><p class=\"text-gray-700\">' + safeMessage + '</p></div></div>');
            $('body').append(dialog);
            $('#' + dialogId).dialog({
                modal: true,
                width: 500,
                resizable: false,
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                    'ui-dialog-title': 'font-semibold',
                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                },
                buttons: {
                    Ok: function() {
                        $(this).dialog('close');
                        $(this).remove();
                        location.reload();
                    }
                },
                open: function() {
                    $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded');
                }
            });
        }
    </script>";
}


/**
 * Input Validation and Sanitization Functions
 * These functions help prevent security vulnerabilities and ensure data integrity
 */

/**
 * Sanitize string input - removes whitespace and escapes special characters
 * 
 * @param string|null $input The input string to sanitize
 * @param bool $allowEmpty Whether to allow empty strings (default: true)
 * @return string|null Sanitized string or null if empty and not allowed
 */
function sanitizeString($input, $allowEmpty = true)
{
    if ($input === null) {
        return $allowEmpty ? '' : null;
    }

    $sanitized = trim($input);
    return $allowEmpty || !empty($sanitized) ? $sanitized : null;
}

/**
 * Validate and sanitize email address
 * 
 * @param string $email Email address to validate
 * @return string|false Validated email or false if invalid
 */
function validateEmail($email)
{
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Validate Philippine mobile number format (09XXXXXXXXX)
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhilippinePhone($phone)
{
    $phone = trim($phone);
    // Philippine mobile format: 09XXXXXXXXX (11 digits starting with 09)
    return preg_match('/^09\d{9}$/', $phone) === 1;
}

/**
 * Validate date format (YYYY-MM-DD)
 * 
 * @param string $date Date string to validate
 * @return bool True if valid date format, false otherwise
 */
function validateDateFormat($date)
{
    $date = trim($date);
    if (empty($date)) {
        return false;
    }

    // Check format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    // Validate actual date
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Sanitize integer input
 * 
 * @param mixed $input Input to convert to integer
 * @param int|null $min Minimum allowed value (null = no minimum)
 * @param int|null $max Maximum allowed value (null = no maximum)
 * @return int|null Validated integer or null if invalid
 */
function sanitizeInt($input, $min = null, $max = null)
{
    $int = filter_var($input, FILTER_VALIDATE_INT);

    if ($int === false) {
        return null;
    }

    if ($min !== null && $int < $min) {
        return null;
    }

    if ($max !== null && $int > $max) {
        return null;
    }

    return $int;
}

/**
 * CSRF Protection Functions
 * These functions help prevent Cross-Site Request Forgery attacks
 */

/**
 * Generate CSRF token and store in session
 * 
 * @return string CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token (generate if doesn't exist)
 * 
 * @return string CSRF token
 */
function getCSRFToken()
{
    return generateCSRFToken();
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Use hash_equals for timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token HTML input field
 * 
 * @return string HTML input field with CSRF token
 */
function csrfTokenField()
{
    $token = getCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate uploaded file security
 * 
 * @param array $file $_FILES array element
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @param array $allowedExtensions Allowed file extensions (lowercase)
 * @return array ['valid' => bool, 'error' => string|null, 'safe_filename' => string|null]
 */
function validateUploadedFile($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 2097152, $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'])
{
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Invalid file upload.', 'safe_filename' => null];
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File size exceeds maximum allowed size.', 'safe_filename' => null];
    }

    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions), 'safe_filename' => null];
    }

    // Validate MIME type using finfo (more secure than $_FILES['type'])
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file MIME type.', 'safe_filename' => null];
    }

    // Additional validation: Check file content for images
    if (strpos($mimeType, 'image/') === 0) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'File is not a valid image.', 'safe_filename' => null];
        }
    }

    // Generate safe filename (prevent path traversal and special characters)
    $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $safeFilename = substr($safeFilename, 0, 100); // Limit length
    $safeFilename = $safeFilename . '_' . time() . '.' . $extension;

    return ['valid' => true, 'error' => null, 'safe_filename' => $safeFilename];
}

<?php
/**
 * Helper Functions
 * MIS Barangay - Utility Functions
 * 
 * This file contains various helper functions used throughout the application.
 */

/**
 * Load an asset (CSS or JS file)
 * 
 * @param string $type Asset type (css, js, node_css, node_js)
 * @param string $path Path to the asset file
 * @return string HTML tag for the asset
 */
function loadAsset(string $type, string $path): string
{
    $basePaths = [
        'css' => '/assets/css/',
        'js' => '/assets/js/',
        'node_css' => '/node_modules/',
        'node_js' => '/node_modules/',
    ];

    switch ($type) {
        case 'css':
        case 'node_css':
            return "<link rel='stylesheet' href='{$basePaths[$type]}$path'>\n";
        case 'js':
        case 'node_js':
            return "<script src='{$basePaths[$type]}$path'></script>\n";
        default:
            return '';
    }
}

/**
 * Load multiple assets
 * 
 * @param array $assets Associative array of asset types and their files
 * @return void
 */
function loadAssets(array $assets): void
{
    foreach ($assets as $type => $files) {
        foreach ($files as $file) {
            echo loadAsset($type, $file);
        }
    }
}

/* ---- Grouped Loaders ---- */

/**
 * Load all CSS stylesheets
 * @return void
 */
function loadAllStyles(): void
{
    // Load Bootstrap (CDN) + local custom styles
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'>\n";
    loadAssets([
        'css' => ['input.css', 'style.css', 'tooltips.css'],
        'node_css' => [
            'datatables.net-jqui/css/dataTables.jqueryui.css',
            'jquery-ui/dist/themes/flick/jquery-ui.css',
        ],
    ]);
}

/**
 * Load all JavaScript files
 * @return void
 */
function loadAllScripts(): void
{
    // Load core JS libraries (jQuery, jQuery UI, DataTables)
    loadAssets([
        'node_js' => [
            'jquery/dist/jquery.js',
            'jquery-ui/dist/jquery-ui.js',
            'datatables.net/js/dataTables.js',
        ],
        'js' => ['app.js'],
    ]);

    // Load Bootstrap bundle (CDN) after local scripts
    echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>\n";
}

/**
 * Load all CSS and JavaScript assets
 * @return void
 */
function loadAllAssets(): void
{
    loadAllStyles();
    loadAllScripts();
}


/**
 * Generate jQuery UI dialog HTML and JavaScript
 * 
 * @param string $message Dialog message to display
 * @param string $title Dialog title
 * @param bool $reloadOnClose Whether to reload page on close
 * @return string HTML and JavaScript for the dialog
 */
function generateDialog(string $message, string $title, bool $reloadOnClose = false): string
{
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $dialogId = 'dialog_' . uniqid();
    $reloadScript = $reloadOnClose ? "\n                                location.reload();" : '';
    
    return "<div id='$dialogId' title='$safeTitle' style='display:none;'>
                <div class='p-4'>
                    <p class='text-body'>$safeMessage</p>
                </div>
            </div>
            <script>
                $(function() {
                    $('#$dialogId').dialog({
                        modal: true,
                        width: 500,
                        resizable: false,
                        classes: {
                            'ui-dialog': 'rounded shadow-lg',
                            'ui-dialog-titlebar': 'dialog-titlebar-primary rounded-top',
                            'ui-dialog-title': 'fw-semibold',
                            'ui-dialog-buttonpane': 'dialog-buttonpane-light rounded-bottom'
                        },
                        buttons: {
                            Ok: function() {
                                $(this).dialog('close');
                                $(this).remove();$reloadScript
                            }
                        },
                        open: function() {
                            $('.ui-dialog-buttonpane button').addClass('btn btn-primary');
                        }
                    });
                });
            </script>";
}

/**
 * Display an alert message using jQuery UI dialog
 * 
 * @param string $message Alert message to display
 * @param string $title Dialog title (default: "Alert")
 * @return string HTML and JavaScript for the alert dialog
 */
function AlertMessage(string $message, string $title = "Alert"): string
{
    return generateDialog($message, $title, false);
}

/**
 * Display a dialog message using jQuery UI dialog
 * 
 * @param string $message Dialog message to display
 * @param string $title Dialog title (default: "Message")
 * @return string HTML and JavaScript for the dialog
 */
function DialogMessage(string $message, string $title = "Message"): string
{
    return generateDialog($message, $title, false);
}

/**
 * Calculate age from birthdate
 * 
 * @param string $birthdate Birthdate in YYYY-MM-DD format
 * @return int Age in years
 */
function AutoComputeAge(string $birthdate): int
{
    $birthDateObj = new DateTime($birthdate);
    $today = new DateTime();
    return $birthDateObj->diff($today)->y;
}

/**
 * Generate JavaScript function for dialog with page reload
 * 
 * @return string JavaScript code for dialog with reload functionality
 */
function showDialogReloadScript(): string
{
    return '<script>
        function showDialogReload(title, message) {
            const dialogId = "dialog_" + Date.now();
            const dialog = $("<div id=\"" + dialogId + "\" title=\"" + title.replace(/"/g, """) + "\" style=\"display:none;\"><div class=\"p-4\"><p class=\"text-body\">" + message.replace(/"/g, """) + "</p></div></div>");
            $("body").append(dialog);
            $("#" + dialogId).dialog({
                modal: true,
                width: 500,
                resizable: false,
                classes: {
                    "ui-dialog": "rounded shadow-lg",
                    "ui-dialog-titlebar": "dialog-titlebar-primary rounded-top",
                    "ui-dialog-title": "fw-semibold",
                    "ui-dialog-buttonpane": "dialog-buttonpane-light rounded-bottom"
                },
                buttons: {
                    Ok: function() {
                        $(this).dialog("close");
                        $(this).remove();
                        location.reload();
                    }
                },
                open: function() {
                    $(".ui-dialog-buttonpane button").addClass("btn btn-primary");
                }
            });
        }
    </script>';
}

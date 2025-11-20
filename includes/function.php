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
    loadAssets([
        'css' => ['input.css', 'tooltips.css'],
        'node_css' => [
            'datatables.net-jqui/css/dataTables.jqueryui.css',
            'jquery-ui/dist/themes/flick/jquery-ui.css',
            'bootstrap/dist/css/bootstrap.min.css',
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
            'bootstrap/dist/js/bootstrap.bundle.min.js'
        ],
        'js' => ['app.js'],
    ]);
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
 * Generate Bootstrap modal HTML and JavaScript
 * 
 * @param string $message Modal message to display
 * @param string $title Modal title
 * @param bool $reloadOnClose Whether to reload page on close
 * @return string HTML and JavaScript for the modal
 */
function generateDialog(string $message, string $title, bool $reloadOnClose = false): string
{
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $modalId = 'modal_' . uniqid();
    $reloadScript = $reloadOnClose ? "location.reload();" : '';
    
    return "<div class='modal fade' id='$modalId' tabindex='-1' aria-labelledby='{$modalId}Label' aria-hidden='true'>
                <div class='modal-dialog modal-dialog-centered'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='{$modalId}Label'>$safeTitle</h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                        </div>
                        <div class='modal-body'>
                            <p class='mb-0'>$safeMessage</p>
                        </div>
                        <div class='modal-footer'>
                            <button type='button' class='btn btn-primary' data-bs-dismiss='modal'>Ok</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                $(function() {
                    const modal = new bootstrap.Modal(document.getElementById('$modalId'));
                    modal.show();
                    $('#$modalId').on('hidden.bs.modal', function() {
                        $(this).remove();
                        $reloadScript
                    });
                });
            </script>";
}

/**
 * Display an alert message using Bootstrap modal
 * 
 * @param string $message Alert message to display
 * @param string $title Modal title (default: "Alert")
 * @return string HTML and JavaScript for the alert modal
 */
function AlertMessage(string $message, string $title = "Alert"): string
{
    return generateDialog($message, $title, false);
}

/**
 * Display a modal message using Bootstrap modal
 * 
 * @param string $message Modal message to display
 * @param string $title Modal title (default: "Message")
 * @return string HTML and JavaScript for the modal
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
 * Generate JavaScript function for Bootstrap modal with page reload
 * 
 * @return string JavaScript code for modal with reload functionality
 */
function showDialogReloadScript(): string
{
    return '<script>
        function showDialogReload(title, message) {
            const modalId = "modal_" + Date.now();
            const safeTitle = title.replace(/"/g, "&quot;").replace(/\'/g, "&#039;");
            const safeMessage = message.replace(/"/g, "&quot;").replace(/\'/g, "&#039;");
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">${safeTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0">${safeMessage}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Ok</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $("body").append(modalHtml);
            const modalElement = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            $(modalElement).on("hidden.bs.modal", function() {
                $(this).remove();
                location.reload();
            });
        }
    </script>';
}

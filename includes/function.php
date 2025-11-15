<?php
function loadAsset($type, $path)
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

function loadAssets(array $assets)
{
    foreach ($assets as $type => $files) {
        foreach ($files as $file) {
            echo loadAsset($type, $file);
        }
    }
}

/* ---- Grouped Loaders ---- */

function loadAllStyles()
{
    loadAssets([
        'css' => ['style.css', 'tooltips.css'],
        'node_css' => [
            'datatables.net-jqui/css/dataTables.jqueryui.css',
            'jquery-ui/dist/themes/base/jquery-ui.css',
        ],
    ]);
}

function loadAllScripts()
{
    loadAssets([
        'node_js' => [
            'jquery/dist/jquery.js',
            'jquery-ui/dist/jquery-ui.js',
            'datatables.net/js/dataTables.js',
        ],
        'js' => ['tailwindcss.js', 'app.js'],
    ]);
}

function loadAllAssets()
{
    loadAllStyles();
    loadAllScripts();
}


// Modernized alert message using jQuery UI dialog
function AlertMessage($message, $title = "Alert")
{
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
                            'ui-dialog-titlebar': 'bg-blue-600 text-white rounded-t-lg',
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
                            $('.ui-dialog-buttonpane button').addClass('bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded');
                        }
                    });
                });
            </script>";
}

// Modernized dialog message using jQuery UI dialog
function DialogMessage($message, $title = "Message")
{
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
                            'ui-dialog-titlebar': 'bg-blue-600 text-white rounded-t-lg',
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
                            $('.ui-dialog-buttonpane button').addClass('bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded');
                        }
                    });
                });
            </script>";
}

// --- Auto-compute age ---
function AutoComputeAge($birthdate)
{
  $birthDateObj = new DateTime($birthdate);
  $today = new DateTime();
  return $birthDateObj->diff($today)->y;
}

// JavaScript function for dialog with reload
function showDialogReloadScript()
{
    return "<script>
        function showDialogReload(title, message) {
            const dialogId = 'dialog_' + Date.now();
            const dialog = $('<div id=\"' + dialogId + '\" title=\"' + title.replace(/\"/g, '&quot;') + '\" style=\"display:none;\"><div class=\"p-4\"><p class=\"text-gray-700\">' + message.replace(/\"/g, '&quot;') + '</p></div></div>');
            $('body').append(dialog);
            $('#' + dialogId).dialog({
                modal: true,
                width: 500,
                resizable: false,
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-titlebar': 'bg-blue-600 text-white rounded-t-lg',
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
                    $('.ui-dialog-buttonpane button').addClass('bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded');
                }
            });
        }
    </script>";
}
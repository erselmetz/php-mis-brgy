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
        'css' => ['style.css'],
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


// create alert message using jquery ui dialog make it complete and functional
function AlertMessage($message, $title = "Alert")
{
    return "<div id='dialog' title='$title' style='display:none;'>
                <p>$message</p>
            </div>
            <script>
                $( function() {
                    $( '#dialog' ).dialog({
                        modal: true,
                        width: 500,
                        buttons: {
                            Ok: function() {
                                $( this ).dialog( 'close' );
                            }
                        }
                    });
                } );
            </script>";
}
function DialogMessage($message, $title = "Message")
{
    return "<div id='dialog' title='$title' style='display:none;'>
                <p>$message</p>
            </div>
            <script>
                $( function() {
                    $( '#dialog' ).dialog({
                        modal: true,
                        width: 500,
                        buttons: {
                            Ok: function() {
                                $( this ).dialog( 'close' );
                            }
                        }
                    });
                } );
            </script>";
}

// --- Auto-compute age ---
function AutoComputeAge($birthdate)
{
  $birthDateObj = new DateTime($birthdate);
  $today = new DateTime();
  return $birthDateObj->diff($today)->y;
}
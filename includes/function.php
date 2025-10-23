<?php

function loadAssets($type, $path)
{
    if ($type == 'css') {
        return "<link rel='stylesheet' href='/assets/css/$path'>";
    } elseif ($type == 'js') {
        return "<script src='/assets/js/$path'></script>";
    }  elseif ($type == 'node_modules_js') {
        return "<script src='/node_modules/$path'></script>";
    } elseif ($type == 'node_modules_css') {
        return "<link rel='stylesheet' href='/node_modules/$path'>";
    }
}  

function addtailwindcss()
{
    return loadAssets('css', 'style.css');
}

function addjqueryjs()
{
    return loadAssets('node_modules_js', 'jquery/dist/jquery.js');
}

function addjqueryuijs()
{
    return loadAssets('node_modules_js', 'jquery-ui/dist/jquery-ui.js');
}

function addjqueruicss()
{
    return loadAssets('node_modules_css', 'jquery-ui/dist/themes/base/jquery-ui.css');
}

function addDataTablejs()
{
    return loadAssets('node_modules_js', 'datatables.net/js/dataTables.js');
}

function adddatatablecss()
{
    return loadAssets('node_modules_css', 'datatables.net-jqui/css/datatables.jqueryui.css');
}

function loadAllStyles(){
    echo addtailwindcss();
    echo adddatatablecss();
    echo addjqueruicss();
}

function loadAllScripts(){
    echo addjqueryjs();
    echo addjqueryuijs();
    echo addDataTablejs();
}

function loadAllAssets(){
    echo addtailwindcss();
    echo adddatatablecss();
    echo addjqueruicss();
    echo addjqueryjs();
    echo addjqueryuijs();
    echo addDataTablejs();
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
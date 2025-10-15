<?php

function loadAssets($type, $path)
{
    if ($type == 'css') {
        return "<link rel='stylesheet' href='/assets/css/$path'>";
    } elseif ($type == 'js') {
        return "<script src='/assets/js/$path'></script>";
    }
}  

function addtailwindcss()
{
    return loadAssets('css', 'style.css');
}

function addjqueryjs()
{
    return loadAssets('js', 'jquery-3.7.1.min.js');
}

function addjqueryuijs()
{
    return loadAssets('js', 'jquery-ui-1.14.1/jquery-ui.min.js');
}

function addjqueruicss()
{
    return loadAssets('css', 'jquery-ui-1.14.1/jquery-ui.min.css');
}

function adddatatablejs()
{
    return loadAssets('js', 'DataTables/datatables.min.js');
}

function adddatatablecss()
{
    return loadAssets('css', 'DataTables/datatables.min.css');
}

function loadAllAssets(){
    echo addtailwindcss();
    echo adddatatablecss();
    echo addjqueruicss();
    echo addjqueryjs();
    echo addjqueryuijs();
    echo adddatatablejs();
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
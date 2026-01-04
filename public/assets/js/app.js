/**
 * Application JavaScript Utilities
 * 
 * Provides reusable dialog functions for user notifications.
 * Uses jQuery UI for consistent dialog styling across the application.
 */

/**
 * Display a modal dialog with a message
 * Dialog closes when user clicks OK (no page reload)
 * 
 * @param {string} title - Dialog title
 * @param {string} message - Dialog message content
 */
function showDialog(title, message) {
    // Set dialog title and content
    $("#dialog-message").attr("title", title);
    // Use text() instead of html() to prevent XSS if message contains HTML
    $("#dialog-text").text(message);

    // Initialize jQuery UI dialog
    $("#dialog-message").dialog({
        modal: true,
        width: 400,
        buttons: {
            Ok: function () {
                $(this).dialog("close");
            }
        }
    });
}

/**
 * Display a modal dialog with a message and reload page on close
 * Useful for operations that modify data and need a refresh to show changes
 * 
 * @param {string} title - Dialog title
 * @param {string} message - Dialog message content
 */
function showDialogReload(title, message) {
    // Set dialog title and content
    $("#dialog-message").attr("title", title);
    // Use text() instead of html() to prevent XSS if message contains HTML
    $("#dialog-text").text(message);

    // Initialize jQuery UI dialog with reload on close
    $("#dialog-message").dialog({
        modal: true,
        width: 400,
        buttons: {
            Ok: function () {
                $(this).dialog("close");
                // Reload page to show updated data
                location.reload();
            }
        }
    });
}

// Helper function to show message dialogs
function showMessage(title, message, isError = false) {
    const $dialog = $('<div class="p-4">' + message + '</div>');
    $dialog.dialog({
        modal: true,
        title: title,
        width: 420,
        resizable: false,
        buttons: {
            Ok: function() {
                $(this).dialog('close').remove();
            }
        },
        classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-titlebar': (isError ? 'bg-red-600' : 'bg-theme-primary') + ' text-white rounded-t-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
        },
        open: function() {
            $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded');
        }
    });
}

// Helper function to show confirmation dialogs
function showConfirm(title, message, onConfirm) {
    const $dialog = $('<div class="p-4">' + message + '</div>');
    $dialog.dialog({
        modal: true,
        title: title,
        width: 420,
        resizable: false,
        buttons: {
            'Yes': function() {
                $(this).dialog('close').remove();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            },
            'Cancel': function() {
                $(this).dialog('close').remove();
            }
        },
        classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-titlebar': 'bg-yellow-600 text-white rounded-t-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
        },
        open: function() {
            $('.ui-dialog-buttonpane button').each(function() {
                if ($(this).text() === 'Yes') {
                    $(this).addClass('bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded');
                } else {
                    $(this).addClass('bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded');
                }
            });
        }
    });
}
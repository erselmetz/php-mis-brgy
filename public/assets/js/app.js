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
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

// force disable autocomplete on all inputs
function disableAutocomplete() {
    $('input[type="text"], input[type="email"], input[type="password"], input[type="search"], input[type="tel"], input[type="url"], textarea, select').each(function () {
        $(this).attr('autocomplete', 'off');
    });
}

disableAutocomplete();

// run on page load
$(function () {
    $('body').show();
    disableAutocomplete();
    // Also run when modals are opened (for dynamic content)
    $(document).on('dialogopen', function () {
        setTimeout(disableAutocomplete, 100);
    });

    // Add global footer
    add_global_footer();

    // Initialize jQuery UI dialog container
    $("body").append(`
        <div id="dialog-message" style="display:none;">
            <p id="dialog-text"></p>
        </div>
    `);


});

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
    const $dialog = $('<div id="message-dialog" title="' + title + '" style="display: none; ">' +
        '<div class="p-4">' +
        '<p class="text-gray-700">' + message + '</p>' +
        '</div>' +
        '</div>');
    $dialog.dialog({
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
            Ok: function () {
                $(this).dialog('close');
                $(this).remove();
            }
        },
        open: function () {
            $('.ui-dialog-buttonpane button').addClass('bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded');
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
            'Yes': function () {
                $(this).dialog('close').remove();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            },
            'Cancel': function () {
                $(this).dialog('close').remove();
            }
        },
        classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-titlebar': 'bg-yellow-600 text-white rounded-t-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
        },
        open: function () {
            $('.ui-dialog-buttonpane button').each(function () {
                if ($(this).text() === 'Yes') {
                    $(this).addClass('bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded');
                } else {
                    $(this).addClass('bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded');
                }
            });
        }
    });
}

function add_global_footer() {
    // Avoid adding twice
    if ($("#mis-global-footer").length) return;

    const year = new Date().getFullYear();

    // Create footer using jQuery
    const $footerWrapper = $(`
            <div id="mis-global-footer">
                <footer class="w-full text-center mt-10 mb-4 text-gray-500 text-sm 
                               opacity-0 transition-all duration-500 ease-out">
                    <div class="inline-block px-4 py-2 bg-white rounded-lg shadow-sm border">
                        &copy; ${year} MIS Barangay. All rights reserved.
                    </div>
                </footer>
            </div>
        `);

    // Append to <main>
    const $main = $("main");

    if ($main.length) {
        $main.append($footerWrapper);

        // Fade-in after slight delay
        setTimeout(() => {
            $footerWrapper.find("footer").removeClass("opacity-0");
        }, 50);
    }

    // Inject animation CSS (only once)
    const style = `
            #mis-global-footer footer {
                transform: translateY(10px);
            }
            #mis-global-footer footer:not(.opacity-0) {
                transform: translateY(0);
            }
        `;
    $("<style></style>").text(style).appendTo("head");
}
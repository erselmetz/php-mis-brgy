$(function () {
    $('body').show();
    $('#blotterTable').DataTable({
        order: [
            [0, 'desc']
        ],
        pageLength: 25
    });

    $("#archivedBlotterDialog").dialog({
        autoOpen: false,
        modal: true,
        width: 900,
        height: 600,
        resizable: true,
        classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
        },
        open: function () {
            loadArchivedBlotters();
            $('.ui-dialog-buttonpane button')
                .addClass('bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded');
        }
    });

    // View Blotter Modal
    let currentBlotterId = null;
    $("#viewBlotterModal").dialog({
        autoOpen: false,
        modal: true,
        width: 900,
        height: 700,
        resizable: true,
        classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
        },
        buttons: {
            "Close": function () {
                $(this).dialog("close");
            }
        },
        open: function () {
            const buttons = $('.ui-dialog-buttonpane button');
            buttons.addClass('text-white px-4 py-2 rounded mr-2');

            // Close button - Gray
            buttons.eq(0).addClass('bg-gray-500 hover:bg-gray-600');
        }
    });

    $("#archivedBlotterDialogBtn").on("click", function () {
        $("#archivedBlotterDialog").dialog("open");
    });

    // History Dialog
    $("#historyBlotterDialog").dialog({
        autoOpen: false,
        modal: true,
        width: 1000,
        height: 600,
        resizable: true,
        classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
        },
        open: function () {
            loadHistoryBlotters();
            $('.ui-dialog-buttonpane button')
                .addClass('bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded');
        }
    });

    $("#historyBlotterDialogBtn").on("click", function () {
        $("#historyBlotterDialog").dialog("open");
    });

    // Search history
    let historySearchTimeout;
    $("#historySearchInput").on("input", function () {
        clearTimeout(historySearchTimeout);
        const searchTerm = $(this).val();
        historySearchTimeout = setTimeout(() => {
            loadHistoryBlotters(searchTerm);
        }, 300);
    });

    // Search archived blotters
    let searchTimeout;
    $("#archiveSearchInput").on("input", function () {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        searchTimeout = setTimeout(() => {
            loadArchivedBlotters(searchTerm);
        }, 300);
    });

    // Handle click on case number or view link
    $(document).on("click", ".view-blotter-btn", function () {
        const blotterId = $(this).data('id');
        if (blotterId) {
            loadBlotterData(blotterId);
        }
    });

    // Load blotter data via AJAX
    function loadBlotterData(id) {
        currentBlotterId = id;
        $.getJSON(`get_blotter.php?id=${id}`, function (data) {
            if (data.error) {
                $('<div>' + data.error + '</div>').dialog({
                    modal: true,
                    title: 'Error',
                    width: 420,
                    buttons: {
                        Ok: function () {
                            $(this).dialog('close');
                        }
                    },
                    classes: {
                        'ui-dialog': 'rounded-lg shadow-lg',
                        'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                        'ui-dialog-title': 'font-semibold',
                        'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                    }
                });
                return;
            }

            // Populate form fields
            $('#blotter_id').val(data.id);
            $('#case_number_display').text(data.case_number);
            $('#status').val(data.status);
            $('#created_by_display').text(data.created_by_name || 'N/A');
            $('#created_at_display').text(formatDateTime(data.created_at));

            $('#incident_date').val(data.incident_date);
            $('#incident_time').val(data.incident_time || '');
            $('#incident_location').val(data.incident_location);
            $('#incident_description').val(data.incident_description);

            $('#complainant_name').val(data.complainant_name);
            $('#complainant_address').val(data.complainant_address || '');
            $('#complainant_contact').val(data.complainant_contact || '');

            $('#respondent_name').val(data.respondent_name);
            $('#respondent_address').val(data.respondent_address || '');
            $('#respondent_contact').val(data.respondent_contact || '');

            $('#resolved_date').val(data.resolved_date || '');
            $('#resolution').val(data.resolution || '');

            // Open the dialog
            $("#viewBlotterModal").dialog("open");
        }).fail(function () {
            $('<div>Failed to load blotter data.</div>').dialog({
                modal: true,
                title: 'Error',
                width: 420,
                buttons: {
                    Ok: function () {
                        $(this).dialog('close');
                    }
                },
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                    'ui-dialog-title': 'font-semibold',
                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                }
            });
        });
    }

    // Load archived blotters
    function loadArchivedBlotters(search = '') {
        const url = search ? `archive_api.php?search=${encodeURIComponent(search)}` : 'archive_api.php';

        $.getJSON(url, function (data) {
            if (!data.success) {
                $('#archivedBlotterTableBody').html('<tr><td colspan="4" class="p-4 text-center text-gray-500">Error loading archived cases.</td></tr>');
                $('#archivedBlotterFooter').text('Error loading data');
                return;
            }

            const tbody = $('#archivedBlotterTableBody');
            tbody.empty();

            if (data.blotters.length === 0) {
                tbody.html('<tr><td colspan="4" class="p-4 text-center text-gray-500">No archived cases found.</td></tr>');
            } else {
                data.blotters.forEach(function (blotter) {
                    const row = `
                        <tr>
                            <td class="p-2 font-semibold">${blotter.case_number}</td>
                            <td class="p-2">
                                ${blotter.parties}
                                <div class="text-xs text-gray-500">${blotter.incident}</div>
                            </td>
                            <td class="p-2">${blotter.archived_date}</td>
                            <td class="p-2 text-center">
                                <button class="restore-blotter-btn bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm" data-id="${blotter.id}">Restore</button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            // Update footer
            const showing = data.blotters.length;
            const total = data.total;
            $('#archivedBlotterFooter').text(`Showing ${showing} of ${total} archived records`);
        }).fail(function () {
            $('#archivedBlotterTableBody').html('<tr><td colspan="4" class="p-4 text-center text-red-500">Failed to load archived cases.</td></tr>');
            $('#archivedBlotterFooter').text('Error loading data');
        });
    }

    // Handle restore button click
    $(document).on("click", ".restore-blotter-btn", function () {
        const blotterId = $(this).data('id');
        if (!blotterId) return;

        $('<div>Are you sure you want to restore this case?</div>').dialog({
            modal: true,
            title: 'Restore Case',
            width: 420,
            buttons: {
                "Yes": function () {
                    $(this).dialog('close');

                    $.ajax({
                        url: 'archive_api.php',
                        type: 'POST',
                        data: {
                            action: 'restore',
                            blotter_id: blotterId
                        },
                        dataType: 'json',
                        success: function (res) {
                            $('<div>' + res.message + '</div>').dialog({
                                modal: true,
                                title: res.success ? 'Success' : 'Error',
                                width: 420,
                                buttons: {
                                    Ok: function () {
                                        $(this).dialog('close');
                                        if (res.success) {
                                            loadArchivedBlotters($("#archiveSearchInput").val());
                                            // Reload main page after a short delay
                                            setTimeout(() => {
                                                location.reload();
                                            }, 500);
                                        }
                                    }
                                },
                                classes: {
                                    'ui-dialog': 'rounded-lg shadow-lg',
                                    'ui-dialog-titlebar': res.success ? 'bg-green-500 text-white rounded-t-lg' : 'bg-red-500 text-white rounded-t-lg',
                                    'ui-dialog-title': 'font-semibold',
                                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                                }
                            });
                        },
                        error: function () {
                            $('<div>Failed to connect to server.</div>').dialog({
                                modal: true,
                                title: 'Error',
                                width: 420,
                                buttons: {
                                    Ok: function () {
                                        $(this).dialog('close');
                                    }
                                },
                                classes: {
                                    'ui-dialog': 'rounded-lg shadow-lg',
                                    'ui-dialog-titlebar': 'bg-red-500 text-white rounded-t-lg',
                                    'ui-dialog-title': 'font-semibold',
                                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                                }
                            });
                        }
                    });
                },
                "No": function () {
                    $(this).dialog('close');
                }
            },
            classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
            }
        });
    });

    // Load history blotters
    function loadHistoryBlotters(search = '') {
        let url = 'history_api.php';
        if (search) {
            url += `?case_number=${encodeURIComponent(search)}`;
        }

        $.getJSON(url, function (data) {
            if (!data.success) {
                $('#historyBlotterTableBody').html('<tr><td colspan="5" class="p-4 text-center text-gray-500">Error loading history.</td></tr>');
                $('#historyBlotterFooter').text('Error loading data');
                return;
            }

            const tbody = $('#historyBlotterTableBody');
            tbody.empty();

            if (data.history.length === 0) {
                tbody.html('<tr><td colspan="5" class="p-4 text-center text-gray-500">No history found.</td></tr>');
            } else {
                data.history.forEach(function (entry) {
                    let statusChange = '';
                    if (entry.old_status && entry.new_status) {
                        statusChange = `
                            <span class="px-2 py-1 rounded text-xs font-semibold ${entry.old_status_color}">${entry.old_status_display}</span>
                            <span class="mx-2">→</span>
                            <span class="px-2 py-1 rounded text-xs font-semibold ${entry.new_status_color}">${entry.new_status_display}</span>
                        `;
                    } else if (entry.new_status) {
                        statusChange = `<span class="px-2 py-1 rounded text-xs font-semibold ${entry.new_status_color}">${entry.new_status_display}</span>`;
                    } else {
                        statusChange = '<span class="text-gray-400">—</span>';
                    }

                    const actionTypeLabels = {
                        'status_changed': 'Status Changed',
                        'updated': 'Updated',
                        'created': 'Created',
                        'archived': 'Archived',
                        'restored': 'Restored'
                    };

                    const row = `
                        <tr>
                            <td class="p-2 font-semibold">${entry.case_number}</td>
                            <td class="p-2">${actionTypeLabels[entry.action_type] || entry.action_type}</td>
                            <td class="p-2">${statusChange}</td>
                            <td class="p-2">${entry.user_name || 'Unknown'}</td>
                            <td class="p-2">${formatDateTime(entry.created_at)}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            // Update footer
            const total = data.total;
            $('#historyBlotterFooter').text(`Showing ${data.history.length} of ${total} history records`);
        }).fail(function () {
            $('#historyBlotterTableBody').html('<tr><td colspan="5" class="p-4 text-center text-red-500">Failed to load history.</td></tr>');
            $('#historyBlotterFooter').text('Error loading data');
        });
    }

    // Format datetime helper
    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        const month = months[date.getMonth()];
        const day = date.getDate();
        const year = date.getFullYear();
        let hours = date.getHours();
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return `${month} ${day}, ${year} ${hours}:${minutes} ${ampm}`;
    }
});
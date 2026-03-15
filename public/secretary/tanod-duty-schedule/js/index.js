/**
 * Tanod Duty Schedule - JS Controller
 */
$(function () {
    let dutyTable;

    // ── Init DataTable ──────────────────────────────────────────────────────
    function initTable() {
        dutyTable = $('#dutyTable').DataTable({
            ajax: {
                url: 'actions/duty_api.php?action=list',
                dataSrc: function (json) {
                    if (json.status === 'ok') {
                        updateCounts(json.counts || {});
                        return json.data || [];
                    }
                    return [];
                }
            },
            columns: [
                { data: 'duty_code', title: 'Duty Code' },
                { data: 'tanod_name', title: 'Tanod Name' },
                {
                    data: 'duty_date', title: 'Date',
                    render: function (d) {
                        if (!d) return '—';
                        const dt = new Date(d + 'T00:00:00');
                        return dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
                    }
                },
                {
                    data: 'shift', title: 'Shift',
                    render: function (d) {
                        const map = {
                            morning: '<span class="px-2 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-800 font-medium">☀️ Morning</span>',
                            afternoon: '<span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800 font-medium">🌤️ Afternoon</span>',
                            night: '<span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-800 font-medium">🌙 Night</span>'
                        };
                        return map[d] || d;
                    }
                },
                { data: 'post_location', title: 'Post / Location', defaultContent: '—' },
                {
                    data: 'status', title: 'Status',
                    render: function (d) {
                        const map = {
                            active: '<span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 font-medium">Active</span>',
                            completed: '<span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700 font-medium">Completed</span>',
                            cancelled: '<span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700 font-medium">Cancelled</span>'
                        };
                        return map[d] || d;
                    }
                },
                { data: 'created_by_name', title: 'Created By', defaultContent: '—' },
                {
                    data: null, title: 'Actions', orderable: false,
                    render: function (d, t, row) {
                        return `<div class="flex gap-1">
                            <button class="view-duty-btn text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200" data-id="${row.id}">View</button>
                            <button class="edit-duty-btn text-xs px-2 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200" data-id="${row.id}">Edit</button>
                            <button class="del-duty-btn text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200" data-id="${row.id}">Delete</button>
                        </div>`;
                    }
                }
            ],
            order: [[2, 'desc']],
            responsive: true,
            pageLength: 25,
            language: { emptyTable: 'No duty schedules found.' }
        });
    }

    function updateCounts(counts) {
        $('#countMorning').text(counts.morning ?? 0);
        $('#countAfternoon').text(counts.afternoon ?? 0);
        $('#countNight').text(counts.night ?? 0);
    }

    function reloadTable() {
        const date  = $('#filterDate').val();
        const shift = $('#filterShift').val();
        const search = $('#searchInput').val();
        let url = 'actions/duty_api.php?action=list';
        if (date)   url += '&filter_date=' + date;
        if (shift)  url += '&filter_shift=' + encodeURIComponent(shift);
        if (search) url += '&search=' + encodeURIComponent(search);
        dutyTable.ajax.url(url).load();
    }

    // ── Filters ─────────────────────────────────────────────────────────────
    $('#filterDate, #filterShift').on('change', reloadTable);
    let searchTimer;
    $('#searchInput').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reloadTable, 400);
    });

    // ── Open Add Dialog ──────────────────────────────────────────────────────
    $('#btnNewDuty').on('click', function () {
        resetForm();
        $('#dutyDialog').dialog('option', 'title', 'Assign Duty Schedule').dialog('open');
    });

    // ── Add/Edit Dialog ──────────────────────────────────────────────────────
    $('#dutyDialog').dialog({
        autoOpen: false,
        modal: true,
        width: 560,
        buttons: {
            'Save': function () { saveDuty(); },
            'Cancel': function () { $(this).dialog('close'); }
        }
    });

    function resetForm() {
        $('#dutyId').val('');
        $('#dutyTanodName').val('');
        $('#dutyDate').val(new Date().toISOString().split('T')[0]);
        $('#dutyShift').val('morning');
        $('#dutyPostLocation').val('');
        $('#dutyNotes').val('');
        $('#dutyStatus').val('active');
    }

    function saveDuty() {
        const id         = $('#dutyId').val();
        const tanodName  = $.trim($('#dutyTanodName').val());
        const dutyDate   = $('#dutyDate').val();
        const shift      = $('#dutyShift').val();
        const post       = $.trim($('#dutyPostLocation').val());
        const notes      = $.trim($('#dutyNotes').val());
        const status     = $('#dutyStatus').val();

        if (!tanodName || !dutyDate) {
            showMsg('Validation Error', 'Tanod Name and Duty Date are required.', true);
            return;
        }

        $.post('actions/duty_api.php?action=save', {
            id, tanod_name: tanodName, duty_date: dutyDate,
            shift, post_location: post, notes, status
        }, function (res) {
            if (res.success) {
                $('#dutyDialog').dialog('close');
                reloadTable();
                showMsg('Success', res.message);
            } else {
                showMsg('Error', res.message, true);
            }
        }, 'json').fail(function () {
            showMsg('Error', 'Request failed. Please try again.', true);
        });
    }

    // ── Table Actions ────────────────────────────────────────────────────────
    $('#dutyTable').on('click', '.view-duty-btn', function () {
        const id = $(this).data('id');
        const row = dutyTable.row($(this).closest('tr')).data();
        if (!row) return;

        const shiftLabels = { morning: '☀️ Morning (6AM–2PM)', afternoon: '🌤️ Afternoon (2PM–10PM)', night: '🌙 Night (10PM–6AM)' };
        const statusBadge = { active: 'bg-green-100 text-green-800', completed: 'bg-gray-100 text-gray-700', cancelled: 'bg-red-100 text-red-700' };
        const dt = new Date(row.duty_date + 'T00:00:00');
        const dateStr = dt.toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        $('#viewDutyContent').html(`
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-500">Duty Code</span>
                    <span class="font-bold text-blue-700">${row.duty_code}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold text-gray-500">Tanod Name</span>
                    <span>${escHtml(row.tanod_name)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold text-gray-500">Date</span>
                    <span>${dateStr}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold text-gray-500">Shift</span>
                    <span>${shiftLabels[row.shift] || row.shift}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold text-gray-500">Post / Location</span>
                    <span>${escHtml(row.post_location || '—')}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold text-gray-500">Status</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge[row.status] || ''}">${capitalize(row.status)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold text-gray-500">Notes</span>
                    <span class="text-right max-w-xs">${escHtml(row.notes || '—')}</span>
                </div>
                <hr>
                <div class="flex justify-between text-xs text-gray-400">
                    <span>Created by: ${escHtml(row.created_by_name || '—')}</span>
                    <span>${row.created_at || ''}</span>
                </div>
                ${row.updated_by_name ? `<div class="text-xs text-gray-400">Updated by: ${escHtml(row.updated_by_name)} on ${row.updated_at || ''}</div>` : ''}
            </div>
        `);
        $('#viewDutyDialog').dialog('option', 'title', 'Duty Details — ' + row.duty_code).dialog('open');
    });

    $('#viewDutyDialog').dialog({ autoOpen: false, modal: true, width: 480, buttons: { 'Close': function () { $(this).dialog('close'); } } });

    $('#dutyTable').on('click', '.edit-duty-btn', function () {
        const row = dutyTable.row($(this).closest('tr')).data();
        if (!row) return;
        $('#dutyId').val(row.id);
        $('#dutyTanodName').val(row.tanod_name);
        $('#dutyDate').val(row.duty_date);
        $('#dutyShift').val(row.shift);
        $('#dutyPostLocation').val(row.post_location || '');
        $('#dutyNotes').val(row.notes || '');
        $('#dutyStatus').val(row.status);
        $('#dutyDialog').dialog('option', 'title', 'Edit Duty — ' + row.duty_code).dialog('open');
    });

    $('#dutyTable').on('click', '.del-duty-btn', function () {
        const id = $(this).data('id');
        $('#deleteDutyId').val(id);
        $('#deleteDutyDialog').dialog('open');
    });

    $('#deleteDutyDialog').dialog({
        autoOpen: false, modal: true, width: 400,
        buttons: {
            'Yes, Delete': function () {
                const id = $('#deleteDutyId').val();
                $.post('actions/duty_api.php?action=delete', { id }, function (res) {
                    $('#deleteDutyDialog').dialog('close');
                    if (res.success) { reloadTable(); showMsg('Deleted', res.message); }
                    else showMsg('Error', res.message, true);
                }, 'json');
            },
            'Cancel': function () { $(this).dialog('close'); }
        }
    });

    // ── Print ────────────────────────────────────────────────────────────────
    $('#btnPrintDuty').on('click', function () {
        const date  = $('#filterDate').val() || '';
        const shift = $('#filterShift').val() || '';
        window.open('print/print_duty.php?filter_date=' + date + '&filter_shift=' + shift, '_blank');
    });

    // ── Helpers ──────────────────────────────────────────────────────────────
    function showMsg(title, msg, isError) {
        const id = 'msg_' + Date.now();
        $('body').append(`<div id="${id}" title="${title}" style="display:none"><p class="${isError ? 'text-red-600' : 'text-green-600'}">${msg}</p></div>`);
        $(`#${id}`).dialog({ modal: true, width: 380, buttons: { OK: function () { $(this).dialog('close').remove(); } } });
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    // ── Boot ─────────────────────────────────────────────────────────────────
    initTable();
    document.body.style.display = '';
});
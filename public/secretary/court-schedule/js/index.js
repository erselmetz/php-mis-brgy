/**
 * Court / Facility Schedule - JS Controller
 */
$(function () {
    let courtTable;

    const facilityLabels = {
        basketball_court: '🏀 Basketball Court',
        multipurpose_area: '🏛️ Multipurpose Area',
        gym: '🏋️ Gym'
    };
    const statusBadge = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        denied: 'bg-red-100 text-red-700',
        completed: 'bg-gray-100 text-gray-700',
        cancelled: 'bg-gray-100 text-gray-500'
    };

    function initTable() {
        courtTable = $('#courtTable').DataTable({
            ajax: {
                url: 'actions/court_api.php?action=list',
                dataSrc: function (json) {
                    if (json.status === 'ok') {
                        updateCounts(json.counts || {});
                        return json.data || [];
                    }
                    return [];
                }
            },
            columns: [
                { data: 'reservation_code', title: 'Code' },
                { data: 'facility', title: 'Facility', render: d => facilityLabels[d] || d },
                { data: 'borrower_name', title: 'Borrower' },
                { data: 'organization', title: 'Organization', defaultContent: '—' },
                {
                    data: 'reservation_date', title: 'Date',
                    render: d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
                },
                {
                    data: null, title: 'Time',
                    render: (d, t, row) => {
                        const fmt = t => {
                            if (!t) return '';
                            const [h, m] = t.split(':');
                            const hr = parseInt(h), ampm = hr >= 12 ? 'PM' : 'AM';
                            return (hr % 12 || 12) + ':' + m + ' ' + ampm;
                        };
                        return fmt(row.time_start) + ' – ' + fmt(row.time_end);
                    }
                },
                { data: 'purpose', title: 'Purpose' },
                {
                    data: 'status', title: 'Status',
                    render: d => `<span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge[d] || ''}">${capitalize(d)}</span>`
                },
                {
                    data: null, title: 'Actions', orderable: false,
                    render: (d, t, row) =>
                        `<div class="flex gap-1">
                            <button class="view-res-btn text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded" data-id="${row.id}">View</button>
                            <button class="edit-res-btn text-xs px-2 py-1 bg-yellow-100 text-yellow-700 rounded" data-id="${row.id}">Edit</button>
                            <button class="del-res-btn text-xs px-2 py-1 bg-red-100 text-red-700 rounded" data-id="${row.id}">Delete</button>
                        </div>`
                }
            ],
            order: [[4, 'desc']],
            responsive: true,
            pageLength: 25,
            language: { emptyTable: 'No reservations found.' }
        });
    }

    function updateCounts(c) {
        $('#countPending').text(c.pending ?? 0);
        $('#countApproved').text(c.approved ?? 0);
        $('#countDenied').text(c.denied ?? 0);
        $('#countCompleted').text(c.completed ?? 0);
    }

    function reloadTable() {
        const date = $('#filterDate').val();
        const fac = $('#filterFacility').val();
        const stat = $('#filterStatus').val();
        const srch = $('#searchInput').val();
        let url = 'actions/court_api.php?action=list';
        if (date) url += '&filter_date=' + date;
        if (fac) url += '&filter_facility=' + encodeURIComponent(fac);
        if (stat) url += '&filter_status=' + encodeURIComponent(stat);
        if (srch) url += '&search=' + encodeURIComponent(srch);
        courtTable.ajax.url(url).load();
    }

    $('#filterDate, #filterFacility, #filterStatus').on('change', reloadTable);
    let searchTimer;
    $('#searchInput').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reloadTable, 400);
    });

    // ── Add/Edit Dialog ──────────────────────────────────────────────────────
    $('#reservationDialog').dialog({
        autoOpen: false, modal: true, width: 580,
        buttons: {
            'Save Reservation': function () { saveReservation(); },
            'Cancel': function () { $(this).dialog('close'); }
        }
    });

    $('#btnNewReservation').on('click', function () {
        resetResForm();
        $('#reservationDialog').dialog('option', 'title', 'New Reservation').dialog('open');
    });

    function resetResForm() {
        $('#resId').val('');
        $('#resBorrower, #resBorrowerContact, #resOrganization, #resPurpose, #resRemarks').val('');
        $('#resFacility').val('basketball_court');
        $('#resDate').val(new Date().toISOString().split('T')[0]);
        $('#resTimeStart').val('');
        $('#resTimeEnd').val('');
        $('#resStatus').val('pending');
        $('#conflictWarning').addClass('hidden');
    }

    // Conflict check on time/facility/date change
    $('#resFacility, #resDate, #resTimeStart, #resTimeEnd').on('change', checkConflict);

    function checkConflict() {
        const facility = $('#resFacility').val();
        const date = $('#resDate').val();
        const tStart = $('#resTimeStart').val();
        const tEnd = $('#resTimeEnd').val();
        const id = $('#resId').val() || 0;
        if (!facility || !date || !tStart || !tEnd) return;
        $.getJSON('actions/court_api.php', { action: 'check_conflict', facility, date, time_start: tStart, time_end: tEnd, exclude_id: id }, function (res) {
            if (res.conflict) {
                $('#conflictWarning').removeClass('hidden');
            } else {
                $('#conflictWarning').addClass('hidden');
            }
        });
    }

    function saveReservation() {
        const borrower = $.trim($('#resBorrower').val());
        const date = $('#resDate').val();
        const tStart = $('#resTimeStart').val();
        const tEnd = $('#resTimeEnd').val();
        const purpose = $.trim($('#resPurpose').val());

        if (!borrower || !date || !tStart || !tEnd || !purpose) {
            showMsg('Validation', 'Please fill all required fields.', true);
            return;
        }

        $.post('actions/court_api.php?action=save', {
            id: $('#resId').val(),
            borrower_name: borrower,
            borrower_contact: $('#resBorrowerContact').val(),
            organization: $('#resOrganization').val(),
            facility: $('#resFacility').val(),
            reservation_date: date,
            time_start: tStart,
            time_end: tEnd,
            purpose,
            status: $('#resStatus').val(),
            remarks: $('#resRemarks').val()
        }, function (res) {
            if (res.success) {
                $('#reservationDialog').dialog('close');
                reloadTable();
                showMsg('Success', res.message);
            } else {
                if (res.conflict) $('#conflictWarning').removeClass('hidden');
                showMsg('Error', res.message, true);
            }
        }, 'json').fail(() => showMsg('Error', 'Request failed.', true));
    }

    // ── Table Actions ────────────────────────────────────────────────────────
    $('#courtTable').on('click', '.view-res-btn', function () {
        const row = courtTable.row($(this).closest('tr')).data();
        if (!row) return;
        const fmt = t => { if (!t) return ''; const [h, m] = t.split(':'); const hr = parseInt(h), ampm = hr >= 12 ? 'PM' : 'AM'; return (hr % 12 || 12) + ':' + m + ' ' + ampm; };
        const dt = new Date(row.reservation_date + 'T00:00:00');
        $('#viewResContent').html(`
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-500">Code</span>
                    <span class="font-bold text-blue-700">${row.reservation_code}</span>
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Borrower</span><span>${escHtml(row.borrower_name)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Contact</span><span>${escHtml(row.borrower_contact || '—')}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Organization</span><span>${escHtml(row.organization || '—')}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Facility</span><span>${facilityLabels[row.facility] || row.facility}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Date</span><span>${dt.toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Time</span><span>${fmt(row.time_start)} – ${fmt(row.time_end)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Purpose</span><span>${escHtml(row.purpose)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge[row.status] || ''}">${capitalize(row.status)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Remarks</span><span>${escHtml(row.remarks || '—')}</span></div>
                <hr><div class="text-xs text-gray-400">Created by: ${escHtml(row.created_by_name || '—')} on ${row.created_at || ''}</div>
            </div>
        `);
        $('#viewResDialog').dialog('option', 'title', 'Reservation — ' + row.reservation_code).dialog('open');
    });

    $('#viewResDialog').dialog({ autoOpen: false, modal: true, width: 480, buttons: { 'Close': function () { $(this).dialog('close'); } } });

    $('#courtTable').on('click', '.edit-res-btn', function () {
        const row = courtTable.row($(this).closest('tr')).data();
        if (!row) return;
        $('#resId').val(row.id);
        $('#resBorrower').val(row.borrower_name);
        $('#resBorrowerContact').val(row.borrower_contact || '');
        $('#resOrganization').val(row.organization || '');
        $('#resFacility').val(row.facility);
        $('#resDate').val(row.reservation_date);
        $('#resTimeStart').val(row.time_start);
        $('#resTimeEnd').val(row.time_end);
        $('#resPurpose').val(row.purpose);
        $('#resStatus').val(row.status);
        $('#resRemarks').val(row.remarks || '');
        $('#conflictWarning').addClass('hidden');
        $('#reservationDialog').dialog('option', 'title', 'Edit Reservation — ' + row.reservation_code).dialog('open');
    });

    $('#courtTable').on('click', '.del-res-btn', function () {
        $('#deleteResId').val($(this).data('id'));
        $('#deleteResDialog').dialog('open');
    });

    $('#deleteResDialog').dialog({
        autoOpen: false, modal: true, width: 400,
        buttons: {
            'Yes, Delete': function () {
                $.post('actions/court_api.php?action=delete', { id: $('#deleteResId').val() }, function (res) {
                    $('#deleteResDialog').dialog('close');
                    if (res.success) { reloadTable(); showMsg('Deleted', res.message); }
                    else showMsg('Error', res.message, true);
                }, 'json');
            },
            'Cancel': function () { $(this).dialog('close'); }
        }
    });

    // ── Print ────────────────────────────────────────────────────────────────
    $('#btnPrintCourt').on('click', function () {
        const date = $('#filterDate').val() || '';
        const fac = $('#filterFacility').val() || '';
        window.open('print/print_court.php?filter_date=' + date + '&filter_facility=' + fac, '_blank');
    });

    function showMsg(title, msg, isError) {
        const id = 'msg_' + Date.now();
        $('body').append(`<div id="${id}" title="${title}" style="display:none"><p class="${isError ? 'text-red-600' : 'text-green-600'}">${msg}</p></div>`);
        $(`#${id}`).dialog({ modal: true, width: 380, buttons: { OK: function () { $(this).dialog('close').remove(); } } });
    }

    function escHtml(str) { const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML; }
    function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    initTable();
    document.body.style.display = '';
});
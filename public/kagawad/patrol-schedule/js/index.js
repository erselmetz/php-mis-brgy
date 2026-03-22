/**
 * Mobile Patrol / Roving Tanod Schedule - JS Controller
 */
$(function () {
    let patrolTable;

    const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const statusBadge = {
        scheduled: 'bg-blue-100 text-blue-800',
        ongoing: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-gray-100 text-gray-500'
    };

    // ── Show/hide week day selector ──────────────────────────────────────────
    $('#patrolIsWeekly').on('change', function () {
        $(this).val() === '1' ? $('#weekDayWrapper').removeClass('hidden') : $('#weekDayWrapper').addClass('hidden');
    });

    // ── Init DataTable ──────────────────────────────────────────────────────
    function initTable() {
        patrolTable = $('#patrolTable').DataTable({
            ajax: {
                url: 'actions/patrol_api.php?action=list',
                dataSrc: function (json) {
                    if (json.status === 'ok') {
                        updateCounts(json.counts || {});
                        return json.data || [];
                    }
                    return [];
                }
            },
            columns: [
                { data: 'patrol_code', title: 'Code' },
                { data: 'team_name', title: 'Team' },
                {
                    data: 'patrol_date', title: 'Date',
                    render: d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
                },
                {
                    data: null, title: 'Time',
                    render: (d, t, row) => {
                        const fmt = v => {
                            if (!v) return '';
                            const [h, m] = v.split(':');
                            const hr = parseInt(h), ap = hr >= 12 ? 'PM' : 'AM';
                            return (hr % 12 || 12) + ':' + m + ' ' + ap;
                        };
                        return fmt(row.time_start) + ' – ' + fmt(row.time_end);
                    }
                },
                {
                    data: null, title: 'Route / Area',
                    render: (d, t, row) => {
                        const parts = [];
                        if (row.patrol_route) parts.push('<span class="text-xs">🗺️ ' + escHtml(row.patrol_route) + '</span>');
                        if (row.area_covered) parts.push('<span class="text-xs text-gray-500">📍 ' + escHtml(row.area_covered) + '</span>');
                        return parts.join('<br>') || '—';
                    }
                },
                {
                    data: 'tanod_members', title: 'Members',
                    render: d => {
                        if (!d) return '—';
                        const members = d.split(',').map(m => `<span class="inline-block bg-gray-100 rounded px-1 text-xs mr-1 mb-0.5">${escHtml(m.trim())}</span>`);
                        return members.join('');
                    }
                },
                {
                    data: null, title: 'Type',
                    render: (d, t, row) => row.is_weekly == 1
                        ? `<span class="px-2 py-0.5 rounded-full text-xs bg-purple-100 text-purple-700 font-medium">🔁 Weekly (${weekDays[row.week_day] || ''})</span>`
                        : `<span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 font-medium">One-time</span>`
                },
                {
                    data: 'status', title: 'Status',
                    render: d => `<span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge[d] || ''}">${capitalize(d)}</span>`
                },
                {
                    data: null, title: 'Actions', orderable: false,
                    render: (d, t, row) =>
                        `<div class="flex gap-1">
                            <button class="view-patrol-btn text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded" data-id="${row.id}">View</button>
                        </div>`
                }
            ],
            order: [[2, 'desc']],
            responsive: true,
            pageLength: 25,
            language: { emptyTable: 'No patrol schedules found.' }
        });
    }

    function updateCounts(c) {
        $('#countScheduled').text(c.scheduled ?? 0);
        $('#countOngoing').text(c.ongoing ?? 0);
        $('#countCompleted').text(c.completed ?? 0);
        $('#countWeekly').text(c.weekly ?? 0);
    }

    function reloadTable() {
        const date = $('#filterDate').val();
        const stat = $('#filterStatus').val();
        const weekly = $('#filterWeekly').val();
        const srch = $('#searchInput').val();
        let url = 'actions/patrol_api.php?action=list';
        if (date) url += '&filter_date=' + date;
        if (stat) url += '&filter_status=' + encodeURIComponent(stat);
        if (weekly !== '') url += '&filter_weekly=' + weekly;
        if (srch) url += '&search=' + encodeURIComponent(srch);
        patrolTable.ajax.url(url).load();
    }

    $('#filterDate, #filterStatus, #filterWeekly').on('change', reloadTable);
    let searchTimer;
    $('#searchInput').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reloadTable, 400);
    });

    // ── Add/Edit Dialog ──────────────────────────────────────────────────────
    $('#patrolDialog').dialog({
        autoOpen: false, modal: true, width: 580,
        buttons: {
            'Save Patrol': function () { savePatrol(); },
            'Cancel': function () { $(this).dialog('close'); }
        }
    });

    $('#btnNewPatrol').on('click', function () {
        resetPatrolForm();
        $('#patrolDialog').dialog('option', 'title', 'New Patrol Schedule').dialog('open');
    });

    function resetPatrolForm() {
        $('#patrolId').val('');
        $('#patrolTeam, #patrolRoute, #patrolArea, #patrolMembers, #patrolNotes').val('');
        $('#patrolDate').val(new Date().toISOString().split('T')[0]);
        $('#patrolTimeStart, #patrolTimeEnd').val('');
        $('#patrolStatus').val('scheduled');
        $('#patrolIsWeekly').val('0');
        $('#patrolWeekDay').val('1');
        $('#weekDayWrapper').addClass('hidden');
    }

    function savePatrol() {
        const team = $.trim($('#patrolTeam').val());
        const date = $('#patrolDate').val();
        const tS = $('#patrolTimeStart').val();
        const tE = $('#patrolTimeEnd').val();

        if (!team || !date || !tS || !tE) {
            showMsg('Validation', 'Team Name, Date, Time Start and Time End are required.', true);
            return;
        }

        $.post('actions/patrol_api.php?action=save', {
            id: $('#patrolId').val(),
            team_name: team,
            patrol_date: date,
            time_start: tS,
            time_end: tE,
            patrol_route: $('#patrolRoute').val(),
            area_covered: $('#patrolArea').val(),
            tanod_members: $('#patrolMembers').val(),
            notes: $('#patrolNotes').val(),
            status: $('#patrolStatus').val(),
            is_weekly: $('#patrolIsWeekly').val(),
            week_day: $('#patrolWeekDay').val()
        }, function (res) {
            if (res.success) {
                $('#patrolDialog').dialog('close');
                reloadTable();
                showMsg('Success', res.message);
            } else {
                showMsg('Error', res.message, true);
            }
        }, 'json').fail(() => showMsg('Error', 'Request failed. Please try again.', true));
    }

    // ── View ─────────────────────────────────────────────────────────────────
    $('#viewPatrolDialog').dialog({
        autoOpen: false, modal: true, width: 500,
        buttons: { 'Close': function () { $(this).dialog('close'); } }
    });

    $('#patrolTable').on('click', '.view-patrol-btn', function () {
        const row = patrolTable.row($(this).closest('tr')).data();
        if (!row) return;
        const fmt = v => { if (!v) return '—'; const [h, m] = v.split(':'); const hr = parseInt(h), ap = hr >= 12 ? 'PM' : 'AM'; return (hr % 12 || 12) + ':' + m + ' ' + ap; };
        const dt = new Date(row.patrol_date + 'T00:00:00');
        const membersHtml = row.tanod_members
            ? row.tanod_members.split(',').map(m => `<span class="inline-block bg-gray-100 border rounded px-1.5 py-0.5 text-xs mr-1 mb-1">${escHtml(m.trim())}</span>`).join('')
            : '—';

        $('#viewPatrolContent').html(`
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-500">Patrol Code</span>
                    <span class="font-bold text-blue-700">${row.patrol_code}</span>
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Team</span><span class="font-medium">${escHtml(row.team_name)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Date</span><span>${dt.toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Time</span><span>${fmt(row.time_start)} – ${fmt(row.time_end)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Patrol Route</span><span class="text-right max-w-xs">${escHtml(row.patrol_route || '—')}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Area Covered</span><span class="text-right max-w-xs">${escHtml(row.area_covered || '—')}</span></div>
                <div>
                    <span class="text-gray-500 block mb-1">Tanod Members</span>
                    <div>${membersHtml}</div>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Schedule Type</span>
                    <span>${row.is_weekly == 1 ? '🔁 Weekly (' + (weekDays[row.week_day] || '') + ')' : 'One-time'}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge[row.status] || ''}">${capitalize(row.status)}</span>
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Notes</span><span class="text-right max-w-xs">${escHtml(row.notes || '—')}</span></div>
                <hr>
                <div class="text-xs text-gray-400">Created by: ${escHtml(row.created_by_name || '—')} on ${row.created_at || ''}</div>
                ${row.updated_by_name ? `<div class="text-xs text-gray-400">Updated by: ${escHtml(row.updated_by_name)} on ${row.updated_at || ''}</div>` : ''}
            </div>
        `);
        $('#viewPatrolDialog').dialog('option', 'title', 'Patrol — ' + row.patrol_code).dialog('open');
    });

    // ── Edit ─────────────────────────────────────────────────────────────────
    $('#patrolTable').on('click', '.edit-patrol-btn', function () {
        const row = patrolTable.row($(this).closest('tr')).data();
        if (!row) return;
        $('#patrolId').val(row.id);
        $('#patrolTeam').val(row.team_name);
        $('#patrolDate').val(row.patrol_date);
        $('#patrolTimeStart').val(row.time_start);
        $('#patrolTimeEnd').val(row.time_end);
        $('#patrolRoute').val(row.patrol_route || '');
        $('#patrolArea').val(row.area_covered || '');
        $('#patrolMembers').val(row.tanod_members || '');
        $('#patrolNotes').val(row.notes || '');
        $('#patrolStatus').val(row.status);
        $('#patrolIsWeekly').val(row.is_weekly ? '1' : '0');
        $('#patrolWeekDay').val(row.week_day ?? 1);
        row.is_weekly == 1 ? $('#weekDayWrapper').removeClass('hidden') : $('#weekDayWrapper').addClass('hidden');
        $('#patrolDialog').dialog('option', 'title', 'Edit Patrol — ' + row.patrol_code).dialog('open');
    });

    // ── Print ────────────────────────────────────────────────────────────────
    $('#btnPrintPatrol').on('click', function () {
        const date = $('#filterDate').val() || '';
        const stat = $('#filterStatus').val() || '';
        window.open('print/print_patrol.php?filter_date=' + date + '&filter_status=' + stat, '_blank');
    });

    // ── Helpers ──────────────────────────────────────────────────────────────
    function showMsg(title, msg, isError) {
        const id = 'msg_' + Date.now();
        $('body').append(`<div id="${id}" title="${title}" style="display:none"><p class="p-4 ${isError ? 'text-red-600' : 'text-green-600'}">${msg}</p></div>`);
        $(`#${id}`).dialog({ modal: true, width: 380, buttons: { OK: function () { $(this).dialog('close').remove(); } } });
    }

    function escHtml(str) { const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML; }
    function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    // ── Boot ─────────────────────────────────────────────────────────────────
    initTable();
    document.body.style.display = '';
});
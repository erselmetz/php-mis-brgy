/**
 * Events & Scheduling — Redesigned JS Controller
 * Tabs: Calendar | Event List (DataTable) | History
 */
$(function () {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────────
    const today = new Date();
    let currentMonth = today.getMonth() + 1;
    let currentYear = today.getFullYear();
    let calEvents = [];   // events for current calendar month
    let eventsTable = null; // DataTable instance
    let historyLoaded = false;

    // ── Helpers ──────────────────────────────────────────────────────────────
    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }
    function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
    function fmtDate(d) {
        if (!d) return '—';
        return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function fmtTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hr = parseInt(h), ap = hr >= 12 ? 'PM' : 'AM';
        return (hr % 12 || 12) + ':' + m + ' ' + ap;
    }

    const priorityBadge = {
        normal: '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">🔵 Normal</span>',
        important: '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">🟢 Important</span>',
        urgent: '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">🔴 Urgent</span>'
    };
    const statusBadge = {
        scheduled: '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Scheduled</span>',
        completed: '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Completed</span>',
        cancelled: '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Cancelled</span>'
    };

    // ── Tab Switching ────────────────────────────────────────────────────────
    $('.tab-btn').on('click', function () {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').removeClass('active');
        $('#tab-' + tab).addClass('active');

        if (tab === 'list' && !eventsTable) initDataTable();
        if (tab === 'history' && !historyLoaded) { historyLoaded = true; loadHistory(); }
        if (tab === 'list' && eventsTable) reloadDataTable();
    });

    // ── Load Calendar Events ─────────────────────────────────────────────────
    function loadEvents() {
        renderCalendarSkeleton();

        $.getJSON('actions/fetch_events.php', {
            month: currentMonth,
            year: currentYear,
            status: 'scheduled'
        }, function (res) {
            calEvents = (res && res.status === 'ok') ? (res.data || []) : [];
            renderEventsList();
            renderCalendar();
            updateStats();
        }).fail(function () {
            calEvents = [];
            renderEventsList();
            renderCalendar();
        });
    }

    // ── Stats ────────────────────────────────────────────────────────────────
    function updateStats() {
        const total = calEvents.length;
        const scheduled = calEvents.filter(e => e.status === 'scheduled').length;
        const urgent = calEvents.filter(e => e.priority === 'urgent').length;

        // Upcoming next 7 days
        const nowMs = today.getTime();
        const in7Ms = nowMs + 7 * 86400000;
        const upcoming = calEvents.filter(e => {
            const d = new Date(e.event_date + 'T00:00:00').getTime();
            return d >= nowMs && d <= in7Ms;
        }).length;

        $('#statTotal').text(total);
        $('#statScheduled').text(scheduled);
        $('#statUrgent').text(urgent);
        $('#statUpcoming').text(upcoming);
        $('#upcomingCount').text(total + ' event' + (total !== 1 ? 's' : ''));
    }

    // ── Render Upcoming Events Mini List ────────────────────────────────────
    function renderEventsList() {
        const $list = $('#eventsList');
        const search = $('#globalSearch').val().toLowerCase();
        const priority = $('#filterPriority').val();

        let filtered = calEvents.filter(e => {
            const matchSearch = !search ||
                (e.title || '').toLowerCase().includes(search) ||
                (e.location || '').toLowerCase().includes(search);
            const matchPriority = !priority || e.priority === priority;
            return matchSearch && matchPriority;
        });

        $list.empty();

        if (filtered.length === 0) {
            $list.html('<div class="text-center text-gray-400 py-10 text-sm">No upcoming events this month.</div>');
            return;
        }

        filtered.forEach(function (ev, i) {
            const timeStr = ev.event_time ? ' · ' + fmtTime(ev.event_time) : '';
            const dt = new Date(ev.event_date + 'T00:00:00');
            const dateStr = dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });

            const card = `
                <div class="event-card-anim event-card-${ev.priority || 'normal'} bg-white border rounded-xl p-3 cursor-pointer hover:shadow-md transition-shadow group"
                     style="animation-delay:${i * 40}ms" data-id="${ev.id}">
                    <div class="flex justify-between items-start gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="text-xs font-semibold text-blue-600">${dateStr}${timeStr}</span>
                            </div>
                            <p class="font-semibold text-sm text-gray-800 truncate">${escHtml(ev.title)}</p>
                            ${ev.location ? `<p class="text-xs text-gray-400 mt-0.5 truncate">📍 ${escHtml(ev.location)}</p>` : ''}
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                            <button class="view-event-btn text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100" data-id="${ev.id}">View</button>
                            <button class="edit-event-btn text-xs px-2 py-1 bg-yellow-50 text-yellow-600 rounded-lg hover:bg-yellow-100" data-id="${ev.id}">Edit</button>
                        </div>
                    </div>
                </div>
            `;
            $list.append(card);
        });
    }

    // ── Render Calendar ──────────────────────────────────────────────────────
    function renderCalendar() {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $('#calendarMonthYear').text(monthNames[currentMonth - 1] + ' ' + currentYear);

        const firstDay = new Date(currentYear, currentMonth - 1, 1);
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
        const startDow = firstDay.getDay();
        const prevDays = new Date(currentYear, currentMonth - 1, 0).getDate();

        const todayStr = today.toISOString().split('T')[0];

        // Index events by date
        const byDate = {};
        calEvents.forEach(e => {
            if (!byDate[e.event_date]) byDate[e.event_date] = [];
            byDate[e.event_date].push(e);
        });

        let html = '';

        // Prev month trailing days
        for (let i = startDow - 1; i >= 0; i--) {
            const d = prevDays - i;
            html += `<div class="cal-day other-month"><span class="text-xs text-gray-300">${d}</span></div>`;
        }

        // Current month days
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const dayEvents = byDate[dateStr] || [];
            const isToday = dateStr === todayStr;

            const dots = dayEvents.slice(0, 4).map(e =>
                `<span class="cal-dot ${e.priority || 'normal'}"></span>`
            ).join('');

            html += `
                <div class="cal-day ${isToday ? 'today' : ''} cursor-pointer" data-date="${dateStr}">
                    <div class="text-xs font-semibold ${isToday ? 'text-blue-600' : 'text-gray-700'} mb-1">${d}</div>
                    <div class="flex flex-wrap gap-0.5">${dots}</div>
                    ${dayEvents.length > 4 ? `<div class="text-xs text-gray-400 mt-0.5">+${dayEvents.length - 4}</div>` : ''}
                </div>
            `;
        }

        // Remaining cells
        const cellsUsed = startDow + daysInMonth;
        const remaining = (Math.ceil(cellsUsed / 7) * 7) - cellsUsed;
        for (let d = 1; d <= remaining; d++) {
            html += `<div class="cal-day other-month"><span class="text-xs text-gray-300">${d}</span></div>`;
        }

        $('#calendarGrid').html(html);
    }

    function renderCalendarSkeleton() {
        $('#eventsList').html('<div class="skeleton"></div><div class="skeleton" style="height:52px"></div><div class="skeleton" style="height:62px"></div>');
    }

    // ── Calendar Navigation ──────────────────────────────────────────────────
    $('#prevMonth').on('click', function () {
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        loadEvents();
    });
    $('#nextMonth').on('click', function () {
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        loadEvents();
    });
    $('#todayBtn').on('click', function () {
        currentMonth = today.getMonth() + 1;
        currentYear = today.getFullYear();
        loadEvents();
    });

    // Calendar day click → open Add New Event modal with that date pre-filled
    $('#calendarGrid').on('click', '.cal-day[data-date]', function () {
        const dateStr = $(this).data('date');
        if (!dateStr) return;

        resetEventForm();
        $('#eventDate').val(dateStr);
        $('#eventModal').dialog('option', 'title', '✨ New Event').dialog('open');
    });

    // ── Search / Filter Reactive ─────────────────────────────────────────────
    let searchTimer;
    $('#globalSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(renderEventsList, 250);
    });
    $('#filterPriority').on('change', renderEventsList);

    // ── DataTable (Event List Tab) ────────────────────────────────────────────
    function initDataTable() {
        eventsTable = $('#eventsTable').DataTable({
            ajax: {
                url: 'actions/fetch_events.php?status=scheduled',
                dataSrc: function (json) {
                    return (json && json.status === 'ok') ? json.data : [];
                }
            },
            columns: [
                { data: 'event_code', title: 'Code' },
                { data: 'title', title: 'Title' },
                { data: 'event_date', title: 'Date', render: d => fmtDate(d) },
                { data: 'event_time', title: 'Time', render: d => d ? fmtTime(d) : '<span class="text-gray-400">—</span>' },
                { data: 'location', title: 'Location', defaultContent: '<span class="text-gray-400">—</span>' },
                { data: 'priority', title: 'Priority', render: d => priorityBadge[d] || d },
                { data: 'status', title: 'Status', render: d => statusBadge[d] || d },
                {
                    data: null, title: 'Actions', orderable: false,
                    render: (d, t, row) =>
                        `<div class="flex gap-1">
                            <button class="view-event-btn text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100" data-id="${row.id}">View</button>
                            <button class="edit-event-btn text-xs px-2 py-1 bg-yellow-50 text-yellow-700 rounded hover:bg-yellow-100" data-id="${row.id}">Edit</button>
                            <button class="del-event-btn text-xs px-2 py-1 bg-red-50 text-red-600 rounded hover:bg-red-100" data-id="${row.id}">Delete</button>
                        </div>`
                }
            ],
            order: [[2, 'asc']],
            responsive: true,
            pageLength: 50,
            lengthMenu: [10, 25, 50, 100],
            language: { emptyTable: 'No events found.' }
        });
    }

    function reloadDataTable() {
        if (!eventsTable) return;
        const status = $('#listStatusFilter').val();
        eventsTable.ajax.url('actions/fetch_events.php?status=' + status).load();
    }

    $('#listStatusFilter').on('change', reloadDataTable);

    // ── History Tab ──────────────────────────────────────────────────────────
    function loadHistory(search) {
        const $list = $('#historyList');
        $list.html('<div class="text-center text-gray-400 py-8 text-sm">Loading history…</div>');
        $.getJSON('actions/fetch_event_history.php', { search: search || '', limit: 60 }, function (res) {
            $list.empty();
            if (!res || res.status !== 'ok' || !res.data || res.data.length === 0) {
                $list.html('<div class="text-center text-gray-400 py-10 text-sm">No history records found.</div>');
                return;
            }
            res.data.forEach(ev => {
                const statusClass = ev.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700';
                $list.append(`
                    <div class="bg-white border rounded-xl p-3 flex justify-between items-start gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-xs font-semibold text-gray-500">${fmtDate(ev.event_date)}${ev.event_time ? ' · ' + fmtTime(ev.event_time) : ''}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusClass}">${capitalize(ev.status)}</span>
                            </div>
                            <p class="font-semibold text-sm text-gray-800">${escHtml(ev.title)}</p>
                            ${ev.location ? `<p class="text-xs text-gray-400 mt-0.5">📍 ${escHtml(ev.location)}</p>` : ''}
                            ${ev.description ? `<p class="text-xs text-gray-500 mt-1">${escHtml(ev.description)}</p>` : ''}
                        </div>
                        <div class="text-xs text-gray-400 text-right flex-shrink-0">
                            ${escHtml(ev.created_by_name || '—')}<br>
                            <span class="text-gray-300">${ev.event_code || ''}</span>
                        </div>
                    </div>
                `);
            });
        }).fail(() => {
            $('#historyList').html('<div class="text-center text-red-400 py-8 text-sm">Failed to load history.</div>');
        });
    }

    let histTimer;
    $('#historySearch').on('keyup', function () {
        clearTimeout(histTimer);
        histTimer = setTimeout(() => loadHistory($(this).val()), 350);
    });

    // ── Add/Edit Event Dialog ────────────────────────────────────────────────
    $('#eventModal').dialog({
        autoOpen: false,
        modal: true,
        width: 520,
        buttons: {
            'Save Event': function () { submitEventForm(); },
            'Cancel': function () { $(this).dialog('close'); }
        }
    });

    $('#btnNewEvent').on('click', function () {
        resetEventForm();
        $('#eventModal').dialog('option', 'title', '✨ New Event').dialog('open');
    });

    function resetEventForm() {
        $('#eventId').val('');
        $('#eventTitle, #eventLocation, #eventDescription').val('');
        $('#eventDate').val(today.toISOString().split('T')[0]);
        $('#eventTime').val('');
        $('#eventPriority').val('normal');
        $('#eventStatus').val('scheduled');
    }

    function submitEventForm() {
        const id = $('#eventId').val();
        const title = $.trim($('#eventTitle').val());
        const date = $('#eventDate').val();

        if (!title || !date) {
            showMsg('Validation', 'Event Title and Date are required.', true);
            return;
        }

        const url = id ? 'actions/update_event.php' : 'actions/insert_event.php';
        $.post(url, $('#eventForm').serialize(), function (res) {
            if (res.success) {
                $('#eventModal').dialog('close');
                showMsg('Success', res.message);
                loadEvents();
                if (eventsTable) reloadDataTable();
            } else {
                showMsg('Error', res.message, true);
            }
        }, 'json').fail(() => showMsg('Error', 'Request failed. Please try again.', true));
    }

    // ── View Event ───────────────────────────────────────────────────────────
    $('#viewEventModal').dialog({ autoOpen: false, modal: true, width: 480, buttons: { 'Close': function () { $(this).dialog('close'); } } });

    function openViewDialog(id) {
        const ev = calEvents.find(e => e.id == id)
            || (eventsTable ? eventsTable.rows().data().toArray().find(e => e.id == id) : null);
        if (!ev) return;

        $('#viewEventContent').html(`
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-500">Code</span>
                    <span class="font-bold text-blue-700">${ev.event_code || '—'}</span>
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Title</span><span class="font-semibold text-gray-800">${escHtml(ev.title)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Date</span><span>${fmtDate(ev.event_date)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Time</span><span>${ev.event_time ? fmtTime(ev.event_time) : '—'}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Location</span><span>${escHtml(ev.location || '—')}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Priority</span>${priorityBadge[ev.priority] || ev.priority}</div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span>${statusBadge[ev.status] || ev.status}</div>
                ${ev.description ? `<div><span class="text-gray-500 block mb-1">Description</span><p class="text-gray-700 bg-gray-50 rounded-lg p-2 text-xs">${escHtml(ev.description)}</p></div>` : ''}
                <hr>
                <div class="text-xs text-gray-400">Created by: ${escHtml(ev.created_by_name || '—')}</div>
            </div>
        `);
        $('#viewEventModal').dialog('option', 'title', '📋 ' + escHtml(ev.title)).dialog('open');
    }

    // ── Edit pre-fill ────────────────────────────────────────────────────────
    function openEditDialog(id) {
        const ev = calEvents.find(e => e.id == id)
            || (eventsTable ? eventsTable.rows().data().toArray().find(e => e.id == id) : null);
        if (!ev) return;
        $('#eventId').val(ev.id);
        $('#eventTitle').val(ev.title);
        $('#eventDate').val(ev.event_date);
        $('#eventTime').val(ev.event_time || '');
        $('#eventLocation').val(ev.location || '');
        $('#eventDescription').val(ev.description || '');
        $('#eventPriority').val(ev.priority || 'normal');
        $('#eventStatus').val(ev.status || 'scheduled');
        $('#eventModal').dialog('option', 'title', '✏️ Edit Event').dialog('open');
    }

    // ── Delete ───────────────────────────────────────────────────────────────
    $('#deleteEventDialog').dialog({
        autoOpen: false, modal: true, width: 400,
        buttons: {
            'Yes, Delete': function () {
                const id = $('#deleteEventId').val();
                $.post('actions/delete_event.php', { id }, function (res) {
                    $('#deleteEventDialog').dialog('close');
                    if (res.success) { loadEvents(); if (eventsTable) reloadDataTable(); showMsg('Deleted', res.message); }
                    else showMsg('Error', res.message, true);
                }, 'json');
            },
            'Cancel': function () { $(this).dialog('close'); }
        }
    });

    // ── Delegated button clicks (calendar list + DataTable) ──────────────────
    $(document).on('click', '.view-event-btn', function (e) { e.stopPropagation(); openViewDialog($(this).data('id')); });
    $(document).on('click', '.edit-event-btn', function (e) { e.stopPropagation(); openEditDialog($(this).data('id')); });
    $(document).on('click', '.del-event-btn', function (e) {
        e.stopPropagation();
        $('#deleteEventId').val($(this).data('id'));
        $('#deleteEventDialog').dialog('open');
    });

    // ── Print ────────────────────────────────────────────────────────────────
    $('#btnPrintEvents').on('click', function () {
        const search = $('#globalSearch').val().toLowerCase();
        const priority = $('#filterPriority').val();
        let filtered = calEvents.filter(e => {
            const matchSearch = !search || (e.title || '').toLowerCase().includes(search) || (e.location || '').toLowerCase().includes(search);
            const matchPriority = !priority || e.priority === priority;
            return matchSearch && matchPriority;
        });

        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const monthLabel = monthNames[currentMonth - 1] + ' ' + currentYear;

        const rows = filtered.map((ev, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${escHtml(ev.event_code || '—')}</td>
                <td>${escHtml(ev.title)}</td>
                <td>${fmtDate(ev.event_date)}</td>
                <td>${ev.event_time ? fmtTime(ev.event_time) : '—'}</td>
                <td>${escHtml(ev.location || '—')}</td>
                <td>${capitalize(ev.priority || 'normal')}</td>
                <td>${capitalize(ev.status || '')}</td>
            </tr>
        `).join('');

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html><html><head><meta charset="UTF-8">
            <title>Events — ${monthLabel}</title>
            <style>
                *{margin:0;padding:0;box-sizing:border-box}
                body{font-family:Arial,sans-serif;font-size:11px;padding:24px}
                h1{font-size:15px;font-weight:bold;text-align:center;text-transform:uppercase;margin-bottom:4px}
                p.sub{text-align:center;color:#555;font-size:11px;margin-bottom:16px}
                table{width:100%;border-collapse:collapse}
                th{background:#1e40af;color:#fff;padding:5px 7px;text-align:left;font-size:10px}
                td{padding:4px 7px;border-bottom:1px solid #e5e7eb;font-size:10px}
                tr:nth-child(even) td{background:#f9fafb}
                .footer{display:flex;justify-content:space-between;margin-top:28px}
                .sig{border-top:1px solid #000;width:180px;text-align:center;padding-top:4px;font-size:10px}
                @media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
            </style></head><body>
            <h1>Barangay Bombongan</h1>
            <p class="sub">Events & Scheduling — ${monthLabel}</p>
            <p class="sub" style="margin-bottom:10px">Generated: ${new Date().toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
            <table>
                <thead><tr><th>#</th><th>Code</th><th>Title</th><th>Date</th><th>Time</th><th>Location</th><th>Priority</th><th>Status</th></tr></thead>
                <tbody>${rows || '<tr><td colspan="8" style="text-align:center;padding:10px;color:#888">No events found.</td></tr>'}</tbody>
            </table>
            <div class="footer">
                <div><div class="sig">Prepared by</div></div>
                <div><div class="sig">Barangay Captain</div></div>
            </div>
            <script>window.onload=()=>{window.print();}<\/script>
            </body></html>
        `);
        printWindow.document.close();
    });

    // ── Global Message ────────────────────────────────────────────────────────
    function showMsg(title, msg, isError) {
        const id = 'msg_' + Date.now();
        $('body').append(`<div id="${id}" title="${title}" style="display:none"><p class="p-2 ${isError ? 'text-red-600' : 'text-green-600'}">${msg}</p></div>`);
        $(`#${id}`).dialog({ modal: true, width: 360, buttons: { OK: function () { $(this).dialog('close').remove(); } } });
    }

    // ── Boot ─────────────────────────────────────────────────────────────────
    loadEvents();
    document.body.style.display = '';
});
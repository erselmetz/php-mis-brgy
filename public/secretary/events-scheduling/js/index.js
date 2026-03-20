/**
 * Events & Scheduling — JS Controller
 * Replaces: public/secretary/events-scheduling/js/index.js
 */

/* ─── Helpers ─── */
function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function fmtDate(s) {
    if (!s) return '—';
    return new Date(s + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}
function fmtTime(t) {
    if (!t) return '';
    const [h, m] = t.split(':');
    const hr = parseInt(h), ap = hr >= 12 ? 'PM' : 'AM';
    return (hr % 12 || 12) + ':' + m + ' ' + ap;
}
function showAlert(title, msg, type) {
    const id  = 'a_' + Date.now();
    const col = type === 'success' ? 'var(--ok-fg)' : type === 'danger' ? 'var(--danger-fg)' : 'var(--warn-fg)';
    $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
        <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
    </div>`);
    $(`#${id}`).dialog({ autoOpen:true, modal:true, width:400, resizable:false,
        buttons: { 'OK': function(){ $(this).dialog('close').remove(); } }
    });
}

const PRI_CONF = {
    normal:    { label:'Normal',    cls:'pri-normal' },
    important: { label:'Important', cls:'pri-important' },
    urgent:    { label:'Urgent',    cls:'pri-urgent' },
};
const STA_CONF = {
    scheduled: { label:'Scheduled', cls:'es-scheduled' },
    completed: { label:'Completed', cls:'es-completed' },
    cancelled: { label:'Cancelled', cls:'es-cancelled' },
};

$(function () {

    /* ═══════════════════════════════════════
       STATE
    ═══════════════════════════════════════ */
    const today = new Date();
    let curMonth = today.getMonth() + 1;
    let curYear  = today.getFullYear();
    let calEvents = [];
    let evTable   = null;
    let histLoaded = false;

    /* ═══════════════════════════════════════
       TAB SWITCHING
    ═══════════════════════════════════════ */
    $('.tab-btn').on('click', function () {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').removeClass('active');
        $('#tab-' + tab).addClass('active');

        if (tab === 'list' && !evTable) initDataTable();
        else if (tab === 'list' && evTable) reloadDataTable();
        if (tab === 'history' && !histLoaded) { histLoaded = true; loadHistory(); }
    });

    /* ═══════════════════════════════════════
       LOAD CALENDAR EVENTS
    ═══════════════════════════════════════ */
    function loadEvents() {
        renderSidebarSkeleton();
        $.getJSON('actions/fetch_events.php', { month: curMonth, year: curYear, status: 'scheduled' }, function (res) {
            calEvents = (res && res.status === 'ok') ? (res.data || []) : [];
            renderSidebar();
            renderCalendar();
            updateStats();
        }).fail(function () {
            calEvents = [];
            renderSidebar();
            renderCalendar();
        });
    }

    /* ── Stats ── */
    function updateStats() {
        const total     = calEvents.length;
        const scheduled = calEvents.filter(e => e.status === 'scheduled').length;
        const urgent    = calEvents.filter(e => e.priority === 'urgent').length;
        const nowMs     = today.getTime();
        const in7Ms     = nowMs + 7 * 86400000;
        const upcoming  = calEvents.filter(e => {
            const d = new Date(e.event_date + 'T00:00:00').getTime();
            return d >= nowMs && d <= in7Ms;
        }).length;
        $('#stTotal').text(total);
        $('#stScheduled').text(scheduled);
        $('#stUrgent').text(urgent);
        $('#stUpcoming').text(upcoming);
        $('#evCount').text(total);
    }

    /* ── Sidebar event list ── */
    function renderSidebar() {
        const $list    = $('#evList');
        const search   = $('#evSearch').val().toLowerCase();
        const priority = $('.priority-filter .pf-btn.active').data('priority');

        let filtered = calEvents.filter(e => {
            const ms = !search || (e.title || '').toLowerCase().includes(search) || (e.location || '').toLowerCase().includes(search);
            const mp = !priority || e.priority === priority;
            return ms && mp;
        });

        $list.empty();
        if (!filtered.length) {
            $list.html('<div style="padding:20px;text-align:center;color:var(--ink-faint);font-size:12px;font-style:italic;">No events found.</div>');
            return;
        }
        filtered.forEach(ev => {
            const dt      = new Date(ev.event_date + 'T00:00:00');
            const dateStr = dt.toLocaleDateString('en-PH', { month:'short', day:'numeric' });
            const timeStr = ev.event_time ? ' · ' + fmtTime(ev.event_time) : '';
            const priCls  = 'priority-' + (ev.priority || 'normal');

            $list.append(`
                <div class="ev-card ${priCls}" data-id="${ev.id}">
                    <div class="ev-card-date">${dateStr}${timeStr}</div>
                    <div class="ev-card-title">${esc(ev.title)}</div>
                    ${ev.location ? `<div class="ev-card-loc">📍 ${esc(ev.location)}</div>` : ''}
                    <div class="ev-card-actions">
                        <button class="btn btn-ghost btn-sm ev-edit-btn" data-id="${ev.id}">Edit</button>
                    </div>
                </div>
            `);
        });
    }
    function renderSidebarSkeleton() {
        $('#evList').html('<div class="skel"></div><div class="skel" style="height:50px;"></div><div class="skel" style="height:66px;"></div>');
    }

    /* Reactive search + priority filter */
    let searchTimer;
    $('#evSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(renderSidebar, 200);
    });
    $('.priority-filter').on('click', '.pf-btn', function () {
        $('.pf-btn').removeClass('active');
        $(this).addClass('active');
        renderSidebar();
    });

    /* ── Calendar grid ── */
    function renderCalendar() {
        const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $('#calMonthLabel').text(MONTHS[curMonth - 1] + ' ' + curYear);

        const first      = new Date(curYear, curMonth - 1, 1);
        const daysInMon  = new Date(curYear, curMonth, 0).getDate();
        const startDow   = first.getDay();
        const prevDays   = new Date(curYear, curMonth - 1, 0).getDate();
        const todayStr   = today.toISOString().split('T')[0];

        // Index by date
        const byDate = {};
        calEvents.forEach(e => {
            if (!byDate[e.event_date]) byDate[e.event_date] = [];
            byDate[e.event_date].push(e);
        });

        let html = '';

        // Prev month trailing
        for (let i = startDow - 1; i >= 0; i--) {
            html += `<div class="cal-day other-month"><div class="cal-day-num">${prevDays - i}</div></div>`;
        }

        // Current month
        for (let d = 1; d <= daysInMon; d++) {
            const dateStr  = `${curYear}-${String(curMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const dayEvs   = byDate[dateStr] || [];
            const isToday  = dateStr === todayStr;
            const dots     = dayEvs.slice(0, 5).map(e =>
                `<div class="cal-dot ${e.priority || 'normal'}"></div>`
            ).join('');
            html += `
                <div class="cal-day ${isToday ? 'today' : ''}" data-date="${dateStr}">
                    <div class="cal-day-num">${d}</div>
                    <div class="cal-dots">${dots}</div>
                    ${dayEvs.length > 5 ? `<div style="font-size:8px;color:var(--ink-faint);margin-top:1px;">+${dayEvs.length-5}</div>` : ''}
                </div>`;
        }

        // Next month leading
        const used      = startDow + daysInMon;
        const remaining = Math.ceil(used / 7) * 7 - used;
        for (let d = 1; d <= remaining; d++) {
            html += `<div class="cal-day other-month"><div class="cal-day-num">${d}</div></div>`;
        }

        $('#calGrid').html(html);
    }

    /* Calendar nav */
    $('#prevMonth').on('click', function () {
        curMonth--; if (curMonth < 1) { curMonth = 12; curYear--; }
        loadEvents();
    });
    $('#nextMonth').on('click', function () {
        curMonth++; if (curMonth > 12) { curMonth = 1; curYear++; }
        loadEvents();
    });
    $('#todayBtn').on('click', function () {
        curMonth = today.getMonth() + 1; curYear = today.getFullYear();
        loadEvents();
    });

    /* Click calendar day → pre-fill date in Add modal */
    $('#calGrid').on('click', '.cal-day:not(.other-month)', function () {
        const dateStr = $(this).data('date');
        if (!dateStr) return;
        resetEventForm();
        $('#evDate').val(dateStr);
        $('#eventModal').dialog('option', 'title', 'New Event').dialog('open');
    });

    /* ═══════════════════════════════════════
       EVENT LIST DATATABLE
    ═══════════════════════════════════════ */
    function initDataTable() {
        evTable = $('#eventsTable').DataTable({
            ajax: {
                url: 'actions/fetch_events.php?status=scheduled',
                dataSrc: function (json) { return (json && json.status === 'ok') ? json.data : []; }
            },
            dom: 'tip',
            order: [[2, 'asc']],
            pageLength: 50,
            language: { info:'Showing _START_–_END_ of _TOTAL_ events', paginate:{previous:'‹',next:'›'}, emptyTable:'No events found.' },
            columns: [
                { data:'event_code', render: d => `<span style="font-family:var(--f-mono);font-size:11px;font-weight:700;color:var(--accent);">${esc(d)}</span>` },
                { data:'title',      render: d => `<strong>${esc(d)}</strong>` },
                { data:'event_date', render: d => `<span style="font-family:var(--f-mono);font-size:11.5px;">${fmtDate(d)}</span>` },
                { data:'event_time', render: d => d ? `<span style="font-family:var(--f-mono);font-size:11px;color:var(--ink-muted);">${fmtTime(d)}</span>` : '<span style="color:var(--ink-faint);">—</span>' },
                { data:'location',   defaultContent:'<span style="color:var(--ink-faint);">—</span>', render: d => d ? esc(d) : '<span style="color:var(--ink-faint);">—</span>' },
                { data:'priority',   render: d => { const p = PRI_CONF[d] || PRI_CONF.normal; return `<span class="pri-badge ${p.cls}">${p.label}</span>`; } },
                { data:'status',     render: d => { const s = STA_CONF[d] || STA_CONF.scheduled; return `<span class="ev-status ${s.cls}">${s.label}</span>`; } },
                { data:null, orderable:false, render:(d,t,row) =>
                    `<div class="td-actions">
                        <button class="act-btn act-edit dt-edit-btn" data-id="${row.id}">Edit</button>
                        <button class="act-btn act-delete dt-del-btn" data-id="${row.id}" data-title="${esc(row.title)}">Delete</button>
                    </div>`
                },
            ]
        });
    }
    function reloadDataTable() {
        if (!evTable) return;
        const status = $('#listStatusFilter').val();
        evTable.ajax.url(`actions/fetch_events.php?status=${status}`).load();
    }
    $('#listStatusFilter').on('change', reloadDataTable);
    $('#listSearch').on('input', function () {
        if (evTable) evTable.search($(this).val()).draw();
    });

    /* ═══════════════════════════════════════
       ADD / EDIT EVENT MODAL
    ═══════════════════════════════════════ */
    $('#eventModal').dialog({
        autoOpen: false, modal: true, width: 580, resizable: false,
        buttons: {
            'Save Event': function () { $('#eventForm').trigger('submit'); },
            'Cancel':     function () { $(this).dialog('close'); }
        }
    });

    $('#btnNewEvent').on('click', function () {
        resetEventForm();
        $('#eventModal').dialog('option', 'title', 'New Event').dialog('open');
    });

    // Open edit from sidebar card
    $(document).on('click', '.ev-edit-btn', function (e) {
        e.stopPropagation();
        openEditById($(this).data('id'), calEvents);
    });

    // Open edit from DataTable
    $(document).on('click', '.dt-edit-btn', function () {
        const rowData = evTable ? evTable.rows().data().toArray().find(r => r.id == $(this).data('id')) : null;
        if (rowData) fillEventForm(rowData);
        else openEditById($(this).data('id'), []);
    });

    function openEditById(id, pool) {
        const ev = pool.find(e => e.id == id);
        if (ev) { fillEventForm(ev); }
        else {
            // Fetch from API
            $.getJSON('actions/fetch_events.php', { status: 'all' }, function (res) {
                const found = (res.data || []).find(e => e.id == id);
                if (found) fillEventForm(found);
            });
        }
    }

    function fillEventForm(ev) {
        $('#evId').val(ev.id);
        $('#evTitle').val(ev.title || '');
        $('#evDate').val(ev.event_date || '');
        $('#evTime').val(ev.event_time || '');
        $('#evLocation').val(ev.location || '');
        $('#evPriority').val(ev.priority || 'normal');
        $('#evStatus').val(ev.status || 'scheduled');
        $('#evDesc').val(ev.description || '');
        $('#eventModal').dialog('option', 'title', 'Edit Event').dialog('open');
    }

    function resetEventForm() {
        $('#evId').val('');
        $('#eventForm')[0].reset();
        $('#evDate').val(today.toISOString().split('T')[0]);
        $('#evPriority').val('normal');
        $('#evStatus').val('scheduled');
    }

    /* Submit */
    $('#eventForm').on('submit', function (e) {
        e.preventDefault();
        const id  = $('#evId').val();
        const url = id ? 'actions/update_event.php' : 'actions/insert_event.php';
        $.post(url, $(this).serialize(), function (res) {
            if (res.success) {
                $('#eventModal').dialog('close');
                loadEvents();
                if (evTable) reloadDataTable();
                showAlert('Saved', res.message || 'Event saved.', 'success');
            } else {
                showAlert('Error', res.message || 'Save failed.', 'danger');
            }
        }, 'json').fail(() => showAlert('Error', 'Request failed.', 'danger'));
    });

    /* ═══════════════════════════════════════
       DELETE EVENT
    ═══════════════════════════════════════ */
    $(document).on('click', '.dt-del-btn', function () {
        const id    = $(this).data('id');
        const title = $(this).data('title');
        const dlgId = 'del_' + Date.now();
        $('body').append(`<div id="${dlgId}" title="Delete Event" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid var(--danger-fg);background:var(--paper);">
                Delete <strong>${esc(title)}</strong>?<br>
                <span style="font-size:11px;color:var(--ink-faint);">This cannot be undone.</span>
            </div>
        </div>`);
        $(`#${dlgId}`).dialog({
            autoOpen: true, modal: true, width: 400, resizable: false,
            buttons: {
                'Delete': function () {
                    $(this).dialog('close').remove();
                    $.post('actions/delete_event.php', { id }, function (res) {
                        if (res.success) {
                            loadEvents();
                            if (evTable) reloadDataTable();
                            showAlert('Deleted', res.message || 'Event deleted.', 'success');
                        } else {
                            showAlert('Error', res.message || 'Delete failed.', 'danger');
                        }
                    }, 'json');
                },
                'Cancel': function () { $(this).dialog('close').remove(); }
            }
        });
        setTimeout(() => {
            $(`#${dlgId}`).closest('.ui-dialog').find('.ui-dialog-buttonpane .ui-button:first-child')
                .css({ background:'var(--danger-bg)', borderColor:'var(--danger-fg)', color:'var(--danger-fg)' });
        }, 50);
    });

    /* ═══════════════════════════════════════
       HISTORY TAB
    ═══════════════════════════════════════ */
    function loadHistory(search) {
        const $list = $('#histList');
        $list.html('<div style="padding:20px;text-align:center;color:var(--ink-faint);">Loading…</div>');
        $.getJSON('actions/fetch_event_history.php', { search: search || '', limit: 80 }, function (res) {
            $list.empty();
            if (!res || res.status !== 'ok' || !res.data || !res.data.length) {
                $list.html('<div style="padding:20px;text-align:center;color:var(--ink-faint);font-style:italic;">No history records found.</div>');
                return;
            }
            res.data.forEach(ev => {
                const sc  = STA_CONF[ev.status] || STA_CONF.cancelled;
                const pc  = PRI_CONF[ev.priority] || PRI_CONF.normal;
                $list.append(`
                    <div class="hist-card">
                        <div class="hist-status-col">
                            <span class="ev-status ${sc.cls}">${sc.label}</span>
                        </div>
                        <div class="hist-body">
                            <div class="hist-card-date">
                                <span style="font-family:var(--f-mono);">${esc(ev.event_code || '')}</span>
                                · ${fmtDate(ev.event_date)}${ev.event_time ? ' ' + fmtTime(ev.event_time) : ''}
                                · <span class="pri-badge ${pc.cls}" style="padding:1px 6px;font-size:8px;">${pc.label}</span>
                            </div>
                            <div class="hist-card-title">${esc(ev.title)}</div>
                            ${ev.location ? `<div class="hist-card-loc">📍 ${esc(ev.location)}</div>` : ''}
                            ${ev.description ? `<div class="hist-card-meta" style="margin-top:3px;">${esc(ev.description.substring(0,120))}${ev.description.length>120?'…':''}</div>` : ''}
                        </div>
                        <div style="flex-shrink:0;font-size:10.5px;color:var(--ink-faint);text-align:right;white-space:nowrap;">
                            ${esc(ev.created_by_name || '—')}
                        </div>
                    </div>
                `);
            });
        }).fail(() => {
            $list.html('<div style="padding:20px;text-align:center;color:var(--danger-fg);">Failed to load history.</div>');
        });
    }

    let histTimer;
    $('#histSearch').on('input', function () {
        clearTimeout(histTimer);
        histTimer = setTimeout(() => loadHistory($(this).val()), 350);
    });

    /* ═══════════════════════════════════════
       PRINT
    ═══════════════════════════════════════ */
    $('#btnPrintEvents').on('click', function () {
        const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const monthLabel = MONTHS[curMonth - 1] + ' ' + curYear;
        const search   = $('#evSearch').val().toLowerCase();
        const priority = $('.priority-filter .pf-btn.active').data('priority');
        let filtered   = calEvents.filter(e => {
            const ms = !search || (e.title||'').toLowerCase().includes(search);
            const mp = !priority || e.priority === priority;
            return ms && mp;
        });

        const rows = filtered.map((ev, i) => `
            <tr>
                <td>${i+1}</td>
                <td style="font-family:monospace;font-weight:700;">${esc(ev.event_code||'—')}</td>
                <td><strong>${esc(ev.title)}</strong></td>
                <td>${fmtDate(ev.event_date)}</td>
                <td>${ev.event_time ? fmtTime(ev.event_time) : '—'}</td>
                <td>${esc(ev.location||'—')}</td>
                <td>${ev.priority||'normal'}</td>
            </tr>`).join('');

        const w = window.open('','_blank');
        w.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8">
            <title>Events — ${monthLabel}</title>
            <style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Arial,sans-serif;font-size:11px;padding:24px}
            h1{font-size:15px;font-weight:bold;text-align:center;text-transform:uppercase;margin-bottom:4px}
            p.sub{text-align:center;color:#555;font-size:11px;margin-bottom:14px}
            table{width:100%;border-collapse:collapse}
            th{background:#2d5a27;color:#fff;padding:6px 8px;text-align:left;font-size:10px}
            td{padding:5px 8px;border-bottom:1px solid #e5e7eb;font-size:10px}
            tr:nth-child(even) td{background:#f9fafb}
            @media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}</style>
            </head><body>
            <h1>Barangay Bombongan</h1>
            <p class="sub">Events &amp; Scheduling — ${monthLabel}</p>
            <p class="sub" style="margin-bottom:10px;">Generated: ${new Date().toLocaleDateString('en-PH',{month:'long',day:'numeric',year:'numeric'})}</p>
            <table><thead><tr><th>#</th><th>Code</th><th>Title</th><th>Date</th><th>Time</th><th>Location</th><th>Priority</th></tr></thead>
            <tbody>${rows||'<tr><td colspan="7" style="text-align:center;padding:12px;color:#888">No events.</td></tr>'}</tbody>
            </table>
            <script>window.onload=()=>window.print();<\/script></body></html>`);
        w.document.close();
    });

    /* ═══════════════════════════════════════
       BOOT
    ═══════════════════════════════════════ */
    loadEvents();
    document.body.style.display = '';

});
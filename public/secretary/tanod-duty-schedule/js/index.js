/**
 * Tanod Duty Schedule — JS Controller
 * Replaces: public/secretary/tanod-duty-schedule/js/index.js
 */

/* ─── Helpers ─── */
function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function fmtDate(s) {
    if (!s) return '—';
    return new Date(s + 'T00:00:00').toLocaleDateString('en-PH', { weekday:'short', month:'short', day:'numeric', year:'numeric' });
}
function fmtDateShort(s) {
    if (!s) return '—';
    return new Date(s + 'T00:00:00').toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
}
function fmtDateTime(s) {
    if (!s) return '—';
    const d = new Date(s);
    return d.toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' })
         + ' ' + d.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit' });
}
function showAlert(title, msg, type) {
    const id  = 'a_' + Date.now();
    const col = type === 'success' ? 'var(--ok-fg)' : type === 'danger' ? 'var(--danger-fg)' : 'var(--warn-fg)';
    $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
        <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
    </div>`);
    $(`#${id}`).dialog({ autoOpen:true, modal:true, width:400, resizable:false,
        buttons:{ 'OK': function(){ $(this).dialog('close').remove(); } }
    });
}

const SHIFT_CONF = {
    morning:   { label:'☀ Morning',   cls:'sb-morning' },
    afternoon: { label:'🌤 Afternoon', cls:'sb-afternoon' },
    night:     { label:'🌙 Night',     cls:'sb-night' },
};
const STATUS_CONF = {
    active:    { label:'Active',    cls:'ss-active' },
    completed: { label:'Completed', cls:'ss-completed' },
    cancelled: { label:'Cancelled', cls:'ss-cancelled' },
};

$(function () {

    /* ═══════════════════════════════════════
       DATATABLE
    ═══════════════════════════════════════ */
    const table = $('#dutyTable').DataTable({
        ajax: {
            url: 'actions/duty_api.php?action=list',
            dataSrc: function (json) {
                if (json.status === 'ok') {
                    updateShiftCounts(json.counts || {});
                    updateCount(json.data ? json.data.length : 0);
                    return json.data || [];
                }
                return [];
            }
        },
        dom: 'tip',
        order: [[2, 'desc']],
        pageLength: 25,
        language: {
            info: 'Showing _START_–_END_ of _TOTAL_ assignments',
            paginate: { previous:'‹', next:'›' },
            emptyTable: 'No duty assignments found.'
        },
        columns: [
            /* Duty Code */
            { data:'duty_code', render: d => `<span class="td-duty-code">${esc(d)}</span>` },
            /* Tanod Name */
            { data:'tanod_name', render: d => `<span class="td-name">${esc(d)}</span>` },
            /* Date */
            { data:'duty_date', render: d => `<span class="td-date">${fmtDateShort(d)}</span>` },
            /* Shift */
            {
                data:'shift',
                render: function(d) {
                    const sc = SHIFT_CONF[d] || { label: d, cls:'sb-morning' };
                    return `<span class="shift-badge ${sc.cls}">${sc.label}</span>`;
                }
            },
            /* Post */
            {
                data:'post_location',
                render: d => d
                    ? `<span style="font-size:12px;color:var(--ink-muted);">${esc(d)}</span>`
                    : `<span style="color:var(--ink-faint);font-size:11px;">—</span>`
            },
            /* Status */
            {
                data:'status',
                render: function(d) {
                    const sc = STATUS_CONF[d] || STATUS_CONF.active;
                    return `<span class="status-badge ${sc.cls}">${sc.label}</span>`;
                }
            },
            /* Created By */
            {
                data:'created_by_name',
                defaultContent: '—',
                render: d => `<span style="font-size:11.5px;color:var(--ink-faint);">${esc(d || '—')}</span>`
            },
            /* Actions */
            {
                data: null, orderable: false,
                render: function(d, t, row) {
                    return `<div class="td-actions">
                        <button class="act-btn act-view view-duty-btn"   data-id="${row.id}">View</button>
                        <button class="act-btn act-edit edit-duty-btn"   data-id="${row.id}">Edit</button>
                        <button class="act-btn act-delete del-duty-btn"
                            data-id="${row.id}"
                            data-name="${esc(row.tanod_name)}"
                            data-code="${esc(row.duty_code)}">Delete</button>
                    </div>`;
                }
            }
        ]
    });

    function updateShiftCounts(c) {
        $('#cntMorning').text(c.morning   ?? 0);
        $('#cntAfternoon').text(c.afternoon ?? 0);
        $('#cntNight').text(c.night     ?? 0);
    }
    function updateCount(n) {
        $('#dutyCount').text(n.toLocaleString() + ' ASSIGNMENT' + (n !== 1 ? 'S' : ''));
    }

    /* ── Reload helper ── */
    function reloadTable() {
        const date  = $('#filterDate').val();
        const shift = $('#filterShift').val();
        const srch  = $('#searchInput').val();
        let url = 'actions/duty_api.php?action=list';
        if (date)  url += '&filter_date='  + date;
        if (shift) url += '&filter_shift=' + encodeURIComponent(shift);
        if (srch)  url += '&search='       + encodeURIComponent(srch);
        table.ajax.url(url).load();
    }

    /* ── Filters ── */
    $('#filterDate, #filterShift').on('change', reloadTable);
    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reloadTable, 350);
    });

    /* ═══════════════════════════════════════
       ADD / EDIT MODAL
    ═══════════════════════════════════════ */
    $('#dutyModal').dialog({
        autoOpen: false, modal: true, width: 580, resizable: false,
        buttons: {
            'Save Assignment': function() { $('#dutyForm').trigger('submit'); },
            'Cancel':          function() { $(this).dialog('close'); }
        }
    });

    /* Open: Add */
    $('#btnNewDuty').on('click', function() {
        resetForm();
        $('#dutyModal').dialog('option', 'title', 'Assign Duty Schedule').dialog('open');
    });

    /* Open: Edit */
    $(document).on('click', '.edit-duty-btn', function() {
        const id = $(this).data('id');
        const row = table.row($(this).closest('tr')).data();
        if (row) fillForm(row);
    });

    function resetForm() {
        $('#dutyId').val('');
        $('#dutyForm')[0].reset();
        $('#dutyDate').val(new Date().toISOString().split('T')[0]);
        $('#dutyShift').val('morning');
        $('#dutyStatus').val('active');
    }

    function fillForm(row) {
        $('#dutyId').val(row.id);
        $('#dutyTanodName').val(row.tanod_name || '');
        $('#dutyDate').val(row.duty_date || '');
        $('#dutyShift').val(row.shift || 'morning');
        $('#dutyStatus').val(row.status || 'active');
        $('#dutyPost').val(row.post_location || '');
        $('#dutyNotes').val(row.notes || '');
        $('#dutyModal')
            .dialog('option', 'title', 'Edit Duty — ' + (row.duty_code || ''))
            .dialog('open');
    }

    /* Submit */
    $('#dutyForm').on('submit', function(e) {
        e.preventDefault();
        const id     = $('#dutyId').val();
        const action = 'actions/duty_api.php?action=save';
        $.post(action, $(this).serialize(), function(res) {
            if (res.success) {
                $('#dutyModal').dialog('close');
                reloadTable();
                showAlert('Saved', res.message || 'Duty assignment saved.', 'success');
            } else {
                showAlert('Error', res.message || 'Save failed.', 'danger');
            }
        }, 'json').fail(() => showAlert('Error', 'Request failed.', 'danger'));
    });

    /* ═══════════════════════════════════════
       VIEW MODAL
    ═══════════════════════════════════════ */
    $('#viewDutyModal').dialog({
        autoOpen: false, modal: true, width: 560, resizable: false,
        buttons: {
            'Edit':  function() {
                $(this).dialog('close');
                const row = table.rows().data().toArray().find(r => r.id == currentViewId);
                if (row) fillForm(row);
            },
            'Close': function() { $(this).dialog('close'); }
        }
    });

    let currentViewId = null;

    $(document).on('click', '.view-duty-btn', function() {
        const row = table.row($(this).closest('tr')).data();
        if (!row) return;
        currentViewId = row.id;
        openViewModal(row);
    });

    function openViewModal(row) {
        const sc = SHIFT_CONF[row.shift]   || { label: row.shift,   cls:'sb-morning' };
        const st = STATUS_CONF[row.status] || { label: row.status,  cls:'ss-active' };

        $('#vd-code').text(row.duty_code || '—');
        $('#vd-name').text(row.tanod_name || '—');
        $('#vd-date').text('📅 ' + fmtDate(row.duty_date));
        $('#vd-shift-badge').html(`<span class="shift-badge ${sc.cls}" style="font-size:9px;padding:2px 8px;">${sc.label}</span>`);
        $('#vd-status-badge').html(`<span class="status-badge ${st.cls}" style="font-size:9px;padding:2px 8px;">${st.label}</span>`);
        $('#vd-post').text(row.post_location || '—');
        $('#vd-by').text(row.created_by_name || '—');
        $('#vd-created').text(fmtDateTime(row.created_at));
        $('#vd-updated').text(row.updated_at ? fmtDateTime(row.updated_at) : '—');
        $('#vd-notes').text(row.notes || '—');

        $('#viewDutyModal')
            .dialog('option', 'title', 'Duty Record — ' + (row.duty_code || ''))
            .dialog('open');
    }

    /* ═══════════════════════════════════════
       DELETE
    ═══════════════════════════════════════ */
    $(document).on('click', '.del-duty-btn', function() {
        const id   = $(this).data('id');
        const name = $(this).data('name');
        const code = $(this).data('code');
        const dlgId = 'del_' + Date.now();

        $('body').append(`<div id="${dlgId}" title="Delete Duty Assignment" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid var(--danger-fg);background:var(--paper);">
                Delete duty assignment <strong style="font-family:var(--f-mono);">${esc(code)}</strong> for <strong>${esc(name)}</strong>?<br>
                <span style="font-size:11px;color:var(--ink-faint);">This action cannot be undone.</span>
            </div>
        </div>`);

        $(`#${dlgId}`).dialog({
            autoOpen: true, modal: true, width: 440, resizable: false,
            buttons: {
                'Delete': function() {
                    $(this).dialog('close').remove();
                    $.post('actions/duty_api.php?action=delete', { id }, function(res) {
                        if (res.success) {
                            reloadTable();
                            showAlert('Deleted', res.message || 'Assignment deleted.', 'success');
                        } else {
                            showAlert('Error', res.message || 'Delete failed.', 'danger');
                        }
                    }, 'json');
                },
                'Cancel': function() { $(this).dialog('close').remove(); }
            }
        });

        // Style delete button as danger
        setTimeout(() => {
            $(`#${dlgId}`).closest('.ui-dialog')
                .find('.ui-dialog-buttonpane .ui-button:first-child')
                .css({ background:'var(--danger-bg)', borderColor:'var(--danger-fg)', color:'var(--danger-fg)' });
        }, 50);
    });

    /* ═══════════════════════════════════════
       PRINT
    ═══════════════════════════════════════ */
    $('#btnPrintDuty').on('click', function() {
        const date  = $('#filterDate').val() || '';
        const shift = $('#filterShift').val() || '';
        window.open(
            `print/print_duty.php?filter_date=${date}&filter_shift=${shift}`,
            '_blank', 'width=1000,height=700'
        );
    });

    /* ═══════════════════════════════════════
       BOOT
    ═══════════════════════════════════════ */
    document.body.style.display = '';

});
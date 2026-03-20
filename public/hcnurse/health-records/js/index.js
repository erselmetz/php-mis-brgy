/**
 * Health Records — JS Controller
 * Replaces: public/hcnurse/health-records/js/index.js
 *
 * Works for all program types: maternal, family_planning,
 * prenatal, postnatal, child_nutrition, immunization.
 */

/* ─── Sub-type options per program ─── */
const SUBTYPE_OPTIONS = {
    immunization:    [['all','All'],['child','Child'],['adult','Adult'],['pregnant','Pregnant']],
    maternal:        [['all','All'],['mother_only','Mother Only'],['child_only','Child Only'],['mother_child','Mother & Child']],
    family_planning: [['all','All'],['pills','Pills'],['injectable','Injectable'],['implant','Implant'],['iud','IUD']],
    prenatal:        [['all','All'],['prenatal','Prenatal']],
    postnatal:       [['all','All'],['postnatal','Postnatal']],
    child_nutrition: [['all','All'],['supplementation','Supplementation'],['deworming','Deworming'],['weighing','Weighing']],
};

$(function () {

    /* ── State ── */
    let currentPeriod = INIT_FILTERS.period || 'all';

    /* ── Helpers ── */
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
    function showAlert(title, msg, type) {
        const col = type === 'success' ? 'var(--ok-fg)' : 'var(--danger-fg)';
        const id  = 'al_' + Date.now();
        $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
        </div>`);
        $(`#${id}`).dialog({
            autoOpen:true, modal:true, width:400, resizable:false,
            buttons:{ 'OK': function(){ $(this).dialog('close').remove(); } }
        });
    }
    function statusBadge(s) {
        if (!s) return '—';
        const map = {
            'Completed': 'sb-completed',
            'Ongoing':   'sb-ongoing',
            'Dismissed': 'sb-dismissed',
            'Follow-up': 'sb-followup',
        };
        return `<span class="status-badge ${map[s] || 'sb-dismissed'}">${esc(s)}</span>`;
    }

    /* ── Fill sub-type select ── */
    function fillSubTypes() {
        const opts  = SUBTYPE_OPTIONS[HEALTH_RECORD_TYPE] || [['all','All']];
        const $sel  = $('#subTypeSelect');
        $sel.empty();
        opts.forEach(([v, l]) => $sel.append(`<option value="${v}">${l}</option>`));
        $sel.val(INIT_FILTERS.sub || 'all');
    }
    fillSubTypes();

    /* ── DataTable ── */
    const table = $('#hrTable').DataTable({
        pageLength: 25, order: [[0,'desc']],
        dom: 'tip',
        language: {
            info: 'Showing _START_–_END_ of _TOTAL_ records',
            paginate: { previous:'‹', next:'›' },
            emptyTable: ''     /* handled by custom empty state */
        },
        columns: [
            /* Date */
            { data:'consultation_date', render: d => `<span class="td-date">${esc(d||'—')}</span>` },
            /* Patient */
            { data:'resident_name', render: d => `<span class="td-patient">${esc(d||'—')}</span>` },
            /* Sub Type */
            {
                data:'meta',
                render: m => {
                    const s = (m||{}).sub_type || '';
                    return s && s !== 'all'
                        ? `<span class="sub-badge">${esc(s.replace(/_/g,' '))}</span>`
                        : '<span style="color:var(--ink-faint);font-size:11px;">—</span>';
                }
            },
            /* Complaint */
            { data:'complaint', render: d => `<div class="td-trunc">${esc(d||'—')}</div>` },
            /* Worker */
            {
                data:'meta',
                render: m => `<span style="font-size:12px;color:var(--ink-muted);">${esc((m||{}).health_worker||'—')}</span>`
            },
            /* Status */
            { data:'meta', render: m => statusBadge((m||{}).status) },
            /* Actions */
            {
                data:'id', orderable:false,
                render: id => `<div class="td-actions">
                    <button class="act-btn act-view viewBtn" data-id="${id}">View</button>
                    <button class="act-btn act-edit editBtn" data-id="${id}">Edit</button>
                </div>`
            }
        ]
    });

    /* ── Load records ── */
    function loadRecords() {
        const params = {
            type:   HEALTH_RECORD_TYPE,
            period: currentPeriod,
            month:  $('#monthPicker').val(),
            search: $('#searchInput').val(),
            sub:    $('#subTypeSelect').val(),
        };

        /* update URL */
        const url = new URL(window.location.href);
        url.searchParams.set('type',   HEALTH_RECORD_TYPE);
        url.searchParams.set('period', currentPeriod);
        if (currentPeriod === 'monthly') url.searchParams.set('month', params.month);
        else url.searchParams.delete('month');
        if (params.search) url.searchParams.set('q', params.search);
        else url.searchParams.delete('q');
        if (params.sub !== 'all') url.searchParams.set('sub', params.sub);
        else url.searchParams.delete('sub');
        window.history.replaceState({}, '', url.toString());

        $.getJSON('api/health_records_api.php', params, function (res) {
            table.clear();
            if (res.status !== 'ok') return;

            const data = res.data || [];
            $('#recCountBadge').text(data.length + (data.length === 1 ? ' RECORD' : ' RECORDS'));

            if (!data.length) {
                $('#hrEmptyState').show();
                $('#hrTable').hide();
            } else {
                $('#hrEmptyState').hide();
                $('#hrTable').show();
                data.forEach(row => table.row.add(row));
            }
            table.draw();
        });
    }

    /* ── Period buttons ── */
    $(document).on('click', '.pb-btn[data-period]', function () {
        currentPeriod = $(this).data('period');
        $('.pb-btn[data-period]').removeClass('active');
        $(this).addClass('active');
        /* month picker opacity */
        $('#monthPicker').css('opacity', currentPeriod === 'monthly' ? '1' : '.45');
        loadRecords();
    });

    /* ── Month picker ── */
    $('#monthPicker').on('change', function () {
        if (currentPeriod === 'monthly') loadRecords();
    });

    /* ── Sub type ── */
    $('#subTypeSelect').on('change', loadRecords);

    /* ── Search (Enter key) ── */
    let searchTimer;
    $('#searchInput').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadRecords, 400);
    });

    /* ── Clear filters ── */
    $('#btnClear').on('click', function () {
        currentPeriod = 'all';
        $('.pb-btn[data-period]').removeClass('active');
        $('[data-period="all"]').addClass('active');
        $('#monthPicker').val(new Date().toISOString().slice(0,7)).css('opacity','.45');
        $('#subTypeSelect').val('all');
        $('#searchInput').val('');
        loadRecords();
    });

    /* ════════════════════════
       VIEW MODAL
    ════════════════════════ */
    $('#viewModal').dialog({
        autoOpen: false, modal: true, width: 820, resizable: true,
        buttons: { 'Close': function(){ $(this).dialog('close'); } }
    });

    $(document).on('click', '.viewBtn', function () {
        const id = $(this).data('id');
        $.getJSON('api/health_records_api.php', { action:'get', type:HEALTH_RECORD_TYPE, id }, function (res) {
            if (res.status !== 'ok') { showAlert('Error', res.message||'Not found.', 'danger'); return; }
            const d = res.data;
            const m = d.meta || {};
            $('#vm-name').text(d.resident_name || '—');
            $('#vm-sub').text('Record #' + String(d.id).padStart(5,'0') + ' · ' + (m.program||HEALTH_RECORD_TYPE).replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()));
            $('#vm-date').text(d.consultation_date || '—');
            $('#vm-time').text(m.time || '—');
            $('#vm-subtype').text((m.sub_type||'').replace(/_/g,' ') || '—');
            $('#vm-worker').text(m.health_worker || '—');
            $('#vm-complaint').text(d.complaint || '—');
            $('#vm-diagnosis').text(d.diagnosis || '—');
            $('#vm-treatment').text(d.treatment || '—');
            $('#vm-remarks').text(m.remarks || '—');
            $('#vm-status-wrap').html(statusBadge(m.status));
            $('#viewModal').dialog('option','title','Record — ' + (d.resident_name||'')).dialog('open');
        });
    });

    /* ════════════════════════
       EDIT MODAL
    ════════════════════════ */
    $('#editModal').dialog({
        autoOpen: false, modal: true, width: 720, resizable: false,
        buttons: {
            'Save Changes': function(){ submitEdit(); },
            'Cancel':       function(){ $(this).dialog('close'); }
        }
    });
    $('#edit_date').datepicker({ dateFormat:'mm/dd/yy', changeMonth:true, changeYear:true });

    $(document).on('click', '.editBtn', function () {
        const id = $(this).data('id');
        $.getJSON('api/health_records_api.php', { action:'get', type:HEALTH_RECORD_TYPE, id }, function (res) {
            if (res.status !== 'ok') { showAlert('Error', res.message||'Not found.', 'danger'); return; }
            const d = res.data;
            const m = d.meta || {};
            $('#edit_id').val(d.id);
            $('#edit_resident_id').val(d.resident_id);
            $('#edit_resident_name').val(d.resident_name || '');
            /* date yyyy-mm-dd → mm/dd/yyyy */
            if (d.consultation_date && d.consultation_date.includes('-')) {
                const p = d.consultation_date.split('-');
                $('#edit_date').val(p[1]+'/'+p[2]+'/'+p[0]);
            } else {
                $('#edit_date').val(d.consultation_date || '');
            }
            $('#edit_time').val(m.time || '');
            $('#edit_status').val(m.status || 'Completed');
            $('#edit_subtype').val((m.sub_type||'').replace(/_/g,' '));
            $('#edit_worker').val(m.health_worker || '');
            $('#edit_complaint').val(d.complaint || '');
            $('#edit_diagnosis').val(d.diagnosis || '');
            $('#edit_treatment').val(d.treatment || '');
            $('#edit_remarks').val(m.remarks || '');
            $('#editModal').dialog('option','title','Edit — ' + (d.resident_name||'')).dialog('open');
        });
    });

    function submitEdit() {
        $.ajax({
            url: 'api/health_records_api.php?action=update&type=' + encodeURIComponent(HEALTH_RECORD_TYPE),
            type: 'POST',
            data: $('#editForm').serialize() + '&id=' + $('#edit_id').val(),
            dataType: 'json',
            success: function (res) {
                if (res.status !== 'ok') { showAlert('Error', res.message||'Update failed.', 'danger'); return; }
                $('#editModal').dialog('close');
                showAlert('Saved', 'Record updated successfully.', 'success');
                loadRecords();
            },
            error: () => showAlert('Error', 'Request failed.', 'danger')
        });
    }

    /* ════════════════════════
       PRINT
    ════════════════════════ */
    $('#btnPrint').on('click', function () {
        const params = new URLSearchParams({
            type:   HEALTH_RECORD_TYPE,
            period: currentPeriod,
            month:  $('#monthPicker').val(),
            search: $('#searchInput').val(),
            sub:    $('#subTypeSelect').val(),
        });
        window.open('../print.php?' + params.toString(), '_blank');
    });

    /* ── Boot ── */
    /* set active period button */
    $(`.pb-btn[data-period="${INIT_FILTERS.period||'all'}"]`).addClass('active');
    if (INIT_FILTERS.period !== 'monthly') $('#monthPicker').css('opacity','.45');

    loadRecords();
    $('body').show();
});
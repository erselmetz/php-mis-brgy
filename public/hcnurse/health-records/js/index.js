/**
 * Health Records JS Controller
 * Replaces: public/hcnurse/health-records/js/index.js
 *
 * FIXES:
 * 1. Sub-types loaded from DB via list_subtypes API (not static)
 * 2. Immunization handled as a normal type tab (no separate page needed)
 * 3. All filter state preserved in URL
 */
$(function () {

    let currentPeriod = INIT_FILTERS.period || 'all';

    function esc(s) {
        const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML;
    }
    function showAlert(title, msg, type) {
        const col = type === 'success' ? 'var(--ok-fg)' : 'var(--danger-fg)';
        const id  = 'al_' + Date.now();
        $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
        </div>`);
        $(`#${id}`).dialog({ autoOpen:true, modal:true, width:400, resizable:false,
            buttons:{ 'OK': function(){ $(this).dialog('close').remove(); } } });
    }
    function statusBadge(s) {
        if (!s) return '—';
        const map = { 'Completed':'sb-completed','Ongoing':'sb-ongoing','Dismissed':'sb-dismissed','Follow-up':'sb-followup' };
        return `<span class="status-badge ${map[s]||'sb-dismissed'}">${esc(s)}</span>`;
    }

    /* ── Load sub-types from DB ── */
    function loadSubTypes(selected) {
        $.getJSON('api/health_records_api.php', { action:'list_subtypes', type:HEALTH_RECORD_TYPE }, function (res) {
            const subs = (res.status === 'ok' && res.data?.subtypes) ? res.data.subtypes : [{ value:'all', label:'All' }];
            const $sel = $('#subTypeSelect').empty();
            subs.forEach(s => $sel.append(`<option value="${s.value}">${s.label}</option>`));
            $sel.val(selected || INIT_FILTERS.sub || 'all');
        }).fail(function () {
            $('#subTypeSelect').empty().append('<option value="all">All</option>');
        });
    }

    /* ── DataTable ── */
    const table = $('#hrTable').DataTable({
        pageLength: 25, order: [[0, 'desc']], dom: 'tip',
        language: { info:'Showing _START_–_END_ of _TOTAL_ records', paginate:{ previous:'‹', next:'›' }, emptyTable:'' },
        columns: [
            { data:'consultation_date', render: d => `<span class="td-date">${esc(d||'—')}</span>` },
            { data:'resident_name',     render: d => `<span class="td-patient">${esc(d||'—')}</span>` },
            {
                data:'meta', render: m => {
                    const s = (m||{}).sub_type || '';
                    return s && s !== 'all'
                        ? `<span class="sub-badge">${esc(s.replace(/_/g,' '))}</span>`
                        : '<span style="color:var(--ink-faint);font-size:11px;">—</span>';
                }
            },
            { data:'complaint', render: d => `<div class="td-trunc">${esc(d||'—')}</div>` },
            { data:'meta', render: m => `<span style="font-size:12px;color:var(--ink-muted);">${esc((m||{}).health_worker||'—')}</span>` },
            { data:'meta', render: m => statusBadge((m||{}).status) },
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
            type: HEALTH_RECORD_TYPE, period: currentPeriod,
            month: $('#monthPicker').val(), search: $('#searchInput').val(),
            sub: $('#subTypeSelect').val(),
        };
        const url = new URL(window.location.href);
        url.searchParams.set('type', HEALTH_RECORD_TYPE);
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
            if (!data.length) { $('#hrEmptyState').show(); $('#hrTable').hide(); }
            else { $('#hrEmptyState').hide(); $('#hrTable').show(); data.forEach(r => table.row.add(r)); }
            table.draw();
        });
    }

    /* ── Period buttons ── */
    $(document).on('click', '.pb-btn[data-period]', function () {
        currentPeriod = $(this).data('period');
        $('.pb-btn[data-period]').removeClass('active'); $(this).addClass('active');
        $('#monthPicker').css('opacity', currentPeriod === 'monthly' ? '1' : '.45');
        loadRecords();
    });
    $('#monthPicker').on('change', () => { if (currentPeriod === 'monthly') loadRecords(); });
    $('#subTypeSelect').on('change', loadRecords);

    let st;
    $('#searchInput').on('input', () => { clearTimeout(st); st = setTimeout(loadRecords, 400); });
    $('#btnClear').on('click', () => {
        currentPeriod = 'all';
        $('.pb-btn[data-period]').removeClass('active'); $('[data-period="all"]').addClass('active');
        $('#monthPicker').val(new Date().toISOString().slice(0,7)).css('opacity','.45');
        $('#subTypeSelect').val('all');
        $('#searchInput').val('');
        loadRecords();
    });

    /* ── View Modal ── */
    $('#viewModal').dialog({ autoOpen:false, modal:true, width:820, resizable:true,
        buttons:{ 'Close': function(){ $(this).dialog('close'); } } });

    $(document).on('click', '.viewBtn', function () {
        const id = $(this).data('id');
        $.getJSON('api/health_records_api.php', { action:'get', type:HEALTH_RECORD_TYPE, id }, function (res) {
            if (res.status !== 'ok') { showAlert('Error', res.message||'Not found.', 'danger'); return; }
            const d = res.data, m = d.meta || {};
            $('#vm-name').text(d.resident_name || '—');
            $('#vm-sub').text('Record #'+String(d.id).padStart(5,'0')+' · '+(m.program||HEALTH_RECORD_TYPE).replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()));
            $('#vm-date').text(d.consultation_date || '—');
            $('#vm-time').text(m.time || '—');
            $('#vm-subtype').text((m.sub_type||'').replace(/_/g,' ') || '—');
            $('#vm-worker').text(m.health_worker || '—');
            $('#vm-complaint').text(d.complaint || '—');
            $('#vm-diagnosis').text(d.diagnosis || '—');
            $('#vm-treatment').text(d.treatment || '—');
            $('#vm-remarks').text(m.remarks || '—');
            $('#vm-status-wrap').html(statusBadge(m.status));
            $('#viewModal').dialog('option','title','Record — '+(d.resident_name||'')).dialog('open');
        });
    });

    /* ── Edit Modal ── */
    $('#editModal').dialog({ autoOpen:false, modal:true, width:720, resizable:false,
        buttons:{
            'Save Changes': function(){ submitEdit(); },
            'Cancel': function(){ $(this).dialog('close'); }
        }
    });
    if ($.fn.datepicker) {
        $('#edit_date').datepicker({ dateFormat:'mm/dd/yy', changeMonth:true, changeYear:true });
    }

    $(document).on('click', '.editBtn', function () {
        const id = $(this).data('id');
        $.getJSON('api/health_records_api.php', { action:'get', type:HEALTH_RECORD_TYPE, id }, function (res) {
            if (res.status !== 'ok') { showAlert('Error', res.message||'Not found.', 'danger'); return; }
            const d = res.data, m = d.meta || {};
            $('#edit_id').val(d.id);
            $('#edit_resident_id').val(d.resident_id);
            $('#edit_resident_name').val(d.resident_name || '');
            if (d.consultation_date && d.consultation_date.includes('-')) {
                const p = d.consultation_date.split('-');
                $('#edit_date').val(p[1]+'/'+p[2]+'/'+p[0]);
            } else { $('#edit_date').val(d.consultation_date || ''); }
            $('#edit_time').val(m.time || '');
            $('#edit_status').val(m.status || 'Completed');
            $('#edit_subtype').val((m.sub_type||'').replace(/_/g,' '));
            $('#edit_worker').val(m.health_worker || '');
            $('#edit_complaint').val(d.complaint || '');
            $('#edit_diagnosis').val(d.diagnosis || '');
            $('#edit_treatment').val(d.treatment || '');
            $('#edit_remarks').val(m.remarks || '');
            $('#editModal').dialog('option','title','Edit — '+(d.resident_name||'')).dialog('open');
        });
    });

    function submitEdit() {
        $.ajax({
            url: 'api/health_records_api.php?action=update&type='+encodeURIComponent(HEALTH_RECORD_TYPE),
            type: 'POST',
            data: $('#editForm').serialize()+'&id='+$('#edit_id').val(),
            dataType: 'json',
            success: function (res) {
                if (res.status !== 'ok') { showAlert('Error', res.message||'Update failed.', 'danger'); return; }
                $('#editModal').dialog('close');
                showAlert('Saved', 'Record updated.', 'success');
                loadSubTypes($('#subTypeSelect').val()); // refresh sub-types in case new value added
                loadRecords();
            },
            error: () => showAlert('Error', 'Request failed.', 'danger')
        });
    }

    /* ── Print ── */
    $('#btnPrint').on('click', function () {
        const p = new URLSearchParams({
            type: HEALTH_RECORD_TYPE, period: currentPeriod,
            month: $('#monthPicker').val(), search: $('#searchInput').val(),
            sub: $('#subTypeSelect').val(),
        });
        window.open('../print.php?' + p.toString(), '_blank');
    });

    /* ── Boot ── */
    $(`.pb-btn[data-period="${INIT_FILTERS.period||'all'}"]`).addClass('active');
    if (INIT_FILTERS.period !== 'monthly') $('#monthPicker').css('opacity','.45');
    loadSubTypes();
    loadRecords();
    $('body').show();
});
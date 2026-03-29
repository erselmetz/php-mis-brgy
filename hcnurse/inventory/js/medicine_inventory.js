/**
 * Medicine Inventory — JS Controller
 * Replaces: public/hcnurse/inventory/js/medicine_inventory.js
 */
$(function () {

    let medTable       = null;
    let currentEditId  = null;

    /* ── Helpers ── */
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
    function showAlert(title, msg, type) {
        const col = type === 'success' ? 'var(--ok-fg)' : type === 'danger' ? 'var(--danger-fg)' : 'var(--warn-fg)';
        const id  = 'al_' + Date.now();
        $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
        </div>`);
        $(`#${id}`).dialog({
            autoOpen:true, modal:true, width:400, resizable:false,
            buttons:{ 'OK': function(){ $(this).dialog('close').remove(); } }
        });
    }

    /* ════════════════════════
       STAT LEDGER
    ════════════════════════ */
    function updateStats(rows) {
        const total    = rows.length;
        let ok=0, crit=0, bad=0;
        rows.forEach(r => {
            const s = r.status || '';
            if (s === 'In-Stock')      ok++;
            else if (s === 'Critical') crit++;
            else                        bad++;   /* Out of Stock + Expired */
        });
        $('#slTotal').text(total);
        $('#slOk').text(ok);
        $('#slCritical').text(crit);
        $('#slBad').text(bad);
        $('#itemCount').text(total + (total === 1 ? ' ITEM' : ' ITEMS'));
    }

    /* ════════════════════════
       CATEGORIES
    ════════════════════════ */
    function loadCategories(selectedId) {
        $.getJSON('api/medicine_inventory_api.php?action=list_categories', function (res) {
            if (!res || res.status !== 'ok') return;
            const $sel = $('#medCategory');
            $sel.find('option:not(:first)').remove();
            res.data.forEach(c => $sel.append(`<option value="${c.id}">${esc(c.name)}</option>`));
            if (selectedId) $sel.val(String(selectedId));
        });
    }
    function loadReportCategories() {
        $.getJSON('api/medicine_inventory_api.php?action=list_categories', function (res) {
            if (!res || res.status !== 'ok') return;
            const $sel = $('#rptCategory');
            $sel.find('option:not(:first)').remove();
            res.data.forEach(c => $sel.append(`<option value="${c.id}">${esc(c.name)}</option>`));
        });
    }

    /* ════════════════════════
       DATATABLE
    ════════════════════════ */
    function initTable() {
        medTable = $('#medicineTable').DataTable({
            ajax: {
                url: 'api/medicine_inventory_api.php?action=list',
                dataSrc: function (json) {
                    if (!json || json.status !== 'ok') return [];
                    updateStats(json.data || []);
                    return json.data || [];
                }
            },
            dom: 'tip',
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                info: 'Showing _START_–_END_ of _TOTAL_ items',
                paginate: { previous:'‹', next:'›' },
                emptyTable: 'No medicines found.'
            },
            columns: [
                /* Item ID */
                {
                    data: 'medicine_code',
                    render: d => `<span class="td-item-id">${esc(d)}</span>`
                },
                /* Name + Category */
                {
                    data: 'name',
                    render: function (d, t, row) {
                        let html = `<div class="td-item-name">${esc(d)}</div>`;
                        if (row.category) html += `<div class="td-category">${esc(row.category)}</div>`;
                        return html;
                    }
                },
                /* Stock */
                {
                    data: 'stock_qty',
                    render: function (d, t, row) {
                        const qty = parseInt(d);
                        const re  = parseInt(row.reorder_level) || 10;
                        const cls = qty <= 0 ? 'qty-critical' : qty <= re ? 'qty-warn' : 'qty-ok';
                        return `<span class="td-qty ${cls}">${qty}</span>
                                <span style="font-size:10px;color:var(--ink-faint);margin-left:4px;">${esc(row.unit||'pcs')}</span>`;
                    }
                },
                /* Reorder */
                {
                    data: 'reorder_level',
                    render: d => `<span style="font-family:var(--f-mono);font-size:11.5px;color:var(--ink-muted);">${d}</span>`
                },
                /* Expiration */
                {
                    data: 'expiration_date',
                    render: function (d) {
                        if (!d) return '<span style="color:var(--ink-faint);font-size:11px;">—</span>';
                        const today = new Date().toISOString().slice(0,10);
                        const soon  = new Date(Date.now() + 30*864e5).toISOString().slice(0,10);
                        const cls   = d < today ? 'exp-danger' : d <= soon ? 'exp-warn' : '';
                        return `<span class="td-expiry ${cls}">${esc(d)}</span>`;
                    }
                },
                /* Status */
                {
                    data: 'status',
                    render: function (d) {
                        const map = {
                            'In-Stock':     'ss-instock',
                            'Critical':     'ss-critical',
                            'Out of Stock': 'ss-outstock',
                            'Expired':      'ss-expired',
                        };
                        return `<span class="status-stamp ${map[d]||'ss-outstock'}">${esc(d||'Unknown')}</span>`;
                    }
                },
                /* Actions */
                {
                    data: null, orderable: false,
                    render: function (d, t, row) {
                        return `<div class="td-actions">
                            <button class="act-btn act-edit editMedBtn" data-id="${row.id}">Edit</button>
                        </div>`;
                    }
                }
            ]
        });

        /* live search */
        let searchTimer;
        $('#searchInput').on('input', function () {
            clearTimeout(searchTimer);
            const q = $(this).val();
            searchTimer = setTimeout(() => {
                medTable.ajax.url('api/medicine_inventory_api.php?action=list&search=' + encodeURIComponent(q)).load();
            }, 350);
        });
    }

    /* ════════════════════════
       ADD MODAL
    ════════════════════════ */
    $('#medicineModal').dialog({
        autoOpen: false, modal: true, width: 560, resizable: false,
        open: function(){
            // prevent jQuery UI from auto-focusing first input (causes flash)
            $(this).find(':input:first').blur();
        }
    });

    $('#btnAddMedicine').on('click', function () {
        currentEditId = null;
        $('#medicineForm')[0].reset();
        $('#medId').val('');
        loadCategories(null);
        $('#medSubmitBtn').text('+ Add Item');
        $('#medicineModal').dialog('option','title','Add Medicine').dialog('open');
    });

    /* Edit button */
    $(document).on('click', '.editMedBtn', function () {
        const id = $(this).data('id');
        currentEditId = id;
        $.getJSON('api/medicine_inventory_api.php?action=get&id=' + id, function (res) {
            if (!res || res.status !== 'ok') return;
            const m = res.data;
            $('#medId').val(m.id);
            $('#medName').val(m.name || '');
            $('#medDesc').val(m.description || '');
            $('#medStock').val(m.stock_qty);
            $('#medReorder').val(m.reorder_level);
            $('#medUnit').val(m.unit || '');
            $('#medExpiry').val(m.expiration_date || '');
            loadCategories(m.category_id || '');
            $('#medSubmitBtn').text('Save Changes');
            $('#medicineModal').dialog('option','title','Edit — ' + (m.name||'')).dialog('open');
        });
    });

    /* Submit */
    $('#medicineForm').on('submit', function (e) {
        e.preventDefault();
        const action = currentEditId ? 'update' : 'add';
        $.post('api/medicine_inventory_api.php?action=' + action, $(this).serialize(), function (res) {
            const json = typeof res === 'string' ? JSON.parse(res) : res;
            if (json.status === 'ok') {
                $('#medicineModal').dialog('close');
                medTable.ajax.reload(null, false);
                showAlert('Saved', json.message || 'Item saved.', 'success');
            } else {
                showAlert('Error', json.message || 'Save failed.', 'danger');
            }
        }).fail(xhr => showAlert('Error', 'Request failed. ' + (xhr.responseText||''), 'danger'));
    });

    /* ════════════════════════
       CATEGORY MODAL
    ════════════════════════ */
    $('#categoryModal').dialog({
        autoOpen: false, modal: true, width: 440, resizable: false
    });
    $('#btnAddCategory').on('click', () => {
        $('#categoryForm')[0].reset();
        $('#categoryModal').dialog('open');
    });
    $('#categoryForm').on('submit', function (e) {
        e.preventDefault();
        $.post('api/medicine_inventory_api.php?action=add_category', $(this).serialize(), function (res) {
            const json = typeof res === 'string' ? JSON.parse(res) : res;
            if (json.status === 'ok') {
                $('#categoryModal').dialog('close');
                loadCategories(json.data?.id);
                showAlert('Category Added', json.message || 'Category saved.', 'success');
            } else {
                showAlert('Error', json.message || 'Failed.', 'danger');
            }
        });
    });

    /* ════════════════════════
       REPORT MODAL
    ════════════════════════ */
    $('#reportModal').dialog({
        autoOpen: false, modal: true, width: 480, resizable: false,
        open: function(){ loadReportCategories(); }
    });
    $('#btnReport').on('click', () => $('#reportModal').dialog('open'));

    $('#reportPreviewModal').dialog({
        autoOpen: false, modal: true, width: 960, resizable: false
    });

    function buildReportQS() {
        return $('#reportForm').serialize();
    }

    /* Preview */
    $('#rptPreviewBtn').on('click', function () {
        $.getJSON('api/medicine_inventory_api.php?action=report&' + buildReportQS(), function (res) {
            if (!res || res.status !== 'ok') { showAlert('Error', 'Failed to load report.', 'danger'); return; }

            const payload = res.data ? res.data : res;
            const rows    = Array.isArray(payload.rows) ? payload.rows : [];

            /* compute summary */
            let total=0, oos=0, crit=0, ok=0, expSoon=0;
            rows.forEach(r => {
                total++;
                const s = (r.stock_status||'').toUpperCase();
                if (s==='OUT_OF_STOCK')  oos++;
                else if (s==='CRITICAL') crit++;
                else                      ok++;
                if (parseInt(r.is_expiring_soon,10)===1) expSoon++;
            });

            $('#rptSummaryBar').html(
                `<span>Total: <strong>${total}</strong></span>
                 <span>Out of Stock: <strong>${oos}</strong></span>
                 <span>Critical: <strong>${crit}</strong></span>
                 <span>OK: <strong>${ok}</strong></span>
                 <span>Expiring Soon: <strong>${expSoon}</strong></span>`
            );

            /* render rows */
            let html = '';
            rows.forEach(r => {
                const sBadge = r.stock_status === 'OUT_OF_STOCK'
                    ? 'ss-outstock' : r.stock_status === 'CRITICAL'
                    ? 'ss-critical' : 'ss-instock';
                const expHtml = parseInt(r.is_expiring_soon,10) === 1
                    ? '<span style="color:var(--danger-fg);font-weight:700;">YES</span>'
                    : '<span style="color:var(--ink-faint);">No</span>';
                html += `<tr>
                    <td style="font-weight:600;">${esc(r.name||'')}</td>
                    <td style="color:var(--ink-muted);">${esc(r.category_name||'—')}</td>
                    <td style="font-family:var(--f-mono);">${r.stock_qty}</td>
                    <td style="font-family:var(--f-mono);">${r.reorder_level}</td>
                    <td style="color:var(--ink-faint);">${esc(r.unit||'pcs')}</td>
                    <td style="font-family:var(--f-mono);font-size:11px;">${esc(r.expiration_date||'—')}</td>
                    <td><span class="status-stamp ${sBadge}">${esc(r.stock_status)}</span></td>
                    <td>${expHtml}</td>
                </tr>`;
            });

            $('#rptTableBody').html(html || '<tr><td colspan="8" style="padding:20px;text-align:center;color:var(--ink-faint);">No data.</td></tr>');
            $('#reportPreviewModal').dialog('open');
        }).fail(() => showAlert('Error', 'Request failed.', 'danger'));
    });

    /* Print */
    $('#rptPrintBtn').on('click', function () {
        /* open print page in hidden iframe so it auto-prints */
        let $f = $('#invPrintFrame');
        if (!$f.length) {
            $f = $('<iframe id="invPrintFrame" style="display:none;"></iframe>');
            $('body').append($f);
        }
        $f.attr('src', 'reports/medicine_inventory_print.php?' + buildReportQS());
    });

    /* ════════════════════════
       BOOT
    ════════════════════════ */
    loadCategories();
    initTable();
    $('body').show();
});
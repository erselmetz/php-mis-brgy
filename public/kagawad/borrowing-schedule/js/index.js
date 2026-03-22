/**
 * Equipment / Items Borrowing Schedule — JS Controller
 * public/secretary/borrowing-schedule/js/index.js
 *
 * Key changes from original:
 *  - inventory_id now read from #inventoryItemId (hidden input, name="inventory_id")
 *    instead of old #inventoryItemSelect (removed)
 *  - borrowId has name="id" so serialize() includes it automatically
 *  - edit form repopulates via window.setInvItem() (defined in index.php inline script)
 */
$(function () {
    let borrowTable;

    /* ─── Status badge classes ─── */
    const statusBadge = {
        borrowed:  'bs-borrowed',
        returned:  'bs-returned',
        overdue:   'bs-overdue',
        cancelled: 'bs-cancelled',
    };

    /* ─── Helpers ─── */
    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
    function fmtDate(d) {
        if (!d) return '—';
        return new Date(d + 'T00:00:00').toLocaleDateString('en-PH',
            { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function showMsg(title, msg, isError) {
        const id = 'bm_' + Date.now();
        const col = isError ? 'var(--danger-fg)' : 'var(--ok-fg)';
        $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);
                        border-left:3px solid ${col};background:var(--paper);">
                ${esc(msg)}
            </div>
        </div>`);
        $(`#${id}`).dialog({
            autoOpen: true, modal: true, width: 400, resizable: false,
            buttons: { 'OK': function () { $(this).dialog('close').remove(); } }
        });
    }

    /* ═══════════════════════════════════════
       DATATABLE
    ═══════════════════════════════════════ */
    function initTable() {
        borrowTable = $('#borrowTable').DataTable({
            ajax: {
                url: 'actions/borrow_api.php?action=list',
                dataSrc: function (json) {
                    if (json.status === 'ok') {
                        updateCounts(json.counts || {});
                        return json.data || [];
                    }
                    return [];
                }
            },
            dom: 'tip',
            order: [[4, 'desc']],
            pageLength: 25,
            language: {
                info:     'Showing _START_–_END_ of _TOTAL_ records',
                paginate: { previous: '‹', next: '›' },
                emptyTable: 'No borrowing records found.'
            },
            columns: [
                {
                    data: 'borrow_code',
                    render: d => `<span style="font-family:var(--f-mono);font-size:11px;font-weight:700;color:var(--accent);">${esc(d)}</span>`
                },
                { data: 'borrower_name', render: d => `<span style="font-weight:500;">${esc(d)}</span>` },
                {
                    data: null,
                    render: (d, t, row) => {
                        let html = `<span style="font-weight:500;">${esc(row.item_name)}</span>`;
                        if (row.asset_code) html += `<br><span style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);">${esc(row.asset_code)}</span>`;
                        return html;
                    }
                },
                { data: 'quantity', render: d => `<span style="font-family:var(--f-mono);font-size:13px;font-weight:600;">${d}</span>` },
                { data: 'borrow_date', render: d => `<span style="font-family:var(--f-mono);font-size:11.5px;">${fmtDate(d)}</span>` },
                {
                    data: 'return_date',
                    render: (d, t, row) => {
                        const str = fmtDate(d);
                        const overdue = row.status === 'overdue' ||
                            (row.status === 'borrowed' && d && new Date(d) < new Date());
                        return overdue
                            ? `<span style="color:var(--danger-fg);font-weight:600;font-family:var(--f-mono);font-size:11.5px;">${str} ⚠</span>`
                            : `<span style="font-family:var(--f-mono);font-size:11.5px;">${str}</span>`;
                    }
                },
                {
                    data: 'actual_return',
                    render: d => d
                        ? `<span style="font-family:var(--f-mono);font-size:11.5px;">${fmtDate(d)}</span>`
                        : `<span style="color:var(--ink-faint);font-size:11px;">Not yet</span>`
                },
                {
                    data: 'status',
                    render: d => `<span class="brw-status ${statusBadge[d] || ''}">${capitalize(d)}</span>`
                },
                {
                    data: null, orderable: false,
                    render: (d, t, row) => {
                        return `<div style="display:flex;gap:5px;">
                            <button class="act-btn act-view view-borrow-btn"  data-id="${row.id}">View</button>
                        </div>`;
                    }
                }
            ]
        });
    }

    function updateCounts(c) {
        $('#countBorrowed').text(c.borrowed  ?? 0);
        $('#countReturned').text(c.returned  ?? 0);
        $('#countOverdue').text(c.overdue   ?? 0);
        $('#countCancelled').text(c.cancelled ?? 0);
    }

    function reloadTable() {
        const stat = $('#filterStatus').val();
        const date = $('#filterDate').val();
        const srch = $('#searchInput').val();
        let url = 'actions/borrow_api.php?action=list';
        if (stat) url += '&filter_status=' + encodeURIComponent(stat);
        if (date) url += '&filter_date=' + date;
        if (srch) url += '&search=' + encodeURIComponent(srch);
        borrowTable.ajax.url(url).load();
    }

    $('#filterStatus, #filterDate').on('change', reloadTable);
    let searchTimer;
    $('#searchInput').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reloadTable, 350);
    });

    /* ═══════════════════════════════════════
       ADD / EDIT DIALOG
    ═══════════════════════════════════════ */
    $('#borrowDialog').dialog({
        autoOpen: false, modal: true, width: 620, resizable: false,
        buttons: {
            'Save Record': function () { saveBorrow(); },
            'Cancel':      function () { $(this).dialog('close'); }
        }
    });

    $('#btnNewBorrow').on('click', function () {
        resetBorrowForm();
        $('#borrowDialog').dialog('option', 'title', 'New Borrowing Entry').dialog('open');
    });

    function resetBorrowForm() {
        $('#borrowForm')[0].reset();
        $('#borrowId').val('');
        $('#inventoryItemId').val('');           // clear hidden inventory_id
        $('#itemName').val('');
        $('#borrowDate').val(new Date().toISOString().split('T')[0]);
        $('#borrowStatus').val('borrowed');
        // The inline script's clearSelection() handles the tag + hint
        if (window.clearBorrowInvSelection) window.clearBorrowInvSelection();
    }

    /* ── Save (Insert or Update) ── */
    function saveBorrow() {
        const borrower  = $.trim($('#borrowerName').val());
        const itemName  = $.trim($('#itemName').val());
        const borrowDate = $('#borrowDate').val();
        const returnDate = $('#returnDate').val();

        if (!borrower || !itemName || !borrowDate || !returnDate) {
            showMsg('Validation', 'Borrower Name, Item Name, Borrow Date and Return Date are required.', true);
            return;
        }

        // Serialize the form — because borrowId has name="id" and
        // inventoryItemId has name="inventory_id", both are included automatically
        const formData = $('#borrowForm').serialize();

        $.post('actions/borrow_api.php?action=save', formData, function (res) {
            if (res.success) {
                $('#borrowDialog').dialog('close');
                reloadTable();
                showMsg('Saved', res.message);
            } else {
                showMsg('Error', res.message, true);
            }
        }, 'json').fail(() => showMsg('Error', 'Request failed. Please try again.', true));
    }

    /* ═══════════════════════════════════════
       VIEW DIALOG
    ═══════════════════════════════════════ */
    $('#viewBorrowDialog').dialog({
        autoOpen: false, modal: true, width: 500, resizable: false,
        buttons: { 'Close': function () { $(this).dialog('close'); } }
    });

    $('#borrowTable').on('click', '.view-borrow-btn', function () {
        const row = borrowTable.row($(this).closest('tr')).data();
        if (!row) return;
        $('#viewBorrowContent').html(`
            <div style="font-size:13px;">
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--rule);padding-bottom:10px;margin-bottom:12px;">
                    <span style="font-weight:700;color:var(--ink-faint);">Borrow Code</span>
                    <span style="font-family:var(--f-mono);font-weight:700;color:var(--accent);">${esc(row.borrow_code)}</span>
                </div>
                ${viewRow('Borrower',        esc(row.borrower_name))}
                ${viewRow('Contact',         esc(row.borrower_contact || '—'))}
                ${viewRow('Item',            `<strong>${esc(row.item_name)}</strong>${row.asset_code ? ' <span style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);">(' + esc(row.asset_code) + ')</span>' : ''}`)}
                ${viewRow('Quantity',        row.quantity)}
                ${viewRow('Borrow Date',     fmtDate(row.borrow_date))}
                ${viewRow('Expected Return', fmtDate(row.return_date))}
                ${viewRow('Actual Return',   fmtDate(row.actual_return))}
                ${viewRow('Condition Out',   esc(row.condition_out || '—'))}
                ${viewRow('Condition In',    esc(row.condition_in  || '—'))}
                ${viewRow('Purpose',         esc(row.purpose || '—'))}
                ${viewRow('Status',          `<span class="brw-status ${statusBadge[row.status] || ''}">${capitalize(row.status)}</span>`)}
                ${viewRow('Notes',           esc(row.notes || '—'))}
                <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--rule);font-size:10.5px;color:var(--ink-faint);">
                    Created by: ${esc(row.created_by_name || '—')} · ${row.created_at || ''}
                    ${row.updated_by_name ? '<br>Updated by: ' + esc(row.updated_by_name) + ' · ' + (row.updated_at || '') : ''}
                </div>
            </div>
        `);
        $('#viewBorrowDialog').dialog('option', 'title', 'Borrowing — ' + row.borrow_code).dialog('open');
    });

    function viewRow(label, value) {
        return `<div style="display:flex;justify-content:space-between;margin-bottom:7px;gap:12px;">
                    <span style="color:var(--ink-faint);flex-shrink:0;">${label}</span>
                    <span style="text-align:right;">${value}</span>
                </div>`;
    }

    /* ═══════════════════════════════════════
       EDIT
    ═══════════════════════════════════════ */
    $('#borrowTable').on('click', '.edit-borrow-btn', function () {
        const row = borrowTable.row($(this).closest('tr')).data();
        if (!row) return;

        resetBorrowForm();
        $('#borrowId').val(row.id);
        $('#borrowerName').val(row.borrower_name);
        $('#borrowerContact').val(row.borrower_contact || '');
        $('#itemName').val(row.item_name);
        $('#borrowQty').val(row.quantity);
        $('#borrowDate').val(row.borrow_date);
        $('#returnDate').val(row.return_date);
        $('#actualReturn').val(row.actual_return || '');
        $('#borrowPurpose').val(row.purpose || '');
        $('#borrowStatus').val(row.status);
        $('#conditionOut').val(row.condition_out || '');
        $('#conditionIn').val(row.condition_in  || '');
        $('#borrowNotes').val(row.notes || '');

        // Repopulate the inventory search tag if linked to an inventory item
        if (row.inventory_id) {
            $('#inventoryItemId').val(row.inventory_id);
            if (window.setInvItem) {
                window.setInvItem(row.inventory_id, row.item_name, row.asset_code || '');
            }
        }

        $('#borrowDialog').dialog('option', 'title', 'Edit Borrowing — ' + row.borrow_code).dialog('open');
    });

    /* ═══════════════════════════════════════
       QUICK RETURN
    ═══════════════════════════════════════ */
    $('#borrowTable').on('click', '.return-btn', function () {
        const row = borrowTable.row($(this).closest('tr')).data();
        if (!row) return;

        const dlgId = 'ret_' + Date.now();
        let condIn = '';

        $('body').append(`<div id="${dlgId}" title="Mark as Returned" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);">
                <p style="margin-bottom:12px;">Mark <strong>${esc(row.borrow_code)}</strong> as returned?</p>
                <label style="display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:5px;">
                    Condition on Return
                </label>
                <input type="text" id="${dlgId}_cond" class="fg-input"
                    placeholder="e.g. Good condition, minor scratches…"
                    style="width:100%;padding:8px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-size:13px;">
            </div>
        </div>`);

        $(`#${dlgId}`).dialog({
            autoOpen: true, modal: true, width: 440, resizable: false,
            buttons: {
                'Confirm Return': function () {
                    condIn = $(`#${dlgId}_cond`).val();
                    $(this).dialog('close').remove();
                    $.post('actions/borrow_api.php?action=mark_returned', {
                        id:           row.id,
                        condition_in: condIn
                    }, function (res) {
                        if (res.success) { reloadTable(); showMsg('Returned', res.message); }
                        else showMsg('Error', res.message, true);
                    }, 'json');
                },
                'Cancel': function () { $(this).dialog('close').remove(); }
            }
        });
    });

    /* ═══════════════════════════════════════
       PRINT
    ═══════════════════════════════════════ */
    $('#btnPrintBorrow').on('click', function () {
        const stat = $('#filterStatus').val() || '';
        const date = $('#filterDate').val() || '';
        window.open('print/print_borrow.php?filter_status=' + stat + '&filter_date=' + date, '_blank');
    });

    /* ═══════════════════════════════════════
       BOOT
    ═══════════════════════════════════════ */
    initTable();
    document.body.style.display = '';
});
/**
 * Equipment / Items Borrowing Schedule - JS Controller
 */
$(function () {
    let borrowTable;

    const statusBadge = {
        borrowed: 'bg-blue-100 text-blue-800',
        returned: 'bg-green-100 text-green-800',
        overdue: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-500'
    };

    // ── Init DataTable ──────────────────────────────────────────────────────
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
            columns: [
                { data: 'borrow_code', title: 'Code' },
                { data: 'borrower_name', title: 'Borrower' },
                { data: 'item_name', title: 'Item' },
                { data: 'quantity', title: 'Qty', className: 'dt-center' },
                {
                    data: 'borrow_date', title: 'Borrow Date',
                    render: d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
                },
                {
                    data: 'return_date', title: 'Return Date',
                    render: function (d, t, row) {
                        if (!d) return '—';
                        const dt = new Date(d + 'T00:00:00');
                        const str = dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
                        const isOverdue = row.status === 'overdue' || (row.status === 'borrowed' && new Date(d) < new Date());
                        return isOverdue ? `<span class="text-red-600 font-semibold">${str} ⚠️</span>` : str;
                    }
                },
                {
                    data: 'actual_return', title: 'Actual Return',
                    render: d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '<span class="text-gray-400">Not yet</span>'
                },
                {
                    data: 'status', title: 'Status',
                    render: d => `<span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge[d] || ''}">${capitalize(d)}</span>`
                },
                {
                    data: null, title: 'Actions', orderable: false,
                    render: function (d, t, row) {
                        return `<div class="flex gap-1">
                            <button class="view-borrow-btn text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded" data-id="${row.id}">View</button>
                            <button class="edit-borrow-btn text-xs px-2 py-1 bg-yellow-100 text-yellow-700 rounded" data-id="${row.id}">Edit</button>
                        </div>`;
                    }
                }
            ],
            order: [[4, 'desc']],
            responsive: true,
            pageLength: 25,
            language: { emptyTable: 'No borrowing records found.' }
        });
    }

    function updateCounts(c) {
        $('#countBorrowed').text(c.borrowed ?? 0);
        $('#countReturned').text(c.returned ?? 0);
        $('#countOverdue').text(c.overdue ?? 0);
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
    $('#searchInput').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reloadTable, 400);
    });

    // ── Inventory item auto-fill ─────────────────────────────────────────────
    $('#inventoryItemSelect').on('change', function () {
        const name = $(this).find(':selected').data('name') || '';
        if (name) $('#itemName').val(name);
    });

    // ── Add/Edit Dialog ──────────────────────────────────────────────────────
    $('#borrowDialog').dialog({
        autoOpen: false, modal: true, width: 600,
        buttons: {
            'Save Record': function () { saveBorrow(); },
            'Cancel': function () { $(this).dialog('close'); }
        }
    });

    $('#btnNewBorrow').on('click', function () {
        resetBorrowForm();
        $('#borrowDialog').dialog('option', 'title', 'New Borrowing Entry').dialog('open');
    });

    function resetBorrowForm() {
        $('#borrowId').val('');
        $('#borrowerName, #borrowerContact, #itemName, #borrowPurpose, #borrowNotes').val('');
        $('#conditionOut, #conditionIn, #actualReturn').val('');
        $('#inventoryItemSelect').val('');
        $('#borrowQty').val(1);
        $('#borrowDate').val(new Date().toISOString().split('T')[0]);
        $('#returnDate').val('');
        $('#borrowStatus').val('borrowed');
    }

    function saveBorrow() {
        const borrower = $.trim($('#borrowerName').val());
        const itemName = $.trim($('#itemName').val());
        const borrowDate = $('#borrowDate').val();
        const returnDate = $('#returnDate').val();

        if (!borrower || !itemName || !borrowDate || !returnDate) {
            showMsg('Validation', 'Borrower Name, Item Name, Borrow Date and Return Date are required.', true);
            return;
        }

        $.post('actions/borrow_api.php?action=save', {
            id: $('#borrowId').val(),
            borrower_name: borrower,
            borrower_contact: $('#borrowerContact').val(),
            item_name: itemName,
            inventory_id: $('#inventoryItemSelect').val() || 0,
            quantity: $('#borrowQty').val(),
            borrow_date: borrowDate,
            return_date: returnDate,
            actual_return: $('#actualReturn').val(),
            purpose: $('#borrowPurpose').val(),
            status: $('#borrowStatus').val(),
            condition_out: $('#conditionOut').val(),
            condition_in: $('#conditionIn').val(),
            notes: $('#borrowNotes').val()
        }, function (res) {
            if (res.success) {
                $('#borrowDialog').dialog('close');
                reloadTable();
                showMsg('Success', res.message);
            } else {
                showMsg('Error', res.message, true);
            }
        }, 'json').fail(() => showMsg('Error', 'Request failed. Please try again.', true));
    }

    // ── View Dialog ──────────────────────────────────────────────────────────
    $('#viewBorrowDialog').dialog({
        autoOpen: false, modal: true, width: 500,
        buttons: { 'Close': function () { $(this).dialog('close'); } }
    });

    $('#borrowTable').on('click', '.view-borrow-btn', function () {
        const row = borrowTable.row($(this).closest('tr')).data();
        if (!row) return;
        const fmtDate = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) : '—';
        $('#viewBorrowContent').html(`
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-semibold text-gray-500">Borrow Code</span>
                    <span class="font-bold text-blue-700">${row.borrow_code}</span>
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Borrower</span><span>${escHtml(row.borrower_name)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Contact</span><span>${escHtml(row.borrower_contact || '—')}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Item</span><span class="font-medium">${escHtml(row.item_name)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Quantity</span><span>${row.quantity}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Borrow Date</span><span>${fmtDate(row.borrow_date)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Expected Return</span><span>${fmtDate(row.return_date)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Actual Return</span><span>${fmtDate(row.actual_return)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Condition (Out)</span><span>${escHtml(row.condition_out || '—')}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Condition (In)</span><span>${escHtml(row.condition_in || '—')}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Purpose</span><span>${escHtml(row.purpose || '—')}</span></div>
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
        $('#viewBorrowDialog').dialog('option', 'title', 'Borrowing — ' + row.borrow_code).dialog('open');
    });

    // ── Edit ─────────────────────────────────────────────────────────────────
    $('#borrowTable').on('click', '.edit-borrow-btn', function () {
        const row = borrowTable.row($(this).closest('tr')).data();
        if (!row) return;
        $('#borrowId').val(row.id);
        $('#borrowerName').val(row.borrower_name);
        $('#borrowerContact').val(row.borrower_contact || '');
        $('#itemName').val(row.item_name);
        $('#inventoryItemSelect').val(row.inventory_id || '');
        $('#borrowQty').val(row.quantity);
        $('#borrowDate').val(row.borrow_date);
        $('#returnDate').val(row.return_date);
        $('#actualReturn').val(row.actual_return || '');
        $('#borrowPurpose').val(row.purpose || '');
        $('#borrowStatus').val(row.status);
        $('#conditionOut').val(row.condition_out || '');
        $('#conditionIn').val(row.condition_in || '');
        $('#borrowNotes').val(row.notes || '');
        $('#borrowDialog').dialog('option', 'title', 'Edit Borrowing — ' + row.borrow_code).dialog('open');
    });

    // ── Quick Return ─────────────────────────────────────────────────────────
    $('#borrowTable').on('click', '.return-btn', function () {
        const row = borrowTable.row($(this).closest('tr')).data();
        if (!row) return;
        const condIn = prompt('Item returned. Enter condition on return (optional):', 'Good condition');
        if (condIn === null) return; // cancelled
        $.post('actions/borrow_api.php?action=mark_returned', {
            id: row.id,
            condition_in: condIn
        }, function (res) {
            if (res.success) { reloadTable(); showMsg('Returned', res.message); }
            else showMsg('Error', res.message, true);
        }, 'json');
    });


    // ── Print ────────────────────────────────────────────────────────────────
    $('#btnPrintBorrow').on('click', function () {
        const stat = $('#filterStatus').val() || '';
        const date = $('#filterDate').val() || '';
        window.open('print/print_borrow.php?filter_status=' + stat + '&filter_date=' + date, '_blank');
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
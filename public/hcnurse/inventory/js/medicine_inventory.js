// js/medicine_inventory.js
$(function () {
    let medTable;
    let currentEditId = null;

    function showMessage(title, message, isError) {
        const $d = $('<div class="p-4 text-sm">' + message + '</div>');
        $d.dialog({
            modal: true,
            title: title,
            width: 420,
            resizable: false,
            buttons: { OK: function () { $(this).dialog('close').remove(); } },
            classes: {
                "ui-dialog": "rounded-lg shadow-lg",
                "ui-dialog-titlebar": (isError ? "bg-red-600" : "bg-theme-primary") + " text-white",
                "ui-dialog-title": "font-semibold",
                "ui-dialog-buttonpane": "bg-gray-50"
            }
        });
    }

    function loadCategories(selectedId) {
        $.getJSON('api/medicine_inventory_api.php?action=list_categories', function (res) {

            if (!res || res.status !== 'ok' || !Array.isArray(res.data)) {
                console.log('Invalid response:', res);
                showDialog('Invalid response:', res);
                return;
            }

            const $sel = $('#medicineCategory');
            $sel.empty().append('<option value="">Select Category</option>');

            res.data.forEach(function (c) {
                $sel.append($('<option>').val(c.id).text(c.name));
            });

            if (selectedId) $sel.val(String(selectedId));
        });
    }

    function initTable() {
        medTable = $('#medicineTable').DataTable({
            ajax: {
                url: 'api/medicine_inventory_api.php?action=list',
                dataSrc: function (json) {
                    if (!json || json.status !== 'ok') return [];
                    return json.data || [];
                }
            },
            columns: [
                { data: 'medicine_code', title: 'Item ID' },
                { data: 'name', title: 'Item Name' },
                { data: 'category', title: 'Category', defaultContent: '-' },
                { data: 'stock_qty', title: 'Stock' },
                { data: 'expiration_date', title: 'Expiration Date', defaultContent: '-' },
                {
                    data: 'status', title: 'Status',
                    render: function (data) {
                        const map = {
                            'In-Stock': 'text-green-600',
                            'Critical': 'text-red-600',
                            'Out of Stock': 'text-red-600',
                            'Expired': 'text-gray-600'
                        };
                        return '<span class="' + (map[data] || '') + '">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: null, title: 'Actions', orderable: false,
                    render: function (row) {
                        return '<button class="editMedBtn text-blue-600 hover:text-blue-800 text-xs px-2 py-1" data-id="' + row.id + '">Edit</button>';
                    }
                }
            ],
            order: [[0, 'desc']],
            responsive: true,
            pageLength: 25,
            language: { emptyTable: "No medicines found" },
            pageLength: 20,
            lengthChange: false,
            info: false,
            dom: 'rt<"flex items-center justify-between mt-4"p>', // hide default search
        });

        $('#searchInput').on('keyup', function () {
            medTable.search(this.value).draw();
        });
    }

    // dialogs
    $("#medicineModal").dialog({
        autoOpen: false,
        modal: true,
        width: 560,
        resizable: false,
        draggable: true,
        classes: {
            "ui-dialog": "rounded-lg shadow-lg",
            "ui-dialog-titlebar": "bg-theme-primary text-white",
            "ui-dialog-title": "font-semibold",
            "ui-dialog-titlebar-close": "text-white"
        },
        open: function () {
            // prevent auto-focus
            $(this).closest('.ui-dialog').find('button, input, textarea, select').blur();
        }
    });

    $("#categoryModal").dialog({
        autoOpen: false,
        modal: true,
        width: 420,
        resizable: false,
        classes: {
            "ui-dialog": "rounded-lg shadow-lg",
            "ui-dialog-titlebar": "bg-theme-primary text-white",
            "ui-dialog-title": "font-semibold",
            "ui-dialog-titlebar-close": "text-white"
        }
    });

    // datepicker (YYYY-MM-DD)
    $("#expiration_date").datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });

    // buttons
    $("#addMedicineBtn").on("click", function () {
        currentEditId = null;
        $("#medicineForm")[0].reset();
        $("#medicineId").val('');
        loadCategories(null);
        $("#medicineModal").dialog("option", "title", "Add Item");
        $("#submitMedicine").text("Add Item");
        $("#medicineModal").dialog("open");
    });

    $("#addCategoryBtn").on("click", function () {
        $("#categoryForm")[0].reset();
        $("#categoryModal").dialog("option", "title", "Add Category");
        $("#submitCategory").text("Add Category");
        $("#categoryModal").dialog("open");
    });

    // search
    let t;
    $('#searchInput').on('keyup', function () {
        clearTimeout(t);
        const q = $(this).val();
        t = setTimeout(function () {
            medTable.ajax.url('api/medicine_inventory_api.php?action=list&search=' + encodeURIComponent(q)).load();
        }, 400);
    });

    // edit
    $(document).on('click', '.editMedBtn', function () {
        const id = $(this).data('id');
        currentEditId = id;

        $.getJSON('api/medicine_inventory_api.php?action=get&id=' + id, function (res) {
            if (!res || res.status !== 'ok') return;

            const m = res.data;
            $("#medicineId").val(m.id);
            $("#medicineName").val(m.name);
            $("#medicineDesc").val(m.description || '');
            $("#stock_qty").val(m.stock_qty);
            $("#reorder_level").val(m.reorder_level);
            $("#unit").val(m.unit || 'pcs');
            $("#expiration_date").val(m.expiration_date || '');

            loadCategories(m.category_id || '');

            $("#medicineModal").dialog("option", "title", "Edit Item");
            $("#submitMedicine").text("Update Item");
            $("#medicineModal").dialog("open");
        });
    });

    // submit medicine
    $('#medicineForm').on('submit', function (e) {
        e.preventDefault();
        const action = currentEditId ? 'update' : 'add';

        $.post('api/medicine_inventory_api.php?action=' + action, $(this).serialize(), function (resp) {
            const json = (typeof resp === 'string') ? JSON.parse(resp) : resp;
            if (json.status === 'ok') {
                $("#medicineModal").dialog('close');
                medTable.ajax.reload(null, false);
                showMessage('Success', json.message || 'Saved');
            } else {
                showMessage('Error', json.message || 'Failed', true);
            }
        }).fail(function (xhr) {
            showMessage('Error', 'Request failed. ' + (xhr.responseText || ''), true);
        });
    });

    // submit category
    $('#categoryForm').on('submit', function (e) {
        e.preventDefault();

        $.post('api/medicine_inventory_api.php?action=add_category', $(this).serialize(), function (resp) {
            const json = (typeof resp === 'string') ? JSON.parse(resp) : resp;
            if (json.status === 'ok') {
                $("#categoryModal").dialog('close');
                loadCategories(json.data.id);
                showMessage('Success', json.message || 'Category added');
            } else {
                showMessage('Error', json.message || 'Failed', true);
            }
        }).fail(function (xhr) {
            showMessage('Error', 'Request failed. ' + (xhr.responseText || ''), true);
        });
    });

    // init
    loadCategories();
    initTable();

    $("#medicineReportPreviewModal").dialog({
        autoOpen: false,
        modal: true,
        width: 980,
        resizable: false,
        draggable: true,
        classes: {
            "ui-dialog": "rounded-lg shadow-lg",
            "ui-dialog-titlebar": "bg-theme-primary text-white",
            "ui-dialog-title": "font-semibold",
            "ui-dialog-titlebar-close": "text-white"
        }
    });

    // ===== Medicine Report Modal =====
    $("#medicineReportModal").dialog({
        autoOpen: false,
        modal: true,
        width: 640,
        resizable: false,
        draggable: true,
        classes: {
            "ui-dialog": "rounded-lg shadow-lg",
            "ui-dialog-titlebar": "bg-theme-primary text-white",
            "ui-dialog-title": "font-semibold",
            "ui-dialog-titlebar-close": "text-white"
        }
    });

    function loadMedicineCategories() {
        $.getJSON('api/medicine_inventory_api.php?action=list_categories', function (res) {
            if (!res || res.status !== 'ok') return;
            const $sel = $("#medReportCategory");
            $sel.empty().append('<option value="0">All</option>');
            res.data.forEach(function (c) {
                $sel.append($('<option>').val(c.id).text(c.name));
            });
        });
    }

    $("#openMedicineReportBtn").on("click", function () {
        loadMedicineCategories();
        $("#medicineReportModal").dialog("open");
    });

    function buildReportQS() {
        const qs = $("#medicineReportForm").serialize();
        return qs;
    }

    // Preview (show in your table area if you want)
    $("#previewMedicineReportBtn").on("click", function () {

        const qs = buildReportQS();

        $("#medSummary").html("Loading...");
        $("#medicineReportTbody").html(
            `<tr><td colspan="8" class="p-3 text-gray-400">Loading...</td></tr>`
        );

        $.getJSON('./api/medicine_inventory_api.php?action=report&' + qs, function (res) {

            if (!res || res.status !== 'ok') {
                showMessage('Error', (res && res.message) ? res.message : 'Failed to load report', true);
                return;
            }

            // ✅ unified payload (new or legacy)
            const payload = res.data ? res.data : res;

            const rows = Array.isArray(payload.rows) ? payload.rows : [];

            // ✅ If backend doesn't return summary, compute it here (works always)
            const summary = payload.summary ? payload.summary : computeMedicineSummary(rows);

            $("#medSummary").html(
                `Total: ${summary.total || 0} | Out: ${summary.out_of_stock || 0} | Critical: ${summary.critical || 0} | OK: ${summary.ok_items || 0} | Expiring: ${summary.expiring_soon || 0}`
            );

            renderMedicineReportRows(rows);

            // ✅ open dialog after render
            $("#medicineReportPreviewModal").dialog("open");

        }).fail(function (xhr) {
            console.log("HTTP:", xhr.status, xhr.statusText);
            console.log("Tried URL:", this.url);
            showMessage("Error", `HTTP ${xhr.status} ${xhr.statusText}`, true);
        });

    });

    function computeMedicineSummary(rows) {
        const s = {
            total: rows.length,
            out_of_stock: 0,
            critical: 0,
            ok_items: 0,
            expiring_soon: 0
        };

        rows.forEach(r => {
            const status = String(r.stock_status || '').toUpperCase();
            if (status === 'OUT_OF_STOCK') s.out_of_stock++;
            else if (status === 'CRITICAL') s.critical++;
            else if (status === 'OK') s.ok_items++;

            if (parseInt(r.is_expiring_soon, 10) === 1) s.expiring_soon++;
        });

        return s;
    }

    // Print - uses hidden iframe so hindi “blank tab”
    $("#printMedicineReportBtn").on("click", function () {
        const qs = buildReportQS();
        const url = 'reports/medicine_inventory_print.php?' + qs;

        let $frame = $("#printFrame");
        if ($frame.length === 0) {
            $frame = $('<iframe id="printFrame" style="display:none;"></iframe>');
            $("body").append($frame);
        }
        $frame.attr("src", url);

        // the print page will auto-print onload via window.print()
    });

    // Minimal renderer (you can upgrade to DataTable if you want)
    function renderMedicineReportRows(rows) {

        let html = '';

        rows.forEach(function (r) {

            const badge =
                (r.stock_status === 'OUT_OF_STOCK') ? 'bg-red-100 text-red-800' :
                    (r.stock_status === 'CRITICAL') ? 'bg-yellow-100 text-yellow-800' :
                        'bg-green-100 text-green-800';

            const exp =
                parseInt(r.is_expiring_soon, 10) === 1
                    ? '<span class="text-red-600 font-semibold">YES</span>'
                    : 'NO';

            html += `
      <tr class="border-b hover:bg-gray-50">
        <td class="p-2">${escapeHtml(r.name || '')}</td>
        <td class="p-2">${escapeHtml(r.category_name || '-')}</td>
        <td class="p-2">${r.stock_qty}</td>
        <td class="p-2">${r.reorder_level}</td>
        <td class="p-2">${escapeHtml(r.unit || 'pcs')}</td>
        <td class="p-2">${escapeHtml(r.expiration_date || '-')}</td>
        <td class="p-2">
          <span class="px-2 py-1 rounded text-xs ${badge}">
            ${r.stock_status}
          </span>
        </td>
        <td class="p-2">${exp}</td>
      </tr>
    `;
        });

        $("#medicineReportTbody").html(
            html || `<tr><td colspan="8" class="p-3 text-gray-500">No data found</td></tr>`
        );
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]);
        });
    }
});
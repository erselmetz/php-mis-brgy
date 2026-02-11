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
            if (!res || res.status !== 'ok') return;
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
});

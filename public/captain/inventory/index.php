<?php
/**
 * Inventory Management Page
 * 
 * Complete inventory system with:
 * - Add, Edit, Delete inventory items
 * - Category management
 * - Search functionality
 * - Audit trail tracking
 */

require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();

// Load categories for the select box
$categories = [];
try {
    $res = $conn->query("SELECT id, name FROM inventory_category_list ORDER BY name ASC");
    while ($r = $res->fetch_assoc()) {
        $categories[] = $r['name'];
    }
} catch (Exception $e) {
    // ignore if table doesn't exist yet
}

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory - MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 h-screen overflow-hidden font-sans" style="display: none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>
        <main class="pb-24 overflow-y-auto flex-1 p-6 w-screen">
            <h1 class="text-green-600 text-2xl font-semibold mb-6">INVENTORY MANAGEMENT</h1>

            <!-- Action Buttons and Search -->
            <div class="flex flex-wrap items-center justify-between mb-4 space-y-2 sm:space-y-0">
                <div class="flex space-x-2">
                    <button id="addInventoryBtn" type="button" class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-full text-sm font-semibold">
                        <span class="mr-1">+</span> Add Asset
                    </button>
                    <button id="addCategoryBtn" type="button" class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-full text-sm font-semibold">
                        <span class="mr-1">+</span> Add Category
                    </button>
                </div>
                <div>
                    <input type="text" id="searchInput" placeholder="Search assets..." 
                           class="border border-gray-300 rounded px-3 py-2 w-48">
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="overflow-x-auto bg-white rounded-lg shadow-sm p-4">
                <table id="inventoryTable" class="display min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th>Asset ID</th>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Currently Using</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via DataTables AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Audit Trail Button -->
            <div class="flex justify-end mt-6">
                <button id="auditTrailBtn" type="button" 
                        class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-full text-sm font-semibold">
                    View Audit Trail
                </button>
            </div>

            <!-- ==================== MODALS ==================== -->
            
            <!-- Add/Edit Inventory Item Modal -->
            <div id="inventoryModal" title="Add New Inventory Item" class="p-0 hidden">
                <form id="inventoryForm" class="bg-gray-100">
                    <input type="hidden" name="id" id="inventoryId">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES); ?>">
                    
                    <div class="p-4 space-y-3 text-sm">
                        <div>
                            <label class="block font-semibold mb-1">Name of Asset <span class="text-red-500">*</span></label>
                            <input name="name" id="assetName" type="text" required
                                   class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Category</label>
                            <select name="category" id="assetCategory" 
                                    class="w-full border border-gray-400 px-3 py-2 rounded">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat, ENT_QUOTES); ?>">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold mb-1">Quantity</label>
                                <input name="quantity" id="assetQuantity" type="number" value="1" min="0"
                                       class="w-full border border-gray-400 px-3 py-2 rounded">
                            </div>
                            <div>
                                <label class="block font-semibold mb-1">Status</label>
                                <select name="status" id="assetStatus" 
                                        class="w-full border border-gray-400 px-3 py-2 rounded">
                                    <option value="available">Available</option>
                                    <option value="in_use">In Use</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="retired">Retired</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Physical Location</label>
                            <input name="location" id="assetLocation" type="text"
                                   class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Condition</label>
                            <select name="condition" id="assetCondition" 
                                    class="w-full border border-gray-400 px-3 py-2 rounded">
                                <option value="">Select Condition</option>
                                <option value="Good">Good</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">
                                Description (license plate if vehicle)
                            </label>
                            <textarea name="description" id="assetDescription" rows="3"
                                      class="w-full border border-gray-400 px-3 py-2 rounded"></textarea>
                        </div>
                    </div>

                    <div class="bg-theme-primary text-center py-3">
                        <button type="submit" id="submitInventory" 
                                class="text-white font-semibold text-sm">
                            Add Inventory Item
                        </button>
                    </div>
                </form>
            </div>

            <!-- Add Category Modal -->
            <div id="categoryModal" title="Add Category" class="p-0 hidden">
                <form id="categoryForm" class="bg-gray-100 p-4">
                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block font-semibold mb-1">Category Name <span class="text-red-500">*</span></label>
                            <input name="category_name" id="categoryName" type="text" required
                                   class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES); ?>">
                    </div>
                    <div class="bg-theme-primary text-center py-3 mt-4">
                        <button type="submit" id="submitCategory" 
                                class="text-white font-semibold text-sm">
                            Add Category
                        </button>
                    </div>
                </form>
            </div>

            <!-- Assign/Return Asset Modal -->
            <div id="assignModal" title="Assign Asset" class="p-0 hidden">
                <form id="assignForm" class="bg-gray-100">
                    <input type="hidden" name="id" id="assignInventoryId">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES); ?>">
                    
                    <div class="p-4 space-y-3 text-sm">
                        <div>
                            <label class="block font-semibold mb-1">Personnel Name <span class="text-red-500">*</span></label>
                            <input name="personnel_name" id="personnelName" type="text" required
                                   class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Personnel Role</label>
                            <input name="personnel_role" id="personnelRole" type="text"
                                   class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Location</label>
                            <input name="location" id="assignLocation" type="text"
                                   class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Purpose</label>
                            <textarea name="purpose" id="assignPurpose" rows="2"
                                      class="w-full border border-gray-400 px-3 py-2 rounded"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold mb-1">Start Time</label>
                                <input name="start_time" id="startTime" type="datetime-local"
                                       class="w-full border border-gray-400 px-3 py-2 rounded">
                            </div>
                            <div>
                                <label class="block font-semibold mb-1">End Time</label>
                                <input name="end_time" id="endTime" type="datetime-local"
                                       class="w-full border border-gray-400 px-3 py-2 rounded">
                            </div>
                        </div>
                    </div>
                    <div class="bg-theme-primary text-center py-3">
                        <button type="submit" id="submitAssign" 
                                class="text-white font-semibold text-sm">
                            Assign Asset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Audit Trail Modal -->
            <div id="assetAuditModal" class="hidden p-0">
                <!-- Header Info -->
                <div class="p-5 border-b bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Asset Name</p>
                            <p class="font-semibold" id="auditAssetName">-</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Property Code</p>
                            <p class="font-semibold" id="auditAssetCode">-</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 border-b bg-gray-50">
                    <input type="date" id="auditDateFilter" 
                           class="border border-gray-300 px-3 py-2 rounded text-sm"
                           placeholder="Filter by Date">
                    <input type="text" id="auditPersonnelFilter" 
                           class="border border-gray-300 px-3 py-2 rounded text-sm"
                           placeholder="Search personnel">
                </div>

                <!-- Table -->
                <div class="px-5 pt-4 pb-2 max-h-96 overflow-y-auto">
                    <table class="w-full text-sm" id="auditTrailTable">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 sticky top-0">
                                <th class="text-left px-3 py-2">Time & Date</th>
                                <th class="text-left px-3 py-2">User / Personnel</th>
                                <th class="text-left px-3 py-2">Action</th>
                                <th class="text-left px-3 py-2">Location</th>
                                <th class="text-left px-3 py-2">Purpose</th>
                            </tr>
                        </thead>
                        <tbody id="auditTrailBody" class="divide-y">
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-gray-500">
                                    Loading audit trail...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="p-5 flex items-center justify-between border-t bg-gray-50">
                    <div class="space-x-2">
                        <button id="exportAuditBtn" 
                                class="bg-theme-primary hover-theme-darker text-white px-5 py-2 rounded text-sm">
                            Export Logs
                        </button>
                        <button id="closeAuditModal" 
                                class="border border-gray-300 px-5 py-2 rounded text-sm">
                            Close
                        </button>
                    </div>
                    <div class="text-xs text-gray-500" id="auditPageInfo">
                        Total: 0 records
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        $(function() {
            $("body").show();
            
            let inventoryTable;
            let currentEditId = null;
            let currentAuditInventoryId = null;

            // Helper function to show confirmation dialogs
            function showConfirm(title, message, onConfirm) {
                const $dialog = $('<div class="p-4">' + message + '</div>');
                $dialog.dialog({
                    modal: true,
                    title: title,
                    width: 420,
                    resizable: false,
                    buttons: {
                        'Yes': function() {
                            $(this).dialog('close').remove();
                            if (typeof onConfirm === 'function') {
                                onConfirm();
                            }
                        },
                        'Cancel': function() {
                            $(this).dialog('close').remove();
                        }
                    },
                    classes: {
                        'ui-dialog': 'rounded-lg shadow-lg',
                        'ui-dialog-titlebar': 'bg-yellow-600 text-white rounded-t-lg',
                        'ui-dialog-title': 'font-semibold',
                        'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                    },
                    open: function() {
                        $('.ui-dialog-buttonpane button').each(function() {
                            if ($(this).text() === 'Yes') {
                                $(this).addClass('bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded');
                            } else {
                                $(this).addClass('bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded');
                            }
                        });
                    }
                });
            }

            // Initialize DataTable
            function initInventoryTable() {
                inventoryTable = $('#inventoryTable').DataTable({
                    ajax: {
                        url: 'api/inventory_api.php?action=list',
                        dataSrc: function(json) {
                            console.log('Inventory API Response:', json);
                            if (!json) {
                                console.error('No response from API');
                                return [];
                            }
                            if (json.status !== 'ok') {
                                console.error('API Error:', json.message || 'Unknown error');
                                if (json.message) {
                                    showMessage('Error', 'Error loading inventory: ' + json.message, true);
                                }
                                return [];
                            }
                            return json.data || [];
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', error, thrown);
                            console.error('Response:', xhr.responseText);
                            showMessage('Error', 'Failed to load inventory data. Please check console for details.', true);
                        }
                    },
                    columns: [
                        { data: 'asset_code', title: 'Asset ID' },
                        { data: 'name', title: 'Asset Name' },
                        { data: 'category', title: 'Category', defaultContent: '-' },
                        { data: 'quantity', title: 'Quantity' },
                        { data: 'location', title: 'Location', defaultContent: '-' },
                        { data: 'cond', title: 'Condition', defaultContent: '-',
                          render: function(data) {
                              if (!data) return '-';
                              const colors = {
                                  'Good': 'text-green-600',
                                  'Maintenance': 'text-yellow-600',
                                  'Damaged': 'text-red-600'
                              };
                              return '<span class="' + (colors[data] || '') + '">' + data + '</span>';
                          }
                        },
                        { data: 'status', title: 'Status',
                          render: function(data) {
                              const statusMap = {
                                  'available': { text: 'Available', class: 'bg-green-100 text-green-800' },
                                  'in_use': { text: 'In Use', class: 'bg-blue-100 text-blue-800' },
                                  'maintenance': { text: 'Maintenance', class: 'bg-yellow-100 text-yellow-800' },
                                  'damaged': { text: 'Damaged', class: 'bg-red-100 text-red-800' },
                                  'retired': { text: 'Retired', class: 'bg-gray-100 text-gray-800' }
                              };
                              const status = statusMap[data] || { text: data, class: 'bg-gray-100 text-gray-800' };
                              return '<span class="px-2 py-1 rounded text-xs ' + status.class + '">' + status.text + '</span>';
                          }
                        },
                        { data: 'currently_using', title: 'Currently Using', 
                          render: function(data) {
                              return data > 0 ? '<span class="text-blue-600 font-semibold">' + data + '</span>' : '0';
                          }
                        },
                        { data: null, title: 'Actions', orderable: false,
                          render: function(data, type, row) {
                              return '<div class="flex space-x-1">' +
                                  '<button class="edit-btn text-blue-600 hover:text-blue-800 text-xs px-2 py-1" data-id="' + row.id + '">Edit</button>' +
                                  '<button class="assign-btn text-green-600 hover:text-green-800 text-xs px-2 py-1" data-id="' + row.id + '">Assign</button>' +
                                  '<button class="delete-btn text-red-600 hover:text-red-800 text-xs px-2 py-1" data-id="' + row.id + '" data-name="' + (row.name || '') + '">Delete</button>' +
                                  '<button class="audit-btn text-purple-600 hover:text-purple-800 text-xs px-2 py-1" data-id="' + row.id + '" data-code="' + (row.asset_code || '') + '" data-name="' + (row.name || '') + '">Audit</button>' +
                                  '</div>';
                          }
                        }
                    ],
                    order: [[0, 'desc']],
                    responsive: true,
                    pageLength: 25,
                    language: {
                        emptyTable: "No inventory items found"
                    }
                });
            }

            // Search functionality
            let searchTimeout;
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                const searchTerm = $(this).val();
                searchTimeout = setTimeout(function() {
                    inventoryTable.ajax.url('api/inventory_api.php?action=list&search=' + encodeURIComponent(searchTerm)).load();
                }, 500);
            });

            // Load categories
            function loadCategories(selectedName) {
                $.getJSON('api/inventory_api.php?action=list_categories', function(res) {
                    if (!res || res.status !== 'ok') return;
                    var $sel = $('#assetCategory');
                    $sel.empty();
                    $sel.append('<option value="">Select Category</option>');
                    res.data.forEach(function(c) {
                        var opt = $('<option>').attr('value', c.name).text(c.name);
                        $sel.append(opt);
                    });
                    if (selectedName) {
                        $sel.val(selectedName);
                    }
                });
            }
            loadCategories();

            // Initialize modals
            $("#inventoryModal").dialog({
                autoOpen: false,
                modal: true,
                width: 600,
                resizable: false,
                draggable: true,
                classes: {
                    "ui-dialog": "rounded-lg shadow-lg",
                    "ui-dialog-titlebar": "bg-theme-primary text-white",
                    "ui-dialog-title": "font-semibold",
                    "ui-dialog-titlebar-close": "text-white"
                }
            });

            $("#categoryModal").dialog({
                autoOpen: false,
                modal: true,
                width: 480,
                resizable: false,
                draggable: true,
                classes: {
                    "ui-dialog": "rounded-lg shadow-lg",
                    "ui-dialog-titlebar": "bg-theme-primary text-white",
                    "ui-dialog-title": "font-semibold",
                    "ui-dialog-titlebar-close": "text-white"
                }
            });

            $("#assignModal").dialog({
                autoOpen: false,
                modal: true,
                width: 600,
                resizable: false,
                draggable: true,
                classes: {
                    "ui-dialog": "rounded-lg shadow-lg",
                    "ui-dialog-titlebar": "bg-theme-primary text-white",
                    "ui-dialog-title": "font-semibold",
                    "ui-dialog-titlebar-close": "text-white"
                }
            });

            $("#assetAuditModal").dialog({
                autoOpen: false,
                modal: true,
                width: 1000,
                height: 700,
                resizable: true,
                draggable: false,
                classes: {
                    "ui-dialog": "rounded-lg shadow-lg",
                    "ui-dialog-titlebar": "bg-theme-primary text-white",
                    "ui-dialog-title": "font-semibold",
                    "ui-dialog-titlebar-close": "text-white"
                },
                title: "Asset Audit Trail"
            });

            // Add Inventory Button
            $("#addInventoryBtn").on("click", function() {
                currentEditId = null;
                $("#inventoryForm")[0].reset();
                $("#inventoryId").val('');
                $("#inventoryModal").dialog("option", "title", "Add New Inventory Item");
                $("#submitInventory").text("Add Inventory Item");
                $("#inventoryModal").dialog("open");
            });

            // Add Category Button
            $("#addCategoryBtn").on("click", function() {
                $("#categoryForm")[0].reset();
                $("#categoryModal").dialog("option", "title", "Add New Category");
                $("#submitCategory").text("Add Category");
                $("#categoryModal").dialog("open");
            });

            // Edit Button
            $(document).on('click', '.edit-btn', function() {
                const id = $(this).data('id');
                currentEditId = id;
                
                $.getJSON('api/inventory_api.php?action=get&id=' + id, function(res) {
                    if (res.status === 'ok' && res.data) {
                        const item = res.data;
                        $("#inventoryId").val(item.id);
                        $("#assetName").val(item.name);
                        $("#assetCategory").val(item.category);
                        $("#assetQuantity").val(item.quantity);
                        $("#assetLocation").val(item.location);
                        $("#assetCondition").val(item.cond);
                        $("#assetStatus").val(item.status || 'available');
                        $("#assetDescription").val(item.description);
                        
                        $("#inventoryModal").dialog("option", "title", "Edit Inventory Item");
                        $("#submitInventory").text("Update Inventory Item");
                        $("#inventoryModal").dialog("open");
                    }
                });
            });

            // Delete Button
            $(document).on('click', '.delete-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                
                showConfirm(
                    'Confirm Delete',
                    'Are you sure you want to delete "' + name + '"? This action cannot be undone.',
                    function() {
                        $.post('api/inventory_api.php?action=delete', {
                            id: id,
                            csrf_token: '<?php echo htmlspecialchars($csrf_token, ENT_QUOTES); ?>'
                        }, function(resp) {
                            const json = typeof resp === 'string' ? JSON.parse(resp) : resp;
                            if (json.status === 'ok') {
                                inventoryTable.ajax.reload(null, false);
                                showMessage('Success', 'Item deleted successfully');
                            } else {
                                showMessage('Error', json.message || 'Failed to delete item', true);
                            }
                        });
                    }
                );
            });

            // Assign Button
            $(document).on('click', '.assign-btn', function() {
                const id = $(this).data('id');
                $("#assignInventoryId").val(id);
                $("#assignForm")[0].reset();
                $("#assignModal").dialog("open");
            });

            // Audit Trail Button (from table row)
            $(document).on('click', '.audit-btn', function() {
                const id = $(this).data('id');
                const code = $(this).data('code');
                const name = $(this).data('name');
                currentAuditInventoryId = id;
                $("#auditAssetCode").text(code);
                $("#auditAssetName").text(name);
                loadAuditTrail(id);
                $("#assetAuditModal").dialog("open");
            });

            // Global Audit Trail Button
            $("#auditTrailBtn").on("click", function() {
                currentAuditInventoryId = null;
                $("#auditAssetCode").text('All Assets');
                $("#auditAssetName").text('Complete Audit Trail');
                loadAuditTrail(null);
                $("#assetAuditModal").dialog("open");
            });

            // Load Audit Trail
            function loadAuditTrail(inventoryId) {
                let url = 'api/inventory_api.php?action=audit_trail';
                if (inventoryId) {
                    url += '&inventory_id=' + inventoryId;
                }
                
                const dateFilter = $("#auditDateFilter").val();
                const personnelFilter = $("#auditPersonnelFilter").val();
                
                if (dateFilter) url += '&date=' + dateFilter;
                if (personnelFilter) url += '&personnel=' + encodeURIComponent(personnelFilter);
                
                $("#auditTrailBody").html('<tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">Loading...</td></tr>');
                
                $.getJSON(url, function(res) {
                    if (res.status === 'ok' && res.data) {
                        const trails = res.data;
                        if (trails.length === 0) {
                            $("#auditTrailBody").html('<tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">No audit trail records found</td></tr>');
                        } else {
                            let html = '';
                            trails.forEach(function(trail) {
                                const date = new Date(trail.created_at);
                                const dateStr = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                                const timeStr = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                                
                                const actionColors = {
                                    'created': 'text-green-600',
                                    'updated': 'text-blue-600',
                                    'deleted': 'text-red-600',
                                    'assigned': 'text-purple-600',
                                    'returned': 'text-indigo-600',
                                    'location_changed': 'text-yellow-600',
                                    'condition_changed': 'text-orange-600',
                                    'quantity_changed': 'text-pink-600'
                                };
                                
                                const actionText = trail.action_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                
                                html += '<tr class="hover:bg-gray-50">';
                                html += '<td class="px-3 py-3"><p class="font-semibold">' + dateStr + '</p><p class="text-xs text-gray-500">' + timeStr + '</p></td>';
                                html += '<td class="px-3 py-3">';
                                if (trail.personnel_name) {
                                    html += '<p class="font-semibold">' + (trail.personnel_name || '-') + '</p>';
                                    html += '<p class="text-xs text-gray-500">' + (trail.personnel_role || '') + '</p>';
                                } else {
                                    html += '<p class="font-semibold">' + (trail.user_name || '-') + '</p>';
                                    html += '<p class="text-xs text-gray-500">' + (trail.user_role || '') + '</p>';
                                }
                                html += '</td>';
                                html += '<td class="px-3 py-3"><span class="' + (actionColors[trail.action_type] || '') + '">' + actionText + '</span></td>';
                                html += '<td class="px-3 py-3">' + (trail.location || '-') + '</td>';
                                html += '<td class="px-3 py-3 text-gray-600">' + (trail.purpose || trail.notes || '-') + '</td>';
                                html += '</tr>';
                            });
                            $("#auditTrailBody").html(html);
                        }
                        $("#auditPageInfo").text('Total: ' + trails.length + ' records');
                    } else {
                        $("#auditTrailBody").html('<tr><td colspan="5" class="px-3 py-4 text-center text-red-500">Error loading audit trail</td></tr>');
                    }
                });
            }

            // Audit Trail Filters
            $("#auditDateFilter, #auditPersonnelFilter").on('change keyup', function() {
                if (currentAuditInventoryId) {
                    loadAuditTrail(currentAuditInventoryId);
                } else {
                    loadAuditTrail(null);
                }
            });

            // Form Submissions
            $('#inventoryForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#submitInventory');
                $btn.prop('disabled', true).text('Saving...');
                
                const action = currentEditId ? 'update' : 'add';
                const formData = $(this).serialize();
                
                $.post('api/inventory_api.php?action=' + action, formData, function(resp) {
                    const json = typeof resp === 'string' ? JSON.parse(resp) : resp;
                    if (json.status === 'ok') {
                        $('#inventoryModal').dialog('close');
                        inventoryTable.ajax.reload(null, false);
                        showMessage('Success', json.message || 'Inventory item saved successfully');
                        $('#inventoryForm')[0].reset();
                    } else {
                        showMessage('Error', json.message || 'Failed to save inventory item', true);
                    }
                }).fail(function() {
                    showMessage('Error', 'Request failed. Please try again.', true);
                }).always(function() {
                    $btn.prop('disabled', false).text(currentEditId ? 'Update Inventory Item' : 'Add Inventory Item');
                });
            });

            $('#categoryForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#submitCategory');
                $btn.prop('disabled', true).text('Adding...');
                
                const formData = $(this).serialize();
                console.log('Submitting category form:', formData);
                
                $.post('api/inventory_api.php?action=add_category', formData, function(resp) {
                    console.log('Category API Response:', resp);
                    let json;
                    try {
                        json = typeof resp === 'string' ? JSON.parse(resp) : resp;
                    } catch (err) {
                        console.error('JSON Parse Error:', err, resp);
                        showMessage('Error', 'Invalid response from server. Please check console.', true);
                        return;
                    }
                    
                    if (json.status === 'ok') {
                        $('#categoryModal').dialog('close');
                        // Reload categories and select the new one
                        loadCategories(json.data ? json.data.name : null);
                        showMessage('Success', json.message || 'Category added successfully');
                        $('#categoryForm')[0].reset();
                    } else {
                        showMessage('Error', json.message || 'Failed to add category', true);
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Category Add Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    showMessage('Error', 'Request failed: ' + error + '. Please check console for details.', true);
                }).always(function() {
                    $btn.prop('disabled', false).text('Add Category');
                });
            });

            $('#assignForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#submitAssign');
                $btn.prop('disabled', true).text('Assigning...');
                
                $.post('api/inventory_api.php?action=assign', $(this).serialize(), function(resp) {
                    const json = typeof resp === 'string' ? JSON.parse(resp) : resp;
                    if (json.status === 'ok') {
                        $('#assignModal').dialog('close');
                        inventoryTable.ajax.reload(null, false);
                        showMessage('Success', json.message || 'Asset assigned successfully');
                        $('#assignForm')[0].reset();
                    } else {
                        showMessage('Error', json.message || 'Failed to assign asset', true);
                    }
                }).fail(function() {
                    showMessage('Error', 'Request failed. Please try again.', true);
                }).always(function() {
                    $btn.prop('disabled', false).text('Assign Asset');
                });
            });

            // Close buttons
            $("#closeAuditModal").on("click", function() {
                $("#assetAuditModal").dialog("close");
            });

            // Export Audit Trail
            $("#exportAuditBtn").on("click", function() {
                showMessage('Information', 'Export functionality will be implemented soon.');
            });

            // Initialize table
            initInventoryTable();
        });
    </script>
</body>
</html>

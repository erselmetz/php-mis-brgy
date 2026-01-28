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
requireKagawad();

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

    <script src="./js/index.js"></script>
</body>
</html>

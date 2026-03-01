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
requireHCNurse(); // Only HC Nurse can access

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
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-green-600 text-2xl font-semibold tracking-wide">INVENTORY</h1>
                </div>

                <div class="flex items-center gap-2">
                    <input id="searchInput" type="text" placeholder="Search"
                        class="w-64 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary" />
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button id="addMedicineBtn"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                    Add Item
                </button>
                <button id="addCategoryBtn"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                    Add Category
                </button>
            </div>

            <div class="mt-3">
                <table id="medicineTable" class="display w-full"></table>
            </div>

            <div class="mt-8 flex justify-end">
                <button id="openMedicineReportBtn" class="bg-theme-primary text-white px-3 py-2 rounded">
                    Generate Medicine Report
                </button>

                <div id="medicineReportModal" title="Generate Medicine Inventory Report" class="hidden">
                    <form id="medicineReportForm" class="space-y-3 p-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" name="search" id="medReportSearch"
                                class="w-full px-3 py-2 border rounded">
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category</label>
                                <select name="category_id" id="medReportCategory"
                                    class="w-full px-3 py-2 border rounded">
                                    <option value="0">All</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select name="status" id="medReportStatus" class="w-full px-3 py-2 border rounded">
                                    <option value="ALL">All</option>
                                    <option value="OUT_OF_STOCK">Out of Stock</option>
                                    <option value="CRITICAL">Critical</option>
                                    <option value="OK">Ok</option>
                                    <option value="EXPIRING_SOON">Expiring Soon</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Expiring soon days</label>
                                <input type="number" name="exp_days" id="medReportExpDays" value="30" min="1" max="365"
                                    class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="flex items-end gap-2">
                                <button type="button" id="previewMedicineReportBtn"
                                    class="bg-blue-600 text-white px-3 py-2 rounded">Preview</button>
                                <button type="button" id="printMedicineReportBtn"
                                    class="bg-green-600 text-white px-3 py-2 rounded">Print</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add/Edit Medicine Modal -->
            <div id="medicineModal" title="Add Item" class="hidden">
                <form id="medicineForm" class="space-y-3 p-3">
                    <input type="hidden" name="id" id="medicineId">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Item Name</label>
                        <input type="text" name="name" id="medicineName"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category_id" id="medicineCategory"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                            <option value="">Select Category</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Stock</label>
                            <input type="number" name="stock_qty" id="stock_qty" min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reorder Level</label>
                            <input type="number" name="reorder_level" id="reorder_level" min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"
                                required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Unit</label>
                            <input type="text" name="unit" id="unit" placeholder="pcs"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Expiration Date</label>
                            <input type="text" name="expiration_date" id="expiration_date" placeholder="yyyy-mm-dd"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="medicineDesc" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
                    </div>

                    <div class="pt-2">
                        <button id="submitMedicine" type="submit"
                            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded">
                            Add Item
                        </button>
                    </div>
                </form>
            </div>

            <!-- Add Category Modal -->
            <div id="categoryModal" title="Add Category" class="hidden">
                <form id="categoryForm" class="space-y-3 p-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category Name</label>
                        <input type="text" name="name"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"
                            required>
                    </div>

                    <button id="submitCategory" type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded">
                        Add Category
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="./js/medicine_inventory.js"></script>
</body>

</html>
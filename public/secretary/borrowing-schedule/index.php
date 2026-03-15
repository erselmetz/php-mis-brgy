<?php
/**
 * Equipment / Items Borrowing Schedule
 * Manages borrowing of barangay assets by residents and staff
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

// Load inventory items for the dropdown
$inventoryItems = [];
try {
    $res = $conn->query("SELECT id, asset_code, name FROM inventory WHERE status='available' OR status IS NULL ORDER BY name ASC");
    if ($res)
        while ($r = $res->fetch_assoc())
            $inventoryItems[] = $r;
} catch (Exception $e) { /* ignore */
}

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Borrowing Schedule - MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 font-sans h-screen overflow-hidden" style="display: none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>
        <main class="pb-24 overflow-y-auto flex-1 p-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
                <div>
                    <h2 class="text-2xl font-semibold">📚 Equipment Borrowing Schedule</h2>
                    <p class="text-sm text-gray-500 mt-1">Track and manage borrowing of barangay assets</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <select id="filterStatus" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="borrowed">Borrowed</option>
                        <option value="returned">Returned</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <input type="date" id="filterDate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <input type="text" id="searchInput" placeholder="Search…"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40">
                    <button id="btnPrintBorrow"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        🖨️ Print
                    </button>
                    <button id="btnNewBorrow"
                        class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        + New Borrowing
                    </button>
                </div>
            </div>

            <!-- Status Summary Cards -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-blue-700" id="countBorrowed">—</div>
                    <div class="text-sm text-blue-600 font-medium mt-1">📦 Borrowed</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-700" id="countReturned">—</div>
                    <div class="text-sm text-green-600 font-medium mt-1">✅ Returned</div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-red-700" id="countOverdue">—</div>
                    <div class="text-sm text-red-600 font-medium mt-1">⚠️ Overdue</div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-gray-700" id="countCancelled">—</div>
                    <div class="text-sm text-gray-600 font-medium mt-1">🚫 Cancelled</div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="bg-white rounded-xl shadow border p-4">
                <table id="borrowTable" class="display min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th>Code</th>
                            <th>Borrower</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Borrow Date</th>
                            <th>Return Date</th>
                            <th>Actual Return</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Add/Edit Borrow Dialog -->
    <div id="borrowDialog" title="New Borrowing Entry" class="hidden">
        <div class="p-4 space-y-4">
            <input type="hidden" id="borrowId">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Borrower Name *</label>
                    <input type="text" id="borrowerName" placeholder="Full name"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                    <input type="text" id="borrowerContact" placeholder="09XXXXXXXXX"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item from Inventory</label>
                    <select id="inventoryItemSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">— Select from inventory —</option>
                        <?php foreach ($inventoryItems as $item): ?>
                            <option value="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>">
                                <?= htmlspecialchars($item['asset_code'] . ' — ' . $item['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
                    <input type="text" id="itemName" placeholder="Enter or auto-fill from inventory"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" id="borrowQty" value="1" min="1"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Borrow Date *</label>
                    <input type="date" id="borrowDate"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Return *</label>
                    <input type="date" id="returnDate"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Actual Return Date</label>
                    <input type="date" id="actualReturn"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="borrowStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="borrowed">Borrowed</option>
                        <option value="returned">Returned</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condition (Out)</label>
                    <input type="text" id="conditionOut" placeholder="e.g. Good condition"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condition (Returned)</label>
                    <input type="text" id="conditionIn" placeholder="e.g. Returned with damage"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                <input type="text" id="borrowPurpose" placeholder="Purpose of borrowing"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="borrowNotes" rows="2" placeholder="Additional notes…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
        </div>
    </div>

    <!-- View Dialog -->
    <div id="viewBorrowDialog" title="Borrowing Details" class="hidden">
        <div id="viewBorrowContent" class="p-4"></div>
    </div>

    <!-- Delete Confirm -->
    <div id="deleteBorrowDialog" title="Confirm Delete" class="hidden">
        <p class="p-4 text-gray-700">Are you sure you want to delete this borrowing record? This cannot be undone.</p>
        <input type="hidden" id="deleteBorrowId">
    </div>

    <script src="js/index.js"></script>
</body>

</html>
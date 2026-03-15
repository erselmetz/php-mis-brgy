<?php
/**
 * Tanod Duty Schedule
 * Manages daily/weekly duty roster of Barangay Tanods
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tanod Duty Schedule - MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 font-sans h-screen overflow-hidden" style="display: none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>
        <main class="pb-24 overflow-y-auto flex-1 p-6">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
                <div>
                    <h2 class="text-2xl font-semibold">🛡️ Tanod Duty Schedule</h2>
                    <p class="text-sm text-gray-500 mt-1">Manage daily and weekly duty roster of Barangay Tanods</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <input type="date" id="filterDate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <select id="filterShift" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Shifts</option>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="night">Night</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="Search tanod…"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40">
                    <button id="btnPrintDuty"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        🖨️ Print
                    </button>
                    <button id="btnNewDuty"
                        class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        + Assign Duty
                    </button>
                </div>
            </div>

            <!-- Shift Summary Cards -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-700" id="countMorning">—</div>
                    <div class="text-sm text-yellow-600 font-medium mt-1">☀️ Morning Shift</div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-blue-700" id="countAfternoon">—</div>
                    <div class="text-sm text-blue-600 font-medium mt-1">🌤️ Afternoon Shift</div>
                </div>
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-indigo-700" id="countNight">—</div>
                    <div class="text-sm text-indigo-600 font-medium mt-1">🌙 Night Shift</div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="bg-white rounded-xl shadow border p-4">
                <table id="dutyTable" class="display min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th>Duty Code</th>
                            <th>Tanod Name</th>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Post / Location</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Add/Edit Duty Dialog -->
    <div id="dutyDialog" title="Assign Duty Schedule" class="hidden">
        <div class="p-4 space-y-4">
            <input type="hidden" id="dutyId">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanod Name *</label>
                    <input type="text" id="dutyTanodName" placeholder="Full name of tanod"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duty Date *</label>
                    <input type="date" id="dutyDate"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Shift *</label>
                    <select id="dutyShift" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="morning">☀️ Morning (6AM - 2PM)</option>
                        <option value="afternoon">🌤️ Afternoon (2PM - 10PM)</option>
                        <option value="night">🌙 Night (10PM - 6AM)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="dutyStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Post / Location</label>
                <input type="text" id="dutyPostLocation" placeholder="e.g. Main Gate, Perimeter Area A"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="dutyNotes" rows="2" placeholder="Additional instructions or notes…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400"></textarea>
            </div>
        </div>
    </div>

    <!-- View Duty Dialog -->
    <div id="viewDutyDialog" title="Duty Schedule Details" class="hidden">
        <div id="viewDutyContent" class="p-4"></div>
    </div>

    <!-- Delete Confirm Dialog -->
    <div id="deleteDutyDialog" title="Confirm Delete" class="hidden">
        <p class="p-4 text-gray-700">Are you sure you want to delete this duty assignment? This action cannot be undone.
        </p>
        <input type="hidden" id="deleteDutyId">
    </div>

    <script src="js/index.js"></script>
</body>

</html>
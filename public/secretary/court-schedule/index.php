<?php
/**
 * Court / Facility Borrowing Schedule
 * Manages reservations for basketball court, multipurpose area, gym
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Court Schedule - MIS Barangay</title>
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
                    <h2 class="text-2xl font-semibold">🏀 Court / Facility Schedule</h2>
                    <p class="text-sm text-gray-500 mt-1">Manage reservations for barangay facilities</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <input type="date" id="filterDate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <select id="filterFacility" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Facilities</option>
                        <option value="basketball_court">Basketball Court</option>
                        <option value="multipurpose_area">Multipurpose Area</option>
                        <option value="gym">Gym</option>
                    </select>
                    <select id="filterStatus" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="denied">Denied</option>
                        <option value="completed">Completed</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="Search…"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40">
                    <button id="btnPrintCourt"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        🖨️ Print
                    </button>
                    <button id="btnNewReservation"
                        class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        + New Reservation
                    </button>
                </div>
            </div>

            <!-- Status Summary Cards -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-700" id="countPending">—</div>
                    <div class="text-sm text-yellow-600 font-medium mt-1">⏳ Pending</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-700" id="countApproved">—</div>
                    <div class="text-sm text-green-600 font-medium mt-1">✅ Approved</div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-red-700" id="countDenied">—</div>
                    <div class="text-sm text-red-600 font-medium mt-1">❌ Denied</div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-gray-700" id="countCompleted">—</div>
                    <div class="text-sm text-gray-600 font-medium mt-1">✔️ Completed</div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="bg-white rounded-xl shadow border p-4">
                <table id="courtTable" class="display min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th>Code</th>
                            <th>Facility</th>
                            <th>Borrower</th>
                            <th>Organization</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Add/Edit Reservation Dialog -->
    <div id="reservationDialog" title="New Reservation" class="hidden">
        <div class="p-4 space-y-4">
            <input type="hidden" id="resId">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Borrower Name *</label>
                    <input type="text" id="resBorrower" placeholder="Full name"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                    <input type="text" id="resBorrowerContact" placeholder="09XXXXXXXXX"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Organization / Group</label>
                <input type="text" id="resOrganization" placeholder="e.g. Basketball League, Youth Org"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Facility *</label>
                    <select id="resFacility" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="basketball_court">🏀 Basketball Court</option>
                        <option value="multipurpose_area">🏛️ Multipurpose Area</option>
                        <option value="gym">🏋️ Gym</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input type="date" id="resDate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time Start *</label>
                    <input type="time" id="resTimeStart"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time End *</label>
                    <input type="time" id="resTimeEnd"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purpose *</label>
                <input type="text" id="resPurpose" placeholder="e.g. Basketball practice, Community meeting"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="resStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="denied">Denied</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <input type="text" id="resRemarks" placeholder="Optional remarks"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Conflict Warning -->
            <div id="conflictWarning"
                class="hidden bg-red-50 border border-red-300 text-red-700 text-sm rounded-lg p-3">
                ⚠️ <strong>Schedule Conflict Detected!</strong> Another reservation exists for the same facility and
                time slot.
            </div>
        </div>
    </div>

    <!-- View Dialog -->
    <div id="viewResDialog" title="Reservation Details" class="hidden">
        <div id="viewResContent" class="p-4"></div>
    </div>

    <!-- Delete Confirm -->
    <div id="deleteResDialog" title="Confirm Delete" class="hidden">
        <p class="p-4 text-gray-700">Are you sure you want to delete this reservation? This cannot be undone.</p>
        <input type="hidden" id="deleteResId">
    </div>

    <script src="js/index.js"></script>
</body>

</html>
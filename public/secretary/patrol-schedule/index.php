<?php
/**
 * Mobile Patrol / Roving Tanod Schedule
 * Manages patrol routes, teams, and time blocks
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Patrol Schedule - MIS Barangay</title>
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
                    <h2 class="text-2xl font-semibold">🚓 Mobile Patrol / Roving Schedule</h2>
                    <p class="text-sm text-gray-500 mt-1">Manage tanod patrol teams, routes, and time blocks</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <input type="date" id="filterDate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <select id="filterStatus" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="filterWeekly" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Types</option>
                        <option value="0">One-time</option>
                        <option value="1">Weekly Repeat</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="Search…"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40">
                    <button id="btnPrintPatrol"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        🖨️ Print
                    </button>
                    <button id="btnNewPatrol"
                        class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        + New Patrol
                    </button>
                </div>
            </div>

            <!-- Status Summary Cards -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-blue-700" id="countScheduled">—</div>
                    <div class="text-sm text-blue-600 font-medium mt-1">📋 Scheduled</div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-700" id="countOngoing">—</div>
                    <div class="text-sm text-yellow-600 font-medium mt-1">🚔 Ongoing</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-700" id="countCompleted">—</div>
                    <div class="text-sm text-green-600 font-medium mt-1">✅ Completed</div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-purple-700" id="countWeekly">—</div>
                    <div class="text-sm text-purple-600 font-medium mt-1">🔁 Weekly Repeats</div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="bg-white rounded-xl shadow border p-4">
                <table id="patrolTable" class="display min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th>Code</th>
                            <th>Team</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Route / Area</th>
                            <th>Members</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Add/Edit Patrol Dialog -->
    <div id="patrolDialog" title="New Patrol Schedule" class="hidden">
        <div class="p-4 space-y-4">
            <input type="hidden" id="patrolId">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Team Name *</label>
                    <input type="text" id="patrolTeam" placeholder="e.g. Alpha Team, Grupo 1"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patrol Date *</label>
                    <input type="date" id="patrolDate"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time Start *</label>
                    <input type="time" id="patrolTimeStart"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time End *</label>
                    <input type="time" id="patrolTimeEnd"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Patrol Route</label>
                <input type="text" id="patrolRoute" placeholder="e.g. Purok 1 → Purok 2 → Main Road → Purok 3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Area Covered</label>
                <input type="text" id="patrolArea" placeholder="e.g. Sitio Mabini, Perimeter, Covered Court Area"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanod Members</label>
                <input type="text" id="patrolMembers"
                    placeholder="Names separated by comma, e.g. Juan Dela Cruz, Pedro Reyes"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="patrolStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule Type</label>
                    <select id="patrolIsWeekly" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="0">One-time</option>
                        <option value="1">🔁 Weekly Repeat</option>
                    </select>
                </div>
            </div>

            <!-- Week day selector (shown only if weekly) -->
            <div id="weekDayWrapper" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Repeat on Day</label>
                <select id="patrolWeekDay" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="0">Sunday</option>
                    <option value="1">Monday</option>
                    <option value="2">Tuesday</option>
                    <option value="3">Wednesday</option>
                    <option value="4">Thursday</option>
                    <option value="5">Friday</option>
                    <option value="6">Saturday</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="patrolNotes" rows="2" placeholder="Additional instructions or notes…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
        </div>
    </div>

    <!-- View Dialog -->
    <div id="viewPatrolDialog" title="Patrol Details" class="hidden">
        <div id="viewPatrolContent" class="p-4"></div>
    </div>

    <!-- Delete Confirm -->
    <div id="deletePatrolDialog" title="Confirm Delete" class="hidden">
        <p class="p-4 text-gray-700">Are you sure you want to delete this patrol schedule? This cannot be undone.</p>
        <input type="hidden" id="deletePatrolId">
    </div>

    <script src="js/index.js"></script>
</body>

</html>
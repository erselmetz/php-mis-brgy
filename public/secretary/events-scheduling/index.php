<?php
/**
 * Events & Scheduling Management
 * 
 * Complete event management system with:
 * - Add, Edit, Delete events
 * - Calendar view
 * - Event list
 * - Search functionality
 * - Event history
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events & Scheduling - MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 font-sans h-screen overflow-hidden" style="display: none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>
        <main class="pb-24 overflow-y-auto flex-1 p-6">

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
                <h2 class="text-2xl font-semibold">Events & Scheduling</h2>

                <div class="flex gap-2">
                    <input type="text" id="searchInput"
                        placeholder="Search event…"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-48">
                    <button id="btnNewEvent"
                        class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        + New Event
                    </button>
                </div>
            </div>

            <div class="bg-white border rounded-lg shadow p-6 flex flex-col lg:flex-row space-y-6 lg:space-y-0 lg:space-x-6">
                <!-- Left: Event List -->
                <div class="flex flex-col w-full lg:w-1/3 border rounded-xl p-4 h-[420px] overflow-y-auto bg-gray-50">
                    <h3 class="font-semibold text-sm mb-3 text-gray-600">Upcoming Events</h3>
                    <div id="eventsList" class="space-y-3">
                        <div class="text-center text-gray-500 py-8">Loading events...</div>
                    </div>
                </div>

                <!-- Right: Calendar -->
                <div class="flex-1 border rounded-xl p-4 bg-white">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold" id="calendarMonthYear">January 2025</h3>
                        <div class="space-x-2 text-sm">
                            <button id="prevMonth" class="px-3 py-1 border rounded hover:bg-gray-100">‹</button>
                            <button id="todayBtn" class="px-3 py-1 border rounded hover:bg-gray-100">Today</button>
                            <button id="nextMonth" class="px-3 py-1 border rounded hover:bg-gray-100">›</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 text-center text-xs text-gray-500 mb-2">
                        <div>SUN</div>
                        <div>MON</div>
                        <div>TUE</div>
                        <div>WED</div>
                        <div>THU</div>
                        <div>FRI</div>
                        <div>SAT</div>
                    </div>

                    <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center text-sm">
                        <!-- Calendar days will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button id="btnHistory" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">
                    History
                </button>
            </div>

        </main>
    </div>

    <!-- New/Edit Event Modal -->
    <div id="eventModal" title="Add New Event" class="hidden">
        <form id="eventForm" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="eventId">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES); ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Event Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="eventTitle" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Date <span class="text-red-500">*</span></label>
                    <input type="date" name="event_date" id="eventDate" required
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Time</label>
                    <input type="time" name="event_time" id="eventTime"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" name="location" id="eventLocation"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="eventDescription" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" id="eventPriority"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="eventStatus"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- History Modal -->
    <div id="historyModal" title="Event History" class="hidden">
        <div class="mb-4">
            <input type="text" id="historySearch" placeholder="Search history..."
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div id="historyList" class="max-h-96 overflow-y-auto space-y-2">
            <div class="text-center text-gray-500 py-8">Loading history...</div>
        </div>
    </div>

    <script src="js/index.js"></script>
</body>
</html>

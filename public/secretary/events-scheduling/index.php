<?php
/**
 * Events & Scheduling Management — Redesigned
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
    <style>
        /* ── Priority left-border accents ── */
        .event-card-normal {
            border-left: 4px solid #94a3b8;
        }

        .event-card-important {
            border-left: 4px solid #22c55e;
        }

        .event-card-urgent {
            border-left: 4px solid #ef4444;
        }

        /* ── Calendar ── */
        .cal-day {
            min-height: 68px;
            border-radius: 8px;
            padding: 6px;
            transition: background .15s;
        }

        .cal-day:hover {
            background: #f1f5f9;
        }

        .cal-day.today {
            outline: 2px solid var(--theme-primary, #2563eb);
            outline-offset: -2px;
        }

        .cal-day.other-month {
            opacity: .35;
        }

        .cal-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin: 1px;
        }

        .cal-dot.normal {
            background: #94a3b8;
        }

        .cal-dot.important {
            background: #22c55e;
        }

        .cal-dot.urgent {
            background: #ef4444;
        }

        /* ── Tabs ── */
        .tab-btn {
            border-bottom: 3px solid transparent;
            transition: all .2s;
        }

        .tab-btn.active {
            border-color: var(--theme-primary, #2563eb);
            color: var(--theme-primary, #2563eb);
            font-weight: 600;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* ── Stat cards ── */
        .stat-card {
            transition: transform .2s, box-shadow .2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        }

        /* ── Skeleton shimmer ── */
        @keyframes shimmer {
            0% {
                background-position: -400px 0
            }

            100% {
                background-position: 400px 0
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 400px 100%;
            animation: shimmer 1.2s infinite;
            border-radius: 6px;
            height: 62px;
            margin-bottom: 10px;
        }

        /* ── Fade-in ── */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .event-card-anim {
            animation: fadeInUp .25s ease forwards;
        }

        /* ── Print ── */
        @media print {

            aside,
            nav,
            .no-print {
                display: none !important;
            }

            main {
                padding: 0 !important;
            }

            .print-section {
                display: block !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>
        <main class="pb-24 overflow-y-auto flex-1 p-6">

            <!-- ── Page Header ── -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">🗓️ Events &amp; Scheduling</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage barangay events, track schedules, and view history
                    </p>
                </div>
                <div class="flex gap-2 flex-wrap no-print">
                    <input type="text" id="globalSearch" placeholder="Search events…"
                        class="border border-gray-300 rounded-xl px-3 py-2 text-sm w-44 focus:ring-2 focus:ring-blue-300 focus:outline-none">
                    <select id="filterPriority"
                        class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                        <option value="">All Priorities</option>
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <button id="btnPrintEvents"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                        🖨️ Print
                    </button>
                    <button id="btnNewEvent"
                        class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
                        + New Event
                    </button>
                </div>
            </div>

            <!-- ── Stat Cards ── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6 no-print">
                <div class="stat-card bg-white rounded-2xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-xl">📋</div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800" id="statTotal">—</div>
                        <div class="text-xs text-gray-500 font-medium">Total This Month</div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center text-xl">✅</div>
                    <div>
                        <div class="text-2xl font-bold text-green-700" id="statScheduled">—</div>
                        <div class="text-xs text-gray-500 font-medium">Scheduled</div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center text-xl">🚨</div>
                    <div>
                        <div class="text-2xl font-bold text-red-600" id="statUrgent">—</div>
                        <div class="text-xs text-gray-500 font-medium">Urgent</div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center text-xl">📅</div>
                    <div>
                        <div class="text-2xl font-bold text-purple-700" id="statUpcoming">—</div>
                        <div class="text-xs text-gray-500 font-medium">Next 7 Days</div>
                    </div>
                </div>
            </div>

            <!-- ── Tabs ── -->
            <div class="bg-white rounded-2xl shadow border overflow-hidden">
                <!-- Tab Nav -->
                <div class="flex border-b px-4 gap-1 no-print">
                    <button class="tab-btn active px-4 py-3 text-sm text-gray-600 hover:text-blue-600"
                        data-tab="calendar">📅 Calendar</button>
                    <button class="tab-btn px-4 py-3 text-sm text-gray-600 hover:text-blue-600" data-tab="list">📋 Event
                        List</button>
                    <button class="tab-btn px-4 py-3 text-sm text-gray-600 hover:text-blue-600" data-tab="history">🕓
                        History</button>
                </div>

                <!-- ── TAB: CALENDAR ── -->
                <div class="tab-pane active p-5" id="tab-calendar">
                    <div class="flex flex-col lg:flex-row gap-5">

                        <!-- Left: Upcoming events mini-list -->
                        <div class="w-full lg:w-72 flex-shrink-0">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold text-sm text-gray-700">Upcoming Events</h3>
                                <span id="upcomingCount"
                                    class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">—</span>
                            </div>
                            <div id="eventsList" class="space-y-2 max-h-[460px] overflow-y-auto pr-1">
                                <div class="skeleton"></div>
                                <div class="skeleton" style="height:52px"></div>
                                <div class="skeleton" style="height:62px"></div>
                            </div>
                        </div>

                        <!-- Right: Calendar -->
                        <div class="flex-1">
                            <!-- Month nav -->
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-2">
                                    <button id="prevMonth"
                                        class="w-8 h-8 rounded-lg border hover:bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-lg">‹</button>
                                    <h3 class="text-base font-bold text-gray-800 w-40 text-center"
                                        id="calendarMonthYear">—</h3>
                                    <button id="nextMonth"
                                        class="w-8 h-8 rounded-lg border hover:bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-lg">›</button>
                                </div>
                                <button id="todayBtn"
                                    class="text-xs border rounded-lg px-3 py-1.5 hover:bg-gray-50 text-gray-600 font-medium">Today</button>
                            </div>

                            <!-- Day headers -->
                            <div
                                class="grid grid-cols-7 text-center text-xs font-semibold text-gray-400 uppercase mb-2 tracking-wider">
                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>
                            </div>

                            <!-- Calendar grid -->
                            <div id="calendarGrid" class="grid grid-cols-7 gap-1 text-sm"></div>

                            <!-- Legend -->
                            <div class="flex gap-4 mt-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1"><span class="cal-dot normal"></span> Normal</span>
                                <span class="flex items-center gap-1"><span class="cal-dot important"></span>
                                    Important</span>
                                <span class="flex items-center gap-1"><span class="cal-dot urgent"></span> Urgent</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: EVENT LIST ── -->
                <div class="tab-pane p-5" id="tab-list">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-sm text-gray-700">All Scheduled Events</h3>
                        <select id="listStatusFilter" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                            <option value="scheduled">Scheduled</option>
                            <option value="all">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="eventsTable" class="display min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Location</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- ── TAB: HISTORY ── -->
                <div class="tab-pane p-5" id="tab-history">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-sm text-gray-700">Completed &amp; Cancelled Events</h3>
                        <input type="text" id="historySearch" placeholder="Search history…"
                            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-44 focus:ring-2 focus:outline-none">
                    </div>
                    <div id="historyList" class="space-y-2 max-h-[420px] overflow-y-auto">
                        <div class="text-center text-gray-400 py-10 text-sm">Click the History tab to load records.
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ── Add / Edit Event Dialog ── -->
    <div id="eventModal" title="New Event" class="hidden">
        <form id="eventForm" class="space-y-4 p-1">
            <input type="hidden" name="id" id="eventId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Event Title <span
                        class="text-red-500">*</span></label>
                <input type="text" name="title" id="eventTitle" placeholder="Enter event title…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="event_date" id="eventDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                    <input type="time" name="event_time" id="eventTime"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" name="location" id="eventLocation" placeholder="Venue or place…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="eventDescription" rows="3" placeholder="Event details…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" id="eventPriority"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm">
                        <option value="normal">🔵 Normal</option>
                        <option value="important">🟢 Important</option>
                        <option value="urgent">🔴 Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="eventStatus"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- ── View Event Dialog ── -->
    <div id="viewEventModal" title="Event Details" class="hidden">
        <div id="viewEventContent" class="p-2"></div>
    </div>

    <!-- ── Delete Confirm Dialog ── -->
    <div id="deleteEventDialog" title="Confirm Delete" class="hidden">
        <p class="p-4 text-gray-700">Are you sure you want to delete this event? This action cannot be undone.</p>
        <input type="hidden" id="deleteEventId">
    </div>

    <!-- ── Day Events Dialog ── -->
    <div id="dayEventsDialog" title="Events on this day" class="hidden">
        <div id="dayEventsList" class="p-2 space-y-3 max-h-80 overflow-y-auto"></div>
    </div>

    <!-- Print template (hidden, printed via JS) -->
    <div id="printArea" class="hidden print-section"></div>

    <script src="js/index.js"></script>
</body>

</html>
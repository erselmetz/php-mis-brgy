<?php
require_once __DIR__ . '/../../../includes/app.php';
requireAdmin();
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
                    <input type="text"
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

                    <div class="space-y-3">

                        <!-- Event Item -->
                        <button class="w-full text-left border p-3 rounded-lg bg-white hover:border-theme-primary">
                            <span class="text-green-600 font-semibold text-sm">Jan 12 · 6:00 PM</span>
                            <p class="font-medium">Lighting the Barangay Court</p>
                        </button>

                        <button class="w-full text-left border p-3 rounded-lg bg-white hover:border-theme-primary">
                            <span class="text-red-600 font-semibold text-sm">Jan 21 · 9:00 AM</span>
                            <p class="font-medium">Barangay Meeting (Urgent)</p>
                        </button>

                        <button class="w-full text-left border p-3 rounded-lg bg-white hover:border-theme-primary">
                            <span class="text-gray-700 font-semibold text-sm">Jan 22 · 11:00 AM</span>
                            <p class="font-medium">Immunization – Purok 2</p>
                        </button>

                    </div>
                </div>


                <!-- Right: Calendar -->
                <div class="flex-1 border rounded-xl p-4 bg-white">

                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold">January 2025</h3>
                        <div class="space-x-2 text-sm">
                            <button class="px-3 py-1 border rounded hover:bg-gray-100">‹</button>
                            <button class="px-3 py-1 border rounded hover:bg-gray-100">›</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 text-center text-xs text-gray-500 mb-2">
                        <div>MON</div>
                        <div>TUE</div>
                        <div>WED</div>
                        <div>THU</div>
                        <div>FRI</div>
                        <div>SAT</div>
                        <div>SUN</div>
                    </div>

                    <div class="grid grid-cols-7 gap-2 text-center text-sm">
                        <div class="py-2 text-gray-400">30</div>
                        <div class="py-2 text-gray-400">31</div>

                        <button class="py-2 rounded hover:bg-gray-100">1</button>
                        <button class="py-2 rounded hover:bg-gray-100">2</button>

                        <button class="py-2 rounded-full bg-theme-primary text-white font-semibold">12</button>

                        <button class="py-2 rounded-full bg-red-400 text-white font-semibold">21</button>
                    </div>

                </div>

            </div>

            <div class="flex justify-end mt-6">
                <button class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">History</button>
            </div>

        </main>

        <!-- New Event Modal -->
        <div id="addEventModal" title="Add New Event" class="hidden">
            <form id="addEventForm" method="POST" class="space-y-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Title *</label>
                    <input type="text" name="title" id="eventTitle" required
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event Date *</label>
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" id="eventPriority"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full bg-theme-primary hover-theme-darker text-white py-2 rounded font-semibold">
                        Save Event
                    </button>
                </div>
            </form>
        </div>


    </div>

    <script>
        $(function() {
            $("body").show();

            $("#addEventModal").dialog({
                autoOpen: false,
                modal: true,
                width: 700,
                height: 'auto',
                resizable: true,
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-title': 'font-semibold text-lg',
                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                },
                open: function() {
                    $('.ui-dialog-buttonpane button')
                        .addClass('bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded');
                }
            });

            // Open modal when button clicked
            $("#btnNewEvent").on("click", function() {
                $("#addEventModal").dialog("open");
            });



            $("#addEventForm").on("submit", function(e) {
                e.preventDefault();

                $.post("actions/insert_event.php", $(this).serialize(), function(res) {
                    if (res.success) {
                        alert("✅ " + res.message);
                        $("#addEventModal").dialog("close");
                        // Optional: reload events list or refresh calendar
                    } else {
                        alert("❌ " + res.message);
                    }
                }, "json");
            });


        });
    </script>
</body>

</html>
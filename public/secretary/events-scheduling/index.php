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
requireAdmin();

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

    <script>
        $(function() {
            try {
                $("body").show();

                let currentMonth = new Date().getMonth() + 1;
                let currentYear = new Date().getFullYear();
                let events = [];
                let currentEditId = null;

            // Initialize modals
            $("#eventModal").dialog({
                autoOpen: false,
                modal: true,
                width: 600,
                height: 'auto',
                maxHeight: 600,
                resizable: true,
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                    'ui-dialog-title': 'font-semibold',
                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg',
                    'ui-dialog-content': 'overflow-y-auto max-h-[500px]'
                },
                buttons: {
                    "Save": function() {
                        $('#eventForm').submit();
                    },
                    "Cancel": function() {
                        $(this).dialog("close");
                    }
                },
                open: function() {
                    $(this).find('.ui-dialog-content').css({
                        'overflow-y': 'auto',
                        'max-height': '500px'
                    });
                    $(".ui-dialog-buttonpane button:contains('Save')")
                        .addClass("bg-green-800 hover:bg-green-700 text-white px-4 py-2 rounded mr-2");
                    $(".ui-dialog-buttonpane button:contains('Cancel')")
                        .addClass("bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded");
                }
            });

            $("#historyModal").dialog({
                autoOpen: false,
                modal: true,
                width: 700,
                height: 600,
                resizable: true,
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                    'ui-dialog-title': 'font-semibold',
                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg',
                    'ui-dialog-content': 'overflow-y-auto max-h-[500px]'
                },
                buttons: {
                    "Close": function() {
                        $(this).dialog("close");
                    }
                },
                open: function() {
                    $(this).find('.ui-dialog-content').css({
                        'overflow-y': 'auto',
                        'max-height': '500px'
                    });
                    loadHistory();
                }
            });

            // Load events
            function loadEvents() {
                $('#eventsList').html('<div class="text-center text-gray-500 py-8">Loading events...</div>');
                
                // Always render calendar first (even if events fail to load)
                renderCalendar();
                
                $.getJSON('actions/fetch_events.php', {
                    month: currentMonth,
                    year: currentYear,
                    status: 'scheduled'
                }, function(res) {
                    if (res && res.status === 'ok') {
                        events = res.data || [];
                        renderEventsList();
                        renderCalendar(); // Re-render calendar with events
                    } else {
                        events = [];
                        const errorMsg = res && res.message ? res.message : 'Failed to load events';
                        if (errorMsg.includes('table not found')) {
                            $('#eventsList').html('<div class="text-center text-yellow-600 py-8 text-sm">' + errorMsg + '</div>');
                        } else {
                            showMessage('Error', errorMsg, true);
                            $('#eventsList').html('<div class="text-center text-red-500 py-8">Error loading events</div>');
                        }
                        renderCalendar(); // Re-render calendar without events
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Load events error:', status, error, xhr.responseText);
                    events = [];
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.message) {
                            showMessage('Error', response.message, true);
                        } else {
                            showMessage('Error', 'Failed to load events. Please check console for details.', true);
                        }
                    } catch (e) {
                        showMessage('Error', 'Failed to load events. Please check console for details.', true);
                    }
                    $('#eventsList').html('<div class="text-center text-red-500 py-8">Error loading events</div>');
                    renderCalendar(); // Re-render calendar without events
                });
            }

            // Render events list
            function renderEventsList() {
                const $list = $('#eventsList');
                $list.empty();

                if (events.length === 0) {
                    $list.html('<div class="text-center text-gray-500 py-8">No upcoming events</div>');
                    return;
                }

                events.forEach(function(event) {
                    const date = new Date(event.event_date);
                    const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    const timeStr = event.event_time ? new Date('2000-01-01 ' + event.event_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '';
                    
                    const priorityColors = {
                        'normal': 'text-gray-700',
                        'important': 'text-green-600',
                        'urgent': 'text-red-600'
                    };
                    
                    const html = `
                        <div class="w-full text-left border p-3 rounded-lg bg-white hover:border-theme-primary cursor-pointer event-item" 
                             data-id="${event.id}">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <span class="${priorityColors[event.priority] || 'text-gray-700'} font-semibold text-sm">
                                        ${dateStr}${timeStr ? ' · ' + timeStr : ''}
                                    </span>
                                    <p class="font-medium mt-1">${escapeHtml(event.title)}</p>
                                    ${event.location ? `<p class="text-xs text-gray-500 mt-1">📍 ${escapeHtml(event.location)}</p>` : ''}
                                </div>
                                <div class="flex space-x-1 ml-2">
                                    <button class="edit-event-btn text-blue-600 hover:text-blue-800 text-xs px-2 py-1" data-id="${event.id}">Edit</button>
                                    <button class="delete-event-btn text-red-600 hover:text-red-800 text-xs px-2 py-1" data-id="${event.id}" data-title="${escapeHtml(event.title)}">Delete</button>
                                </div>
                            </div>
                        </div>
                    `;
                    $list.append(html);
                });
            }

            // Render calendar
            function renderCalendar() {
                try {
                    // Get first day of month (0 = Sunday, 1 = Monday, etc.)
                    const firstDay = new Date(currentYear, currentMonth - 1, 1);
                    const lastDay = new Date(currentYear, currentMonth, 0);
                    const daysInMonth = lastDay.getDate();
                    const startingDayOfWeek = firstDay.getDay(); // 0 = Sunday, 6 = Saturday

                    // Update month/year display
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                                       'July', 'August', 'September', 'October', 'November', 'December'];
                    $('#calendarMonthYear').text(monthNames[currentMonth - 1] + ' ' + currentYear);

                    // Get previous month's last days
                    const prevMonth = new Date(currentYear, currentMonth - 1, 0);
                    const prevMonthDays = prevMonth.getDate();

                    let html = '';
                    
                    // Previous month's trailing days (fill in days before the 1st)
                    // startingDayOfWeek: 0=Sunday, 1=Monday, ..., 6=Saturday
                    // We need to show (startingDayOfWeek) number of days from previous month
                    for (let i = 0; i < startingDayOfWeek; i++) {
                        const day = prevMonthDays - (startingDayOfWeek - 1 - i);
                        html += `<div class="py-2 text-gray-400">${day}</div>`;
                    }

                    // Current month's days
                    const today = new Date();
                    const isCurrentMonth = today.getMonth() + 1 === currentMonth && today.getFullYear() === currentYear;
                    
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                        const dayEvents = events.filter(e => e.event_date === dateStr);
                        
                        let classes = 'py-2 rounded hover:bg-gray-100 cursor-pointer';
                        let content = day;
                        
                        // Highlight today
                        if (isCurrentMonth && day === today.getDate()) {
                            classes += ' border-2 border-blue-500';
                        }
                        
                        if (dayEvents.length > 0) {
                            const hasUrgent = dayEvents.some(e => e.priority === 'urgent');
                            const hasImportant = dayEvents.some(e => e.priority === 'important');
                            
                            if (hasUrgent) {
                                classes += ' bg-red-400 text-white font-semibold';
                            } else if (hasImportant) {
                                classes += ' bg-green-400 text-white font-semibold';
                            } else {
                                classes += ' bg-theme-primary text-white font-semibold';
                            }
                            
                            content = `<div class="relative">
                                ${day}
                                <span class="absolute top-0 right-0 text-xs">${dayEvents.length}</span>
                            </div>`;
                        }
                        
                        html += `<button class="${classes}" data-date="${dateStr}">${content}</button>`;
                    }

                    // Next month's leading days (fill remaining cells to complete 6 weeks)
                    const totalCells = 42; // 6 weeks * 7 days
                    const cellsUsed = startingDayOfWeek + daysInMonth;
                    const remainingCells = totalCells - cellsUsed;
                    
                    for (let day = 1; day <= remainingCells; day++) {
                        html += `<div class="py-2 text-gray-400">${day}</div>`;
                    }

                    $('#calendarGrid').html(html);
                } catch (error) {
                    console.error('Calendar rendering error:', error);
                    $('#calendarGrid').html('<div class="col-span-7 text-center text-red-500 py-8">Error rendering calendar</div>');
                }
            }

            // Load history
            function loadHistory(search = '') {
                $.getJSON('actions/fetch_event_history.php', { search: search }, function(res) {
                    const $list = $('#historyList');
                    $list.empty();

                    if (res.status === 'ok' && res.data) {
                        const history = res.data;
                        if (history.length === 0) {
                            $list.html('<div class="text-center text-gray-500 py-8">No history found</div>');
                            return;
                        }

                        history.forEach(function(event) {
                            const date = new Date(event.event_date);
                            const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            const timeStr = event.event_time ? new Date('2000-01-01 ' + event.event_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '';
                            
                            const statusColors = {
                                'completed': 'bg-green-100 text-green-800',
                                'cancelled': 'bg-red-100 text-red-800'
                            };
                            
                            const html = `
                                <div class="border rounded-lg p-3 bg-white">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <span class="text-gray-600 font-semibold text-sm">${dateStr}${timeStr ? ' · ' + timeStr : ''}</span>
                                            <p class="font-medium mt-1">${escapeHtml(event.title)}</p>
                                            ${event.location ? `<p class="text-xs text-gray-500 mt-1">📍 ${escapeHtml(event.location)}</p>` : ''}
                                            ${event.description ? `<p class="text-xs text-gray-600 mt-2">${escapeHtml(event.description)}</p>` : ''}
                                        </div>
                                        <span class="px-2 py-1 rounded text-xs font-semibold ${statusColors[event.status] || 'bg-gray-100 text-gray-800'}">
                                            ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                                        </span>
                                    </div>
                                </div>
                            `;
                            $list.append(html);
                        });
                    } else {
                        $list.html('<div class="text-center text-red-500 py-8">Error loading history</div>');
                    }
                });
            }

            // Escape HTML
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
            }

            // Event handlers
            $("#btnNewEvent").on("click", function() {
                currentEditId = null;
                $("#eventForm")[0].reset();
                $("#eventId").val('');
                $("#eventModal").dialog("option", "title", "Add New Event");
                $("#eventModal").dialog("open");
            });

            $(document).on('click', '.edit-event-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const event = events.find(e => e.id == id);
                
                if (event) {
                    currentEditId = id;
                    $("#eventId").val(event.id);
                    $("#eventTitle").val(event.title);
                    $("#eventDate").val(event.event_date);
                    $("#eventTime").val(event.event_time || '');
                    $("#eventLocation").val(event.location || '');
                    $("#eventDescription").val(event.description || '');
                    $("#eventPriority").val(event.priority || 'normal');
                    $("#eventStatus").val(event.status || 'scheduled');
                    
                    $("#eventModal").dialog("option", "title", "Edit Event");
                    $("#eventModal").dialog("open");
                }
            });

            $(document).on('click', '.delete-event-btn', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const title = $(this).data('title');
                
                showConfirm(
                    'Confirm Delete',
                    'Are you sure you want to delete "' + title + '"? This action cannot be undone.',
                    function() {
                        $.post('actions/delete_event.php', { id: id }, function(res) {
                            if (res.success) {
                                showMessage('Success', res.message);
                                loadEvents();
                            } else {
                                showMessage('Error', res.message, true);
                            }
                        }, 'json');
                    }
                );
            });

            $("#btnHistory").on("click", function() {
                $("#historyModal").dialog("open");
            });

            // Calendar navigation
            $("#prevMonth").on("click", function() {
                currentMonth--;
                if (currentMonth < 1) {
                    currentMonth = 12;
                    currentYear--;
                }
                loadEvents();
            });

            $("#nextMonth").on("click", function() {
                currentMonth++;
                if (currentMonth > 12) {
                    currentMonth = 1;
                    currentYear++;
                }
                loadEvents();
            });

            $("#todayBtn").on("click", function() {
                const today = new Date();
                currentMonth = today.getMonth() + 1;
                currentYear = today.getFullYear();
                loadEvents();
            });

            // Search
            let searchTimeout;
            $("#searchInput").on("keyup", function() {
                clearTimeout(searchTimeout);
                const search = $(this).val();
                searchTimeout = setTimeout(function() {
                    $.getJSON('actions/fetch_events.php', {
                        month: currentMonth,
                        year: currentYear,
                        status: 'scheduled',
                        search: search
                    }, function(res) {
                        if (res.status === 'ok') {
                            events = res.data || [];
                            renderEventsList();
                        }
                    });
                }, 500);
            });

            $("#historySearch").on("keyup", function() {
                clearTimeout(searchTimeout);
                const search = $(this).val();
                searchTimeout = setTimeout(function() {
                    loadHistory(search);
                }, 500);
            });

            // Form submission
            $("#eventForm").on("submit", function(e) {
                e.preventDefault();
                const $btn = $(".ui-dialog-buttonpane button:contains('Save')");
                $btn.prop('disabled', true).text('Saving...');

                const url = currentEditId ? 'actions/update_event.php' : 'actions/insert_event.php';
                const formData = $(this).serialize();

                $.post(url, formData, function(res) {
                    if (res.success) {
                        $("#eventModal").dialog("close");
                        showMessage('Success', res.message);
                        loadEvents();
                        $("#eventForm")[0].reset();
                    } else {
                        showMessage('Error', res.message, true);
                    }
                }, 'json').fail(function() {
                    showMessage('Error', 'Request failed. Please try again.', true);
                }).always(function() {
                    $btn.prop('disabled', false).text('Save');
                });
            });


            // Initial load
            loadEvents();
            } catch (error) {
                console.error('Page initialization error:', error);
                showMessage('Error', 'Failed to initialize page. Please refresh.', true);
            }
        });
    </script>
</body>
</html>

$(function () {
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
                "Save": function () {
                    $('#eventForm').submit();
                },
                "Cancel": function () {
                    $(this).dialog("close");
                }
            },
            open: function () {
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
                "Close": function () {
                    $(this).dialog("close");
                }
            },
            open: function () {
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
            }, function (res) {
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
            }).fail(function (xhr, status, error) {
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

            events.forEach(function (event) {
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

        // Show message function
        function showMessage(title, message, isError = false) {
            const dialogId = 'msg_' + Date.now();
            const colorClass = isError ? 'text-red-600' : 'text-green-600';

            $('body').append(`
            <div id="${dialogId}" title="${title}" style="display:none;">
                <p class="${colorClass}">${message}</p>
            </div>
        `);

            $(`#${dialogId}`).dialog({
                modal: true,
                width: 400,
                buttons: {
                    "OK": function () {
                        $(this).dialog("close");
                    }
                },
                close: function () {
                    $(this).remove();
                }
            });
        }

        // Escape HTML function
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load history
        function loadHistory(search = '') {
            $.getJSON('actions/fetch_event_history.php', { search: search }, function (res) {
                const $list = $('#historyList');
                $list.empty();

                if (res.status === 'ok' && res.data) {
                    const history = res.data;
                    if (history.length === 0) {
                        $list.html('<div class="text-center text-gray-500 py-8">No history found</div>');
                        return;
                    }

                    history.forEach(function (event) {
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

        // Event handlers
        let searchTimeout;

        // Navigation buttons
        $("#prevMonth").on("click", function () {
            currentMonth--;
            if (currentMonth < 1) {
                currentMonth = 12;
                currentYear--;
            }
            loadEvents();
        });

        $("#nextMonth").on("click", function () {
            currentMonth++;
            if (currentMonth > 12) {
                currentMonth = 1;
                currentYear++;
            }
            loadEvents();
        });

        // Today Button
        $("#todayBtn").on("click", function () {
            const today = new Date();
            currentMonth = today.getMonth() + 1;
            currentYear = today.getFullYear();
            loadEvents();
        });


        // Calendar date click
        $(document).on("click", "#calendarGrid button", function () {
            const date = $(this).data("date");
            if (date) {
                $("#eventModal").dialog("open");
                $("#eventForm")[0].reset();
                $("#eventId").val('');
                $("#eventDate").val(date);
                currentEditId = null;
                $("#eventModal").dialog("option", "title", "Add New Event");
            }
        });

        $('#btnNewEvent').on('click', function () {
            $("#eventModal").dialog("open");
            $("#eventForm")[0].reset();
            $("#eventId").val('');
            currentEditId = null;
            $("#eventModal").dialog("option", "title", "Add New Event");
        });

        // Event item click
        $(document).on("click", ".event-item", function (e) {
            if ($(e.target).closest(".edit-event-btn").length) {
                return; // ⛔ Skip when clicking edit OR delete
            }

            const eventId = $(this).data("id");
            const event = events.find(e => e.id == eventId);
            if (event) {
                $("#eventModal").dialog("open");
                $("#eventId").val(event.id);
                $("#eventTitle").val(event.title);
                $("#eventDate").val(event.event_date);
                $("#eventTime").val(event.event_time);
                $("#eventLocation").val(event.location);
                $("#eventDescription").val(event.description);
                $("#eventPriority").val(event.priority);
                $("#eventStatus").val(event.status);
                currentEditId = eventId;
                $("#eventModal").dialog("option", "title", "Edit Event");
            }
        });


        // Edit event button
        $(document).on("click", ".edit-event-btn", function (e) {
            e.stopPropagation();
            const eventId = $(this).data("id");
            const event = events.find(e => e.id == eventId);
            if (event) {
                $("#eventModal").dialog("open");
                $("#eventId").val(event.id);
                $("#eventTitle").val(event.title);
                $("#eventDate").val(event.event_date);
                $("#eventTime").val(event.event_time);
                $("#eventLocation").val(event.location);
                $("#eventDescription").val(event.description);
                $("#eventPriority").val(event.priority);
                $("#eventStatus").val(event.status);
                currentEditId = eventId;
                $("#eventModal").dialog("option", "title", "Edit Event");
            }
        });

        // History button
        $("#btnHistory").on("click", function () {
            $("#historyModal").dialog("open");
        });

        // Search functionality with debouncing
        $("#historySearch").on("keyup", function () {
            clearTimeout(searchTimeout);
            const search = $(this).val();
            searchTimeout = setTimeout(function () {
                loadHistory(search);
            }, 500);
        });

        // Form submission
        $("#eventForm").on("submit", function (e) {
            e.preventDefault();
            const $btn = $(".ui-dialog-buttonpane button:contains('Save')");
            $btn.prop('disabled', true).text('Saving...');

            const url = currentEditId ? 'actions/update_event.php' : 'actions/insert_event.php';
            const formData = $(this).serialize();

            $.post(url, formData, function (res) {
                if (res.success) {
                    $("#eventModal").dialog("close");
                    showMessage('Success', res.message);
                    loadEvents();
                    $("#eventForm")[0].reset();
                } else {
                    showMessage('Error', res.message, true);
                }
            }, 'json').fail(function () {
                showMessage('Error', 'Request failed. Please try again.', true);
            }).always(function () {
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
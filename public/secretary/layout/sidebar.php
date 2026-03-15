<!-- Sidebar -->
<aside class="pb-24 w-60 bg-white shadow-md min-h-screen border-r border-gray-200 sticky top-0 overflow-y-auto">
    <nav class="p-2 space-y-2">
        <a href="/secretary/dashboard/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            🏠 Dashboard
        </a>

        <a href="/secretary/resident/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            👥 Residents
        </a>

        <a href="/secretary/admin/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            🧑 Official & Staff
        </a>

        <a href="/secretary/certificate/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            📊 Certificates
        </a>

        <a href="/secretary/blotter/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            📝 Blotter
        </a>

        <!-- Scheduling Dropdown -->
        <div class="relative">
            <button type="button" id="schedulingToggle"
                class="w-full text-left px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 flex items-center justify-between">
                <span>📅 Scheduling</span>
                <span id="schedulingArrow" class="text-xs transition-transform duration-200">▼</span>
            </button>

            <div id="schedulingMenu" class="hidden mt-1 ml-3 space-y-1">
                <a href="/secretary/events-scheduling/"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 text-sm">
                    🗓️ Events & Scheduling
                </a>
                <a href="/secretary/tanod-duty-schedule/"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 text-sm">
                    🛡️ Tanod Duty Schedule
                </a>
                <a href="/secretary/court-schedule/"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 text-sm">
                    🏀 Court / Facility Schedule
                </a>
                <a href="/secretary/borrowing-schedule/"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 text-sm">
                    📚 Borrowing Schedule
                </a>
                <a href="/secretary/patrol-schedule/"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 text-sm">
                    🚓 Patrol Schedule
                </a>
            </div>
        </div>

        <a href="/secretary/inventory/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            📁 Inventory
        </a>

        <a href="/secretary/profile/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            ⚙️ Settings
        </a>

        <a href="/logout.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-red-200">
            🚪 Logout
        </a>

        <hr class="my-2 border-gray-300">
    </nav>
</aside>

<script>
    (function () {
        const toggle = document.getElementById('schedulingToggle');
        const menu = document.getElementById('schedulingMenu');
        const arrow = document.getElementById('schedulingArrow');

        // Auto-expand if current page is a scheduling sub-page
        const path = window.location.pathname;
        const schedulingPaths = [
            '/secretary/events-scheduling/',
            '/secretary/tanod-duty-schedule/',
            '/secretary/court-schedule/',
            '/secretary/borrowing-schedule/',
            '/secretary/patrol-schedule/'
        ];
        const isSchedulingPage = schedulingPaths.some(p => path.startsWith(p));

        if (isSchedulingPage) {
            menu.classList.remove('hidden');
            arrow.style.transform = 'rotate(180deg)';

            // Highlight active link
            document.querySelectorAll('#schedulingMenu a').forEach(function (link) {
                if (path.startsWith(link.getAttribute('href'))) {
                    link.classList.add('bg-gray-200', 'font-semibold');
                }
            });
        }

        toggle.addEventListener('click', function () {
            const isHidden = menu.classList.contains('hidden');
            menu.classList.toggle('hidden');
            arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
        });
    })();
</script>
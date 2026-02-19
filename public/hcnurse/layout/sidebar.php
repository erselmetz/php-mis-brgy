<!-- Sidebar -->
<aside class="pb-24 w-60 bg-white shadow-md min-h-screen border-r border-gray-200 sticky top-0 overflow-y-auto">
    <nav class="p-2 space-y-2">
        <a href="/hcnurse/dashboard/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            🏠 Dashboard
        </a>

        <a href="/hcnurse/resident/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            👥 Residents
        </a>

        <a href="/hcnurse/consultation/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            📝 Consultation
        </a>

        <!-- Health Records Dropdown -->
        <div class="relative">
            <!-- Toggle Button -->
            <button type="button" id="healthRecordsToggle"
                class="w-full text-left px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 flex items-center justify-between">
                <span>🩺 Health Records</span>
                <span id="healthRecordsArrow" class="text-xs">▼</span>
            </button>

            <!-- Dropdown Items -->
            <div id="healthRecordsMenu" class="hidden mt-1 ml-3 space-y-1">
                <a href="/hcnurse/health-records/?type=immunization"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
                    💉 Immunization
                </a>

                <a href="/hcnurse/health-records/?type=maternal"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
                    🤱 Maternal
                </a>

                <a href="/hcnurse/health-records/?type=family_planning"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
                    💊 Family Planning
                </a>

                <a href="/hcnurse/health-records/?type=prenatal"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
                    👶 Prenatal Care
                </a>

                <a href="/hcnurse/health-records/?type=postnatal"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
                    🍼 Postnatal Care
                </a>

                <a href="/hcnurse/health-records/?type=child_nutrition"
                    class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
                    🥗 Child Nutrition
                </a>
            </div>
        </div>

        <script>
            // Sidebar dropdown toggle (no jQuery needed)
            (function() {
                const toggle = document.getElementById('healthRecordsToggle');
                const menu = document.getElementById('healthRecordsMenu');
                const arrow = document.getElementById('healthRecordsArrow');

                if (!toggle || !menu) return;

                toggle.addEventListener('click', function() {
                    const isHidden = menu.classList.contains('hidden');
                    if (isHidden) {
                        menu.classList.remove('hidden');
                        arrow.textContent = '▲';
                    } else {
                        menu.classList.add('hidden');
                        arrow.textContent = '▼';
                    }
                });

                // Auto-open dropdown if currently on /hcnurse/health-records/
                const path = window.location.pathname;
                if (path.includes('/hcnurse/health-records/')) {
                    menu.classList.remove('hidden');
                    arrow.textContent = '▲';
                }
            })();
        </script>

        <a href="/hcnurse/inventory/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            📁 Inventory
        </a>

        <a href="/hcnurse/profile/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
            ⚙️ Settings
        </a>

        <a href="/logout.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-red-200">
            🚪 Logout
        </a>

        <hr class="my-2 border-gray-300">
    </nav>
</aside>
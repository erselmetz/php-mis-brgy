        <nav class="bg-blue-700 text-white p-4 flex justify-between items-center relative">
            <!-- Left: Title + Navigation -->
            <div class="flex items-center space-x-6">
                <h1 class="text-2xl font-bold">MIS Barangay</h1>
                <!-- <a href="/dashboard.php" class="hover:text-gray-200 font-medium">Dashboard</a> -->
                <!-- <a href="/officer.php" class="hover:text-gray-200 font-medium">Officer</a> -->
                <!-- <a href="/resident.php" class="hover:text-gray-200 font-medium">Resident</a> -->
                <!-- <a href="/certificate.php" class="hover:text-gray-200 font-medium">Certificate</a> -->
            </div>

            <!-- Right: Profile Dropdown -->
            <div class="relative">
                <button id="userDropdownBtn" class="flex items-center bg-blue-800 px-3 py-2 rounded hover:bg-blue-900 focus:outline-none">
                    <span class="mr-2">👤 <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div id="userDropdown"
                    class="hidden absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded shadow-lg z-50">
                    <a href="/profile.php" class="block px-4 py-2 hover:bg-gray-100">Profile Account</a>
                    <?php if ($_SESSION['role'] == "admin") {
                        echo "<a href='/admin/account.php' class='block px-4 py-2 hover:bg-gray-100'>Manage Account</a>";
                    } ?>
                    <hr>
                    <a href="/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 font-medium">Logout</a>
                </div>
            </div>
        </nav>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('userDropdownBtn');
                const menu = document.getElementById('userDropdown');

                btn.addEventListener('click', () => {
                    menu.classList.toggle('hidden');
                });

                // Hide dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!btn.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });
            });
        </script>
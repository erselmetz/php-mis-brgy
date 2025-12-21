        <nav class="text-white p-4 flex justify-between items-center relative shadow-lg" style="background-color: var(--theme-secondary);">
            <!-- Left: Logo + Title + Navigation -->
            <div class="flex items-center space-x-4">
                <img src="/assets/images/logo.ico" alt="Barangay Logo" class="w-10 h-10 object-contain bg-white rounded-lg p-1 shadow-md">
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold">MIS Barangay</h1>
                    <p class="text-xs" style="color: var(--theme-accent);">Barangay Bombongan</p>
                </div>
            </div>

            <!-- Right: Profile Dropdown -->
            <div class="relative">
                <button id="userDropdownBtn" class="flex items-center px-3 py-2 rounded-lg hover:bg-theme-900 focus:outline-none transition-all duration-200 shadow-md hover:shadow-lg" style="background-color: transparent;">
                    <?php 
                    $profilePic = $_SESSION['profile_picture'] ?? '';
                    if (!empty($profilePic) && file_exists(__DIR__ . '/uploads/profiles/' . $profilePic)): 
                    ?>
                        <img src="/uploads/profiles/<?= htmlspecialchars($profilePic) ?>" 
                             alt="Profile" 
                             class="w-8 h-8 rounded-full mr-2 object-cover border-2 border-white">
                    <?php else: ?>
                        <span class="mr-2 text-xl">👤</span>
                    <?php endif; ?>
                    <span class="mr-2 font-medium"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
                    <svg class="w-4 h-4 fill-current transition-transform duration-200" id="dropdownArrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div id="userDropdown"
                    class="hidden absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded-lg shadow-xl z-50 border border-gray-200 overflow-hidden">
                    <a href="/profile" class="block px-4 py-2 hover-theme-light transition-colors duration-150 theme-link">Profile Account</a>
                    <?php if ($_SESSION['role'] == "admin") {
                        echo "<a href='/admin/account' class='block px-4 py-2 hover-theme-light transition-colors duration-150 theme-link'>Manage Account</a>";
                    } ?>
                    <hr class="border-gray-200">
                    <a href="../../logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 font-medium transition-colors duration-150">Logout</a>
                </div>
            </div>
        </nav>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('userDropdownBtn');
                const menu = document.getElementById('userDropdown');
                const arrow = document.getElementById('dropdownArrow');

                btn.addEventListener('click', () => {
                    const isHidden = menu.classList.contains('hidden');
                    menu.classList.toggle('hidden');
                    if (arrow) {
                        arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                    }
                });

                // Hide dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!btn.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.add('hidden');
                        if (arrow) {
                            arrow.style.transform = 'rotate(0deg)';
                        }
                    }
                });
            });
        </script>
        <nav class="bg-primary text-white p-3 d-flex justify-content-between align-items-center position-relative">
            <!-- Left: Title + Navigation -->
            <div class="d-flex align-items-center gap-4">
                <h1 class="h5 mb-0 fw-bold">MIS Barangay</h1>
                <span class="small text-info"><?= VERSION ?></span>
            </div>

            <!-- Right: Profile Dropdown -->
            <div class="position-relative">
                <button id="userDropdownBtn" class="d-flex align-items-center btn btn-secondary px-2 py-1 btn-sm">
                    <?php 
                    $profilePic = $_SESSION['profile_picture'] ?? '';
                    if (!empty($profilePic) && file_exists(__DIR__ . '/uploads/profiles/' . $profilePic)): 
                    ?>
                    <img src="/uploads/profiles/<?= htmlspecialchars($profilePic) ?>" 
                                alt="Profile" 
                                width="32" height="32"
                                style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; border-radius: 50%;"
                                class="settings-avatar avatar rounded-circle me-2 border-2 border-white">
                    <?php else: ?>
                        <span class="me-2">👤</span>
                    <?php endif; ?>
                    <span class="me-2"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
                    <svg width="16" height="16" class="fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div id="userDropdown"
                    class="d-none position-absolute end-0 mt-2" style="width: 12rem; min-width: 192px;">
                    <div class="bg-white text-dark rounded shadow-lg">
                        <a href="/profile" class="d-block px-3 py-2 text-decoration-none text-dark">Profile Account</a>
                        <?php if ($_SESSION['role'] == "admin") {
                            echo "<a href='/admin/account' class='d-block px-3 py-2 text-decoration-none text-dark'>Manage Account</a>";
                        } ?>
                        <hr class="m-0">
                        <a href="/logout" class="d-block px-3 py-2 text-decoration-none text-danger fw-medium">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('userDropdownBtn');
                const menu = document.getElementById('userDropdown');

                btn.addEventListener('click', () => {
                    menu.classList.toggle('d-none');
                });

                // Hide dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!btn.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.add('d-none');
                    }
                });
            });
        </script>
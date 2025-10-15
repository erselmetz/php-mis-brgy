<!-- Sidebar -->
<aside class="w-60 bg-white shadow-md min-h-screen border-r border-gray-200">
    <nav class="p-2 space-y-2">
        <a href="/dashboard.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
            🏠 Dashboard
        </a>
        <a href="/resident.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
            👥 Residents
        </a>
        <?php if ($_SESSION['role'] == "admin") {
            echo "<a href='/admin/account.php' class='block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200'>🧑‍💼 Staff</a>";
        } ?>
        <!-- <a href="#" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
            📊 Reports
        </a> -->
        <a href="/profile.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
            ⚙️ Settings
        </a>
    </nav>
</aside>
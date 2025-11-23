<!-- Sidebar -->
<aside class="w-60 bg-white shadow-md min-h-screen border-r border-gray-200">
    <nav class="p-2 space-y-2">
        <a href="/dashboard" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
            🏠 Dashboard
        </a>
        
        <?php if ($_SESSION['role'] == "admin" || $_SESSION['role'] == "staff"): ?>
            <a href="/resident/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
                👥 Residents
            </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == "admin"): ?>
            <a href="/admin/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
                🧑‍💼 Staff
            </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == "admin" || $_SESSION['role'] == "staff"): ?>
            <a href="/certificate/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
                📊 Certificates
            </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == "tanod" || $_SESSION['role'] == "admin"): ?>
            <a href="/blotter/" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
                📝 Blotter
            </a>
        <?php endif; ?>
        
        <a href="/profile" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200 ">
            ⚙️ Settings
        </a>
        
        <hr class="my-2 border-gray-300">
        
        
        <div class="px-3 py-1 text-xs text-gray-500">
            Version: <span class="font-semibold"><a href="/docs" class="px-3 py-2 rounded-md text-gray-700 hover:text-blue-500">v1.2.0</a></span>
        </div>
    </nav>
</aside>
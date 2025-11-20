<!-- Sidebar -->
<aside class="bg-white shadow-sm border-end" style="width: 15rem; min-height: 100vh;">
    <nav class="p-2">
        <a href="/dashboard" class="d-block px-3 py-2 rounded text-body text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='transparent'">
            🏠 Dashboard
        </a>
        
        <?php if ($_SESSION['role'] == "admin" || $_SESSION['role'] == "staff"): ?>
            <a href="/resident" class="d-block px-3 py-2 rounded text-body text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='transparent'">
                👥 Residents
            </a>
            <a href="/household" class="d-block px-3 py-2 rounded text-body text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='transparent'">
                🏘️ Households
            </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == "admin"): ?>
            <a href="/admin/account" class="d-block px-3 py-2 rounded text-body text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='transparent'">
                🧑‍💼 Staff
            </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == "admin" || $_SESSION['role'] == "staff"): ?>
            <a href="/certificate" class="d-block px-3 py-2 rounded text-body text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='transparent'">
                📊 Certificates
            </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == "tanod" || $_SESSION['role'] == "admin"): ?>
            <a href="/blotter" class="d-block px-3 py-2 rounded text-body text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='transparent'">
                📝 Blotter
            </a>
        <?php endif; ?>
        
        <a href="/profile" class="d-block px-3 py-2 rounded text-body text-decoration-none" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='transparent'">
            ⚙️ Settings
        </a>
        
        <hr class="my-2">
        
        <!-- <a href="/docs" class="d-block px-3 py-2 rounded text-body text-decoration-none">
            📚 Documentation
        </a> -->
        <div class="px-3 py-1 small text-muted">
            Version: <span class="fw-semibold"><a href="/docs" class="text-decoration-none">
            <?= VERSION ?>
        </a></span>
        </div>
    </nav>
</aside>
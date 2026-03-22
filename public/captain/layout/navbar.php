<?php
/**
 * Navbar — Government-official formal design
 * Replaces: public/secretary/layout/navbar.php
 *
 * Design language: Source Serif 4 + Source Sans 3, ink-on-paper,
 * ruled borders, monospaced accents — matches the certificate page aesthetic.
 */

$currentUser     = $_SESSION['name']     ?? 'User';
$currentRole     = $_SESSION['role']     ?? '';
$profilePic      = $_SESSION['profile_picture'] ?? '';
$profilePicPath  = __DIR__ . '/uploads/profiles/' . $profilePic;
$hasProfilePic   = !empty($profilePic) && file_exists($profilePicPath);

// Build initials from name
$nameParts = explode(' ', trim($currentUser));
$initials  = strtoupper(
    substr($nameParts[0] ?? 'U', 0, 1) .
    substr($nameParts[count($nameParts) - 1] ?? '', 0, 1)
);

// Role display label
$roleLabels = [
    'secretary' => 'Barangay Secretary',
    'captain'   => 'Barangay Captain',
    'kagawad'   => 'Kagawad',
    'hcnurse'   => 'Health Center Nurse',
    'tanod'     => 'Barangay Tanod',
    'admin'     => 'Administrator',
];
$roleLabel = $roleLabels[$currentRole] ?? ucfirst($currentRole);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ── NAVBAR ROOT ─────────────────────────────────────────── */
:root {
    --nb-accent:    var(--theme-primary, #2d5a27);
    --nb-accent-dk: color-mix(in srgb, var(--nb-accent) 65%, black);
    --nb-paper:     #fdfcf9;
    --nb-ink:       #1a1a1a;
    --nb-muted:     #5a5a5a;
    --nb-faint:     #a0a0a0;
    --nb-rule:      #d8d4cc;
    --nb-rule-dk:   #b0aba0;
    --nb-font-s:    'Source Serif 4', Georgia, serif;
    --nb-font-n:    'Source Sans 3', 'Segoe UI', sans-serif;
    --nb-font-m:    'Courier New', monospace;
}

/* ── MAIN NAVBAR ─────────────────────────────────────────── */
.nb-root {
    background: var(--nb-accent);
    border-bottom: 3px solid var(--nb-accent-dk);
    display: flex;
    align-items: stretch;
    padding: 0;
    height: 62px;
    position: relative;
    z-index: 100;
    font-family: var(--nb-font-n);
}

/* subtle grain overlay for depth */
.nb-root::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E");
    background-size: 180px;
    pointer-events: none;
}

/* ── LEFT: SEAL + IDENTITY ───────────────────────────────── */
.nb-identity {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 24px 0 20px;
    border-right: 1px solid rgba(255,255,255,.15);
    text-decoration: none;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    transition: background .15s;
}
.nb-identity:hover { background: rgba(255,255,255,.06); }

.nb-seal {
    width: 38px; height: 38px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,.4);
    background: rgba(255,255,255,.1);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}
.nb-seal img  { width: 100%; height: 100%; object-fit: contain; }
.nb-seal-ico  { font-size: 18px; }

.nb-id-text { line-height: 1; }
.nb-republic {
    font-size: 8px; font-weight: 700; letter-spacing: 1.3px;
    text-transform: uppercase; color: rgba(255,255,255,.55);
    margin-bottom: 4px;
}
.nb-brgy {
    font-family: var(--nb-font-s);
    font-size: 14.5px; font-weight: 700;
    color: #fff; letter-spacing: .1px;
}
.nb-office {
    font-size: 9px; letter-spacing: .7px;
    text-transform: uppercase; color: rgba(255,255,255,.5);
    margin-top: 3px;
}

/* ── CENTER: SPACER / DATE STRIP ─────────────────────────── */
.nb-center {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 0 20px;
    position: relative;
    z-index: 1;
}
.nb-date-strip {
    font-family: var(--nb-font-m);
    font-size: 9.5px;
    color: rgba(255,255,255,.3);
    letter-spacing: .5px;
}

/* ── RIGHT: USER PROFILE ─────────────────────────────────── */
.nb-user-wrap {
    position: relative;
    display: flex;
    align-items: center;
    z-index: 1;
}

.nb-user-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 20px;
    height: 100%;
    cursor: pointer;
    border: none;
    background: none;
    border-left: 1px solid rgba(255,255,255,.15);
    font-family: var(--nb-font-n);
    transition: background .15s;
    min-width: 0;
}
.nb-user-btn:hover { background: rgba(255,255,255,.07); }

.nb-avatar {
    width: 34px; height: 34px;
    border-radius: 3px;
    border: 1.5px solid rgba(255,255,255,.4);
    overflow: hidden;
    flex-shrink: 0;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
}
.nb-avatar img  { width: 100%; height: 100%; object-fit: cover; }
.nb-avatar-init {
    font-family: var(--nb-font-m);
    font-size: 12px; font-weight: 700;
    color: #fff; letter-spacing: .5px;
}

.nb-user-text { text-align: left; }
.nb-user-name {
    font-size: 12.5px; font-weight: 600;
    color: #fff; white-space: nowrap;
    max-width: 160px; overflow: hidden;
    text-overflow: ellipsis; display: block;
}
.nb-user-role {
    font-size: 9.5px; color: rgba(255,255,255,.5);
    letter-spacing: .3px; display: block;
    margin-top: 1px; white-space: nowrap;
}

.nb-caret {
    color: rgba(255,255,255,.45);
    font-size: 10px;
    flex-shrink: 0;
    transition: transform .2s;
}
.nb-user-wrap.open .nb-caret { transform: rotate(180deg); }

/* ── DROPDOWN MENU ───────────────────────────────────────── */
.nb-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    min-width: 240px;
    background: var(--nb-paper);
    border: 1.5px solid var(--nb-rule-dk);
    border-radius: 2px;
    box-shadow: 0 8px 32px rgba(0,0,0,.16), 0 2px 6px rgba(0,0,0,.1);
    overflow: hidden;
    display: none;
    z-index: 500;
}
.nb-user-wrap.open .nb-dropdown { display: block; }

/* Dropdown header */
.nb-dd-head {
    padding: 14px 16px 12px;
    background: #f5f3ee;
    border-bottom: 1px solid var(--nb-rule);
}
.nb-dd-head-name {
    font-family: var(--nb-font-s);
    font-size: 14px; font-weight: 600;
    color: var(--nb-ink); margin-bottom: 2px;
}
.nb-dd-head-role {
    font-size: 10px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    color: var(--nb-muted);
}
.nb-dd-head-role::before {
    content: '— ';
    color: var(--nb-accent);
}

/* Dropdown links */
.nb-dd-links { padding: 6px 0; }
.nb-dd-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 16px;
    font-family: var(--nb-font-n);
    font-size: 13px;
    color: var(--nb-ink);
    text-decoration: none;
    border-left: 2px solid transparent;
    transition: background .12s, border-color .12s, color .12s;
}
.nb-dd-item:hover {
    background: var(--nb-accent-lt, #f0f9f0);
    border-left-color: var(--nb-accent);
    color: var(--nb-accent);
}
.nb-dd-item .dd-item-icon {
    font-size: 14px;
    width: 18px; text-align: center;
    flex-shrink: 0;
    opacity: .6;
}
.nb-dd-item.is-danger { color: #7a1f1a; }
.nb-dd-item.is-danger:hover {
    background: #fdeeed;
    border-left-color: #7a1f1a;
}
.nb-dd-divider {
    height: 1px;
    background: var(--nb-rule);
    margin: 6px 0;
}

/* Doc ref corner */
.nb-dd-footer {
    padding: 8px 16px;
    background: #f7f5f0;
    border-top: 1px solid var(--nb-rule);
    font-family: var(--nb-font-m);
    font-size: 8.5px;
    color: var(--nb-faint);
    letter-spacing: .5px;
}
</style>

<nav class="nb-root">

    <!-- Identity / Seal -->
    <a href="/secretary/dashboard/" class="nb-identity">
        <div class="nb-seal">
            <?php
            $logoPath1 = __DIR__ . '/../../assets/images/logo.ico';
            $logoPath2 = __DIR__ . '/../../assets/images/logo.png';
            if (file_exists($logoPath1) || file_exists($logoPath2)):
            ?>
                <img src="/assets/images/logo.<?= file_exists($logoPath1) ? 'ico' : 'png' ?>" alt="Seal">
            <?php else: ?>
                <span class="nb-seal-ico">🏛️</span>
            <?php endif; ?>
        </div>
        <div class="nb-id-text">
            <div class="nb-republic">Republic of the Philippines · Rizal</div>
            <div class="nb-brgy">Barangay Bombongan</div>
            <div class="nb-office">Management Information System</div>
        </div>
    </a>

    <!-- Center -->
    <div class="nb-center">
        <span class="nb-date-strip"><?= date('D, d M Y') ?></span>
    </div>

    <!-- User profile + dropdown -->
    <div class="nb-user-wrap" id="nbUserWrap">
        <button class="nb-user-btn" id="nbUserBtn" type="button" aria-expanded="false">
            <div class="nb-avatar">
                <?php if ($hasProfilePic): ?>
                    <img src="/uploads/profiles/<?= htmlspecialchars($profilePic) ?>" alt="Profile">
                <?php else: ?>
                    <span class="nb-avatar-init"><?= htmlspecialchars($initials) ?></span>
                <?php endif; ?>
            </div>
            <div class="nb-user-text">
                <span class="nb-user-name"><?= htmlspecialchars($currentUser) ?></span>
                <span class="nb-user-role"><?= htmlspecialchars($roleLabel) ?></span>
            </div>
            <span class="nb-caret">▼</span>
        </button>

        <div class="nb-dropdown" id="nbDropdown" role="menu">
            <!-- Head -->
            <div class="nb-dd-head">
                <div class="nb-dd-head-name"><?= htmlspecialchars($currentUser) ?></div>
                <div class="nb-dd-head-role"><?= htmlspecialchars($roleLabel) ?></div>
            </div>

            <!-- Links -->
            <div class="nb-dd-links">
                <a class="nb-dd-item" href="/secretary/profile/" role="menuitem">
                    <span class="dd-item-icon">⚙</span> Profile &amp; Settings
                </a>
                <?php if ($currentRole === 'secretary'): ?>
                <a class="nb-dd-item" href="/secretary/admin/" role="menuitem">
                    <span class="dd-item-icon">🧑</span> Manage Accounts
                </a>
                <?php endif; ?>
                <div class="nb-dd-divider"></div>
                <a class="nb-dd-item is-danger" href="/logout.php" role="menuitem">
                    <span class="dd-item-icon">→</span> Sign Out
                </a>
            </div>

            <!-- Footer ref -->
            <div class="nb-dd-footer">
                SESSION · <?= htmlspecialchars(strtoupper($currentRole)) ?> · <?= date('H:i') ?>
            </div>
        </div>
    </div>
</nav>

<script>
(function () {
    const wrap = document.getElementById('nbUserWrap');
    const btn  = document.getElementById('nbUserBtn');
    if (!wrap || !btn) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const open = wrap.classList.toggle('open');
        btn.setAttribute('aria-expanded', open);
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) {
            wrap.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            wrap.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>
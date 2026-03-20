<?php
/**
 * Sidebar — HCNurse Portal
 * Replaces: public/hcnurse/layout/sidebar.php
 *
 * Active detection via current URL path.
 * Health Records sub-menu auto-expands when on /health-records/.
 */

$path = $_SERVER['REQUEST_URI'] ?? '';

function sb_active(string $segment, string $path): bool {
    return str_contains($path, $segment);
}
?>
<style>
/* ── TOKENS (same root as navbar/dashboard) ── */
:root {
    --sb-paper:   #fdfcf9;
    --sb-paper-lt:#f5f3ee;
    --sb-ink:     #1a1a1a;
    --sb-muted:   #5a5a5a;
    --sb-faint:   #a0a0a0;
    --sb-rule:    #d8d4cc;
    --sb-accent:  var(--theme-primary, #2d5a27);
    --sb-font-n:  'Source Sans 3', 'Segoe UI', sans-serif;
    --sb-font-m:  'Source Code Pro', 'Courier New', monospace;
}

/* ── ASIDE ───────────────────────────────── */
.sb-root {
    width: 220px; flex-shrink:0;
    background: var(--sb-paper);
    border-right: 1px solid var(--sb-rule);
    min-height: 100%; overflow-y: auto;
    padding-bottom: 56px;
    font-family: var(--sb-font-n);
    display: flex; flex-direction: column;
}

/* ── SECTION LABEL ───────────────────────── */
.sb-section {
    padding: 18px 16px 6px;
}
.sb-section-lbl {
    font-size: 7.5px; font-weight: 700; letter-spacing: 1.8px;
    text-transform: uppercase; color: var(--sb-faint);
    display: flex; align-items: center; gap: 8px; white-space: nowrap;
}
.sb-section-lbl::after {
    content:''; flex:1; height:1px; background:var(--sb-rule);
}

/* ── NAV ITEM ────────────────────────────── */
.sb-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 16px 8px 14px;
    font-size: 13px; font-weight: 500;
    color: var(--sb-muted); text-decoration: none;
    border-left: 3px solid transparent;
    transition: background .12s, color .12s, border-color .12s;
    white-space: nowrap; cursor: pointer;
    background: none; border-top: none; border-right: none; border-bottom: none;
    width: 100%; text-align: left; font-family: var(--sb-font-n);
}
.sb-item:hover {
    background: color-mix(in srgb, var(--sb-accent) 6%, white);
    color: var(--sb-ink);
    border-left-color: var(--sb-rule);
}
.sb-item.active {
    background: color-mix(in srgb, var(--sb-accent) 8%, white);
    color: var(--sb-accent);
    border-left-color: var(--sb-accent);
    font-weight: 700;
}
.sb-icon { font-size: 15px; flex-shrink:0; width: 20px; text-align:center; }
.sb-label { flex: 1; }
.sb-caret {
    font-size: 9px; color: var(--sb-faint); flex-shrink:0;
    transition: transform .2s;
}
.sb-item.open .sb-caret { transform: rotate(180deg); }

/* ── SUB-MENU ────────────────────────────── */
.sb-submenu {
    overflow: hidden; max-height: 0;
    transition: max-height .25s ease;
    background: var(--sb-paper-lt);
    border-left: 1px solid var(--sb-rule);
    margin-left: 30px;
}
.sb-submenu.open { max-height: 400px; }
.sb-sub-item {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 14px 7px 12px;
    font-size: 12px; font-weight: 500;
    color: var(--sb-muted); text-decoration: none;
    border-left: 2px solid transparent;
    transition: background .1s, color .1s, border-color .1s;
}
.sb-sub-item:hover {
    color: var(--sb-ink);
    background: color-mix(in srgb, var(--sb-accent) 5%, white);
    border-left-color: var(--sb-rule);
}
.sb-sub-item.active {
    color: var(--sb-accent); font-weight: 700;
    border-left-color: var(--sb-accent);
    background: color-mix(in srgb, var(--sb-accent) 7%, white);
}
.sb-sub-icon { font-size: 13px; width:18px; text-align:center; flex-shrink:0; }

/* ── DIVIDER ─────────────────────────────── */
.sb-rule { height:1px; background:var(--sb-rule); margin: 8px 0; }

/* ── FOOTER ──────────────────────────────── */
.sb-footer {
    margin-top: auto; padding: 14px 16px;
    border-top: 1px solid var(--sb-rule);
    background: var(--sb-paper-lt);
}
.sb-footer-txt {
    font-family: var(--sb-font-m); font-size: 8px;
    color: var(--sb-faint); letter-spacing: .5px; line-height: 1.8;
}
</style>

<aside class="sb-root">

    <!-- ── MAIN ── -->
    <div class="sb-section">
        <div class="sb-section-lbl">Main</div>
    </div>

    <a href="/hcnurse/dashboard/"
       class="sb-item <?= sb_active('/hcnurse/dashboard', $path) ? 'active' : '' ?>">
        <span class="sb-icon">🏠</span>
        <span class="sb-label">Dashboard</span>
    </a>

    <a href="/hcnurse/resident/"
       class="sb-item <?= sb_active('/hcnurse/resident', $path) ? 'active' : '' ?>">
        <span class="sb-icon">👥</span>
        <span class="sb-label">Residents</span>
    </a>

    <a href="/hcnurse/consultation/"
       class="sb-item <?= sb_active('/hcnurse/consultation', $path) ? 'active' : '' ?>">
        <span class="sb-icon">📝</span>
        <span class="sb-label">Consultation</span>
    </a>

    <!-- ── HEALTH RECORDS ── -->
    <div class="sb-section">
        <div class="sb-section-lbl">Health Records</div>
    </div>

    <?php
    $hrActive  = sb_active('/hcnurse/health-records', $path);
    $hrOpen    = $hrActive;
    $hrType    = '';
    if (preg_match('/[?&]type=([a-z_]+)/', $path, $m)) $hrType = $m[1];
    ?>

    <!-- Immunization (standalone page) -->
    <a href="/hcnurse/immunization/"
       class="sb-item <?= sb_active('/hcnurse/immunization', $path) ? 'active' : '' ?>">
        <span class="sb-icon">💉</span>
        <span class="sb-label">Immunization</span>
    </a>

    <!-- Collapsible group for other health record types -->
    <button class="sb-item <?= $hrActive ? 'active open' : '' ?>" id="sbHrToggle"
            type="button" aria-expanded="<?= $hrActive ? 'true' : 'false' ?>">
        <span class="sb-icon">🩺</span>
        <span class="sb-label">Care Records</span>
        <span class="sb-caret">▼</span>
    </button>
    <div class="sb-submenu <?= $hrOpen ? 'open' : '' ?>" id="sbHrMenu">
        <?php
        $subItems = [
            'immunization'   => ['💉', 'Immunization'],
            'maternal'       => ['🤱', 'Maternal'],
            'family_planning'=> ['💊', 'Family Planning'],
            'prenatal'       => ['👶', 'Prenatal Care'],
            'postnatal'      => ['🍼', 'Postnatal Care'],
            'child_nutrition'=> ['🥗', 'Child Nutrition'],
        ];
        foreach ($subItems as $type => [$icon, $label]):
            $subActive = $hrActive && $hrType === $type;
        ?>
        <a href="/hcnurse/health-records/?type=<?= $type ?>"
           class="sb-sub-item <?= $subActive ? 'active' : '' ?>">
            <span class="sb-sub-icon"><?= $icon ?></span>
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── CLINIC ── -->
    <div class="sb-section">
        <div class="sb-section-lbl">Clinic</div>
    </div>

    <a href="/hcnurse/inventory/"
       class="sb-item <?= sb_active('/hcnurse/inventory', $path) ? 'active' : '' ?>">
        <span class="sb-icon">📦</span>
        <span class="sb-label">Inventory</span>
    </a>

    <div class="sb-rule"></div>

    <!-- ── ACCOUNT ── -->
    <a href="/hcnurse/profile/"
       class="sb-item <?= sb_active('/hcnurse/profile', $path) ? 'active' : '' ?>">
        <span class="sb-icon">⚙</span>
        <span class="sb-label">Settings</span>
    </a>

    <a href="/logout.php" class="sb-item"
       style="color:#7a1f1a;">
        <span class="sb-icon">→</span>
        <span class="sb-label">Sign Out</span>
    </a>

    <!-- Footer -->
    <div class="sb-footer">
        <div class="sb-footer-txt">
            HEALTH CENTER PORTAL<br>
            MIS BARANGAY BOMBONGAN<br>
            <?= date('Y') ?>
        </div>
    </div>

</aside>

<script>
(function () {
    const toggle = document.getElementById('sbHrToggle');
    const menu   = document.getElementById('sbHrMenu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', function () {
        const isOpen = menu.classList.contains('open');
        menu.classList.toggle('open', !isOpen);
        toggle.classList.toggle('open', !isOpen);
        toggle.setAttribute('aria-expanded', !isOpen);
    });

    // Auto-open if currently on health-records
    const path = window.location.pathname + window.location.search;
    if (path.includes('/hcnurse/health-records/')) {
        menu.classList.add('open');
        toggle.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
    }
})();
</script>
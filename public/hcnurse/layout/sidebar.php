<?php
/**
 * HC Nurse Sidebar
 * Replaces: public/hcnurse/layout/sidebar.php
 *
 * Design: narrow document-register aesthetic.
 * - 56px collapsed rail with icons + tooltips
 * - 220px expanded panel on hover/toggle
 * - Active state: 3px left accent bar + tinted bg
 * - Care Records: inline sub-menu expands in place
 * - Active sub-item detected from ?type= param
 */

$uri    = $_SERVER['REQUEST_URI'] ?? '';
$qtype  = $_GET['type'] ?? '';

// Determine which top-level section is active
function isActive(string $uri, string $path): bool {
    return str_starts_with(parse_url($uri, PHP_URL_PATH) ?? '', $path);
}

$isHR = isActive($uri, '/hcnurse/health-records/');

$careTypes = [
    'maternal'        => ['🤱', 'Maternal'],
    'family_planning' => ['💊', 'Family Planning'],
    'prenatal'        => ['👶', 'Prenatal'],
    'postnatal'       => ['🍼', 'Postnatal'],
    'child_nutrition' => ['🥗', 'Child Nutrition'],
    'immunization'    => ['💉', 'Immunization'],
];
?>

<aside id="hcSidebar" class="hcs">

    <!-- ── Branding strip ── -->
    <div class="hcs-brand">
        <div class="hcs-brand-icon">🏥</div>
        <div class="hcs-brand-text">
            <div class="hcs-brand-name">HC Portal</div>
            <div class="hcs-brand-role">Health Center</div>
        </div>
    </div>

    <nav class="hcs-nav">

        <!-- Dashboard -->
        <a href="/hcnurse/dashboard/"
           class="hcs-item <?= isActive($uri, '/hcnurse/dashboard/') ? 'hcs-active' : '' ?>"
           data-tip="Dashboard">
            <span class="hcs-icon">🏠</span>
            <span class="hcs-label">Dashboard</span>
        </a>

        <!-- Residents -->
        <a href="/hcnurse/resident/"
           class="hcs-item <?= isActive($uri, '/hcnurse/resident/') ? 'hcs-active' : '' ?>"
           data-tip="Residents">
            <span class="hcs-icon">👥</span>
            <span class="hcs-label">Residents</span>
        </a>

        <!-- Consultation -->
        <a href="/hcnurse/consultation/"
           class="hcs-item <?= isActive($uri, '/hcnurse/consultation/') ? 'hcs-active' : '' ?>"
           data-tip="Consultation">
            <span class="hcs-icon">📝</span>
            <span class="hcs-label">Consultation</span>
        </a>

        <!-- ── Care Records (collapsible) ── -->
        <div class="hcs-group <?= $isHR ? 'hcs-group-open' : '' ?>">

            <button class="hcs-group-btn <?= $isHR ? 'hcs-active' : '' ?>"
                    id="careToggle" type="button" data-tip="Care Records">
                <span class="hcs-icon">🩺</span>
                <span class="hcs-label">Care Records</span>
                <span class="hcs-chevron" id="careChevron"><?= $isHR ? '▲' : '▼' ?></span>
            </button>

            <div class="hcs-sub <?= $isHR ? '' : 'hcs-sub-hidden' ?>" id="careMenu">
                <?php foreach ($careTypes as $type => [$icon, $label]):
                    $href      = "/hcnurse/health-records/?type={$type}";
                    $subActive = $isHR && $qtype === $type;
                ?>
                <a href="<?= $href ?>"
                   class="hcs-sub-item <?= $subActive ? 'hcs-sub-active' : '' ?>">
                    <span class="hcs-sub-icon"><?= $icon ?></span>
                    <span><?= $label ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Inventory -->
        <a href="/hcnurse/inventory/"
           class="hcs-item <?= isActive($uri, '/hcnurse/inventory/') ? 'hcs-active' : '' ?>"
           data-tip="Inventory">
            <span class="hcs-icon">📁</span>
            <span class="hcs-label">Inventory</span>
        </a>

        <!-- Divider -->
        <div class="hcs-div"></div>

        <!-- Settings -->
        <a href="/hcnurse/profile/"
           class="hcs-item <?= isActive($uri, '/hcnurse/profile/') ? 'hcs-active' : '' ?>"
           data-tip="Settings">
            <span class="hcs-icon">⚙️</span>
            <span class="hcs-label">Settings</span>
        </a>

        <!-- Sign Out -->
        <a href="/logout.php" class="hcs-item hcs-signout" data-tip="Sign Out">
            <span class="hcs-icon">🚪</span>
            <span class="hcs-label">Sign Out</span>
        </a>

    </nav>

    <!-- Version stamp -->
    <div class="hcs-foot">
        <span class="hcs-foot-txt">MIS Barangay</span>
    </div>
</aside>

<style>
/* ════════════════════════════════════════
   HC SIDEBAR
════════════════════════════════════════ */
.hcs {
    --s-w:        220px;
    --s-accent:   var(--theme-primary, #2d5a27);
    --s-accent-lt:color-mix(in srgb, var(--s-accent) 10%, white);
    --s-paper:    #fdfcf9;
    --s-rule:     #e8e4dc;
    --s-ink:      #1a1a1a;
    --s-muted:    #6b7280;
    --s-faint:    #a0a0a0;
    --s-brand-h:  56px;
    --s-item-h:   38px;
    --f-sans:     'Source Sans 3','Segoe UI',sans-serif;
    --f-mono:     'Source Code Pro','Courier New',monospace;

    width: var(--s-w);
    min-height: 100vh;
    background: var(--s-paper);
    border-right: 1px solid var(--s-rule);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    font-family: var(--f-sans);
    position: sticky;
    top: 0;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: var(--s-rule) transparent;

    /* subtle grain */
    background-image:
        radial-gradient(ellipse at 0% 0%, color-mix(in srgb, var(--s-accent) 4%, transparent) 0%, transparent 60%);
}

/* ── Brand ── */
.hcs-brand {
    height: var(--s-brand-h);
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 16px;
    border-bottom: 1px solid var(--s-rule);
    background: color-mix(in srgb, var(--s-accent) 6%, white);
    flex-shrink: 0;
}
.hcs-brand-icon {
    font-size: 20px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--s-accent);
    border-radius: 6px;
    flex-shrink: 0;
}
.hcs-brand-name {
    font-family: var(--f-mono);
    font-size: 11.5px;
    font-weight: 700;
    color: var(--s-accent);
    letter-spacing: .3px;
    line-height: 1;
}
.hcs-brand-role {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--s-faint);
    margin-top: 2px;
}

/* ── Nav ── */
.hcs-nav {
    flex: 1;
    padding: 10px 8px;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

/* ── Item (link or button) ── */
.hcs-item,
.hcs-group-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 10px;
    height: var(--s-item-h);
    border-radius: 4px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--s-muted);
    text-decoration: none;
    cursor: pointer;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
    transition: background .12s, color .12s;
    position: relative;
    font-family: var(--f-sans);
    white-space: nowrap;
    overflow: hidden;
}
.hcs-item::before,
.hcs-group-btn::before {
    content: '';
    position: absolute;
    left: 0; top: 6px; bottom: 6px;
    width: 0;
    background: var(--s-accent);
    border-radius: 0 2px 2px 0;
    transition: width .14s;
}
.hcs-item:hover,
.hcs-group-btn:hover {
    background: var(--s-accent-lt);
    color: var(--s-accent);
}
.hcs-item.hcs-active,
.hcs-group-btn.hcs-active {
    background: var(--s-accent-lt);
    color: var(--s-accent);
    font-weight: 700;
}
.hcs-item.hcs-active::before,
.hcs-group-btn.hcs-active::before { width: 3px; }

.hcs-icon  { font-size: 15px; flex-shrink: 0; width: 20px; text-align: center; }
.hcs-label { flex: 1; overflow: hidden; text-overflow: ellipsis; }
.hcs-chevron {
    font-size: 8px;
    color: var(--s-faint);
    flex-shrink: 0;
    transition: transform .2s;
}
.hcs-group-open .hcs-chevron,
.hcs-group-btn:not(.hcs-active) .hcs-chevron { /* inherit */ }

/* ── Sub-menu ── */
.hcs-sub {
    overflow: hidden;
    max-height: 400px;
    transition: max-height .22s ease, opacity .18s;
    opacity: 1;
    padding: 3px 0 3px 12px;
}
.hcs-sub-hidden {
    max-height: 0;
    opacity: 0;
    padding: 0;
}
.hcs-sub-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 10px;
    height: 33px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 500;
    color: var(--s-muted);
    text-decoration: none;
    transition: background .11s, color .11s;
    position: relative;
    white-space: nowrap;
    overflow: hidden;
}
.hcs-sub-item::before {
    content: '';
    position: absolute;
    left: 0; top: 5px; bottom: 5px;
    width: 0;
    background: var(--s-accent);
    border-radius: 0 2px 2px 0;
    transition: width .13s;
}
.hcs-sub-item:hover {
    background: var(--s-accent-lt);
    color: var(--s-accent);
}
.hcs-sub-item.hcs-sub-active {
    background: var(--s-accent-lt);
    color: var(--s-accent);
    font-weight: 700;
}
.hcs-sub-item.hcs-sub-active::before { width: 2px; }
.hcs-sub-icon { font-size: 13px; flex-shrink: 0; width: 18px; text-align: center; }

/* ── Divider ── */
.hcs-div {
    height: 1px;
    background: var(--s-rule);
    margin: 6px 4px;
}

/* ── Sign Out ── */
.hcs-signout { color: #b91c1c; }
.hcs-signout:hover { background: #fef2f2; color: #7f1d1d; }
.hcs-signout::before { background: #b91c1c !important; }

/* ── Footer ── */
.hcs-foot {
    padding: 10px 16px;
    border-top: 1px solid var(--s-rule);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.hcs-foot-txt {
    font-family: var(--f-mono);
    font-size: 8.5px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--s-faint);
}

/* ── Section label (decorative) ── */
.hcs-section-lbl {
    font-size: 7.5px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: var(--s-faint);
    padding: 10px 10px 4px;
}
</style>

<script>
(function () {
    const btn    = document.getElementById('careToggle');
    const menu   = document.getElementById('careMenu');
    const chev   = document.getElementById('careChevron');
    const group  = btn?.closest('.hcs-group');
    if (!btn || !menu) return;

    btn.addEventListener('click', function () {
        const open = !menu.classList.contains('hcs-sub-hidden');
        if (open) {
            menu.classList.add('hcs-sub-hidden');
            chev.textContent = '▼';
            group?.classList.remove('hcs-group-open');
        } else {
            menu.classList.remove('hcs-sub-hidden');
            chev.textContent = '▲';
            group?.classList.add('hcs-group-open');
        }
    });
})();
</script>
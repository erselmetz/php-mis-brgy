<?php
/**
 * HC Nurse Sidebar
 * Replaces: public/hcnurse/layout/sidebar.php
 *
 * CHANGE: Care Records sub-items are now flat links (no dropdown).
 * Immunization is listed directly under a "Care Records" section label.
 */

function hcActive(string $path): bool {
    return str_starts_with($_SERVER['REQUEST_URI'] ?? '', $path);
}

$uri   = $_SERVER['REQUEST_URI'] ?? '';
$qtype = $_GET['type'] ?? '';

$careTypes = [
    'maternal'        => ['🤱', 'Maternal'],
    'family_planning' => ['💊', 'Family Planning'],
    'prenatal'        => ['👶', 'Prenatal'],
    'postnatal'       => ['🍼', 'Postnatal'],
    'child_nutrition' => ['🥗', 'Child Nutrition'],
    'immunization'    => ['💉', 'Immunization'],
];
?>

<style>
/* ══ SIDEBAR ROOT ─────────────────────────────── */
.sb-root {
    width: 232px;
    min-width: 232px;
    background: #fdfcf9;
    border-right: 1px solid #d8d4cc;
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    font-family: 'Source Sans 3', 'Segoe UI', sans-serif;
    position: relative;
    flex-shrink: 0;
    scrollbar-width: thin;
    scrollbar-color: #e0dcd6 transparent;
}
.sb-root::after {
    content: '';
    position: absolute;
    top: 0; right: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, #c8c4bc, #e8e4de 40%, #c8c4bc);
    pointer-events: none;
}

/* ── Section label ── */
.sb-section {
    padding: 18px 16px 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sb-section-lbl {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    color: #a0a0a0;
    white-space: nowrap;
}
.sb-section-rule {
    flex: 1;
    height: 1px;
    background: #e0dcd6;
}

/* ── Nav item ── */
.sb-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 9px 16px 9px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #3a3a3a;
    text-decoration: none;
    border-left: 2px solid transparent;
    position: relative;
    transition: background .12s, border-color .12s, color .12s;
    line-height: 1.2;
}
.sb-item:hover {
    background: color-mix(in srgb, var(--theme-primary, #2d5a27) 6%, white);
    color: var(--theme-primary, #2d5a27);
    border-left-color: color-mix(in srgb, var(--theme-primary, #2d5a27) 40%, transparent);
}
.sb-item.is-active {
    background: color-mix(in srgb, var(--theme-primary, #2d5a27) 9%, white);
    color: var(--theme-primary, #2d5a27);
    border-left-color: var(--theme-primary, #2d5a27);
    font-weight: 600;
}
.sb-item.is-active::before {
    content: '';
    position: absolute;
    left: -1px; top: 50%;
    transform: translateY(-50%);
    width: 3px; height: 18px;
    background: var(--theme-primary, #2d5a27);
    border-radius: 0 2px 2px 0;
}

/* Icon */
.sb-icon {
    font-size: 14px;
    width: 20px;
    text-align: center;
    flex-shrink: 0;
    opacity: .65;
    transition: opacity .12s;
}
.sb-item.is-active .sb-icon,
.sb-item:hover .sb-icon { opacity: 1; }

/* ── Footer ── */
.sb-footer {
    margin-top: auto;
    border-top: 1px solid #e0dcd6;
    padding: 12px 16px;
}
.sb-signout {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 8px 14px;
    font-size: 12.5px;
    font-weight: 500;
    color: #7a1f1a;
    text-decoration: none;
    border-left: 2px solid transparent;
    border-radius: 2px;
    transition: background .12s, border-color .12s;
}
.sb-signout:hover {
    background: #fdeeed;
    border-left-color: #7a1f1a;
}

/* ── Doc reference ── */
.sb-docref {
    padding: 10px 16px 14px;
    font-family: 'Courier New', monospace;
    font-size: 8px;
    color: #c0bbb4;
    letter-spacing: .6px;
    line-height: 1.7;
}
</style>

<aside class="sb-root" role="navigation" aria-label="HC Nurse navigation">

    <!-- ── Primary ── -->
    <div class="sb-section">
        <span class="sb-section-lbl">Navigation</span>
        <div class="sb-section-rule"></div>
    </div>

    <a href="/hcnurse/dashboard/"
       class="sb-item <?= hcActive('/hcnurse/dashboard/') ? 'is-active' : '' ?>">
        <span class="sb-icon">🏠</span> Dashboard
    </a>

    <a href="/hcnurse/resident/"
       class="sb-item <?= hcActive('/hcnurse/resident/') ? 'is-active' : '' ?>">
        <span class="sb-icon">👥</span> Residents
    </a>

    <a href="/hcnurse/consultation/"
       class="sb-item <?= hcActive('/hcnurse/consultation/') ? 'is-active' : '' ?>">
        <span class="sb-icon">📝</span> Consultation
    </a>

    <!-- ── Care Records (flat) ── -->
    <div class="sb-section" style="padding-top:14px;">
        <span class="sb-section-lbl">Care Records</span>
        <div class="sb-section-rule"></div>
    </div>

    <?php foreach ($careTypes as $type => [$icon, $label]):
        $href      = "/hcnurse/health-records/?type={$type}";
        $isHRPage  = hcActive('/hcnurse/health-records/');
        $isSubActive = $isHRPage && $qtype === $type;
    ?>
    <a href="<?= $href ?>"
       class="sb-item <?= $isSubActive ? 'is-active' : '' ?>">
        <span class="sb-icon"><?= $icon ?></span> <?= $label ?>
    </a>
    <?php endforeach; ?>

    <!-- ── Resources ── -->
    <div class="sb-section" style="padding-top:14px;">
        <span class="sb-section-lbl">Resources</span>
        <div class="sb-section-rule"></div>
    </div>

    <a href="/hcnurse/inventory/"
       class="sb-item <?= hcActive('/hcnurse/inventory/') ? 'is-active' : '' ?>">
        <span class="sb-icon">📁</span> Inventory
    </a>

    <!-- ── Account ── -->
    <div class="sb-section" style="padding-top:14px;">
        <span class="sb-section-lbl">Account</span>
        <div class="sb-section-rule"></div>
    </div>

    <a href="/hcnurse/profile/"
       class="sb-item <?= hcActive('/hcnurse/profile/') ? 'is-active' : '' ?>">
        <span class="sb-icon">⚙️</span> Settings
    </a>

    <!-- Footer -->
    <div class="sb-footer">
        <a href="/logout.php" class="sb-signout">
            <span style="font-size:13px;">→</span> Sign Out
        </a>
    </div>

    <div class="sb-docref">
        MIS · BRGY BOMBONGAN<br>
        <?= date('Y') ?> · HC NURSE
    </div>

</aside>
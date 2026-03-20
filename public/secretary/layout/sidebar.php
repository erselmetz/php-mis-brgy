<?php
/**
 * Sidebar — Government-official formal design
 * Replaces: public/secretary/layout/sidebar.php
 *
 * Same design language as navbar + certificate page.
 * Active page detection via $_SERVER['REQUEST_URI'].
 */

$uri = $_SERVER['REQUEST_URI'] ?? '';

// Helper: is this path active?
function nbActive(string $path): bool {
    return str_starts_with($_SERVER['REQUEST_URI'] ?? '', $path);
}

// Scheduling sub-paths
$schedulingPaths = [
    '/secretary/events-scheduling/',
    '/secretary/tanod-duty-schedule/',
    '/secretary/court-schedule/',
    '/secretary/borrowing-schedule/',
    '/secretary/patrol-schedule/',
];
$schedulingActive = array_reduce(
    $schedulingPaths,
    fn($carry, $p) => $carry || nbActive($p),
    false
);
?>

<style>
/* ── SIDEBAR ROOT ────────────────────────────────────────── */
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
}

/* subtle vertical grain line */
.sb-root::after {
    content: '';
    position: absolute;
    top: 0; right: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, #c8c4bc, #e8e4de 40%, #c8c4bc);
    pointer-events: none;
}

/* ── SECTION LABEL ───────────────────────────────────────── */
.sb-section {
    padding: 20px 16px 7px;
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

/* ── NAV ITEM ────────────────────────────────────────────── */
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
    /* small ruled tick mark — like a document margin annotation */
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

/* ── DROPDOWN TRIGGER (Scheduling) ───────────────────────── */
.sb-group {}
.sb-group-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 9px 16px 9px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #3a3a3a;
    background: none;
    border: none;
    border-left: 2px solid transparent;
    cursor: pointer;
    font-family: 'Source Sans 3', 'Segoe UI', sans-serif;
    text-align: left;
    transition: background .12s, border-color .12s, color .12s;
    position: relative;
}
.sb-group-btn:hover,
.sb-group-btn.is-open {
    background: color-mix(in srgb, var(--theme-primary, #2d5a27) 6%, white);
    color: var(--theme-primary, #2d5a27);
    border-left-color: color-mix(in srgb, var(--theme-primary, #2d5a27) 40%, transparent);
}
.sb-group-btn.is-active {
    border-left-color: var(--theme-primary, #2d5a27);
    color: var(--theme-primary, #2d5a27);
    background: color-mix(in srgb, var(--theme-primary, #2d5a27) 9%, white);
    font-weight: 600;
}
.sb-group-label { flex: 1; }
.sb-caret {
    font-size: 9px;
    color: #a0a0a0;
    transition: transform .2s;
    flex-shrink: 0;
}
.sb-group-btn.is-open .sb-caret { transform: rotate(180deg); }

/* Sub-items */
.sb-sub {
    display: none;
    background: #f9f7f4;
    border-top: 1px solid #ede9e3;
    border-bottom: 1px solid #ede9e3;
}
.sb-sub.is-open { display: block; }
.sb-sub-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px 8px 42px;
    font-size: 12px;
    font-weight: 400;
    color: #5a5a5a;
    text-decoration: none;
    border-left: 2px solid transparent;
    transition: background .12s, border-color .12s, color .12s;
}
.sb-sub-item:hover {
    background: color-mix(in srgb, var(--theme-primary, #2d5a27) 7%, white);
    color: var(--theme-primary, #2d5a27);
    border-left-color: color-mix(in srgb, var(--theme-primary, #2d5a27) 35%, transparent);
}
.sb-sub-item.is-active {
    background: color-mix(in srgb, var(--theme-primary, #2d5a27) 10%, white);
    color: var(--theme-primary, #2d5a27);
    border-left-color: var(--theme-primary, #2d5a27);
    font-weight: 600;
}
.sb-sub-dot {
    width: 5px; height: 5px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
    opacity: .45;
}
.sb-sub-item.is-active .sb-sub-dot { opacity: 1; }

/* ── FOOTER ──────────────────────────────────────────────── */
.sb-footer {
    margin-top: auto;
    border-top: 1px solid #e0dcd6;
    padding: 12px 16px;
}
.sb-signout {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 8px 14px 8px 14px;
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

/* ── DOC REFERENCE ───────────────────────────────────────── */
.sb-docref {
    padding: 10px 16px 14px;
    font-family: 'Courier New', monospace;
    font-size: 8px;
    color: #c0bbb4;
    letter-spacing: .6px;
    line-height: 1.7;
}
</style>

<aside class="sb-root" role="navigation" aria-label="Main navigation">

    <!-- ── Primary Navigation ── -->
    <div class="sb-section">
        <span class="sb-section-lbl">Navigation</span>
        <div class="sb-section-rule"></div>
    </div>

    <a href="/secretary/dashboard/"
       class="sb-item <?= nbActive('/secretary/dashboard/') ? 'is-active' : '' ?>">
        <span class="sb-icon">▦</span> Dashboard
    </a>

    <a href="/secretary/resident/"
       class="sb-item <?= nbActive('/secretary/resident/') ? 'is-active' : '' ?>">
        <span class="sb-icon">⊞</span> Residents
    </a>

    <a href="/secretary/admin/"
       class="sb-item <?= nbActive('/secretary/admin/') ? 'is-active' : '' ?>">
        <span class="sb-icon">⊟</span> Officials &amp; Staff
    </a>

    <a href="/secretary/certificate/"
       class="sb-item <?= nbActive('/secretary/certificate/') ? 'is-active' : '' ?>">
        <span class="sb-icon">▤</span> Certificates
    </a>

    <a href="/secretary/blotter/"
       class="sb-item <?= nbActive('/secretary/blotter/') ? 'is-active' : '' ?>">
        <span class="sb-icon">⊠</span> Blotter
    </a>

    <!-- ── Scheduling Group ── -->
    <div class="sb-section" style="padding-top:14px;">
        <span class="sb-section-lbl">Scheduling</span>
        <div class="sb-section-rule"></div>
    </div>

    <div class="sb-group" id="sbSchedulingGroup">
        <button
            class="sb-group-btn <?= $schedulingActive ? 'is-active' : '' ?> <?= $schedulingActive ? 'is-open' : '' ?>"
            id="sbSchedulingBtn"
            type="button"
            aria-expanded="<?= $schedulingActive ? 'true' : 'false' ?>"
        >
            <span class="sb-icon">▣</span>
            <span class="sb-group-label">All Schedules</span>
            <span class="sb-caret">▼</span>
        </button>

        <div class="sb-sub <?= $schedulingActive ? 'is-open' : '' ?>" id="sbSchedulingSub">
            <a href="/secretary/events-scheduling/"
               class="sb-sub-item <?= nbActive('/secretary/events-scheduling/') ? 'is-active' : '' ?>">
                <span class="sb-sub-dot"></span> Events &amp; Scheduling
            </a>
            <a href="/secretary/tanod-duty-schedule/"
               class="sb-sub-item <?= nbActive('/secretary/tanod-duty-schedule/') ? 'is-active' : '' ?>">
                <span class="sb-sub-dot"></span> Tanod Duty
            </a>
            <a href="/secretary/court-schedule/"
               class="sb-sub-item <?= nbActive('/secretary/court-schedule/') ? 'is-active' : '' ?>">
                <span class="sb-sub-dot"></span> Court / Facility
            </a>
            <a href="/secretary/borrowing-schedule/"
               class="sb-sub-item <?= nbActive('/secretary/borrowing-schedule/') ? 'is-active' : '' ?>">
                <span class="sb-sub-dot"></span> Borrowing
            </a>
            <a href="/secretary/patrol-schedule/"
               class="sb-sub-item <?= nbActive('/secretary/patrol-schedule/') ? 'is-active' : '' ?>">
                <span class="sb-sub-dot"></span> Patrol
            </a>
        </div>
    </div>

    <!-- ── Resources ── -->
    <div class="sb-section" style="padding-top:14px;">
        <span class="sb-section-lbl">Resources</span>
        <div class="sb-section-rule"></div>
    </div>

    <a href="/secretary/inventory/"
       class="sb-item <?= nbActive('/secretary/inventory/') ? 'is-active' : '' ?>">
        <span class="sb-icon">◫</span> Inventory
    </a>

    <!-- ── Account ── -->
    <div class="sb-section" style="padding-top:14px;">
        <span class="sb-section-lbl">Account</span>
        <div class="sb-section-rule"></div>
    </div>

    <a href="/secretary/profile/"
       class="sb-item <?= nbActive('/secretary/profile/') ? 'is-active' : '' ?>">
        <span class="sb-icon">◎</span> Settings
    </a>

    <!-- ── Footer: Sign Out ── -->
    <div class="sb-footer">
        <a href="/logout.php" class="sb-signout">
            <span style="font-size:13px;">→</span> Sign Out
        </a>
    </div>

    <!-- Doc reference stamp -->
    <div class="sb-docref">
        MIS · BRGY BOMBONGAN<br>
        <?= date('Y') ?> · <?= strtoupper($_SESSION['role'] ?? 'USER') ?>
    </div>

</aside>

<script>
(function () {
    const btn = document.getElementById('sbSchedulingBtn');
    const sub = document.getElementById('sbSchedulingSub');
    if (!btn || !sub) return;

    btn.addEventListener('click', function () {
        const open = sub.classList.toggle('is-open');
        btn.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', String(open));
    });
})();
</script>
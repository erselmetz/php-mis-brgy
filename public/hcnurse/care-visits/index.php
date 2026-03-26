<?php
/**
 * Care Visits — v5  (Professional Redesign)
 * ─────────────────────────────────────────
 * Features:
 *  - Polished clinical dashboard layout
 *  - Editable NIP immunization card (click any slot → edit modal)
 *  - Maternal profile quick-edit
 *  - Tabbed visit history with type-aware detail panels
 *  - Quick-add visit button per patient
 *  - Filterable patient list with demographics
 *  - Real-time stats banner
 *  - Print / Generate Report scoped to patient or all
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type    = $_GET['type'] ?? 'general';
$allowed = ['general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','other'];
if (!in_array($type, $allowed, true)) $type = 'general';

// Module config
$mod = [
    'general'         => ['icon'=>'❤️‍🩹','label'=>'General',         'color'=>'#2d5a27','bg'=>'#f0fdf4','pill'=>'#2d5a27'],
    'maternal'        => ['icon'=>'🤱','label'=>'Maternal Health',  'color'=>'#9f1239','bg'=>'#fff1f2','pill'=>'#9f1239'],
    'family_planning' => ['icon'=>'💊','label'=>'Family Planning',  'color'=>'#1e40af','bg'=>'#eff6ff','pill'=>'#1e40af'],
    'prenatal'        => ['icon'=>'👶','label'=>'Prenatal / ANC',   'color'=>'#92400e','bg'=>'#fffbeb','pill'=>'#92400e'],
    'postnatal'       => ['icon'=>'🍼','label'=>'Postnatal / PNC',  'color'=>'#134e4a','bg'=>'#f0fdfa','pill'=>'#134e4a'],
    'child_nutrition' => ['icon'=>'🥗','label'=>'Child Nutrition',  'color'=>'#14532d','bg'=>'#f0fdf4','pill'=>'#14532d'],
    'immunization'    => ['icon'=>'💉','label'=>'Immunization',     'color'=>'#4c1d95','bg'=>'#f5f3ff','pill'=>'#4c1d95'],
    'other'           => ['icon'=>'📋','label'=>'Other',            'color'=>'#374151','bg'=>'#f9fafb','pill'=>'#374151'],
];
$mc = $mod[$type];

// Count patients
$cStmt = $conn->prepare("
    SELECT COUNT(DISTINCT r.id) cnt
    FROM consultations c
    INNER JOIN residents r ON r.id=c.resident_id AND r.deleted_at IS NULL
    WHERE c.consult_type=?
    UNION ALL
    SELECT COUNT(DISTINCT r.id) cnt
    FROM care_visits cv
    INNER JOIN residents r ON r.id=cv.resident_id AND r.deleted_at IS NULL
    WHERE cv.care_type=?
");
$cStmt->bind_param('ss', $type, $type);
$cStmt->execute();
$cr = $cStmt->get_result();
$total = 0;
while ($row = $cr->fetch_assoc()) $total += (int)$row['cnt'];

// Stats for banner
$today = date('Y-m-d');
$statsStmt = $conn->prepare("
    SELECT COUNT(*) today_visits FROM consultations
    WHERE consult_type=? AND consultation_date=?
");
$statsStmt->bind_param('ss', $type, $today);
$statsStmt->execute();
$todayVisits = (int)$statsStmt->get_result()->fetch_assoc()['today_visits'];

$monthStart = date('Y-m-01');
$monthStmt = $conn->prepare("
    SELECT COUNT(*) month_visits FROM consultations
    WHERE consult_type=? AND consultation_date BETWEEN ? AND ?
");
$monthStmt->bind_param('sss', $type, $monthStart, $today);
$monthStmt->execute();
$monthVisits = (int)$monthStmt->get_result()->fetch_assoc()['month_visits'];

function age(string $bd=''): string {
    if (!$bd) return '';
    $b = new DateTime($bd);
    $y = $b->diff(new DateTime())->y;
    $m = $b->diff(new DateTime())->m;
    if ($y === 0) return $m.'mo';
    return $y.'y';
}

function ageGroup(string $bd=''): string {
    if (!$bd) return '';
    $a = (new DateTime($bd))->diff(new DateTime())->y;
    if ($a < 1)  return 'Infant';
    if ($a < 6)  return 'Child';
    if ($a < 13) return 'Pre-teen';
    if ($a < 18) return 'Teen';
    if ($a < 60) return 'Adult';
    return 'Senior';
}

// Immunization schedule for NIP card
$nipSchedule = [];
if ($type === 'immunization') {
    $ns = $conn->query("SELECT * FROM immunization_schedule WHERE is_nip=1 ORDER BY sort_order");
    if ($ns) while ($r = $ns->fetch_assoc()) $nipSchedule[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($mc['label']) ?> — Health Center</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php loadAllAssets(); ?>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
/* ════════════════════════════════════════════════
   DESIGN SYSTEM — Clinical Olive/Slate Editorial
════════════════════════════════════════════════ */
:root {
    --mod:       <?= $mc['color'] ?>;
    --mod-bg:    <?= $mc['bg'] ?>;
    --mod-lt:    color-mix(in srgb, <?= $mc['color'] ?> 6%, white);
    --mod-mid:   color-mix(in srgb, <?= $mc['color'] ?> 14%, white);
    --mod-border:color-mix(in srgb, <?= $mc['color'] ?> 22%, transparent);

    --surface:    #fafaf8;
    --surface-2:  #f4f3ef;
    --surface-3:  #ece9e2;
    --canvas:     #e8e5de;
    --ink-1:      #1c1c1a;
    --ink-2:      #4a4a45;
    --ink-3:      #8a8a80;
    --ink-4:      #b8b5aa;
    --line-1:     #d5d2cb;
    --line-2:     #e5e2da;
    --line-3:     #eeece6;

    --ok:     #16a34a; --ok-bg:   #f0fdf4;
    --warn:   #d97706; --warn-bg: #fffbeb;
    --danger: #dc2626; --danger-bg:#fef2f2;
    --info:   #2563eb; --info-bg:  #eff6ff;
    --purple: #7c3aed; --purple-bg:#f5f3ff;

    --f-display: 'Sora', 'Segoe UI', sans-serif;
    --f-body:    'Sora', 'Segoe UI', sans-serif;
    --f-serif:   'Lora', Georgia, serif;
    --f-mono:    'JetBrains Mono', 'Courier New', monospace;

    --r-sm:   4px;
    --r-md:   8px;
    --r-lg:   12px;
    --r-xl:   16px;
    --r-full: 9999px;

    --sh-1: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --sh-2: 0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
    --sh-3: 0 12px 40px rgba(0,0,0,.12), 0 4px 12px rgba(0,0,0,.06);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; font-family: var(--f-body); }
body { background: var(--canvas); color: var(--ink-1); font-size: 13px; }

/* ── Layout ── */
.cv-layout { display: flex; flex-direction: column; height: 100%; overflow: hidden; }

/* ── Header ── */
.cv-header {
    background: var(--mod);
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}
.cv-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.cv-header-inner {
    position: relative;
    padding: 20px 28px 0;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.cv-header-left {}
.cv-eyebrow {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(255,255,255,.55);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cv-eyebrow::before { content: ''; width: 20px; height: 1.5px; background: rgba(255,255,255,.4); }
.cv-title {
    font-family: var(--f-serif);
    font-size: 26px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -.4px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 2px;
}
.cv-title-icon { font-size: 22px; filter: drop-shadow(0 2px 4px rgba(0,0,0,.2)); }
.cv-sub { font-size: 12px; color: rgba(255,255,255,.55); font-style: italic; margin-bottom: 14px; }

/* Stats banner */
.cv-stats {
    display: flex;
    gap: 0;
    background: rgba(0,0,0,.15);
    border-top: 1px solid rgba(255,255,255,.1);
}
.cv-stat {
    flex: 1;
    padding: 10px 20px;
    border-right: 1px solid rgba(255,255,255,.1);
    text-align: center;
}
.cv-stat:last-child { border-right: none; }
.cv-stat-val {
    font-family: var(--f-mono);
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    line-height: 1;
    margin-bottom: 3px;
}
.cv-stat-lbl {
    font-size: 8.5px;
    font-weight: 600;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: rgba(255,255,255,.5);
}

/* Header actions */
.cv-header-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    padding-bottom: 14px;
}

/* ── Module tabs ── */
.cv-tabs {
    background: var(--surface);
    border-bottom: 1px solid var(--line-1);
    padding: 0 28px;
    display: flex;
    gap: 0;
    overflow-x: auto;
    scrollbar-width: none;
    flex-shrink: 0;
}
.cv-tabs::-webkit-scrollbar { display: none; }
.cv-tab {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 11px 14px;
    font-size: 11.5px;
    font-weight: 600;
    color: var(--ink-3);
    text-decoration: none;
    border-bottom: 2.5px solid transparent;
    white-space: nowrap;
    transition: all .12s;
}
.cv-tab:hover { color: var(--ink-1); border-bottom-color: var(--line-1); }
.cv-tab.active { color: var(--mod); border-bottom-color: var(--mod); }

/* ── Body ── */
.cv-body {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 0;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

/* ── Patient Panel ── */
.patient-panel {
    background: var(--surface);
    border-right: 1px solid var(--line-1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.patient-panel-search {
    padding: 14px 16px;
    border-bottom: 1px solid var(--line-2);
    background: var(--surface-2);
    flex-shrink: 0;
}
.search-wrap {
    position: relative;
}
.search-input {
    width: 100%;
    padding: 8px 12px 8px 34px;
    border: 1.5px solid var(--line-1);
    border-radius: var(--r-md);
    font-family: var(--f-body);
    font-size: 12.5px;
    color: var(--ink-1);
    background: #fff;
    outline: none;
    transition: border-color .14s, box-shadow .14s;
}
.search-input:focus {
    border-color: var(--mod);
    box-shadow: 0 0 0 3px var(--mod-border);
}
.search-input::placeholder { color: var(--ink-4); font-style: italic; font-size: 12px; }
.search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ink-3);
    pointer-events: none;
}
.patient-panel-filter {
    padding: 8px 16px;
    border-bottom: 1px solid var(--line-2);
    background: var(--surface);
    flex-shrink: 0;
    display: flex;
    gap: 6px;
    align-items: center;
}
.filter-label {
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--ink-3);
    flex-shrink: 0;
}
.filter-chip {
    padding: 3px 9px;
    border-radius: var(--r-full);
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid var(--line-1);
    background: #fff;
    color: var(--ink-3);
    transition: all .12s;
}
.filter-chip.active {
    background: var(--mod);
    border-color: var(--mod);
    color: #fff;
}
.filter-chip:hover:not(.active) { border-color: var(--mod); color: var(--mod); }

.patient-list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--line-1) transparent;
}
.pt-empty {
    padding: 32px 20px;
    text-align: center;
    color: var(--ink-3);
    font-size: 12px;
    font-style: italic;
    line-height: 1.8;
}

.pt-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    cursor: pointer;
    border-bottom: 1px solid var(--line-3);
    transition: background .1s;
    position: relative;
}
.pt-item:hover { background: var(--mod-lt); }
.pt-item.active {
    background: var(--mod-bg);
    border-left: 3px solid var(--mod);
}
.pt-item.active::after {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-top: 7px solid transparent;
    border-bottom: 7px solid transparent;
    border-right: 7px solid var(--canvas);
}
.pt-avatar {
    width: 38px;
    height: 38px;
    border-radius: var(--r-md);
    background: var(--mod-mid);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--f-mono);
    font-size: 13px;
    font-weight: 700;
    color: var(--mod);
    flex-shrink: 0;
    border: 1.5px solid var(--mod-border);
}
.pt-info { flex: 1; min-width: 0; }
.pt-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-1);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}
.pt-meta { font-size: 10.5px; color: var(--ink-3); display: flex; gap: 5px; align-items: center; }
.pt-age-chip {
    padding: 1px 6px;
    border-radius: var(--r-full);
    background: var(--surface-2);
    color: var(--ink-2);
    font-size: 9.5px;
    font-weight: 600;
    border: 1px solid var(--line-2);
}
.pt-right { text-align: right; flex-shrink: 0; }
.pt-last-visit { font-family: var(--f-mono); font-size: 9.5px; color: var(--ink-3); }
.pt-count { font-family: var(--f-mono); font-size: 10px; font-weight: 700; color: var(--mod); margin-top: 2px; }

/* ── Detail Panel ── */
.detail-panel {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--canvas);
}

/* Empty state */
.cv-empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    color: var(--ink-3);
    padding: 40px;
}
.cv-empty-icon { font-size: 48px; opacity: .25; }
.cv-empty-title { font-family: var(--f-serif); font-size: 18px; font-weight: 600; color: var(--ink-2); }
.cv-empty-sub { font-size: 12px; font-style: italic; text-align: center; line-height: 1.7; max-width: 320px; }

/* Patient detail header */
.pt-detail-header {
    background: var(--surface);
    border-bottom: 1px solid var(--line-1);
    padding: 16px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    flex-shrink: 0;
    box-shadow: var(--sh-1);
}
.pt-detail-name { font-family: var(--f-serif); font-size: 17px; font-weight: 700; color: var(--ink-1); margin-bottom: 3px; }
.pt-detail-meta { font-size: 11px; color: var(--ink-3); display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.pt-detail-tag {
    padding: 2px 8px;
    border-radius: var(--r-full);
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: .3px;
    text-transform: uppercase;
    background: var(--mod-bg);
    color: var(--mod);
    border: 1px solid var(--mod-border);
}
.pt-detail-actions { display: flex; gap: 7px; }

/* Detail content */
.pt-detail-content {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--line-1) transparent;
    padding: 18px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* Cards */
.cv-card {
    background: var(--surface);
    border: 1px solid var(--line-1);
    border-radius: var(--r-lg);
    box-shadow: var(--sh-1);
    overflow: hidden;
}
.cv-card-head {
    padding: 12px 16px;
    border-bottom: 1px solid var(--line-2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--surface-2);
}
.cv-card-title {
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: var(--ink-2);
    display: flex;
    align-items: center;
    gap: 8px;
}
.cv-card-title::before {
    content: '';
    width: 3px;
    height: 13px;
    background: var(--mod);
    border-radius: 2px;
    flex-shrink: 0;
}
.cv-card-meta { font-family: var(--f-mono); font-size: 10px; color: var(--ink-3); }

/* ── NIP CARD ── */
.nip-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 8px;
    padding: 14px 16px;
}
.nip-slot {
    border: 1.5px solid var(--line-1);
    border-radius: var(--r-md);
    padding: 10px 12px;
    cursor: pointer;
    transition: all .15s;
    position: relative;
    background: #fff;
}
.nip-slot:hover { box-shadow: var(--sh-2); transform: translateY(-1px); border-color: var(--mod); }
.nip-slot.given {
    border-color: var(--ok);
    background: var(--ok-bg);
}
.nip-slot.given:hover { border-color: var(--ok); }
.nip-slot.overdue {
    border-color: var(--danger);
    background: var(--danger-bg);
}
.nip-slot.overdue:hover { border-color: var(--danger); }
.nip-slot.pending-soon {
    border-color: var(--warn);
    background: var(--warn-bg);
}
.nip-slot.catch-up {
    border-left: 3px solid var(--purple);
}
.ns-vaccine {
    font-size: 12px;
    font-weight: 700;
    color: var(--ink-1);
    margin-bottom: 2px;
    line-height: 1.2;
}
.ns-dose {
    font-size: 9.5px;
    color: var(--ink-3);
    margin-bottom: 6px;
}
.ns-age {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: .3px;
    color: var(--ink-4);
    margin-bottom: 6px;
    font-family: var(--f-mono);
}
.ns-status-row { display: flex; align-items: center; justify-content: space-between; }
.ns-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: .4px;
    text-transform: uppercase;
}
.ns-status::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.ns-status.given   { color: var(--ok); }
.ns-status.overdue { color: var(--danger); }
.ns-status.soon    { color: var(--warn); }
.ns-status.pending { color: var(--ink-3); }
.ns-date {
    font-family: var(--f-mono);
    font-size: 8.5px;
    color: var(--ink-3);
}
.nip-edit-icon {
    position: absolute;
    top: 7px;
    right: 8px;
    font-size: 11px;
    opacity: 0;
    transition: opacity .12s;
    color: var(--mod);
}
.nip-slot:hover .nip-edit-icon { opacity: 1; }
.nip-progress {
    padding: 10px 16px;
    border-top: 1px solid var(--line-2);
    background: var(--surface-2);
    display: flex;
    align-items: center;
    gap: 12px;
}
.nip-prog-bar-wrap {
    flex: 1;
    height: 5px;
    background: var(--line-1);
    border-radius: var(--r-full);
    overflow: hidden;
}
.nip-prog-bar {
    height: 100%;
    background: var(--ok);
    border-radius: var(--r-full);
    transition: width .4s ease;
}
.nip-prog-label { font-family: var(--f-mono); font-size: 10px; color: var(--ink-2); white-space: nowrap; }

/* ── Visit history ── */
.visit-tabs {
    padding: 0 16px;
    border-bottom: 1px solid var(--line-2);
    background: var(--surface);
    display: flex;
    gap: 0;
}
.v-tab {
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 600;
    color: var(--ink-3);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all .12s;
    white-space: nowrap;
}
.v-tab:hover { color: var(--ink-1); }
.v-tab.active { color: var(--mod); border-bottom-color: var(--mod); }

.visit-list { overflow-y: auto; max-height: 320px; }
.v-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    border-bottom: 1px solid var(--line-3);
    cursor: pointer;
    transition: background .1s;
}
.v-item:hover { background: var(--surface-2); }
.v-item.active { background: var(--mod-lt); }
.v-item-date {
    font-family: var(--f-mono);
    font-size: 10px;
    color: var(--ink-3);
    min-width: 70px;
}
.v-item-summary {
    flex: 1;
    font-size: 12.5px;
    color: var(--ink-2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.v-item-badges { display: flex; gap: 5px; flex-shrink: 0; }

/* ── Visit detail ── */
.vd-panel {
    margin-top: 10px;
    display: none;
}
.vd-panel.open { display: block; }
.vd-close {
    float: right;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--ink-3);
    font-size: 16px;
    line-height: 1;
    padding: 0 4px;
}
.vd-close:hover { color: var(--danger); }
.vd-body { padding: 14px 16px; }
.vd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.vd-field {}
.vd-lbl { font-size: 8.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--ink-3); margin-bottom: 3px; }
.vd-val { font-size: 12.5px; color: var(--ink-1); line-height: 1.5; white-space: pre-line; }
.vd-val.empty { font-style: italic; color: var(--ink-4); }
.vd-section { margin-bottom: 14px; }
.vd-sec-title {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: var(--mod);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}
.vd-sec-title::after { content: ''; flex: 1; height: 1px; background: var(--mod-border); }

.vitals-strip {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.vital-pill {
    padding: 6px 12px;
    border-radius: var(--r-md);
    background: var(--surface-2);
    border: 1px solid var(--line-1);
    text-align: center;
    min-width: 70px;
}
.vital-pill-lbl { font-size: 7.5px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--ink-3); margin-bottom: 2px; }
.vital-pill-val { font-family: var(--f-mono); font-size: 13px; font-weight: 700; color: var(--ink-1); }

/* ── Maternal profile ── */
.mp-display {
    padding: 14px 16px;
}
.gtpal-row {
    display: flex;
    gap: 6px;
    margin-bottom: 10px;
}
.gtpal-cell {
    flex: 1;
    text-align: center;
    padding: 8px 4px;
    background: var(--mod-bg);
    border: 1px solid var(--mod-border);
    border-radius: var(--r-sm);
}
.gtpal-val { font-family: var(--f-mono); font-size: 20px; font-weight: 700; color: var(--mod); line-height: 1; }
.gtpal-lbl { font-size: 8px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--ink-3); margin-top: 3px; }
.flag-list { display: flex; flex-wrap: wrap; gap: 5px; }
.flag-chip {
    padding: 3px 8px;
    border-radius: var(--r-full);
    font-size: 9.5px;
    font-weight: 700;
    background: var(--danger-bg);
    color: var(--danger);
    border: 1px solid color-mix(in srgb, var(--danger) 20%, transparent);
}

/* ── Badges ── */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: var(--r-full);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .4px;
    text-transform: uppercase;
    border: 1px solid transparent;
    white-space: nowrap;
}
.badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.b-completed { background: var(--ok-bg);   color: var(--ok);     border-color: color-mix(in srgb,var(--ok) 20%,transparent); }
.b-ongoing   { background: var(--warn-bg);  color: var(--warn);   border-color: color-mix(in srgb,var(--warn) 20%,transparent); }
.b-followup  { background: var(--info-bg);  color: var(--info);   border-color: color-mix(in srgb,var(--info) 20%,transparent); }
.b-dismissed { background: var(--surface-2);color: var(--ink-2);  border-color: var(--line-1); }
.b-consult   { background: var(--info-bg);  color: var(--info);   border-color: color-mix(in srgb,var(--info) 20%,transparent); }
.b-care      { background: var(--ok-bg);    color: var(--ok);     border-color: color-mix(in srgb,var(--ok) 20%,transparent); }

/* ── Buttons ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: var(--r-md);
    font-family: var(--f-body);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .3px;
    cursor: pointer;
    border: 1.5px solid;
    transition: all .14s;
    white-space: nowrap;
    text-decoration: none;
}
.btn-mod { background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.35); color: #fff; backdrop-filter: blur(4px); }
.btn-mod:hover { background: rgba(255,255,255,.25); }
.btn-mod-solid { background: var(--mod); border-color: var(--mod); color: #fff; }
.btn-mod-solid:hover { filter: brightness(1.08); }
.btn-ghost { background: var(--surface); border-color: var(--line-1); color: var(--ink-2); }
.btn-ghost:hover { border-color: var(--mod); color: var(--mod); background: var(--mod-lt); }
.btn-ghost-sm { padding: 4px 10px; font-size: 9.5px; }
.btn-danger { background: var(--danger-bg); border-color: color-mix(in srgb,var(--danger) 30%,transparent); color: var(--danger); }
.btn-danger:hover { background: var(--danger); color: #fff; }
.btn:disabled { opacity: .4; cursor: not-allowed; filter: none !important; }
.btn-icon { width: 28px; height: 28px; padding: 0; justify-content: center; border-radius: var(--r-sm); }

/* ── Modals ── */
.ui-dialog { border: 1px solid var(--line-1) !important; border-radius: var(--r-xl) !important; box-shadow: var(--sh-3) !important; padding: 0 !important; font-family: var(--f-body) !important; overflow: hidden !important; }
.ui-dialog-titlebar { background: var(--mod) !important; border: none !important; padding: 14px 18px !important; border-radius: 0 !important; }
.ui-dialog-title { font-family: var(--f-body) !important; font-size: 11px !important; font-weight: 700 !important; letter-spacing: 1.2px !important; text-transform: uppercase !important; color: #fff !important; }
.ui-dialog-titlebar-close { background: rgba(255,255,255,.2) !important; border: 1px solid rgba(255,255,255,.3) !important; border-radius: var(--r-sm) !important; color: #fff !important; width: 26px !important; height: 26px !important; top: 50% !important; transform: translateY(-50%) !important; }
.ui-dialog-content { padding: 0 !important; }
.ui-dialog-buttonpane { display: none !important; }
.ui-autocomplete { z-index: 9999 !important; border-radius: var(--r-md) !important; border: 1.5px solid var(--line-1) !important; box-shadow: var(--sh-2) !important; font-family: var(--f-body) !important; max-height: 220px; overflow-y: auto !important; }
.ui-menu-item-wrapper { padding: 9px 13px !important; font-size: 12.5px !important; }
.ui-state-active { background: var(--mod-lt) !important; color: var(--ink-1) !important; }

/* Modal inner */
.modal-inner { max-height: 72vh; overflow-y: auto; }
.m-section { padding: 14px 18px 0; border-top: 1px solid var(--line-2); }
.m-section:first-child { border-top: none; padding-top: 18px; }
.m-title {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    color: var(--ink-3);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}
.m-title::after { content: ''; flex: 1; height: 1px; background: var(--line-2); }
.m-body { padding-bottom: 14px; }
.fg { margin-bottom: 12px; }
.fg-lbl { display: block; font-size: 8.5px; font-weight: 700; letter-spacing: 1.1px; text-transform: uppercase; color: var(--ink-2); margin-bottom: 5px; }
.fg-lbl .req { color: var(--danger); }
.fg-in, .fg-sel, .fg-ta {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid var(--line-1);
    border-radius: var(--r-md);
    font-family: var(--f-body);
    font-size: 13px;
    color: var(--ink-1);
    background: #fff;
    outline: none;
    transition: border-color .14s, box-shadow .14s;
}
.fg-in:focus, .fg-sel:focus, .fg-ta:focus {
    border-color: var(--mod);
    box-shadow: 0 0 0 3px var(--mod-border);
}
.fg-in::placeholder { color: var(--ink-4); font-style: italic; font-size: 12px; }
.fg-ta { resize: vertical; min-height: 64px; }
.fg-hint { font-size: 10px; color: var(--ink-3); margin-top: 3px; }
.g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.m-footer {
    padding: 12px 18px;
    border-top: 1px solid var(--line-2);
    background: var(--surface-2);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.chk-row { display: flex; flex-wrap: wrap; gap: 6px; }
.chk-label {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border: 1.5px solid var(--line-1);
    border-radius: var(--r-md);
    cursor: pointer;
    font-size: 12px;
    color: var(--ink-2);
    transition: all .12s;
    background: #fff;
}
.chk-label:has(input:checked) { border-color: var(--mod); background: var(--mod-bg); color: var(--mod); }
.chk-label input { display: none; }

/* NIP status legend */
.nip-legend {
    padding: 8px 16px;
    border-top: 1px solid var(--line-2);
    background: var(--surface-2);
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.nl-item { display: flex; align-items: center; gap: 5px; font-size: 9.5px; color: var(--ink-2); }
.nl-dot { width: 8px; height: 8px; border-radius: 2px; flex-shrink: 0; }
.nl-given   { background: var(--ok); }
.nl-overdue { background: var(--danger); }
.nl-soon    { background: var(--warn); }
.nl-pending { background: var(--line-1); border: 1px solid var(--line-1); }

/* Alert inline */
.cv-alert {
    padding: 10px 14px;
    border-radius: var(--r-md);
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cv-alert-ok { background: var(--ok-bg); color: var(--ok); border: 1px solid color-mix(in srgb,var(--ok) 20%,transparent); }
.cv-alert-err { background: var(--danger-bg); color: var(--danger); border: 1px solid color-mix(in srgb,var(--danger) 20%,transparent); }

/* Generate modal scope toggle */
.scope-toggle { display: flex; gap: 0; border: 1.5px solid var(--line-1); border-radius: var(--r-md); overflow: hidden; margin-bottom: 14px; }
.scope-btn { flex: 1; padding: 8px 12px; text-align: center; font-size: 11px; font-weight: 700; cursor: pointer; color: var(--ink-2); background: #fff; border: none; transition: all .12s; }
.scope-btn.active { background: var(--mod); color: #fff; }

/* Scrollbar */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--line-1); border-radius: 99px; }
::-webkit-scrollbar-thumb:hover { background: var(--ink-4); }
</style>
</head>
<body class="h-screen overflow-hidden" style="display:none;">
<?php include_once '../layout/navbar.php'; ?>
<div class="cv-layout" style="height:calc(100vh - 62px);">

    <!-- ══ HEADER ══ -->
    <div class="cv-header">
        <div class="cv-header-inner">
            <div class="cv-header-left">
                <div class="cv-eyebrow">Barangay Bombongan · Health Center · Care Records</div>
                <div class="cv-title">
                    <span class="cv-title-icon"><?= $mc['icon'] ?></span>
                    <?= htmlspecialchars($mc['label']) ?>
                </div>
                <div class="cv-sub">Clinical records, visit history, and care programme tracking</div>
            </div>
            <div class="cv-header-actions">
                <button class="btn btn-mod" id="btnGenerate">↗ Generate Report</button>
                <button class="btn btn-mod" id="btnNewVisit" disabled>+ New Visit</button>
            </div>
        </div>
        <div class="cv-stats">
            <div class="cv-stat"><div class="cv-stat-val"><?= $total ?></div><div class="cv-stat-lbl">Total Patients</div></div>
            <div class="cv-stat"><div class="cv-stat-val"><?= $todayVisits ?></div><div class="cv-stat-lbl">Today's Visits</div></div>
            <div class="cv-stat"><div class="cv-stat-val"><?= $monthVisits ?></div><div class="cv-stat-lbl">This Month</div></div>
            <div class="cv-stat"><div class="cv-stat-val" id="hdrSelected">—</div><div class="cv-stat-lbl">Selected</div></div>
        </div>
    </div>

    <!-- ══ MODULE TABS ══ -->
    <div class="cv-tabs">
        <?php foreach ($mod as $t => [$icon, $label]):
            // Re-extract from the $mod array
            $cfg = $mod[$t];
        ?>
        <a href="?type=<?= $t ?>" class="cv-tab <?= $t===$type?'active':'' ?>"
           style="<?= $t===$type?'--mod:'.$cfg['color'].';':'' ?>">
            <?= $cfg['icon'] ?> <?= htmlspecialchars($cfg['label']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ══ BODY ══ -->
    <div class="cv-body">

        <!-- ─ Patient Panel ─ -->
        <div class="patient-panel">
            <div class="patient-panel-search">
                <div class="search-wrap">
                    <svg class="search-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="anySearch" class="search-input" placeholder="Search any resident…" autocomplete="off">
                    <input type="hidden" id="anyId">
                </div>
            </div>
            <div class="patient-panel-filter">
                <span class="filter-label">Show</span>
                <button class="filter-chip active" data-filter="all">All</button>
                <button class="filter-chip" data-filter="recent">Recent</button>
                <button class="filter-chip" data-filter="this_month">Month</button>
                <span id="ptCountLabel" style="margin-left:auto;font-family:var(--f-mono);font-size:9px;color:var(--ink-3);"></span>
            </div>
            <div class="patient-list" id="ptList">
                <div class="pt-empty" id="ptLoading">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3;margin-bottom:10px;animation:spin 1s linear infinite;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Loading patients…
                </div>
            </div>
            <style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
        </div>

        <!-- ─ Detail Panel ─ -->
        <div class="detail-panel" id="detailPanel">
            <div class="cv-empty-state" id="emptyState">
                <div class="cv-empty-icon"><?= $mc['icon'] ?></div>
                <div class="cv-empty-title">Select a patient</div>
                <div class="cv-empty-sub">Choose from the list on the left or search to find a patient and view their <?= htmlspecialchars(strtolower($mc['label'])) ?> records.</div>
            </div>

            <div id="patientDetail" style="display:none;flex:1;overflow:hidden;display:none;flex-direction:column;">
                <!-- Patient bar -->
                <div class="pt-detail-header">
                    <div>
                        <div class="pt-detail-name" id="selName">—</div>
                        <div class="pt-detail-meta">
                            <span id="selAge"></span>
                            <span id="selGender"></span>
                            <span class="pt-detail-tag" id="selTag"><?= htmlspecialchars($mc['label']) ?></span>
                        </div>
                    </div>
                    <div class="pt-detail-actions">
                        <button class="btn btn-ghost btn-ghost-sm" id="btnQuickAddVisit">+ Add Visit</button>
                        <button class="btn btn-ghost btn-ghost-sm" id="btnViewProfile">View Profile</button>
                    </div>
                </div>

                <!-- Detail content -->
                <div class="pt-detail-content" id="detailContent">

                    <?php if ($type === 'immunization'): ?>
                    <!-- NIP CARD -->
                    <div class="cv-card" id="nipCard">
                        <div class="cv-card-head">
                            <div class="cv-card-title">NIP Immunization Card</div>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span class="cv-card-meta" id="nipStats">— / — given</span>
                                <button class="btn btn-ghost btn-ghost-sm" id="btnAddCustomImm">+ Custom Vaccine</button>
                            </div>
                        </div>
                        <div class="nip-grid" id="nipGrid">
                            <div style="grid-column:1/-1;padding:20px;text-align:center;color:var(--ink-3);font-size:12px;font-style:italic;">Select a patient to load NIP card</div>
                        </div>
                        <div class="nip-progress">
                            <div class="nip-prog-bar-wrap"><div class="nip-prog-bar" id="nipProgBar" style="width:0%"></div></div>
                            <span class="nip-prog-label" id="nipProgLabel">0%</span>
                        </div>
                        <div class="nip-legend">
                            <div class="nl-item"><div class="nl-dot nl-given"></div> Given</div>
                            <div class="nl-item"><div class="nl-dot nl-overdue"></div> Overdue</div>
                            <div class="nl-item"><div class="nl-dot nl-soon"></div> Due Soon (≤30d)</div>
                            <div class="nl-item"><div class="nl-dot nl-pending"></div> Pending</div>
                        </div>
                    </div>

                    <!-- Custom immunizations (not in NIP) -->
                    <div class="cv-card" id="customImmCard" style="display:none;">
                        <div class="cv-card-head">
                            <div class="cv-card-title">Additional Vaccines</div>
                            <span class="cv-card-meta" id="customImmCount">—</span>
                        </div>
                        <div id="customImmList" style="padding:12px 16px;font-size:12px;color:var(--ink-2);"></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($type === 'maternal'): ?>
                    <!-- MATERNAL PROFILE -->
                    <div class="cv-card" id="mpCard">
                        <div class="cv-card-head">
                            <div class="cv-card-title">Obstetric Profile (GTPAL)</div>
                            <button class="btn btn-ghost btn-ghost-sm" id="btnEditMp">Edit Profile</button>
                        </div>
                        <div class="mp-display" id="mpDisplay">
                            <div style="color:var(--ink-3);font-size:12px;font-style:italic;">No profile recorded yet.</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- VISIT HISTORY -->
                    <div class="cv-card">
                        <div class="cv-card-head">
                            <div class="cv-card-title">Visit History</div>
                            <span class="cv-card-meta" id="vCount">—</span>
                        </div>
                        <div class="visit-tabs">
                            <div class="v-tab active" data-vsrc="all">All</div>
                            <div class="v-tab" data-vsrc="consult">Consultations</div>
                            <div class="v-tab" data-vsrc="care">Care Visits</div>
                        </div>
                        <div class="visit-list" id="vList">
                            <div style="padding:20px;text-align:center;color:var(--ink-3);font-size:12px;">—</div>
                        </div>
                    </div>

                    <!-- VISIT DETAIL PANEL (inline) -->
                    <div class="cv-card vd-panel" id="vDetailPanel">
                        <div class="cv-card-head">
                            <div class="cv-card-title" id="vdTitle">Visit Detail</div>
                            <div style="display:flex;gap:6px;">
                                <button class="btn btn-ghost btn-ghost-sm" id="btnEditVd">✏ Edit</button>
                                <button class="btn btn-ghost btn-ghost-sm" onclick="$('#vDetailPanel').removeClass('open')">✕</button>
                            </div>
                        </div>
                        <div class="vd-body" id="vdBody"></div>
                    </div>

                </div><!-- /detail-content -->
            </div><!-- /patientDetail -->
        </div><!-- /detail-panel -->
    </div><!-- /body -->
</div><!-- /layout -->

<!-- ═════════════════════════════════════════
     MODAL: EDIT NIP SLOT
═════════════════════════════════════════ -->
<div id="nipEditModal" title="Edit Vaccine Record" class="hidden">
<form id="nipEditForm" class="modal-inner">
    <input type="hidden" name="immun_id"    id="ne_imm_id">
    <input type="hidden" name="schedule_id" id="ne_sched_id">
    <input type="hidden" name="resident_id" id="ne_rid">
    <input type="hidden" name="vaccine_name" id="ne_vaccine">
    <input type="hidden" name="dose"        id="ne_dose_label">

    <div class="m-section" style="border-top:none;padding-top:18px;">
        <!-- Vaccine info banner -->
        <div style="padding:12px 14px;background:var(--mod-bg);border:1px solid var(--mod-border);border-radius:var(--r-md);margin-bottom:16px;">
            <div style="font-size:15px;font-weight:700;color:var(--mod);" id="ne_vaccine_display">—</div>
            <div style="font-size:11px;color:var(--ink-3);margin-top:2px;" id="ne_dose_display">—</div>
            <div style="font-size:10px;color:var(--ink-3);margin-top:4px;" id="ne_target_age_display">—</div>
        </div>

        <div class="m-title">Vaccination Record</div>
        <div class="m-body">
            <div class="g3">
                <div class="fg">
                    <label class="fg-lbl">Date Given <span class="req">*</span></label>
                    <input type="date" name="date_given" id="ne_date" class="fg-in">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Route</label>
                    <select name="route" id="ne_route" class="fg-sel">
                        <option value="IM">IM</option>
                        <option value="SC">SC</option>
                        <option value="ID">ID (Intradermal)</option>
                        <option value="Oral">Oral</option>
                        <option value="Nasal">Nasal</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Site Given</label>
                    <input type="text" name="site_given" id="ne_site" class="fg-in" placeholder="e.g. Right thigh" autocomplete="off">
                </div>
            </div>
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Administered By</label>
                    <input type="text" name="administered_by" id="ne_admin" class="fg-in"
                           value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Next Schedule</label>
                    <input type="date" name="next_schedule" id="ne_next" class="fg-in">
                </div>
            </div>
        </div>
    </div>

    <div class="m-section">
        <div class="m-title">Vaccine Details</div>
        <div class="m-body">
            <div class="g3">
                <div class="fg">
                    <label class="fg-lbl">Batch / Lot Number</label>
                    <input type="text" name="batch_number" id="ne_batch" class="fg-in" autocomplete="off">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Lot Number</label>
                    <input type="text" name="lot_number" id="ne_lot" class="fg-in" autocomplete="off">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Expiry Date</label>
                    <input type="date" name="expiry_date" id="ne_expiry" class="fg-in">
                </div>
            </div>
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">VVM Status</label>
                    <select name="vvm_status" id="ne_vvm" class="fg-sel">
                        <option value="OK">OK (Stage 1-2)</option>
                        <option value="WARN">Warning (Stage 3)</option>
                        <option value="DISCARD">Discard (Stage 4)</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Cold Chain Temp (°C)</label>
                    <input type="number" name="temperature_at_vaccination" id="ne_temp" class="fg-in" step="0.1" placeholder="2–8°C">
                </div>
            </div>
            <div class="fg">
                <label class="fg-lbl">Facility (if given elsewhere)</label>
                <input type="text" name="given_at_facility" id="ne_facility" class="fg-in" autocomplete="off"
                       placeholder="Leave blank if given here">
            </div>
        </div>
    </div>

    <div class="m-section">
        <div class="m-title">Clinical Notes</div>
        <div class="m-body">
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Adverse Reaction</label>
                    <textarea name="adverse_reaction" id="ne_adverse" class="fg-ta" placeholder="Any adverse events following immunization…"></textarea>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Remarks</label>
                    <textarea name="remarks" id="ne_remarks" class="fg-ta" placeholder="Additional notes…"></textarea>
                </div>
            </div>
            <div class="fg">
                <div class="chk-row">
                    <label class="chk-label"><input type="checkbox" name="is_defaulter" value="1" id="ne_defaulter"> Defaulter (missed scheduled date)</label>
                    <label class="chk-label"><input type="checkbox" name="catch_up" value="1" id="ne_catchup"> Catch-up dose</label>
                </div>
            </div>
        </div>
    </div>

    <div style="padding:10px 18px;background:var(--danger-bg);display:none;" id="ne_undo_wrap">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <span style="font-size:12px;color:var(--danger);">⚠ This vaccine is marked as given. Remove the record?</span>
            <button type="button" class="btn btn-danger" style="padding:5px 12px;font-size:10px;" id="ne_undo_btn">Remove Record</button>
        </div>
    </div>

    <div class="m-footer">
        <button type="button" class="btn btn-ghost" onclick="$('#nipEditModal').dialog('close')">Cancel</button>
        <button type="submit" class="btn btn-mod-solid" id="ne_submit">Save Vaccine Record</button>
    </div>
</form>
</div>

<!-- ═════════════════════════════════════════
     MODAL: ADD CUSTOM VACCINE
═════════════════════════════════════════ -->
<div id="customImmModal" title="Add Custom Vaccine" class="hidden">
<form id="customImmForm" class="modal-inner">
    <input type="hidden" name="resident_id"  id="ci_rid">
    <input type="hidden" name="schedule_id"  value="">
    <div class="m-section" style="border-top:none;padding-top:18px;">
        <div class="m-title">Vaccine Information</div>
        <div class="m-body">
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Vaccine Name <span class="req">*</span></label>
                    <input type="text" name="vaccine_name" id="ci_vaccine" class="fg-in" required autocomplete="off"
                           placeholder="e.g. Influenza, HPV, Varicella…">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Dose</label>
                    <input type="text" name="dose" id="ci_dose" class="fg-in" autocomplete="off" placeholder="e.g. Dose 1">
                </div>
            </div>
            <div class="g3">
                <div class="fg">
                    <label class="fg-lbl">Date Given <span class="req">*</span></label>
                    <input type="date" name="date_given" id="ci_date" class="fg-in" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Route</label>
                    <select name="route" class="fg-sel">
                        <option value="IM">IM</option><option value="SC">SC</option>
                        <option value="ID">ID</option><option value="Oral">Oral</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Next Schedule</label>
                    <input type="date" name="next_schedule" class="fg-in">
                </div>
            </div>
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Administered By</label>
                    <input type="text" name="administered_by" class="fg-in"
                           value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Site Given</label>
                    <input type="text" name="site_given" class="fg-in" placeholder="e.g. Right arm" autocomplete="off">
                </div>
            </div>
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Batch Number</label>
                    <input type="text" name="batch_number" class="fg-in" autocomplete="off">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Expiry Date</label>
                    <input type="date" name="expiry_date" class="fg-in">
                </div>
            </div>
            <div class="fg">
                <label class="fg-lbl">Adverse Reaction / Remarks</label>
                <textarea name="adverse_reaction" class="fg-ta" placeholder="AEFI, notes…"></textarea>
            </div>
        </div>
    </div>
    <div class="m-footer">
        <button type="button" class="btn btn-ghost" onclick="$('#customImmModal').dialog('close')">Cancel</button>
        <button type="submit" class="btn btn-mod-solid">Save Vaccine</button>
    </div>
</form>
</div>

<!-- ═════════════════════════════════════════
     MODAL: MATERNAL PROFILE
═════════════════════════════════════════ -->
<?php if ($type === 'maternal'): ?>
<div id="mpModal" title="Edit Obstetric Profile" class="hidden">
<form id="mpForm" class="modal-inner">
    <input type="hidden" name="resident_id" id="mp_rid">
    <div class="m-section" style="border-top:none;padding-top:18px;">
        <div class="m-title">GTPAL — Obstetric History</div>
        <div class="m-body">
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;">
                <?php foreach(['gravida'=>'G — Pregnancies','term'=>'T — Term','preterm'=>'P — Preterm','abortions'=>'A — Abortions','living_children'=>'L — Living'] as $n=>$l): ?>
                <div class="fg">
                    <label class="fg-lbl"><?= explode(' — ',$l)[0] ?></label>
                    <input type="number" name="<?= $n ?>" min="0" class="fg-in" placeholder="0" style="text-align:center;font-family:var(--f-mono);font-size:16px;font-weight:700;">
                    <div class="fg-hint" style="font-size:9px;"><?= explode(' — ',$l)[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="m-section">
        <div class="m-title">Risk Flags — Complications History</div>
        <div class="m-body">
            <div class="chk-row">
                <?php foreach(['hx_pre_eclampsia'=>'Pre-eclampsia','hx_pph'=>'PPH','hx_cesarean'=>'C-Section','hx_ectopic'=>'Ectopic','hx_stillbirth'=>'Stillbirth'] as $n=>$l): ?>
                <label class="chk-label"><input type="checkbox" name="<?= $n ?>" value="1"> <?= $l ?></label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="m-section">
        <div class="m-title">Chronic Conditions</div>
        <div class="m-body">
            <div class="chk-row">
                <?php foreach(['has_diabetes'=>'Diabetes','has_hypertension'=>'Hypertension','has_hiv'=>'HIV','has_anemia'=>'Anemia'] as $n=>$l): ?>
                <label class="chk-label"><input type="checkbox" name="<?= $n ?>" value="1"> <?= $l ?></label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="m-section">
        <div class="m-title">Additional</div>
        <div class="m-body">
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Blood Type</label>
                    <select name="blood_type" class="fg-sel">
                        <?php foreach(['Unknown','A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                        <option value="<?= $bt ?>"><?= $bt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Other Conditions</label>
                    <input type="text" name="other_conditions" class="fg-in" autocomplete="off">
                </div>
            </div>
            <div class="fg">
                <label class="fg-lbl">Notes</label>
                <textarea name="notes" class="fg-ta"></textarea>
            </div>
        </div>
    </div>
    <div class="m-footer">
        <button type="button" class="btn btn-ghost" onclick="$('#mpModal').dialog('close')">Cancel</button>
        <button type="submit" class="btn btn-mod-solid">Save Profile</button>
    </div>
</form>
</div>
<?php endif; ?>

<!-- ═════════════════════════════════════════
     MODAL: EDIT CARE VISIT
═════════════════════════════════════════ -->
<div id="editVisitModal" title="Edit Visit" class="hidden">
<form id="editVisitForm" class="modal-inner">
    <input type="hidden" name="care_visit_id" id="ev_vid">
    <input type="hidden" name="type"          id="ev_type">
    <div class="m-section" style="border-top:none;padding-top:18px;">
        <div class="m-title">Visit Details</div>
        <div class="m-body">
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Visit Date <span class="req">*</span></label>
                    <input type="date" name="visit_date" id="ev_date" class="fg-in">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Health Worker</label>
                    <input type="text" name="health_worker" id="ev_worker" class="fg-in" autocomplete="off">
                </div>
            </div>
            <div class="fg">
                <label class="fg-lbl">Notes / Remarks</label>
                <textarea name="notes" id="ev_notes" class="fg-ta"></textarea>
            </div>
        </div>
    </div>
    <div id="ev_fields"></div>
    <div class="m-footer">
        <button type="button" class="btn btn-ghost" onclick="$('#editVisitModal').dialog('close')">Cancel</button>
        <button type="submit" class="btn btn-mod-solid">Save Changes</button>
    </div>
</form>
</div>

<!-- ═════════════════════════════════════════
     MODAL: GENERATE REPORT
═════════════════════════════════════════ -->
<div id="generateModal" title="Generate Report" class="hidden">
<form id="generateForm" style="padding:18px 20px 0;">
    <div class="scope-toggle">
        <button type="button" class="scope-btn active" id="genScopePatient">Selected Patient</button>
        <button type="button" class="scope-btn" id="genScopeAll">All Patients</button>
    </div>
    <div id="genPatientInfo" style="padding:10px 12px;background:var(--mod-bg);border:1px solid var(--mod-border);border-radius:var(--r-md);margin-bottom:14px;font-size:12px;color:var(--mod);display:none;">
        Patient: <strong id="genPatientName">—</strong>
    </div>
    <div class="g2">
        <div class="fg">
            <label class="fg-lbl">From Date</label>
            <input type="date" id="gen_from" class="fg-in" value="<?= date('Y-01-01') ?>">
        </div>
        <div class="fg">
            <label class="fg-lbl">To Date</label>
            <input type="date" id="gen_to" class="fg-in" value="<?= date('Y-m-d') ?>">
        </div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:16px;">
        <?php foreach(['today'=>'Today','week'=>'This Week','month'=>'This Month','year'=>'This Year'] as $k=>$lbl): ?>
        <button type="button" class="btn btn-ghost" style="padding:4px 10px;font-size:9.5px;" data-range="<?= $k ?>"><?= $lbl ?></button>
        <?php endforeach; ?>
    </div>
    <div class="m-footer" style="margin:0 -20px;">
        <button type="button" class="btn btn-ghost" onclick="$('#generateModal').dialog('close')">Cancel</button>
        <button type="button" class="btn btn-mod-solid" id="doGenerate">↗ Generate &amp; Print</button>
    </div>
</form>
</div>

<script>
const CV_TYPE  = <?= json_encode($type) ?>;
const CV_API   = 'api/care_visits_api.php';
const CONS_API = '../consultation/api/list_by_type.php';

let selId = null, selName = '', selBirthdate = '', selGender = '';
let allVisits = [], vFilter = 'all';
let nipData = null;
let genScope = 'patient';

$(function () {
    $('body').show();

    /* ── Escape ── */
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = String(s ?? '');
        return d.innerHTML;
    }
    function or(v, fb = '—') { return (v === null || v === undefined || v === '' || v === 'null') ? fb : v; }
    function fmt(d) {
        if (!d) return '—';
        return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function time12(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hr = parseInt(h), sfx = hr >= 12 ? 'PM' : 'AM';
        return (hr % 12 || 12) + ':' + m + ' ' + sfx;
    }
    function calcAge(bd) {
        if (!bd) return '';
        const b = new Date(bd + 'T00:00:00'), n = new Date();
        let y = n.getFullYear() - b.getFullYear();
        const m = n.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && n.getDate() < b.getDate())) y--;
        return y === 0 ? (n.getMonth() - b.getMonth() + (n.getFullYear() - b.getFullYear()) * 12) + 'mo old' : y + ' yrs old';
    }
    function showToast(msg, type = 'ok') {
        const col = type === 'ok' ? 'var(--ok)' : 'var(--danger)';
        const t = $(`<div style="
            position:fixed;bottom:28px;right:28px;z-index:9999;
            padding:11px 18px;border-radius:var(--r-md);
            background:#fff;border:1.5px solid ${col};color:var(--ink-1);
            font-size:12.5px;font-weight:600;box-shadow:var(--sh-3);
            display:flex;align-items:center;gap:8px;
            animation:slideIn .2s ease;
        "><span style="color:${col};font-size:15px;">${type === 'ok' ? '✓' : '⚠'}</span>${esc(msg)}</div>`);
        $('body').append(t);
        setTimeout(() => t.fadeOut(300, () => t.remove()), 3000);
    }
    $('<style>@keyframes slideIn{from{transform:translateX(20px);opacity:0}to{transform:none;opacity:1}}</style>').appendTo('head');

    /* ── Status badge ── */
    function statusBadge(s) {
        if (!s) return '';
        const map = { Completed: 'b-completed', Ongoing: 'b-ongoing', 'Follow-up': 'b-followup', Dismissed: 'b-dismissed' };
        return `<span class="badge ${map[s] || 'b-dismissed'}">${esc(s)}</span>`;
    }

    /* ════════════════════════════════
       LOAD PATIENT LIST
    ════════════════════════════════ */
    function loadPatients() {
        const from = getCurFrom();
        const to = getCurTo();
        const p1 = $.getJSON(CONS_API, { type: CV_TYPE, from, to });
        const p2 = $.getJSON(CV_API, { action: 'list', type: CV_TYPE, from: from, to: to, resident_id: 0 });

        $.when(
            p1.then(r => r.status === 'ok' ? r.data : [], () => []),
            p2.then(r => r.status === 'ok' ? (r.data.data || []) : [], () => [])
        ).done(function (cons, cvs) {
            // Merge by resident_id
            const map = {};
            cons.forEach(c => {
                const id = c.resident_id;
                if (!map[id]) map[id] = { id, name: c.resident_name, birthdate: '', gender: '', lastVisit: '', cnt: 0 };
                map[id].cnt++;
                if ((c.consultation_date || '') > (map[id].lastVisit || '')) map[id].lastVisit = c.consultation_date || '';
            });
            cvs.forEach(c => {
                const id = c.resident_id;
                if (!map[id]) map[id] = { id, name: c.resident_name || '', birthdate: '', gender: '', lastVisit: '', cnt: 0 };
                map[id].cnt++;
                if ((c.visit_date || '') > (map[id].lastVisit || '')) map[id].lastVisit = c.visit_date || '';
            });

            // Also fetch resident details for those we found
            const ids = Object.keys(map);
            if (!ids.length) {
                renderPatients([]);
                return;
            }

            // Batch fetch resident details (limit to avoid huge requests)
            const batchIds = ids.slice(0, 200);
            const qs = batchIds.map(i => `ids[]=${i}`).join('&');
            $.getJSON(`../resident/residents_batch.php?${qs}`).always(function (resData) {
                if (resData && resData.residents) {
                    resData.residents.forEach(r => {
                        if (map[r.id]) {
                            map[r.id].birthdate = r.birthdate || '';
                            map[r.id].gender = r.gender || '';
                            if (!map[r.id].name) {
                                map[r.id].name = [r.first_name, r.middle_name, r.last_name, r.suffix].filter(Boolean).join(' ');
                            }
                        }
                    });
                }
                const list = Object.values(map).sort((a, b) => b.lastVisit.localeCompare(a.lastVisit));
                renderPatients(list);
            });
        });
    }

    function getCurFrom() {
        const chip = $('.filter-chip.active').data('filter');
        const n = new Date();
        if (chip === 'this_month') return n.getFullYear() + '-' + String(n.getMonth() + 1).padStart(2, '0') + '-01';
        if (chip === 'recent') {
            const d = new Date(n); d.setDate(d.getDate() - 30);
            return d.toISOString().slice(0, 10);
        }
        return '2000-01-01';
    }
    function getCurTo() { return new Date().toISOString().slice(0, 10); }

    function renderPatients(list) {
        const q = $('#anySearch').val().toLowerCase().trim();
        let filtered = list;
        if (q) filtered = list.filter(p => (p.name || '').toLowerCase().includes(q));
        const cnt = filtered.length;
        $('#ptCountLabel').text(cnt + ' patient' + (cnt !== 1 ? 's' : ''));
        if (!filtered.length) {
            $('#ptList').html(`<div class="pt-empty">No ${esc(CV_TYPE.replace(/_/g, ' '))} patients found.</div>`);
            return;
        }
        let html = '';
        filtered.forEach(p => {
            const ageStr = p.birthdate ? calcAge(p.birthdate).replace(' old', '') : '';
            const parts = (p.name || '').trim().split(' ');
            const init = (parts[0] ? parts[0][0] : '?') + (parts.length > 1 ? parts[parts.length - 1][0] : '');
            html += `<div class="pt-item${selId == p.id ? ' active' : ''}"
                data-id="${p.id}" data-name="${esc(p.name)}"
                data-bd="${esc(p.birthdate)}" data-gender="${esc(p.gender)}">
                <div class="pt-avatar">${esc(init.toUpperCase())}</div>
                <div class="pt-info">
                    <div class="pt-name">${esc(p.name || '—')}</div>
                    <div class="pt-meta">
                        ${ageStr ? `<span class="pt-age-chip">${esc(ageStr)}</span>` : ''}
                        ${esc(p.gender || '')}
                    </div>
                </div>
                <div class="pt-right">
                    <div class="pt-last-visit">${p.lastVisit ? fmt(p.lastVisit) : 'No visits'}</div>
                    <div class="pt-count">${p.cnt} visit${p.cnt !== 1 ? 's' : ''}</div>
                </div>
            </div>`;
        });
        $('#ptList').html(html);
        $('#hdrSelected').text(cnt);
    }

    // Patient click
    $(document).on('click', '.pt-item', function () {
        const id = $(this).data('id');
        selId = id;
        selName = $(this).data('name');
        selBirthdate = $(this).data('bd');
        selGender = $(this).data('gender');
        $('.pt-item').removeClass('active');
        $(this).addClass('active');
        openPatient();
    });

    // Filter chips
    $(document).on('click', '.filter-chip', function () {
        $('.filter-chip').removeClass('active');
        $(this).addClass('active');
        loadPatients();
    });

    // Search
    let searchTimer;
    $('#anySearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadPatients, 300);
    });

    // Autocomplete (any resident)
    $('#anySearch').autocomplete({
        minLength: 1, appendTo: 'body',
        source: (req, res) => $.getJSON('../consultation/api/resident.php', { term: req.term }, res),
        select(e, ui) {
            e.preventDefault();
            $('#anySearch').val(ui.item.label);
            $('#anyId').val(ui.item.id);
            selId = ui.item.id;
            selName = ui.item.label;
            selBirthdate = '';
            selGender = '';
            openPatient();
            return false;
        }
    });

    function openPatient() {
        $('#emptyState').hide();
        const pd = $('#patientDetail');
        pd.css('display', 'flex').show();
        $('#btnNewVisit').prop('disabled', false);

        $('#selName').text(selName || '—');
        const ageStr = selBirthdate ? calcAge(selBirthdate) : '';
        $('#selAge').text(ageStr);
        $('#selGender').text(selGender || '');

        $('#vDetailPanel').removeClass('open');
        allVisits = [];
        $('#vList').html('<div style="padding:16px;text-align:center;color:var(--ink-3);font-size:12px;">Loading…</div>');

        if (CV_TYPE === 'immunization') loadNIPCard(selId);
        if (CV_TYPE === 'maternal')     loadMaternalProfile(selId);
        loadVisits(selId);
    }

    /* ════════════════════════════════
       NIP CARD
    ════════════════════════════════ */
    function loadNIPCard(rid) {
        $('#nipGrid').html('<div style="grid-column:1/-1;padding:16px;text-align:center;color:var(--ink-3);font-size:12px;">Loading NIP card…</div>');
        $.getJSON(CV_API, { action: 'immunization_card', resident_id: rid }, function (res) {
            if (res.status !== 'ok') return;
            nipData = res.data;
            renderNIPCard(nipData);
        });
    }

    function renderNIPCard(data) {
        const schedule = data.schedule || [];
        const given    = data.given_count || 0;
        const total    = data.total || 1;
        const pct      = Math.round(given / total * 100);

        $('#nipStats').text(`${given} / ${total} doses given`);
        $('#nipProgBar').css('width', pct + '%');
        $('#nipProgLabel').text(pct + '%');

        let html = '';
        schedule.forEach(slot => {
            const isGiven   = slot.given !== null;
            const isOverdue = !isGiven && slot.is_overdue;
            const dueDate   = slot.due_date;
            const isSoon    = !isGiven && !isOverdue && dueDate && (() => {
                const days = (new Date(dueDate) - new Date()) / 864e5;
                return days >= 0 && days <= 30;
            })();
            const isCatchup = isGiven && slot.given.catch_up == 1;

            let cls = '';
            if (isGiven)   cls = 'given';
            else if (isOverdue) cls = 'overdue';
            else if (isSoon)    cls = 'pending-soon';

            let status = 'Pending', statusCls = 'pending';
            if (isGiven)        { status = 'Given';   statusCls = 'given'; }
            else if (isOverdue) { status = 'Overdue'; statusCls = 'overdue'; }
            else if (isSoon)    { status = 'Due Soon'; statusCls = 'soon'; }

            const dateDisplay = isGiven ? fmt(slot.given.date_given) : (dueDate ? `Due: ${fmt(dueDate)}` : '');

            html += `<div class="nip-slot ${cls}${isCatchup?' catch-up':''}"
                data-slot='${JSON.stringify({
                    schedule_id: slot.id,
                    vaccine_name: slot.vaccine_name,
                    dose_label: slot.dose_label,
                    target_age_label: slot.target_age_label,
                    route: slot.route,
                    site: slot.site,
                    due_date: dueDate,
                    given: slot.given
                }).replace(/'/g, "&apos;")}'>
                <span class="nip-edit-icon">✏</span>
                <div class="ns-vaccine">${esc(slot.vaccine_name)}</div>
                <div class="ns-dose">${esc(slot.dose_label)}</div>
                <div class="ns-age">${esc(slot.target_age_label || '')}</div>
                <div class="ns-status-row">
                    <span class="ns-status ${statusCls}">${esc(status)}</span>
                    ${dateDisplay ? `<span class="ns-date">${esc(dateDisplay)}</span>` : ''}
                </div>
            </div>`;
        });

        $('#nipGrid').html(html || '<div style="grid-column:1/-1;padding:20px;text-align:center;color:var(--ink-3);">No NIP schedule configured.</div>');

        // Custom vaccines
        const customs = (nipData.all_given || []).filter(g => !g.schedule_id);
        if (customs.length) {
            let cHtml = '';
            customs.forEach(g => {
                cHtml += `<div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--line-3);">
                    <span style="font-weight:600;">${esc(g.vaccine_name)} ${g.dose ? '— ' + g.dose : ''}</span>
                    <span style="font-family:var(--f-mono);font-size:10px;color:var(--ink-3);">${fmt(g.date_given)}</span>
                </div>`;
            });
            $('#customImmList').html(cHtml);
            $('#customImmCard').show();
            $('#customImmCount').text(customs.length + ' vaccine' + (customs.length !== 1 ? 's' : ''));
        } else {
            $('#customImmCard').hide();
        }
    }

    // Click NIP slot → open edit modal
    $(document).on('click', '.nip-slot', function () {
        const raw = $(this).attr('data-slot').replace(/&apos;/g, "'");
        let slot;
        try { slot = JSON.parse(raw); } catch (e) { return; }

        const given = slot.given;
        const today = new Date().toISOString().slice(0, 10);

        $('#ne_sched_id').val(slot.schedule_id || '');
        $('#ne_rid').val(selId);
        $('#ne_vaccine').val(slot.vaccine_name);
        $('#ne_dose_label').val(slot.dose_label);
        $('#ne_vaccine_display').text(slot.vaccine_name);
        $('#ne_dose_display').text(slot.dose_label);
        $('#ne_target_age_display').text(slot.target_age_label ? 'Recommended: ' + slot.target_age_label : '');
        $('#ne_route').val(slot.route || 'IM');
        $('#ne_site').val(slot.site || '');

        if (given) {
            $('#ne_imm_id').val(given.id);
            $('#ne_date').val(given.date_given || today);
            $('#ne_next').val(given.next_schedule || '');
            $('#ne_admin').val(given.administered_by || '<?= htmlspecialchars($_SESSION['name'] ?? '') ?>');
            $('#ne_batch').val(given.batch_number || '');
            $('#ne_lot').val(given.lot_number || '');
            $('#ne_expiry').val(given.expiry_date || '');
            $('#ne_vvm').val(given.vvm_status || 'OK');
            $('#ne_temp').val(given.temperature_at_vaccination || '');
            $('#ne_facility').val(given.given_at_facility || '');
            $('#ne_adverse').val(given.adverse_reaction || '');
            $('#ne_remarks').val(given.remarks || '');
            $('#ne_defaulter').prop('checked', given.is_defaulter == 1);
            $('#ne_catchup').prop('checked', given.catch_up == 1);
            $('#ne_route').val(given.route || slot.route || 'IM');
            $('#ne_site').val(given.site_given || slot.site || '');
            $('#ne_submit').text('Update Record');
            $('#ne_undo_wrap').show();
        } else {
            $('#ne_imm_id').val('');
            $('#ne_date').val(slot.due_date && slot.due_date >= today ? slot.due_date : today);
            $('#ne_next').val('');
            $('#ne_admin').val('<?= htmlspecialchars($_SESSION['name'] ?? '') ?>');
            $('#ne_batch').val(''); $('#ne_lot').val(''); $('#ne_expiry').val('');
            $('#ne_vvm').val('OK'); $('#ne_temp').val(''); $('#ne_facility').val('');
            $('#ne_adverse').val(''); $('#ne_remarks').val('');
            $('#ne_defaulter').prop('checked', false); $('#ne_catchup').prop('checked', false);
            $('#ne_submit').text('Save Vaccine Record');
            $('#ne_undo_wrap').hide();
        }

        const title = given ? `Edit — ${slot.vaccine_name} ${slot.dose_label}` : `Record — ${slot.vaccine_name} ${slot.dose_label}`;
        $('#nipEditModal').dialog('option', 'title', title).dialog('open');
    });

    // NIP Edit form submit
    $('#nipEditForm').on('submit', function (e) {
        e.preventDefault();
        const immId = $('#ne_imm_id').val();
        const rid = parseInt($('#ne_rid').val());
        if (!rid) return;

        const data = {};
        $(this).serializeArray().forEach(f => data[f.name] = f.value);
        // also include checkbox values
        data.is_defaulter = $('#ne_defaulter').prop('checked') ? 1 : 0;
        data.catch_up     = $('#ne_catchup').prop('checked') ? 1 : 0;
        data.care_visit_id = 0; // no linked care_visit for direct NIP entries
        data.resident_id   = rid;

        const url = immId
            ? `api/nip_update.php?id=${encodeURIComponent(immId)}`
            : `api/nip_update.php`;

        const $btn = $('#ne_submit');
        $btn.prop('disabled', true).text('Saving…');

        $.post(url, data, function (res) {
            const r = typeof res === 'string' ? JSON.parse(res) : res;
            if (r.status !== 'ok') { showToast(r.message || 'Save failed.', 'err'); return; }
            $('#nipEditModal').dialog('close');
            showToast(immId ? 'Record updated.' : 'Vaccine recorded.', 'ok');
            loadNIPCard(selId);
        }).fail(() => showToast('Request failed.', 'err'))
          .always(() => $btn.prop('disabled', false).text(immId ? 'Update Record' : 'Save Vaccine Record'));
    });

    // Remove vaccine record
    $('#ne_undo_btn').on('click', function () {
        const immId = $('#ne_imm_id').val();
        if (!immId) return;
        if (!confirm('Remove this vaccination record? This cannot be undone.')) return;
        $.post(`api/nip_update.php?action=delete&id=${encodeURIComponent(immId)}`, {}, function (res) {
            const r = typeof res === 'string' ? JSON.parse(res) : res;
            if (r.status !== 'ok') { showToast(r.message || 'Delete failed.', 'err'); return; }
            $('#nipEditModal').dialog('close');
            showToast('Record removed.', 'ok');
            loadNIPCard(selId);
        });
    });

    // Custom vaccine modal
    $('#btnAddCustomImm').on('click', function () {
        $('#ci_rid').val(selId);
        $('#customImmForm')[0].reset();
        $('#ci_rid').val(selId);
        $('#ci_date').val(new Date().toISOString().slice(0, 10));
        $('#customImmModal').dialog('open');
    });
    $('#customImmForm').on('submit', function (e) {
        e.preventDefault();
        const data = {};
        $(this).serializeArray().forEach(f => data[f.name] = f.value);
        data.care_visit_id = 0;
        $.post(`api/nip_update.php`, data, function (res) {
            const r = typeof res === 'string' ? JSON.parse(res) : res;
            if (r.status !== 'ok') { showToast(r.message || 'Save failed.', 'err'); return; }
            $('#customImmModal').dialog('close');
            showToast('Vaccine recorded.', 'ok');
            loadNIPCard(selId);
        });
    });

    /* ════════════════════════════════
       MATERNAL PROFILE
    ════════════════════════════════ */
    function loadMaternalProfile(rid) {
        $.getJSON(CV_API, { action: 'maternal_profile', resident_id: rid }, function (res) {
            const p = res.data?.profile;
            if (!p) { $('#mpDisplay').html('<div style="color:var(--ink-3);font-size:12px;font-style:italic;">No profile recorded yet.</div>'); return; }
            const keys = ['gravida', 'term', 'preterm', 'abortions', 'living_children'];
            const labels = ['G', 'T', 'P', 'A', 'L'];
            let gtpalHtml = '<div class="gtpal-row">';
            keys.forEach((k, i) => {
                gtpalHtml += `<div class="gtpal-cell"><div class="gtpal-val">${or(p[k], 0)}</div><div class="gtpal-lbl">${labels[i]}</div></div>`;
            });
            gtpalHtml += '</div>';

            const flags = [
                ['hx_pre_eclampsia', 'Pre-eclampsia'], ['hx_pph', 'PPH'],
                ['hx_cesarean', 'C-Section'], ['hx_ectopic', 'Ectopic'],
                ['hx_stillbirth', 'Stillbirth'], ['has_diabetes', 'Diabetes'],
                ['has_hypertension', 'Hypertension'], ['has_hiv', 'HIV'], ['has_anemia', 'Anemia'],
            ].filter(([k]) => p[k] == 1).map(([, l]) => `<span class="flag-chip">${esc(l)}</span>`).join('');

            let html = gtpalHtml;
            if (p.blood_type && p.blood_type !== 'Unknown') {
                html += `<div style="margin-bottom:8px;font-size:12px;color:var(--ink-2);">Blood type: <strong>${esc(p.blood_type)}</strong></div>`;
            }
            if (flags) html += `<div class="flag-list">${flags}</div>`;
            if (p.notes) html += `<div style="margin-top:8px;font-size:11px;color:var(--ink-3);font-style:italic;">${esc(p.notes)}</div>`;

            $('#mpDisplay').html(html);

            // Pre-fill form
            ['gravida', 'term', 'preterm', 'abortions', 'living_children',
             'hx_pre_eclampsia', 'hx_pph', 'hx_cesarean', 'hx_ectopic', 'hx_stillbirth',
             'has_diabetes', 'has_hypertension', 'has_hiv', 'has_anemia',
             'blood_type', 'other_conditions', 'notes'].forEach(k => {
                const el = $(`#mpForm [name="${k}"]`);
                if (!el.length) return;
                if (el.attr('type') === 'checkbox') el.prop('checked', p[k] == 1);
                else el.val(p[k] || '');
            });
            $('#mp_rid').val(rid);
        });
    }

    $('#mpModal').dialog({ autoOpen: false, modal: true, width: 640, resizable: false });
    $('#btnEditMp').on('click', () => { $('#mp_rid').val(selId); $('#mpModal').dialog('open'); });
    $('#mpForm').on('submit', function (e) {
        e.preventDefault();
        $.post(CV_API + '?action=save_maternal_profile', $(this).serialize(), function (res) {
            const r = typeof res === 'string' ? JSON.parse(res) : res;
            if (r.status !== 'ok') { showToast(r.message || 'Failed.', 'err'); return; }
            $('#mpModal').dialog('close');
            showToast('Profile saved.', 'ok');
            loadMaternalProfile(selId);
        });
    });

    /* ════════════════════════════════
       VISIT HISTORY
    ════════════════════════════════ */
    function loadVisits(rid) {
        const from = '2000-01-01', to = new Date().toISOString().slice(0, 10);
        const p1 = $.getJSON(CONS_API, { type: CV_TYPE, resident_id: rid, from, to });
        const p2 = $.getJSON(CV_API, { action: 'list', type: CV_TYPE, resident_id: rid, from, to });

        $.when(
            p1.then(r => r.status === 'ok' ? r.data : [], () => []),
            p2.then(r => r.status === 'ok' ? (r.data?.data || []) : [], () => [])
        ).done(function (cons, cvs) {
            const merged = [
                ...cons.map(c => ({ ...c, _src: 'consult', _date: c.consultation_date || '' })),
                ...cvs.map(c => ({ ...c, _src: 'care', _date: c.visit_date || '' }))
            ].sort((a, b) => b._date.localeCompare(a._date));

            allVisits = merged;
            $('#vCount').text(merged.length + (merged.length === 1 ? ' record' : ' records'));
            renderVisitList();
        });
    }

    function renderVisitList() {
        const src = vFilter;
        let rows = allVisits;
        if (src === 'consult') rows = allVisits.filter(r => r._src === 'consult');
        if (src === 'care')    rows = allVisits.filter(r => r._src === 'care');

        if (!rows.length) {
            $('#vList').html('<div style="padding:20px;text-align:center;color:var(--ink-3);font-size:12px;font-style:italic;">No visits recorded.</div>');
            return;
        }
        let html = '';
        rows.forEach(r => {
            const date = r._date;
            const status = r.consult_status || '';
            const srcBadge = r._src === 'consult'
                ? '<span class="badge b-consult">Consult</span>'
                : '<span class="badge b-care">Care</span>';
            const summary = r.complaint || r.notes || '';

            html += `<div class="v-item" data-id="${r.id}" data-src="${r._src}">
                <div class="v-item-date">${esc(fmt(date))}</div>
                <div class="v-item-summary">${esc((summary || '—').substring(0, 70))}</div>
                <div class="v-item-badges">${statusBadge(status)}${srcBadge}</div>
            </div>`;
        });
        $('#vList').html(html);
    }

    $(document).on('click', '.v-tab', function () {
        $('.v-tab').removeClass('active'); $(this).addClass('active');
        vFilter = $(this).data('vsrc');
        renderVisitList();
    });

    $(document).on('click', '.v-item', function () {
        $('.v-item').removeClass('active'); $(this).addClass('active');
        const id = $(this).data('id'), src = $(this).data('src');
        if (src === 'consult') showConsultDetail(id);
        else showCareDetail(id);
    });

    /* ─ Consult detail ─ */
    function showConsultDetail(id) {
        $.getJSON('../consultation/api/view.php', { id }, function (res) {
            if (!res.success) return;
            const d = res.data;
            $('#vdTitle').text('Consultation — ' + esc(d.consultation_date || ''));
            $('#btnEditVd').data({ id, src: 'consult' });

            let html = '';
            // Status banner
            html += `<div style="padding:8px 0 12px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;border-bottom:1px solid var(--line-2);margin-bottom:12px;">
                ${d.consult_status ? statusBadge(d.consult_status) : ''}
                ${d.health_worker ? `<span style="font-size:11px;color:var(--ink-3);">by ${esc(d.health_worker)}</span>` : ''}
                ${d.follow_up_date ? `<span style="font-family:var(--f-mono);font-size:10px;color:var(--ink-3);margin-left:auto;">Follow-up: ${esc(d.follow_up_date)}</span>` : ''}
            </div>`;

            // Vitals
            const vitals = [
                ['Temp', d.temp_celsius ? d.temp_celsius + '°C' : null],
                ['BP', d.bp_systolic ? d.bp_systolic + '/' + d.bp_diastolic : null],
                ['Pulse', d.pulse_rate ? d.pulse_rate + ' bpm' : null],
                ['Weight', d.weight_kg ? d.weight_kg + ' kg' : null],
                ['Height', d.height_cm ? d.height_cm + ' cm' : null],
                ['BMI', d.bmi ? d.bmi + (d.bmi_class ? ' ('+d.bmi_class+')' : '') : null],
            ].filter(([,v]) => v);
            if (vitals.length) {
                html += `<div class="vd-section"><div class="vd-sec-title">Vitals</div><div class="vitals-strip">`;
                vitals.forEach(([l,v]) => { html += `<div class="vital-pill"><div class="vital-pill-lbl">${esc(l)}</div><div class="vital-pill-val">${esc(v)}</div></div>`; });
                html += `</div></div>`;
            }

            function vf(l, v, full = false) {
                if (!v) return '';
                return `<div class="vd-field${full ? ' style="grid-column:1/-1"' : ''}"><div class="vd-lbl">${esc(l)}</div><div class="vd-val">${esc(String(v))}</div></div>`;
            }

            html += `<div class="vd-section"><div class="vd-sec-title">Clinical</div><div class="vd-grid">
                <div>${vf('Chief Complaint', d.chief_complaint || d.complaint)}${vf('Diagnosis', d.primary_diagnosis || d.diagnosis)}${vf('Treatment', d.treatment)}</div>
                <div>${vf('Health Advice', d.health_advice)}${vf('Assessment', d.assessment)}${vf('Plan', d.plan)}</div>
            </div></div>`;

            $('#vdBody').html(html);
            $('#vDetailPanel').addClass('open');
        });
    }

    /* ─ Care visit detail ─ */
    function showCareDetail(id) {
        $.getJSON(CV_API, { action: 'get', type: CV_TYPE, id }, function (res) {
            if (res.status !== 'ok') return;
            const d = res.data;
            $('#vdTitle').text('Care Visit — ' + esc(d.visit_date || ''));
            $('#btnEditVd').data({ id, src: 'care' });

            let html = `<div style="padding:0 0 10px;border-bottom:1px solid var(--line-2);margin-bottom:12px;display:flex;gap:8px;align-items:center;">
                <span style="font-family:var(--f-mono);font-size:10px;color:var(--mod);font-weight:700;">${esc(d.visit_date || '')}</span>
                ${d.health_worker ? `<span style="font-size:11px;color:var(--ink-3);">by ${esc(d.health_worker)}</span>` : ''}
            </div>`;

            if (d.notes) html += `<div class="vd-section"><div class="vd-val">${esc(d.notes)}</div></div>`;

            // Type-specific
            if (CV_TYPE === 'immunization' && d.vaccine_name) {
                html += `<div class="vd-section"><div class="vd-sec-title">Immunization</div><div class="vd-grid">
                    <div>
                        <div class="vd-field"><div class="vd-lbl">Vaccine</div><div class="vd-val">${esc(d.vaccine_name)}</div></div>
                        <div class="vd-field"><div class="vd-lbl">Dose</div><div class="vd-val">${esc(or(d.dose))}</div></div>
                        <div class="vd-field"><div class="vd-lbl">Date Given</div><div class="vd-val">${esc(or(d.date_given))}</div></div>
                    </div>
                    <div>
                        <div class="vd-field"><div class="vd-lbl">Route / Site</div><div class="vd-val">${esc(or(d.route))} — ${esc(or(d.site_given))}</div></div>
                        <div class="vd-field"><div class="vd-lbl">Next Schedule</div><div class="vd-val">${esc(or(d.next_schedule))}</div></div>
                        <div class="vd-field"><div class="vd-lbl">Administered By</div><div class="vd-val">${esc(or(d.administered_by))}</div></div>
                    </div>
                </div></div>`;
            } else if (CV_TYPE === 'prenatal') {
                html += `<div class="vd-section"><div class="vd-sec-title">Prenatal Details</div><div class="vitals-strip">
                    ${d.aog_weeks ? `<div class="vital-pill"><div class="vital-pill-lbl">AOG</div><div class="vital-pill-val">${esc(d.aog_weeks)}wk</div></div>` : ''}
                    ${d.weight_kg ? `<div class="vital-pill"><div class="vital-pill-lbl">Weight</div><div class="vital-pill-val">${esc(d.weight_kg)}kg</div></div>` : ''}
                    ${d.bp_systolic ? `<div class="vital-pill"><div class="vital-pill-lbl">BP</div><div class="vital-pill-val">${esc(d.bp_systolic)}/${esc(d.bp_diastolic)}</div></div>` : ''}
                    ${d.fetal_heart_rate ? `<div class="vital-pill"><div class="vital-pill-lbl">FHR</div><div class="vital-pill-val">${esc(d.fetal_heart_rate)}bpm</div></div>` : ''}
                    ${d.risk_level ? `<div class="vital-pill"><div class="vital-pill-lbl">Risk</div><div class="vital-pill-val">${esc(d.risk_level)}</div></div>` : ''}
                </div></div>`;
            }

            $('#vdBody').html(html);
            $('#vDetailPanel').addClass('open');
        });
    }

    /* ─ Edit visit ─ */
    $('#editVisitModal').dialog({ autoOpen: false, modal: true, width: 660, resizable: false });
    $('#btnEditVd').on('click', function () {
        const { id, src } = $(this).data();
        if (src === 'consult') { window.location.href = `../consultation/?edit=${id}`; return; }
        $.getJSON(CV_API, { action: 'get', type: CV_TYPE, id }, function (res) {
            if (res.status !== 'ok') return;
            const d = res.data;
            $('#ev_vid').val(d.id); $('#ev_type').val(CV_TYPE);
            $('#ev_date').val(d.visit_date || '');
            $('#ev_worker').val(d.health_worker || d.administered_by || '');
            $('#ev_notes').val(d.notes || '');
            $('#editVisitModal').dialog('option', 'title', 'Edit Visit — ' + esc(d.visit_date || '')).dialog('open');
        });
    });
    $('#editVisitForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: CV_API + '?action=update', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success(res) {
                if (res.status !== 'ok') { showToast(res.message || 'Failed.', 'err'); return; }
                $('#editVisitModal').dialog('close');
                showToast('Visit updated.', 'ok');
                loadVisits(selId);
                $('#vDetailPanel').removeClass('open');
            },
            error() { showToast('Request failed.', 'err'); }
        });
    });

    /* ── New Visit ── */
    $('#btnNewVisit').on('click', () => {
        if (!selId) return;
        window.location.href = `../consultation/?new=1&type=${CV_TYPE}&resident_id=${selId}`;
    });
    $('#btnQuickAddVisit').on('click', () => {
        if (!selId) return;
        window.location.href = `../consultation/?new=1&type=${CV_TYPE}&resident_id=${selId}`;
    });
    $('#btnViewProfile').on('click', () => {
        if (!selId) return;
        window.open(`../resident/view.php?id=${selId}`, '_blank');
    });

    /* ════════════════════════════════
       GENERATE MODAL
    ════════════════════════════════ */
    $('#generateModal').dialog({ autoOpen: false, modal: true, width: 460, resizable: false });
    $('#btnGenerate').on('click', () => {
        if (selId) { genScope = 'patient'; setGenScope(); }
        else       { genScope = 'all'; setGenScope(); }
        $('#generateModal').dialog('open');
    });

    function setGenScope() {
        if (genScope === 'patient' && selId) {
            $('#genScopePatient').addClass('active'); $('#genScopeAll').removeClass('active');
            $('#genPatientInfo').show(); $('#genPatientName').text(selName);
        } else {
            $('#genScopeAll').addClass('active'); $('#genScopePatient').removeClass('active');
            $('#genPatientInfo').hide();
        }
    }
    $('#genScopePatient').on('click', () => { if (!selId) { showToast('Select a patient first.', 'err'); return; } genScope = 'patient'; setGenScope(); });
    $('#genScopeAll').on('click', () => { genScope = 'all'; setGenScope(); });

    $('[data-range]').on('click', function () {
        const r = $(this).data('range');
        const today = new Date(), fmt2 = d => d.toISOString().slice(0, 10);
        let from;
        if (r === 'today')  { from = fmt2(today); }
        else if (r === 'week') { const d = new Date(today); d.setDate(today.getDate()-today.getDay()+1); from = fmt2(d); }
        else if (r === 'month') { from = today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-01'; }
        else { from = today.getFullYear()+'-01-01'; }
        $('#gen_from').val(from); $('#gen_to').val(fmt2(today));
    });

    $('#doGenerate').on('click', () => {
        const from = $('#gen_from').val(), to = $('#gen_to').val();
        if (!from || !to) { showToast('Set date range.', 'err'); return; }
        const rid = genScope === 'patient' && selId ? selId : '';
        const url = `print.php?type=${encodeURIComponent(CV_TYPE)}&resident_id=${encodeURIComponent(rid)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
        window.open(url, '_blank');
        $('#generateModal').dialog('close');
    });

    /* ── Init dialogs ── */
    $('#nipEditModal').dialog({ autoOpen: false, modal: true, width: 640, resizable: false });
    $('#customImmModal').dialog({ autoOpen: false, modal: true, width: 560, resizable: false });

    /* ── BOOT ── */
    loadPatients();
});
</script>
</body>
</html>
<?php
/**
 * Health Records — Main Page
 * Replaces: public/hcnurse/health-records/index.php
 *
 * BUG FIXES:
 * 1. INIT_FILTERS.period defaults to 'all' so all records show on first load
 * 2. Month picker hidden when period != monthly
 * 3. Page title, eyebrow, sub all reflect current type
 * 4. Edit form fields use correct names matching the API
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type = $_GET['type'] ?? 'maternal';
$allowed = ['general','immunization', 'maternal', 'family_planning', 'prenatal', 'postnatal', 'child_nutrition'];
if (!in_array($type, $allowed, true)) $type = 'maternal';

// BUG FIX: default period = 'all' so all records are visible by default
$period = $_GET['period'] ?? 'all';
$month  = $_GET['month']  ?? date('Y-m');
$q      = $_GET['q']      ?? '';
$sub    = $_GET['sub']    ?? 'all';

$pageLabels = [
    'general'        => 'General Health Records',
    'maternal'        => 'Maternal Records',
    'family_planning' => 'Family Planning',
    'prenatal'        => 'Prenatal Care',
    'postnatal'       => 'Postnatal Care',
    'child_nutrition' => 'Child Nutrition',
    'immunization'    => 'Immunization Records',
];
$pageIcons = [
    'general'         => '🩺',
    'maternal'        => '🤱',
    'family_planning' => '💊',
    'prenatal'        => '👶',
    'postnatal'       => '🍼',
    'child_nutrition' => '🥗',
    'immunization'    => '💉',
];
$pageDescs = [
    'general'         => 'General health consultations and visit records',
    'maternal'        => 'Maternal health consultations and visit records',
    'family_planning' => 'Family planning consultations and visit records',
    'prenatal'        => 'Prenatal care consultations and visit records',
    'postnatal'       => 'Postnatal care consultations and visit records',
    'child_nutrition' => 'Child nutrition consultations and visit records',
    'immunization'    => 'Immunization and vaccine records',
];

$pageTitle = $pageLabels[$type] ?? 'Health Records';
$pageIcon  = $pageIcons[$type]  ?? '🩺';
$pageDesc  = $pageDescs[$type]  ?? 'Health program consultations and visit records';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllStyles(); ?>
    <?php loadAllScripts(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --paper:     #fdfcf9; --paper-lt: #f9f7f3;
        --ink:       #1a1a1a; --ink-muted: #5a5a5a; --ink-faint: #a0a0a0;
        --rule:      #d8d4cc; --rule-dk:  #b8b4ac; --bg: #edeae4;
        --accent:    var(--theme-primary, #2d5a27);
        --accent-lt: color-mix(in srgb, var(--accent) 8%, white);
        --ok-bg:     #edfaf3; --ok-fg:     #1a5c35;
        --warn-bg:   #fef9ec; --warn-fg:   #7a5700;
        --danger-bg: #fdeeed; --danger-fg: #7a1f1a;
        --info-bg:   #edf3fa; --info-fg:   #1a3a5c;
        --neu-bg:    #f3f1ec; --neu-fg:    #5a5a5a;
        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 14px rgba(0,0,0,.05);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select, textarea { font-family: var(--f-sans); }
    .hr-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

    /* ── Doc header ── */
    .doc-header { background: var(--paper); border-bottom: 1px solid var(--rule); }
    .doc-header-inner {
        padding: 20px 28px 0;
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 16px; flex-wrap: wrap;
    }
    .doc-eyebrow {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.8px;
        text-transform: uppercase; color: var(--ink-faint);
        display: flex; align-items: center; gap: 8px; margin-bottom: 6px;
    }
    .doc-eyebrow::before { content: ''; width: 18px; height: 2px; background: var(--accent); display: inline-block; }
    .doc-title {
        font-family: var(--f-serif); font-size: 22px; font-weight: 700;
        color: var(--ink); letter-spacing: -.3px; margin-bottom: 3px;
        display: flex; align-items: center; gap: 10px;
    }
    .doc-sub { font-size: 12px; color: var(--ink-faint); font-style: italic; }
    .header-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; padding-bottom: 2px; }

    /* ── Filter bar ── */
    .filter-bar {
        background: var(--paper-lt); border-bottom: 3px solid var(--accent);
        padding: 10px 28px;
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .period-lbl {
        font-size: 8px; font-weight: 700; letter-spacing: 1.4px;
        text-transform: uppercase; color: var(--ink-faint); flex-shrink: 0;
    }
    .pb-btn {
        padding: 5px 12px; border-radius: 2px;
        border: 1.5px solid var(--rule-dk); background: #fff;
        font-size: 10.5px; font-weight: 700; letter-spacing: .3px;
        text-transform: uppercase; color: var(--ink-muted); cursor: pointer;
        transition: all .12s; font-family: var(--f-sans); flex-shrink: 0;
    }
    .pb-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
    .pb-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
    .pb-sep { width: 1px; height: 18px; background: var(--rule); flex-shrink: 0; }
    .pb-month {
        padding: 5px 10px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-size: 12.5px; font-family: var(--f-sans); color: var(--ink);
        background: #fff; outline: none; transition: border-color .14s;
    }
    .pb-month:focus { border-color: var(--accent); }
    .pb-sub {
        padding: 5px 10px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-size: 12px; font-family: var(--f-sans); color: var(--ink);
        background: #fff; outline: none; transition: border-color .14s;
    }
    .pb-search {
        padding: 6px 11px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-size: 12.5px; color: var(--ink); background: #fff; outline: none;
        width: 220px; transition: border-color .14s, box-shadow .14s;
    }
    .pb-search:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .pb-search::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }
    .pb-clear {
        padding: 5px 12px; border-radius: 2px; background: #fff;
        border: 1.5px solid var(--rule-dk); color: var(--ink-muted);
        font-size: 10.5px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase;
        cursor: pointer; font-family: var(--f-sans); transition: all .12s;
    }
    .pb-clear:hover { border-color: var(--danger-fg); color: var(--danger-fg); background: var(--danger-bg); }
    .filter-spacer { flex: 1; }
    .rec-count {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 3px 10px; border-radius: 2px;
        background: var(--accent-lt);
        border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
        font-family: var(--f-mono); font-size: 9.5px; color: var(--accent); letter-spacing: .4px;
    }

    /* ── Table ── */
    .hr-table-wrap {
        margin: 22px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule); border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .hr-table-wrap .dataTables_wrapper .dataTables_filter,
    .hr-table-wrap .dataTables_wrapper .dataTables_length { display: none; }
    .hr-table-wrap .dataTables_info {
        padding: 10px 18px; font-size: 11px; color: var(--ink-faint);
        font-family: var(--f-mono); border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .hr-table-wrap .dataTables_paginate {
        padding: 10px 18px; border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .hr-table-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important; border-radius: 2px;
        font-size: 11px; font-weight: 600;
        color: var(--ink-muted) !important; background: #fff !important;
        cursor: pointer; margin: 0 2px; transition: all .13s;
    }
    .hr-table-wrap .paginate_button:hover   { border-color: var(--accent) !important; color: var(--accent) !important; background: var(--accent-lt) !important; }
    .hr-table-wrap .paginate_button.current { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
    .hr-table-wrap .paginate_button.disabled { opacity: .35 !important; }

    #hrTable { width: 100% !important; border-collapse: collapse; }
    #hrTable thead th {
        padding: 10px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        cursor: pointer; user-select: none;
    }
    #hrTable thead th:hover { color: var(--accent); }
    #hrTable thead th.sorting_asc::after  { content: ' ↑'; color: var(--accent); }
    #hrTable thead th.sorting_desc::after { content: ' ↓'; color: var(--accent); }
    #hrTable tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    #hrTable tbody tr:last-child { border-bottom: none; }
    #hrTable tbody tr:hover { background: var(--accent-lt); }
    #hrTable td { padding: 10px 14px; font-size: 12.5px; color: var(--ink); vertical-align: middle; }

    .td-date    { font-family: var(--f-mono); font-size: 11.5px; color: var(--ink-muted); white-space: nowrap; }
    .td-patient { font-weight: 600; font-size: 13px; }
    .td-trunc   { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 12px; color: var(--ink-muted); }

    .sub-badge {
        display: inline-block; padding: 2px 8px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        background: var(--neu-bg); color: var(--neu-fg); border: 1px solid var(--rule);
    }
    .status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid;
    }
    .status-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    .sb-completed { background: var(--ok-bg);   color: var(--ok-fg);   border-color: color-mix(in srgb, var(--ok-fg) 25%, transparent); }
    .sb-ongoing   { background: var(--warn-bg);  color: var(--warn-fg); border-color: color-mix(in srgb, var(--warn-fg) 25%, transparent); }
    .sb-dismissed { background: var(--neu-bg);   color: var(--neu-fg);  border-color: var(--rule); }
    .sb-followup  { background: var(--info-bg);  color: var(--info-fg); border-color: color-mix(in srgb, var(--info-fg) 25%, transparent); }

    .td-actions { display: flex; gap: 5px; }
    .act-btn {
        display: inline-flex; align-items: center;
        padding: 4px 10px; border-radius: 2px; font-size: 9.5px;
        font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid var(--rule-dk);
        font-family: var(--f-sans); transition: all .13s;
        background: #fff; color: var(--ink-muted); white-space: nowrap;
    }
    .act-view:hover { border-color: var(--info-fg); color: var(--info-fg); background: var(--info-bg); }
    .act-edit:hover { border-color: var(--accent);  color: var(--accent);  background: var(--accent-lt); }

    .empty-state {
        padding: 56px 24px; text-align: center; color: var(--ink-faint);
    }
    .empty-icon { font-size: 36px; margin-bottom: 12px; opacity: .4; }
    .empty-text { font-size: 13px; font-style: italic; }

    /* ── Dialog overrides ── */
    .ui-dialog { border: 1px solid var(--rule-dk) !important; border-radius: 2px !important; box-shadow: 0 8px 48px rgba(0,0,0,.18) !important; padding: 0 !important; font-family: var(--f-sans) !important; }
    .ui-dialog-titlebar { background: var(--accent) !important; border: none !important; padding: 12px 16px !important; }
    .ui-dialog-title { font-family: var(--f-sans) !important; font-size: 11px !important; font-weight: 700 !important; letter-spacing: 1px !important; text-transform: uppercase !important; color: #fff !important; }
    .ui-dialog-titlebar-close { background: rgba(255,255,255,.15) !important; border: 1px solid rgba(255,255,255,.25) !important; border-radius: 2px !important; color: #fff !important; width: 24px !important; height: 24px !important; top: 50% !important; transform: translateY(-50%) !important; }
    .ui-dialog-content { padding: 0 !important; }
    .ui-dialog-buttonpane { background: var(--paper-lt) !important; border-top: 1px solid var(--rule) !important; padding: 12px 16px !important; margin: 0 !important; }
    .ui-dialog-buttonpane .ui-button { font-family: var(--f-sans) !important; font-size: 11px !important; font-weight: 700 !important; letter-spacing: .5px !important; text-transform: uppercase !important; padding: 7px 18px !important; border-radius: 2px !important; cursor: pointer !important; }
    .ui-dialog-buttonpane .ui-button:first-child { background: var(--accent) !important; border: 1.5px solid var(--accent) !important; color: #fff !important; }
    .ui-dialog-buttonpane .ui-button:not(:first-child) { background: #fff !important; border: 1.5px solid var(--rule-dk) !important; color: var(--ink-muted) !important; }

    /* ── View modal ── */
    .view-header { padding: 16px 22px; background: var(--accent-lt); border-bottom: 1px solid var(--rule); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
    .view-header-name { font-family: var(--f-serif); font-size: 16px; font-weight: 600; color: var(--ink); }
    .view-header-sub  { font-size: 11px; color: var(--ink-faint); margin-top: 3px; }
    .view-body  { padding: 18px 22px; }
    .view-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .view-section-title { font-size: 8px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: var(--ink-faint); display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
    .view-section-title::after { content: ''; flex: 1; height: 1px; background: var(--rule); }
    .view-row { margin-bottom: 10px; }
    .view-lbl { font-size: 8.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 2px; }
    .view-val  { font-size: 13px; font-weight: 500; color: var(--ink); line-height: 1.6; white-space: pre-line; }
    .view-val-mono { font-family: var(--f-mono); font-size: 12px; color: var(--ink-muted); }

    /* ── Edit modal ── */
    .modal-scroll  { max-height: 68vh; overflow-y: auto; }
    .form-section  { padding: 14px 18px 0; border-top: 1px solid var(--rule); }
    .form-section:first-child { border-top: none; padding-top: 18px; }
    .form-section-lbl { font-size: 8px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .form-section-lbl::after { content: ''; flex: 1; height: 1px; background: var(--rule); }
    .form-section-body { padding-bottom: 14px; }
    .fg { margin-bottom: 12px; }
    .fg-label { display: block; font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--ink-muted); margin-bottom: 5px; }
    .fg-label .req { color: var(--danger-fg); }
    .fg-input, .fg-select, .fg-textarea {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13px; color: var(--ink);
        background: #fff; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .fg-input:focus, .fg-select:focus, .fg-textarea:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .fg-input::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }
    .fg-textarea { resize: vertical; min-height: 72px; }
    .fg-input:disabled { background: var(--paper-lt); color: var(--ink-muted); cursor: not-allowed; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto hr-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Health Center · Care Records</div>
                        <div class="doc-title">
                            <span><?= $pageIcon ?></span>
                            <?= htmlspecialchars($pageTitle) ?>
                        </div>
                        <div class="doc-sub"><?= htmlspecialchars($pageDesc) ?></div>
                    </div>
                    <div class="header-actions">
                        <span class="rec-count" id="recCountBadge">— RECORDS</span>
                        <button class="pb-btn" id="btnPrint" style="display:inline-flex;align-items:center;gap:5px;">↗ Print</button>
                    </div>
                </div>
            </div>

            <!-- ── Filter bar ── -->
            <div class="filter-bar">
                <span class="period-lbl">Period</span>
                <!-- BUG FIX: default active = 'all' -->
                <button class="pb-btn <?= $period === 'all'     ? 'active' : '' ?>" data-period="all">All Time</button>
                <button class="pb-btn <?= $period === 'daily'   ? 'active' : '' ?>" data-period="daily">Today</button>
                <button class="pb-btn <?= $period === 'weekly'  ? 'active' : '' ?>" data-period="weekly">This Week</button>
                <button class="pb-btn <?= $period === 'monthly' ? 'active' : '' ?>" data-period="monthly">Monthly</button>
                <input  type="month" class="pb-month" id="monthPicker"
                        value="<?= htmlspecialchars($month) ?>"
                        style="<?= $period !== 'monthly' ? 'opacity:.45;' : '' ?>">
                <div class="pb-sep"></div>
                <select class="pb-sub" id="subTypeSelect">
                    <!-- populated by JS from DB -->
                </select>
                <div class="pb-sep"></div>
                <input type="text" class="pb-search" id="searchInput"
                       placeholder="Search patient name…"
                       value="<?= htmlspecialchars($q) ?>">
                <button class="pb-clear" id="btnClear">✕ Clear</button>
            </div>

            <!-- ── Table ── -->
            <div class="hr-table-wrap">
                <div id="hrEmptyState" class="empty-state" style="display:none;">
                    <div class="empty-icon"><?= $pageIcon ?></div>
                    <div class="empty-text">No records found.<br>Add a consultation record first via the <a href="/hcnurse/consultation/" style="color:var(--accent);">Consultation</a> page.</div>
                </div>
                <table id="hrTable" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Sub Type</th>
                            <th>Complaint</th>
                            <th>Health Worker</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="hrTableBody"></tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ── VIEW MODAL ── -->
    <div id="viewModal" title="Record Detail" class="hidden">
        <div class="view-header">
            <div>
                <div class="view-header-name" id="vm-name">—</div>
                <div class="view-header-sub"  id="vm-sub">—</div>
            </div>
            <div id="vm-status-wrap"></div>
        </div>
        <div class="view-body">
            <div class="view-grid">
                <div>
                    <div class="view-section-title">Visit Information</div>
                    <div class="view-row"><div class="view-lbl">Date</div><div class="view-val view-val-mono" id="vm-date">—</div></div>
                    <div class="view-row"><div class="view-lbl">Time</div><div class="view-val view-val-mono" id="vm-time">—</div></div>
                    <div class="view-row"><div class="view-lbl">Sub Type</div><div class="view-val" id="vm-subtype">—</div></div>
                    <div class="view-row"><div class="view-lbl">Health Worker</div><div class="view-val" id="vm-worker">—</div></div>
                    <div class="view-section-title" style="margin-top:16px;">Chief Complaint</div>
                    <div class="view-row"><div class="view-val" id="vm-complaint">—</div></div>
                </div>
                <div>
                    <div class="view-section-title">Clinical Notes</div>
                    <div class="view-row"><div class="view-lbl">Diagnosis</div><div class="view-val" id="vm-diagnosis">—</div></div>
                    <div class="view-row"><div class="view-lbl">Treatment</div><div class="view-val" id="vm-treatment">—</div></div>
                    <div class="view-row"><div class="view-lbl">Remarks</div><div class="view-val" id="vm-remarks" style="font-style:italic;color:var(--ink-muted);">—</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── EDIT MODAL ── -->
    <div id="editModal" title="Edit Record" class="hidden">
        <form id="editForm" class="modal-scroll">
            <input type="hidden" name="id"          id="edit_id">
            <input type="hidden" name="resident_id" id="edit_resident_id">

            <div class="form-section">
                <div class="form-section-lbl">Patient</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Resident</label>
                        <input type="text" id="edit_resident_name" class="fg-input" disabled
                               style="background:var(--paper-lt);color:var(--ink-muted);">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Visit Details</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Date <span class="req">*</span></label>
                            <input type="text" name="consultation_date" id="edit_date" class="fg-input" placeholder="mm/dd/yyyy">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Time</label>
                            <input type="time" name="consultation_time" id="edit_time" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select name="status" id="edit_status" class="fg-select">
                                <option>Completed</option>
                                <option>Ongoing</option>
                                <option>Dismissed</option>
                                <option>Follow-up</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Sub Type</label>
                            <input type="text" name="sub_type" id="edit_subtype" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Health Worker</label>
                            <input type="text" name="health_worker" id="edit_worker" class="fg-input" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Clinical Notes</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Chief Complaint <span class="req">*</span></label>
                        <textarea name="complaint" id="edit_complaint" class="fg-textarea"></textarea>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Diagnosis</label>
                            <textarea name="diagnosis" id="edit_diagnosis" class="fg-textarea"></textarea>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Treatment</label>
                            <textarea name="treatment" id="edit_treatment" class="fg-textarea"></textarea>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Remarks</label>
                        <input type="text" name="remarks" id="edit_remarks" class="fg-input" autocomplete="off">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    const HEALTH_RECORD_TYPE = <?= json_encode($type) ?>;
    const INIT_FILTERS = {
        period: <?= json_encode($period) ?>,
        month:  <?= json_encode($month) ?>,
        q:      <?= json_encode($q) ?>,
        sub:    <?= json_encode($sub) ?>
    };
    </script>
    <script src="js/index.js"></script>
</body>
</html>
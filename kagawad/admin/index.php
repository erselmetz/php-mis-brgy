<?php
require_once __DIR__ . '/../../includes/app.php';

include_once __DIR__ . '/helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    include_once __DIR__ . '/add_account.php';
    include_once __DIR__ . '/edit_account.php';
}
requireKagawad();

// Fetch all users with officer + resident info
$stmt = $conn->prepare("
    SELECT u.*,
           o.id as officer_id, o.resident_id, o.position as officer_position,
           o.term_start, o.term_end, o.status as officer_status,
           CONCAT(r.first_name, ' ', COALESCE(r.middle_name,''), ' ', r.last_name,
                  COALESCE(CONCAT(', ', r.suffix),'')) as resident_full_name
    FROM users u
    LEFT JOIN officers o ON u.id = o.user_id AND o.archived_at IS NULL
    LEFT JOIN residents r ON o.resident_id = r.id
    ORDER BY u.id DESC
");
$stmt->execute();
$result = $stmt->get_result();

// Role meta
$roleMeta = [
    'captain'   => ['label' => 'Barangay Captain',   'code' => 'CPT'],
    'kagawad'   => ['label' => 'Kagawad',             'code' => 'KGW'],
    'secretary' => ['label' => 'Barangay Secretary',  'code' => 'SEC'],
    'hcnurse'   => ['label' => 'HC Nurse',            'code' => 'HCN'],
    'admin'     => ['label' => 'Administrator',       'code' => 'ADM'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officials &amp; Staff — MIS Barangay</title>
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
    /* ═══ TOKENS ═══════════════════════════════════════════ */
    :root {
        --paper:     #fdfcf9; --paper-lt: #f9f7f3; --paper-dk: #f0ede6;
        --ink:       #1a1a1a; --ink-muted: #5a5a5a; --ink-faint: #a0a0a0;
        --rule:      #d8d4cc; --rule-dk:   #b8b4ac;
        --bg:        #edeae4;
        --accent:    var(--theme-primary, #2d5a27);
        --accent-lt: color-mix(in srgb, var(--accent)  8%, white);
        --accent-md: color-mix(in srgb, var(--accent) 18%, white);
        --accent-dk: color-mix(in srgb, var(--accent) 65%, black);
        --ok-bg:  #edfaf3; --ok-fg:  #1a5c35;
        --warn-bg:#fef9ec; --warn-fg:#7a5700;
        --err-bg: #fdeeed; --err-fg: #7a1f1a;
        --info-bg:#edf3fa; --info-fg:#1a3a5c;
        --f-serif:'Source Serif 4', Georgia, serif;
        --f-sans: 'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono: 'Source Code Pro', 'Courier New', monospace;
        --shadow: 0 1px 2px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select, textarea { font-family: var(--f-sans); }

    /* ═══ PAGE ══════════════════════════════════════════════ */
    .adm-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

    /* ── Document Header ── */
    .doc-header { background: var(--paper); border-bottom: 1px solid var(--rule); }
    .doc-header-inner {
        padding: 20px 28px 18px;
        display: flex; align-items: flex-end;
        justify-content: space-between; gap: 20px; flex-wrap: wrap;
    }
    .doc-eyebrow {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.8px;
        text-transform: uppercase; color: var(--ink-faint); margin-bottom: 6px;
        display: flex; align-items: center; gap: 8px;
    }
    .doc-eyebrow::before { content:''; display:inline-block; width:18px; height:2px; background:var(--accent); }
    .doc-title { font-family:var(--f-serif); font-size:22px; font-weight:700; color:var(--ink); letter-spacing:-.2px; margin-bottom:3px; }
    .doc-sub   { font-size:12px; color:var(--ink-faint); font-style:italic; }
    .header-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    /* ── Toolbar ── */
    .adm-toolbar {
        background: var(--paper-lt); border-bottom: 3px solid var(--accent);
        padding: 12px 28px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .toolbar-left  { display:flex; gap:8px; align-items:center; }
    .toolbar-right { font-family:var(--f-mono); font-size:10px; color:var(--ink-faint); letter-spacing:.5px; }

    /* ── Buttons ── */
    .btn {
        display:inline-flex; align-items:center; gap:6px;
        padding:7px 16px; border-radius:2px;
        font-family:var(--f-sans); font-size:11.5px; font-weight:700;
        letter-spacing:.4px; text-transform:uppercase;
        cursor:pointer; border:1.5px solid; transition:all .14s;
        white-space:nowrap; text-decoration:none;
    }
    .btn-primary { background:var(--accent); border-color:var(--accent); color:#fff; }
    .btn-primary:hover { filter:brightness(1.1); }
    .btn-ghost   { background:#fff; border-color:var(--rule-dk); color:var(--ink-muted); }
    .btn-ghost:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-lt); }
    .btn-sm { padding:5px 11px; font-size:10px; }

    /* ── Search ── */
    .adm-search {
        padding:7px 12px; border:1.5px solid var(--rule-dk); border-radius:2px;
        font-family:var(--f-sans); font-size:13px; color:var(--ink); background:#fff;
        outline:none; width:220px; transition:border-color .15s, box-shadow .15s;
    }
    .adm-search:focus { border-color:var(--accent); box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent); }
    .adm-search::placeholder { color:var(--ink-faint); font-style:italic; font-size:12px; }

    /* ═══ TABLE ═════════════════════════════════════════════ */
    .adm-table-wrap {
        margin: 22px 28px;
        background: var(--paper);
        border: 1px solid var(--rule); border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .adm-table-wrap .dataTables_wrapper { padding:0; font-family:var(--f-sans); }
    .adm-table-wrap .dataTables_filter,
    .adm-table-wrap .dataTables_length  { display:none; }
    .adm-table-wrap .dataTables_info {
        padding:10px 18px; font-size:11px; color:var(--ink-faint);
        font-family:var(--f-mono); letter-spacing:.3px;
        border-top:1px solid var(--rule); background:var(--paper-lt);
    }
    .adm-table-wrap .dataTables_paginate {
        padding:10px 18px; border-top:1px solid var(--rule); background:var(--paper-lt);
    }
    .adm-table-wrap .paginate_button {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:30px; height:28px; padding:0 8px;
        border:1.5px solid var(--rule-dk) !important; border-radius:2px;
        font-size:11px; font-weight:600; color:var(--ink-muted) !important;
        background:#fff !important; cursor:pointer; margin:0 2px; transition:all .13s;
    }
    .adm-table-wrap .paginate_button:hover { border-color:var(--accent) !important; color:var(--accent) !important; background:var(--accent-lt) !important; }
    .adm-table-wrap .paginate_button.current { background:var(--accent) !important; border-color:var(--accent) !important; color:#fff !important; }
    .adm-table-wrap .paginate_button.disabled { opacity:.35 !important; cursor:not-allowed; }

    #accountsTable { width:100% !important; border-collapse:collapse; }
    #accountsTable thead th {
        padding:10px 14px; background:var(--paper-lt);
        text-align:left; font-size:8.5px; font-weight:700;
        letter-spacing:1.2px; text-transform:uppercase; color:var(--ink-muted);
        border-bottom:1px solid var(--rule-dk); white-space:nowrap; cursor:pointer; user-select:none;
    }
    #accountsTable thead th:hover { color:var(--accent); }
    #accountsTable tbody tr { border-bottom:1px solid #f0ede8; transition:background .1s; }
    #accountsTable tbody tr:last-child { border-bottom:none; }
    #accountsTable tbody tr:hover { background:var(--accent-lt); }
    #accountsTable td { padding:10px 14px; font-size:12.5px; color:var(--ink); vertical-align:middle; }

    /* Name cell */
    .td-name    { font-weight:600; font-size:13px; margin-bottom:2px; }
    .td-user    { font-family:var(--f-mono); font-size:9.5px; color:var(--ink-faint); letter-spacing:.5px; }

    /* Role badge */
    .role-badge {
        display:inline-flex; align-items:center; gap:5px;
        padding:3px 9px; border-radius:2px;
        font-size:9px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; border:1px solid;
    }
    .role-code { font-family:var(--f-mono); }
    .rb-captain   { background:var(--info-bg); color:var(--info-fg); border-color:color-mix(in srgb,var(--info-fg) 25%,transparent); }
    .rb-kagawad   { background:#f3e8ff; color:#6b21a8; border-color:color-mix(in srgb,#6b21a8 25%,transparent); }
    .rb-secretary { background:var(--ok-bg); color:var(--ok-fg); border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .rb-hcnurse   { background:var(--warn-bg); color:var(--warn-fg); border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent); }
    .rb-admin     { background:var(--err-bg); color:var(--err-fg); border-color:color-mix(in srgb,var(--err-fg) 25%,transparent); }
    .rb-dev       { background:#1a1a1a; color:#fff; border-color:#1a1a1a; }

    /* Status dot */
    .status-dot {
        display:inline-flex; align-items:center; gap:5px;
        font-size:11px; color:var(--ink-muted);
    }
    .status-dot::before {
        content:''; width:7px; height:7px; border-radius:50%; flex-shrink:0;
    }
    .status-active::before   { background:var(--ok-fg); }
    .status-disabled::before { background:var(--rule-dk); }

    /* Term date */
    .term-range {
        font-family:var(--f-mono); font-size:10.5px; color:var(--ink-muted);
        white-space:nowrap;
    }
    .term-expiring { color:var(--warn-fg); }
    .term-expired  { color:var(--err-fg); }

    /* Position */
    .td-position { font-size:12px; color:var(--ink); }

    /* Action buttons */
    .td-actions { display:flex; gap:5px; align-items:center; }
    .act-btn {
        display:inline-flex; align-items:center; gap:4px;
        padding:4px 10px; border-radius:2px; font-size:9.5px;
        font-weight:700; letter-spacing:.4px; text-transform:uppercase;
        cursor:pointer; border:1.5px solid; font-family:var(--f-sans);
        transition:all .13s; white-space:nowrap;
    }
    .act-edit    { background:#fff; border-color:var(--rule-dk); color:var(--ink-muted); }
    .act-edit:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-lt); }

    /* ═══ DIALOG OVERRIDES ══════════════════════════════════ */
    .ui-dialog { border:1px solid var(--rule-dk) !important; border-radius:2px !important; box-shadow:0 8px 48px rgba(0,0,0,.18) !important; padding:0 !important; font-family:var(--f-sans) !important; }
    .ui-dialog-titlebar { background:var(--accent) !important; border:none !important; border-radius:0 !important; padding:12px 16px !important; }
    .ui-dialog-title { font-family:var(--f-sans) !important; font-size:11px !important; font-weight:700 !important; letter-spacing:1px !important; text-transform:uppercase !important; color:#fff !important; }
    .ui-dialog-titlebar-close { background:rgba(255,255,255,.15) !important; border:1px solid rgba(255,255,255,.25) !important; border-radius:2px !important; color:#fff !important; width:24px !important; height:24px !important; top:50% !important; transform:translateY(-50%) !important; }
    .ui-dialog-content { padding:0 !important; }
    .ui-dialog-buttonpane { background:var(--paper-lt) !important; border-top:1px solid var(--rule) !important; padding:12px 16px !important; margin:0 !important; }
    .ui-dialog-buttonpane .ui-button { font-family:var(--f-sans) !important; font-size:11px !important; font-weight:700 !important; letter-spacing:.5px !important; text-transform:uppercase !important; padding:7px 18px !important; border-radius:2px !important; border:1.5px solid var(--accent) !important; background:var(--accent) !important; color:#fff !important; cursor:pointer !important; transition:filter .13s !important; }
    .ui-dialog-buttonpane .ui-button:hover { filter:brightness(1.1) !important; }
    .ui-dialog-buttonpane .ui-button:last-child { background:#fff !important; border-color:var(--rule-dk) !important; color:var(--ink-muted) !important; }
    .ui-dialog-buttonpane .ui-button:last-child:hover { border-color:var(--ink-muted) !important; color:var(--ink) !important; }

    /* ═══ FORM INSIDE MODALS ════════════════════════════════ */
    .modal-form { max-height:70vh; overflow-y:auto; }
    .form-section { padding:14px 18px 0; border-top:1px solid var(--rule); margin-top:4px; }
    .form-section:first-child { border-top:none; padding-top:18px; }
    .form-section-lbl {
        font-size:8px; font-weight:700; letter-spacing:1.6px;
        text-transform:uppercase; color:var(--ink-faint); margin-bottom:12px;
        display:flex; align-items:center; gap:8px;
    }
    .form-section-lbl::after { content:''; flex:1; height:1px; background:var(--rule); }
    .form-section-body { padding-bottom:14px; }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
    .fg { margin-bottom:12px; }
    .fg-label { display:block; font-size:8.5px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--ink-muted); margin-bottom:5px; }
    .fg-label .req { color:var(--err-fg); margin-left:2px; }
    .fg-input, .fg-select {
        width:100%; padding:9px 12px; border:1.5px solid var(--rule-dk); border-radius:2px;
        font-family:var(--f-sans); font-size:13px; color:var(--ink); background:#fff;
        outline:none; transition:border-color .15s, box-shadow .15s;
    }
    .fg-input:focus, .fg-select:focus { border-color:var(--accent); box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent); }
    .fg-input::placeholder { color:var(--ink-faint); font-style:italic; font-size:12px; }

    /* Info note */
    .fg-note { font-size:10px; color:var(--ink-faint); margin-top:4px; font-style:italic; }

    /* hcnurse term note */
    .hcnurse-note {
        display:none; padding:9px 13px; background:var(--info-bg);
        border:1px solid color-mix(in srgb,var(--info-fg) 20%,transparent);
        border-radius:2px; font-size:11px; color:var(--info-fg);
        margin-bottom:12px;
    }

    /* ═══ RESIDENT SEARCH IN FORM ══════════════════════════ */
    .res-search-wrap { position:relative; }
    .res-search-dd {
        position:absolute; top:calc(100% + 4px); left:0; right:0;
        background:#fff; border:1.5px solid var(--rule-dk); border-radius:2px;
        box-shadow:0 4px 20px rgba(0,0,0,.12); max-height:200px; overflow-y:auto;
        z-index:9999; display:none;
    }
    .res-search-dd.open { display:block; }
    .rsd-row {
        display:grid; grid-template-columns:36px 1fr;
        align-items:center; gap:10px; padding:9px 12px;
        cursor:pointer; border-bottom:1px solid #f0ede8; transition:background .1s;
    }
    .rsd-row:last-child { border-bottom:none; }
    .rsd-row:hover { background:var(--accent-lt); }
    .rsd-id { font-family:var(--f-mono); font-size:9.5px; font-weight:700; color:var(--ink-faint); }
    .rsd-name { font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:1px; }
    .rsd-addr { font-size:10.5px; color:var(--ink-faint); }
    .res-selected-tag {
        display:flex; align-items:center; justify-content:space-between;
        padding:8px 12px; margin-top:6px;
        background:var(--accent-lt); border:1px solid color-mix(in srgb,var(--accent) 25%,transparent);
        border-radius:2px; font-size:12px; font-weight:600; color:var(--accent);
    }
    .res-clear-btn {
        background:none; border:none; cursor:pointer; font-size:12px;
        color:var(--err-fg); padding:0 4px; font-weight:700;
    }

    /* ═══ ARCHIVE REGISTRY DIALOG ══════════════════════════ */
    .arc-tab-bar { display:flex; background:var(--paper-lt); border-bottom:1px solid var(--rule); }
    .arc-tab {
        display:flex; align-items:center; gap:7px; padding:12px 22px;
        font-family:var(--f-sans); font-size:10px; font-weight:700;
        letter-spacing:1px; text-transform:uppercase;
        color:var(--ink-faint); background:none; border:none;
        border-bottom:2px solid transparent; cursor:pointer;
        transition:all .14s; margin-bottom:-1px;
    }
    .arc-tab:hover { color:var(--ink-muted); }
    .arc-tab.active { color:var(--accent); border-bottom-color:var(--accent); background:var(--paper); }
    .arc-tab-count {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:20px; height:16px; padding:0 5px;
        background:var(--rule); border-radius:2px;
        font-family:var(--f-mono); font-size:9px; font-weight:700;
        color:var(--ink-muted); transition:all .14s;
    }
    .arc-tab.active .arc-tab-count { background:var(--accent-md); color:var(--accent); }
    .arc-pane { display:none; }
    .arc-pane.active { display:block; }

    /* Stats row */
    .arc-stats { display:grid; grid-template-columns:repeat(3,1fr); border-bottom:1px solid var(--rule); }
    .arc-stat-cell { padding:13px 18px; border-right:1px solid var(--rule); text-align:center; }
    .arc-stat-cell:last-child { border-right:none; }
    .arc-stat-val { font-family:var(--f-mono); font-size:24px; font-weight:600; color:var(--ink); line-height:1; margin-bottom:5px; }
    .arc-stat-lbl { font-size:8px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--ink-faint); }

    .arc-search-bar { padding:11px 16px; border-bottom:1px solid var(--rule); background:var(--paper-lt); }
    .arc-table-scroll { overflow-y:auto; max-height:288px; }

    /* Archive table */
    .arc-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    .arc-table thead th {
        padding:9px 15px; background:var(--paper-lt);
        text-align:left; font-size:8.5px; font-weight:700;
        letter-spacing:1.1px; text-transform:uppercase; color:var(--ink-muted);
        border-bottom:1px solid var(--rule-dk); white-space:nowrap;
    }
    .arc-table tbody tr { border-bottom:1px solid #f0ede8; transition:background .1s; }
    .arc-table tbody tr:last-child { border-bottom:none; }
    .arc-table tbody tr:hover { background:var(--paper-lt); }
    .arc-table td { padding:10px 15px; color:var(--ink); vertical-align:middle; }
    .arc-empty { padding:36px; text-align:center; color:var(--ink-faint); font-style:italic; font-size:12px; }
    .arc-footer {
        padding:8px 16px; border-top:1px solid var(--rule); background:var(--paper-lt);
        font-family:var(--f-mono); font-size:9px; color:var(--ink-faint); letter-spacing:.5px;
    }

    /* ═══ TERM HISTORY DIALOG ═══════════════════════════════ */
    .hist-search-bar { padding:11px 16px; border-bottom:1px solid var(--rule); background:var(--paper-lt); }
    .hist-table { width:100%; border-collapse:collapse; font-size:12px; }
    .hist-table thead th {
        padding:9px 14px; background:var(--paper-lt);
        text-align:left; font-size:8.5px; font-weight:700;
        letter-spacing:1.1px; text-transform:uppercase; color:var(--ink-muted);
        border-bottom:1px solid var(--rule-dk); white-space:nowrap;
    }
    .hist-table tbody tr { border-bottom:1px solid #f0ede8; transition:background .1s; }
    .hist-table tbody tr:hover { background:var(--paper-lt); }
    .hist-table td { padding:9px 14px; color:var(--ink); vertical-align:middle; }
    .hist-footer { padding:8px 16px; border-top:1px solid var(--rule); background:var(--paper-lt); font-family:var(--f-mono); font-size:9px; color:var(--ink-faint); }

    /* Action type badge */
    .action-badge {
        display:inline-block; padding:2px 8px; border-radius:2px;
        font-size:9px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; border:1px solid;
    }
    .ab-archived  { background:var(--warn-bg); color:var(--warn-fg); border-color:color-mix(in srgb,var(--warn-fg) 20%,transparent); }
    .ab-restored  { background:var(--ok-bg);   color:var(--ok-fg);   border-color:color-mix(in srgb,var(--ok-fg)   20%,transparent); }
    .ab-created   { background:var(--info-bg); color:var(--info-fg); border-color:color-mix(in srgb,var(--info-fg) 20%,transparent); }
    .ab-changed   { background:var(--paper-dk); color:var(--ink-muted); border-color:var(--rule); }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto adm-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Personnel Records</div>
                        <div class="doc-title">Officials &amp; Staff</div>
                        <div class="doc-sub">Registered barangay officers, kagawads, and health center personnel</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnTermHistory">▤ Term History</button>
                        <button class="btn btn-ghost" id="btnViewArchive">◫ Archive Registry</button>
                    </div>
                </div>
                <div class="adm-toolbar">
                    <div class="toolbar-left">
                        <input type="text" class="adm-search" id="admTableSearch" placeholder="Search by name, position, role…">
                    </div>
                    <div class="toolbar-right">
                        <?php
                        $cnt = $conn->query("SELECT COUNT(*) as c FROM users u LEFT JOIN officers o ON u.id=o.user_id WHERE o.archived_at IS NULL OR o.id IS NULL");
                        $total = $cnt->fetch_assoc()['c'] ?? 0;
                        echo number_format($total) . ' ACTIVE PERSONNEL';
                        ?>
                    </div>
                </div>
            </div>

            <!-- ── Accounts Table ── -->
            <div class="adm-table-wrap" style="margin:22px 28px;">
                <table id="accountsTable" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Name / Username</th>
                            <th>Role</th>
                            <th>Position</th>
                            <th>Term Period</th>
                            <th>Status</th>
                            <th>Since</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()):
                        //$isDev = ($row['id'] == 1 || $row['position'] === 'developer');
                        $isDev = false; // Disable dev badge for now since we have multiple seeded accounts
                        $role  = $isDev ? 'admin' : ($row['role'] ?? '');
                        $rm    = $roleMeta[$role] ?? ['label' => ucfirst($role), 'code' => strtoupper(substr($role,0,3))];

                        $rbClass = $isDev ? 'rb-dev' : ('rb-' . $role);
                        $roleLabel = $isDev ? 'Developer' : $rm['label'];
                        $roleCode  = $isDev ? 'DEV' : $rm['code'];

                        // Term dates
                        $termStart = $row['term_start'] ?? '';
                        $termEnd   = $row['term_end']   ?? '';
                        $termClass = '';
                        if ($termEnd) {
                            $daysLeft = (strtotime($termEnd) - time()) / 86400;
                            if ($daysLeft < 0)   $termClass = 'term-expired';
                            elseif ($daysLeft < 60) $termClass = 'term-expiring';
                        }
                        $termDisplay = ($termStart && $termEnd)
                            ? date('M Y', strtotime($termStart)) . ' — ' . date('M Y', strtotime($termEnd))
                            : ($row['role'] === 'hcnurse' ? 'Permanent Post' : '—');

                        $statusClass = $row['status'] === 'active' ? 'status-active' : 'status-disabled';
                    ?>
                        <tr>
                            <td>
                                <div class="td-name"><?= htmlspecialchars($row['name']) ?></div>
                                <div class="td-user">@<?= htmlspecialchars($row['username']) ?></div>
                            </td>
                            <td>
                                <span class="role-badge <?= $rbClass ?>">
                                    <span class="role-code"><?= $roleCode ?></span>
                                    <?= $roleLabel ?>
                                </span>
                            </td>
                            <td class="td-position"><?= htmlspecialchars($row['officer_position'] ?? '—') ?></td>
                            <td><span class="term-range <?= $termClass ?>"><?= htmlspecialchars($termDisplay) ?></span></td>
                            <td><span class="status-dot <?= $statusClass ?>"><?= ucfirst($row['status']) ?></span></td>
                            <td style="font-family:var(--f-mono);font-size:11px;color:var(--ink-faint);"><?= date('M Y', strtotime($row['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ════════════════════════════════════════════
         MODAL: ARCHIVE REGISTRY
    ════════════════════════════════════════════ -->
    <div id="archiveRegistryDialog" title="Archive Registry" class="hidden">
        <!-- Tab bar -->
        <div class="arc-tab-bar">
            <button class="arc-tab active" data-tab="officers">
                <span style="font-size:12px;opacity:.65;">⊟</span>
                Archived Officers
                <span class="arc-tab-count" id="arc-off-count">—</span>
            </button>
        </div>

        <!-- Officers pane -->
        <div class="arc-pane active" id="arc-pane-officers">
            <div class="arc-stats">
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-off-total">—</div>
                    <div class="arc-stat-lbl">Total Archived</div>
                </div>
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-off-roles">—</div>
                    <div class="arc-stat-lbl">Unique Roles</div>
                </div>
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-off-latest">—</div>
                    <div class="arc-stat-lbl">Last Archived</div>
                </div>
            </div>
            <div class="arc-search-bar">
                <input type="text" class="adm-search" id="arcOffSearch"
                    placeholder="Search by name, position, or role…" style="width:100%;">
            </div>
            <div class="arc-table-scroll">
                <table class="arc-table">
                    <thead><tr>
                        <th style="width:36px;">#</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Role</th>
                        <th>Term Period</th>
                        <th>Archived</th>
                    </tr></thead>
                    <tbody id="arcOffBody">
                        <tr><td colspan="7" class="arc-empty">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="arc-footer" id="arcOffFooter">—</div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════
         MODAL: TERM HISTORY
    ════════════════════════════════════════════ -->
    <div id="termHistoryDialog" title="Term History" class="hidden">
        <div class="hist-search-bar">
            <input type="text" class="adm-search" id="histSearch"
                placeholder="Search by officer name or position…" style="width:100%;">
        </div>
        <div style="overflow-y:auto;max-height:460px;">
            <table class="hist-table">
                <thead><tr>
                    <th>Officer</th>
                    <th>Position</th>
                    <th>Action</th>
                    <th>Status Change</th>
                    <th>Term Period</th>
                    <th>Changed By</th>
                    <th>Date</th>
                </tr></thead>
                <tbody id="histBody">
                    <tr><td colspan="7" class="arc-empty">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div class="hist-footer" id="histFooter">—</div>
    </div>

    <!-- Success/error from PHP POST -->
    <?php if (isset($success) && $success): echo DialogMessage($success, 'Success'); endif; ?>
    <?php if (isset($error)   && $error):   echo DialogMessage($error,   'Error');   endif; ?>

    <script src="js/index.js"></script>
</body>
</html>
<?php
require_once __DIR__ . '/../../includes/app.php';
requireSecretary();

$categories = [];
try {
    $res = $conn->query("SELECT id, name FROM inventory_category_list ORDER BY name ASC");
    while ($r = $res->fetch_assoc()) $categories[] = $r['name'];
} catch (Exception $e) {}

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Register — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    /* ═══════════════════════════════════════
       TOKENS
    ═══════════════════════════════════════ */
    :root {
        --paper:     #fdfcf9;
        --paper-lt:  #f9f7f3;
        --paper-dk:  #f0ede6;
        --ink:       #1a1a1a;
        --ink-muted: #5a5a5a;
        --ink-faint: #a0a0a0;
        --rule:      #d8d4cc;
        --rule-dk:   #b8b4ac;
        --bg:        #edeae4;
        --accent:    var(--theme-primary, #2d5a27);
        --accent-lt: color-mix(in srgb, var(--accent) 8%,  white);
        --accent-dk: color-mix(in srgb, var(--accent) 65%, black);
        --ok-bg:     #edfaf3; --ok-fg:     #1a5c35;
        --warn-bg:   #fef9ec; --warn-fg:   #7a5700;
        --info-bg:   #edf3fa; --info-fg:   #1a3a5c;
        --danger-bg: #fdeeed; --danger-fg: #7a1f1a;
        --neu-bg:    #f3f1ec; --neu-fg:    #5a5a5a;
        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select, textarea { font-family: var(--f-sans); }

    /* ═══════════════════════════════════════
       PAGE
    ═══════════════════════════════════════ */
    .inv-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

    /* ── Document Header ── */
    .doc-header { background: var(--paper); border-bottom: 1px solid var(--rule); }
    .doc-header-inner {
        padding: 20px 28px 18px;
        display: flex; align-items: flex-end;
        justify-content: space-between; gap: 20px; flex-wrap: wrap;
    }
    .doc-eyebrow {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.8px;
        text-transform: uppercase; color: var(--ink-faint);
        margin-bottom: 6px; display: flex; align-items: center; gap: 8px;
    }
    .doc-eyebrow::before {
        content: ''; display: inline-block; width: 18px; height: 2px; background: var(--accent);
    }
    .doc-title {
        font-family: var(--f-serif); font-size: 22px; font-weight: 700;
        color: var(--ink); letter-spacing: -.2px; margin-bottom: 3px;
    }
    .doc-sub { font-size: 12px; color: var(--ink-faint); font-style: italic; }
    .header-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    /* ── Toolbar ── */
    .inv-toolbar {
        background: var(--paper-lt); border-bottom: 3px solid var(--accent);
        padding: 12px 28px; display: flex; align-items: center;
        justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .toolbar-left  { display: flex; gap: 8px; align-items: center; }
    .toolbar-right { display: flex; gap: 8px; align-items: center; }

    /* ── Buttons ── */
    .btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 16px; border-radius: 2px;
        font-family: var(--f-sans); font-size: 11.5px; font-weight: 700;
        letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid; transition: all .14s; white-space: nowrap;
    }
    .btn-primary { background: var(--accent); border-color: var(--accent); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-ghost   { background: #fff; border-color: var(--rule-dk); color: var(--ink-muted); }
    .btn-ghost:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
    .btn-sm { padding: 5px 11px; font-size: 10px; }

    /* ── Search ── */
    .inv-search {
        padding: 7px 12px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13px; color: var(--ink);
        background: #fff; outline: none; width: 220px;
        transition: border-color .15s, box-shadow .15s;
    }
    .inv-search:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .inv-search::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }

    /* ═══════════════════════════════════════
       STAT LEDGER
       4 cells: Total | Available | Borrowed | Maintenance | Damaged
    ═══════════════════════════════════════ */
    .stat-ledger {
        display: grid; grid-template-columns: repeat(5, 1fr);
        margin: 22px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .stat-cell {
        padding: 16px 18px; border-right: 1px solid var(--rule);
        position: relative; transition: background .12s; cursor: default;
    }
    .stat-cell:last-child { border-right: none; }
    .stat-cell::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0;
        width: 0; background: var(--accent); transition: width .15s;
    }
    .stat-cell:hover::before { width: 3px; }
    .stat-cell:hover { background: var(--paper-lt); }
    .stat-eyebrow {
        font-size: 8px; font-weight: 700; letter-spacing: 1.4px;
        text-transform: uppercase; color: var(--ink-faint); margin-bottom: 8px;
    }
    .stat-val {
        font-family: var(--f-mono); font-size: 26px; font-weight: 600;
        color: var(--ink); line-height: 1; margin-bottom: 4px; letter-spacing: -1px;
    }
    .stat-sub { font-size: 10.5px; color: var(--ink-faint); }

    /* ═══════════════════════════════════════
       TABLE
    ═══════════════════════════════════════ */
    .inv-table-wrap {
        margin: 18px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .inv-table-wrap .dataTables_wrapper { padding: 0; font-family: var(--f-sans); }
    .inv-table-wrap .dataTables_filter,
    .inv-table-wrap .dataTables_length { display: none; }
    .inv-table-wrap .dataTables_info {
        padding: 10px 18px; font-size: 11px; color: var(--ink-faint);
        font-family: var(--f-mono); letter-spacing: .3px;
        border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .inv-table-wrap .dataTables_paginate {
        padding: 10px 18px; border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .inv-table-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important; border-radius: 2px;
        font-size: 11px; font-weight: 600;
        color: var(--ink-muted) !important; background: #fff !important;
        cursor: pointer; margin: 0 2px; transition: all .13s;
    }
    .inv-table-wrap .paginate_button:hover { border-color: var(--accent) !important; color: var(--accent) !important; background: var(--accent-lt) !important; }
    .inv-table-wrap .paginate_button.current { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
    .inv-table-wrap .paginate_button.disabled { opacity: .35 !important; cursor: not-allowed; }

    #inventoryTable { width: 100% !important; border-collapse: collapse; }
    #inventoryTable thead th {
        padding: 10px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        cursor: pointer; user-select: none;
    }
    #inventoryTable thead th:hover { color: var(--accent); }
    #inventoryTable thead th.sorting_asc::after  { content: ' ↑'; color: var(--accent); }
    #inventoryTable thead th.sorting_desc::after { content: ' ↓'; color: var(--accent); }
    #inventoryTable tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    #inventoryTable tbody tr:last-child { border-bottom: none; }
    #inventoryTable tbody tr:hover { background: var(--accent-lt); }
    #inventoryTable td { padding: 10px 14px; font-size: 12.5px; color: var(--ink); vertical-align: middle; }

    /* Asset code */
    .td-asset-code {
        font-family: var(--f-mono); font-size: 11px; font-weight: 700;
        color: var(--accent); letter-spacing: .5px; white-space: nowrap;
        display: block; margin-bottom: 2px;
    }
    .td-asset-name { font-weight: 600; font-size: 13px; color: var(--ink); }
    .td-category   { font-size: 11px; color: var(--ink-faint); margin-top: 2px; }

    /* Quantity — shows Total / Available */
    .td-qty { font-family: var(--f-mono); font-size: 13px; font-weight: 600; color: var(--ink); }
    .td-qty.low { color: var(--danger-fg); }

    /* Status badges — no in_use */
    .inv-status {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid; white-space: nowrap;
    }
    .inv-status::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    .is-available   { background: var(--ok-bg);     color: var(--ok-fg);     border-color: color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .is-maintenance { background: var(--warn-bg);   color: var(--warn-fg);   border-color: color-mix(in srgb,var(--warn-fg) 25%,transparent); }
    .is-damaged     { background: var(--danger-bg); color: var(--danger-fg); border-color: color-mix(in srgb,var(--danger-fg) 25%,transparent); }
    .is-retired     { background: var(--neu-bg);    color: var(--neu-fg);    border-color: var(--rule); }

    /* Condition chip */
    .cond-chip {
        display: inline-block; padding: 2px 8px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; border: 1px solid;
    }
    .cond-Good        { background: var(--ok-bg);     color: var(--ok-fg);     border-color: color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .cond-Maintenance { background: var(--warn-bg);   color: var(--warn-fg);   border-color: color-mix(in srgb,var(--warn-fg) 25%,transparent); }
    .cond-Damaged     { background: var(--danger-bg); color: var(--danger-fg); border-color: color-mix(in srgb,var(--danger-fg) 25%,transparent); }

    /* Actions */
    .td-actions { display: flex; gap: 5px; }
    .act-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 2px; font-size: 9.5px;
        font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid var(--rule-dk); font-family: var(--f-sans);
        transition: all .13s; white-space: nowrap; background: #fff; color: var(--ink-muted);
    }
    .act-edit:hover   { border-color: var(--accent);    color: var(--accent);    background: var(--accent-lt); }
    .act-audit:hover  { border-color: var(--warn-fg);   color: var(--warn-fg);   background: var(--warn-bg); }

    /* ═══════════════════════════════════════
       DIALOG OVERRIDES
    ═══════════════════════════════════════ */
    .ui-dialog {
        border: 1px solid var(--rule-dk) !important; border-radius: 2px !important;
        box-shadow: 0 8px 48px rgba(0,0,0,.18) !important;
        padding: 0 !important; font-family: var(--f-sans) !important;
    }
    .ui-dialog-titlebar {
        background: var(--accent) !important; border: none !important;
        border-radius: 0 !important; padding: 12px 16px !important;
    }
    .ui-dialog-title {
        font-family: var(--f-sans) !important; font-size: 11px !important;
        font-weight: 700 !important; letter-spacing: 1px !important;
        text-transform: uppercase !important; color: #fff !important;
    }
    .ui-dialog-titlebar-close {
        background: rgba(255,255,255,.15) !important;
        border: 1px solid rgba(255,255,255,.25) !important;
        border-radius: 2px !important; color: #fff !important;
        width: 24px !important; height: 24px !important;
        top: 50% !important; transform: translateY(-50%) !important;
    }
    .ui-dialog-content { padding: 0 !important; }
    .ui-dialog-buttonpane {
        background: var(--paper-lt) !important; border-top: 1px solid var(--rule) !important;
        padding: 12px 16px !important; margin: 0 !important;
    }
    .ui-dialog-buttonpane .ui-button {
        font-family: var(--f-sans) !important; font-size: 11px !important;
        font-weight: 700 !important; letter-spacing: .5px !important;
        text-transform: uppercase !important; padding: 7px 18px !important;
        border-radius: 2px !important; cursor: pointer !important;
    }
    .ui-dialog-buttonpane .ui-button:first-child {
        background: var(--accent) !important; border: 1.5px solid var(--accent) !important; color: #fff !important;
    }
    .ui-dialog-buttonpane .ui-button:first-child:hover { filter: brightness(1.1) !important; }
    .ui-dialog-buttonpane .ui-button:not(:first-child) {
        background: #fff !important; border: 1.5px solid var(--rule-dk) !important; color: var(--ink-muted) !important;
    }

    /* ═══════════════════════════════════════
       FORM FIELDS
    ═══════════════════════════════════════ */
    .modal-form  { max-height: 70vh; overflow-y: auto; }
    .form-section { padding: 14px 18px 0; border-top: 1px solid var(--rule); }
    .form-section:first-child { border-top: none; padding-top: 18px; }
    .form-section-lbl {
        font-size: 8px; font-weight: 700; letter-spacing: 1.6px;
        text-transform: uppercase; color: var(--ink-faint);
        margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
    }
    .form-section-lbl::after { content: ''; flex: 1; height: 1px; background: var(--rule); }
    .form-section-body { padding-bottom: 14px; }
    .fg { margin-bottom: 12px; }
    .fg-label {
        display: block; font-size: 8.5px; font-weight: 700;
        letter-spacing: 1.2px; text-transform: uppercase;
        color: var(--ink-muted); margin-bottom: 5px;
    }
    .req { color: var(--danger-fg); }
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
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

    /* ═══════════════════════════════════════
       AUDIT TRAIL MODAL
    ═══════════════════════════════════════ */
    .audit-header {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 0; border-bottom: 1px solid var(--rule);
        background: var(--paper-lt);
    }
    .audit-hdr-cell { padding: 13px 18px; border-right: 1px solid var(--rule); }
    .audit-hdr-cell:last-child { border-right: none; }
    .audit-hdr-lbl { font-size: 8.5px; font-weight: 700; letter-spacing: 1.1px; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 3px; }
    .audit-hdr-val { font-family: var(--f-mono); font-size: 14px; font-weight: 600; color: var(--ink); }
    .audit-filters {
        padding: 11px 16px; background: var(--paper-lt);
        border-bottom: 1px solid var(--rule);
        display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
    }
    .audit-table-scroll { overflow-y: auto; max-height: 340px; }
    .audit-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .audit-table thead th {
        padding: 9px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.1px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        position: sticky; top: 0;
    }
    .audit-table tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    .audit-table tbody tr:hover { background: var(--paper-lt); }
    .audit-table td { padding: 10px 14px; vertical-align: middle; }
    .audit-footer {
        padding: 9px 16px; border-top: 1px solid var(--rule); background: var(--paper-lt);
        display: flex; justify-content: space-between; align-items: center;
    }
    .audit-footer-count { font-family: var(--f-mono); font-size: 9px; color: var(--ink-faint); letter-spacing: .5px; }
    .action-pill {
        display: inline-block; padding: 2px 8px; border-radius: 2px;
        font-size: 9px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; border: 1px solid;
    }
    .ap-created     { background: var(--ok-bg);    color: var(--ok-fg);    border-color: color-mix(in srgb,var(--ok-fg)    25%,transparent); }
    .ap-assigned    { background: var(--info-bg);  color: var(--info-fg);  border-color: color-mix(in srgb,var(--info-fg)  25%,transparent); }
    .ap-returned    { background: var(--ok-bg);    color: var(--ok-fg);    border-color: color-mix(in srgb,var(--ok-fg)    25%,transparent); }
    .ap-updated     { background: var(--warn-bg);  color: var(--warn-fg);  border-color: color-mix(in srgb,var(--warn-fg)  25%,transparent); }
    .ap-deleted     { background: var(--danger-bg);color: var(--danger-fg);border-color: color-mix(in srgb,var(--danger-fg)25%,transparent); }
    .ap-other       { background: var(--neu-bg);   color: var(--neu-fg);   border-color: var(--rule); }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto inv-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Property Management</div>
                        <div class="doc-title">Inventory Register</div>
                        <div class="doc-sub">Official register of barangay assets, equipment, and properties</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="addCategoryBtn">+ Category</button>
                        <button class="btn btn-ghost" id="auditTrailBtn">◷ Audit Trail</button>
                        <button class="btn btn-primary" id="addInventoryBtn">+ Add Asset</button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="inv-toolbar">
                    <div class="toolbar-left">
                        <input type="text" class="inv-search" id="invSearch"
                            placeholder="Search by asset code, name, category, location…">
                        <select class="inv-search" id="invStatusFilter" style="width:150px;">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="damaged">Damaged</option>
                            <option value="retired">Retired</option>
                        </select>
                    </div>
                    <div class="toolbar-right"
                         style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;"
                         id="invCount">
                        — ASSETS
                    </div>
                </div>
            </div>

            <!-- ── Stat Ledger ── -->
            <!-- 5 cells: Total | Available | Has Active Borrows | Maintenance | Damaged/Retired -->
            <div class="stat-ledger">
                <div class="stat-cell">
                    <div class="stat-eyebrow">Total Assets</div>
                    <div class="stat-val" id="st-total">—</div>
                    <div class="stat-sub">all records</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-eyebrow">Available</div>
                    <div class="stat-val" id="st-available" style="color:var(--ok-fg);">—</div>
                    <div class="stat-sub">ready to borrow</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-eyebrow">Has Active Borrows</div>
                    <div class="stat-val" id="st-inuse" style="color:var(--info-fg);">—</div>
                    <div class="stat-sub">partially borrowed out</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-eyebrow">Maintenance</div>
                    <div class="stat-val" id="st-maintenance" style="color:var(--warn-fg);">—</div>
                    <div class="stat-sub">under repair</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-eyebrow">Damaged / Retired</div>
                    <div class="stat-val" id="st-damaged" style="color:var(--danger-fg);">—</div>
                    <div class="stat-sub">needs attention</div>
                </div>
            </div>

            <!-- ── Inventory Table ── -->
            <div class="inv-table-wrap">
                <table id="inventoryTable" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Qty (Total / Available)</th>
                            <th>Location</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <tr><td colspan="7" style="padding:32px;text-align:center;color:var(--ink-faint);font-style:italic;">Loading assets…</td></tr>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ════════════════════════════
         MODAL: ADD / EDIT ASSET
    ════════════════════════════ -->
    <div id="inventoryModal" title="New Asset Record" class="hidden">
        <form id="inventoryForm" class="modal-form">
            <input type="hidden" name="id"         id="inventoryId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-section">
                <div class="form-section-lbl">Asset Identification</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Asset Name <span class="req">*</span></label>
                        <input type="text" name="name" id="assetName" class="fg-input" required
                            autocomplete="off" placeholder="e.g. Monoblock Chairs, Generator Set…">
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Category</label>
                            <select name="category" id="assetCategory" class="fg-select">
                                <option value="">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Total Quantity</label>
                            <input type="number" name="quantity" id="assetQuantity" class="fg-input"
                                value="1" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Condition &amp; Location</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Condition</label>
                            <select name="condition" id="assetCondition" class="fg-select">
                                <option value="">— Select —</option>
                                <option value="Good">Good</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <!-- in_use removed — borrowing schedule tracks active borrows -->
                            <select name="status" id="assetStatus" class="fg-select">
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="damaged">Damaged</option>
                                <option value="retired">Retired</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Physical Location</label>
                            <input type="text" name="location" id="assetLocation" class="fg-input"
                                autocomplete="off" placeholder="e.g. Barangay Hall – Storage">
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Description
                            <span style="font-weight:400;font-size:9px;text-transform:none;color:var(--ink-faint);">
                                (include plate / serial no. for vehicles)
                            </span>
                        </label>
                        <textarea name="description" id="assetDescription" class="fg-textarea"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════
         MODAL: ADD CATEGORY
    ════════════════════════════ -->
    <div id="categoryModal" title="New Category" class="hidden">
        <form id="categoryForm" class="modal-form">
            <div class="form-section">
                <div class="form-section-lbl">Category Details</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Category Name <span class="req">*</span></label>
                        <input type="text" name="category_name" id="categoryName" class="fg-input"
                            required autocomplete="off"
                            placeholder="e.g. Furniture, Equipment, Vehicles…">
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                </div>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════
         MODAL: AUDIT TRAIL
    ════════════════════════════ -->
    <div id="assetAuditModal" title="Asset Audit Trail" class="hidden">
        <div class="audit-header">
            <div class="audit-hdr-cell">
                <div class="audit-hdr-lbl">Asset Name</div>
                <div class="audit-hdr-val" id="auditAssetName">—</div>
            </div>
            <div class="audit-hdr-cell">
                <div class="audit-hdr-lbl">Property Code</div>
                <div class="audit-hdr-val" id="auditAssetCode">—</div>
            </div>
        </div>
        <div class="audit-filters">
            <input type="date"   class="inv-search" id="auditDateFilter"
                placeholder="Filter by date" style="width:160px;">
            <input type="text"   class="inv-search" id="auditPersonnelFilter"
                placeholder="Search by user…" style="width:200px;">
            <button class="btn btn-ghost btn-sm" id="auditRefreshBtn">↺ Refresh</button>
        </div>
        <div class="audit-table-scroll">
            <table class="audit-table">
                <thead><tr>
                    <th>Date &amp; Time</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>Location</th>
                    <th>Purpose</th>
                    <th>Notes</th>
                </tr></thead>
                <tbody id="auditTrailBody">
                    <tr><td colspan="6" style="padding:24px;text-align:center;color:var(--ink-faint);">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div class="audit-footer">
            <span class="audit-footer-count" id="auditPageInfo">0 RECORDS</span>
            <button class="btn btn-ghost btn-sm" id="exportAuditBtn">Export Logs</button>
        </div>
    </div>

    <script src="./js/index.js?v=<?= time() ?>"></script>
</body>
</html>
<?php
/**
 * Equipment / Items Borrowing Schedule — Redesigned
 * Matches the government-official design system used across the app
 */

require_once __DIR__ . '/../../includes/app.php';
requireKagawad();

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrowing Schedule — MIS Barangay</title>
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

    .brw-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

    /* ── Doc Header ── */
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
    .doc-eyebrow::before { content:''; display:inline-block; width:18px; height:2px; background:var(--accent); }
    .doc-title {
        font-family: var(--f-serif); font-size: 22px; font-weight: 700;
        color: var(--ink); letter-spacing: -.2px; margin-bottom: 3px;
    }
    .doc-sub { font-size: 12px; color: var(--ink-faint); font-style: italic; }
    .header-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    /* ── Toolbar ── */
    .brw-toolbar {
        background: var(--paper-lt); border-bottom: 3px solid var(--accent);
        padding: 12px 28px; display: flex; align-items: center;
        justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .toolbar-left { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

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
    .btn-return  { background: var(--ok-bg); border-color: color-mix(in srgb,var(--ok-fg) 30%,transparent); color: var(--ok-fg); }
    .btn-return:hover { background: var(--ok-fg); color: #fff; border-color: var(--ok-fg); }
    .btn-sm { padding: 5px 11px; font-size: 10px; }

    /* ── Search/Filter ── */
    .brw-input {
        padding: 7px 12px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13px; color: var(--ink);
        background: #fff; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .brw-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent); }
    .brw-input::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }

    /* ═══════════════════════════════════════
       STAT LEDGER
    ═══════════════════════════════════════ */
    .stat-ledger {
        display: grid; grid-template-columns: repeat(4, 1fr);
        margin: 22px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .stat-cell {
        padding: 18px 20px; border-right: 1px solid var(--rule);
        position: relative; transition: background .12s;
    }
    .stat-cell:last-child { border-right: none; }
    .stat-cell::before {
        content:''; position:absolute; left:0; top:0; bottom:0;
        width:0; background:var(--accent); transition:width .15s;
    }
    .stat-cell:hover::before { width:3px; }
    .stat-cell:hover { background: var(--paper-lt); }
    .stat-eyebrow {
        font-size: 8px; font-weight: 700; letter-spacing: 1.4px;
        text-transform: uppercase; color: var(--ink-faint); margin-bottom: 9px;
    }
    .stat-val {
        font-family: var(--f-mono); font-size: 28px; font-weight: 600;
        color: var(--ink); line-height: 1; margin-bottom: 5px; letter-spacing: -1px;
    }
    .stat-sub { font-size: 10.5px; color: var(--ink-faint); }
    .s-borrowed  .stat-val { color: var(--info-fg); }
    .s-returned  .stat-val { color: var(--ok-fg); }
    .s-overdue   .stat-val { color: var(--danger-fg); }
    .s-cancelled .stat-val { color: var(--neu-fg); }

    /* ═══════════════════════════════════════
       TABLE
    ═══════════════════════════════════════ */
    .brw-table-wrap {
        margin: 18px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .brw-table-wrap .dataTables_wrapper { padding: 0; font-family: var(--f-sans); }
    .brw-table-wrap .dataTables_filter,
    .brw-table-wrap .dataTables_length  { display: none; }
    .brw-table-wrap .dataTables_info {
        padding: 10px 18px; font-size: 11px; color: var(--ink-faint);
        font-family: var(--f-mono); letter-spacing: .3px;
        border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .brw-table-wrap .dataTables_paginate {
        padding: 10px 18px; border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .brw-table-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important; border-radius: 2px;
        font-size: 11px; font-weight: 600; color: var(--ink-muted) !important;
        background: #fff !important; cursor: pointer; margin: 0 2px; transition: all .13s;
    }
    .brw-table-wrap .paginate_button:hover { border-color: var(--accent) !important; color: var(--accent) !important; background: var(--accent-lt) !important; }
    .brw-table-wrap .paginate_button.current { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
    .brw-table-wrap .paginate_button.disabled { opacity: .35 !important; }

    #borrowTable { width: 100% !important; border-collapse: collapse; }
    #borrowTable thead th {
        padding: 10px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        cursor: pointer; user-select: none;
    }
    #borrowTable thead th:hover { color: var(--accent); }
    #borrowTable thead th.sorting_asc::after  { content:' ↑'; color:var(--accent); }
    #borrowTable thead th.sorting_desc::after { content:' ↓'; color:var(--accent); }
    #borrowTable tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    #borrowTable tbody tr:last-child { border-bottom: none; }
    #borrowTable tbody tr:hover { background: var(--accent-lt); }
    #borrowTable td { padding: 10px 14px; font-size: 12.5px; color: var(--ink); vertical-align: middle; }

    .td-borrow-code {
        font-family: var(--f-mono); font-size: 11px; font-weight: 700;
        color: var(--accent); letter-spacing: .5px; display: block;
    }
    .td-borrower-name { font-weight: 600; font-size: 13px; }
    .td-borrower-contact { font-family: var(--f-mono); font-size: 10.5px; color: var(--ink-faint); margin-top: 2px; }
    .td-item-name { font-weight: 600; font-size: 12.5px; }
    .td-item-code { font-family: var(--f-mono); font-size: 10px; color: var(--ink-faint); margin-top: 2px; }
    .td-date { font-family: var(--f-mono); font-size: 11.5px; color: var(--ink-muted); white-space: nowrap; }
    .td-date-overdue { color: var(--danger-fg); font-weight: 700; }
    .td-qty { font-family: var(--f-mono); font-size: 13px; font-weight: 600; }

    /* Status badges */
    .bw-status {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid; white-space: nowrap;
    }
    .bw-status::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; flex-shrink:0; }
    .bs-borrowed  { background:var(--info-bg);   color:var(--info-fg);   border-color:color-mix(in srgb,var(--info-fg) 25%,transparent); }
    .bs-returned  { background:var(--ok-bg);     color:var(--ok-fg);     border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .bs-overdue   { background:var(--danger-bg); color:var(--danger-fg); border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent); }
    .bs-cancelled { background:var(--neu-bg);    color:var(--neu-fg);    border-color:var(--rule); }

    /* Actions */
    .td-actions { display: flex; gap: 5px; flex-wrap: wrap; }
    .act-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 2px; font-size: 9.5px;
        font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid var(--rule-dk); font-family: var(--f-sans);
        transition: all .13s; white-space: nowrap; background: #fff; color: var(--ink-muted);
    }
    .act-view:hover   { border-color:var(--info-fg);   color:var(--info-fg);   background:var(--info-bg); }
    .act-edit:hover   { border-color:var(--accent);    color:var(--accent);    background:var(--accent-lt); }
    .act-return:hover { border-color:var(--ok-fg);     color:var(--ok-fg);     background:var(--ok-bg); }
    .act-delete:hover { border-color:var(--danger-fg); color:var(--danger-fg); background:var(--danger-bg); }

    /* ── Inventory item search-with-dropdown ── */
    .inv-search-wrap { position: relative; }
    .inv-search-dd {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        box-shadow: 0 6px 24px rgba(0,0,0,.13);
        max-height: 220px; overflow-y: auto;
        z-index: 9999; display: none;
    }
    .inv-search-dd.open { display: block; }
    .inv-dd-empty {
        padding: 14px 13px; font-size: 12px;
        color: var(--ink-faint); font-style: italic; text-align: center;
    }
    .inv-dd-row {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 13px; cursor: pointer;
        border-bottom: 1px solid #f0ede8; transition: background .1s;
    }
    .inv-dd-row:last-child { border-bottom: none; }
    .inv-dd-row:hover { background: var(--accent-lt); }
    .inv-dd-row:hover .inv-dd-code { border-color: var(--accent); color: var(--accent); }
    .inv-dd-code {
        font-family: var(--f-mono); font-size: 9.5px; font-weight: 700;
        color: var(--ink-faint); border: 1px solid var(--rule-dk);
        border-radius: 2px; padding: 3px 6px; flex-shrink: 0;
        transition: all .12s; white-space: nowrap;
    }
    .inv-dd-body { flex: 1; min-width: 0; }
    .inv-dd-name { font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .inv-dd-avail { font-size: 10.5px; font-family: var(--f-mono); }
    .inv-dd-avail.ok   { color: var(--ok-fg); }
    .inv-dd-avail.warn { color: var(--warn-fg); }
    .inv-dd-avail.none { color: var(--danger-fg); }
    .inv-selected-tag {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 12px; margin-top: 6px;
        background: var(--accent-lt);
        border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
        border-radius: 2px; font-size: 12px; font-weight: 600; color: var(--accent);
    }
    .inv-clear-btn {
        background: none; border: none; cursor: pointer;
        font-size: 12px; color: var(--danger-fg); padding: 0 4px; font-weight: 700;
    }

    /* ═══════════════════════════════════════
       DIALOG OVERRIDES
    ═══════════════════════════════════════ */
    .ui-dialog { border:1px solid var(--rule-dk) !important; border-radius:2px !important; box-shadow:0 8px 48px rgba(0,0,0,.18) !important; padding:0 !important; font-family:var(--f-sans) !important; }
    .ui-dialog-titlebar { background:var(--accent) !important; border:none !important; border-radius:0 !important; padding:12px 16px !important; }
    .ui-dialog-title { font-family:var(--f-sans) !important; font-size:11px !important; font-weight:700 !important; letter-spacing:1px !important; text-transform:uppercase !important; color:#fff !important; }
    .ui-dialog-titlebar-close { background:rgba(255,255,255,.15) !important; border:1px solid rgba(255,255,255,.25) !important; border-radius:2px !important; color:#fff !important; width:24px !important; height:24px !important; top:50% !important; transform:translateY(-50%) !important; }
    .ui-dialog-content { padding:0 !important; }
    .ui-dialog-buttonpane { background:var(--paper-lt) !important; border-top:1px solid var(--rule) !important; padding:12px 16px !important; margin:0 !important; }
    .ui-dialog-buttonpane .ui-button { font-family:var(--f-sans) !important; font-size:11px !important; font-weight:700 !important; letter-spacing:.5px !important; text-transform:uppercase !important; padding:7px 18px !important; border-radius:2px !important; cursor:pointer !important; }
    .ui-dialog-buttonpane .ui-button:first-child { background:var(--accent) !important; border:1.5px solid var(--accent) !important; color:#fff !important; }
    .ui-dialog-buttonpane .ui-button:first-child:hover { filter:brightness(1.1) !important; }
    .ui-dialog-buttonpane .ui-button:not(:first-child) { background:#fff !important; border:1.5px solid var(--rule-dk) !important; color:var(--ink-muted) !important; }

    /* ── Form Fields ── */
    .modal-form  { max-height: 72vh; overflow-y: auto; }
    .form-section { padding: 14px 18px 0; border-top: 1px solid var(--rule); }
    .form-section:first-child { border-top: none; padding-top: 18px; }
    .form-section-lbl {
        font-size: 8px; font-weight: 700; letter-spacing: 1.6px;
        text-transform: uppercase; color: var(--ink-faint);
        margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
    }
    .form-section-lbl::after { content:''; flex:1; height:1px; background:var(--rule); }
    .form-section-body { padding-bottom: 14px; }
    .fg { margin-bottom: 12px; }
    .fg-label { display:block; font-size:8.5px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--ink-muted); margin-bottom:5px; }
    .req { color: var(--danger-fg); }
    .fg-input, .fg-select, .fg-textarea {
        width:100%; padding:9px 12px; border:1.5px solid var(--rule-dk); border-radius:2px;
        font-family:var(--f-sans); font-size:13px; color:var(--ink); background:#fff;
        outline:none; transition:border-color .15s, box-shadow .15s;
    }
    .fg-input:focus, .fg-select:focus, .fg-textarea:focus { border-color:var(--accent); box-shadow:0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent); }
    .fg-input::placeholder { color:var(--ink-faint); font-style:italic; font-size:12px; }
    .fg-textarea { resize:vertical; min-height:72px; }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }

    /* View modal detail rows */
    .vd-row { display:flex; justify-content:space-between; align-items:flex-start; padding:8px 0; border-bottom:1px solid #f0ede8; font-size:12.5px; }
    .vd-row:last-child { border-bottom:none; }
    .vd-lbl { color:var(--ink-faint); font-size:11px; font-weight:600; letter-spacing:.3px; text-transform:uppercase; flex-shrink:0; padding-right:12px; }
    .vd-val { color:var(--ink); text-align:right; }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto brw-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Asset Management</div>
                        <div class="doc-title">Equipment Borrowing Schedule</div>
                        <div class="doc-sub">Track and manage borrowing of barangay equipment and assets by residents and staff</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnPrintBorrow">↗ Print</button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="brw-toolbar">
                    <div class="toolbar-left">
                        <input type="text"  class="brw-input" id="searchInput"   placeholder="Search borrower, item, or code…" style="width:240px;">
                        <input type="date"  class="brw-input" id="filterDate"    title="Filter by borrow date">
                        <select            class="brw-input" id="filterStatus"  style="width:140px;">
                            <option value="">All Status</option>
                            <option value="borrowed">Borrowed</option>
                            <option value="returned">Returned</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;" id="brwCount">
                        — RECORDS
                    </div>
                </div>
            </div>

            <!-- ── Stat Ledger ── -->
            <div class="stat-ledger">
                <div class="stat-cell s-borrowed">
                    <div class="stat-eyebrow">Currently Borrowed</div>
                    <div class="stat-val" id="countBorrowed">—</div>
                    <div class="stat-sub">active loans</div>
                </div>
                <div class="stat-cell s-returned">
                    <div class="stat-eyebrow">Returned</div>
                    <div class="stat-val" id="countReturned">—</div>
                    <div class="stat-sub">items back in stock</div>
                </div>
                <div class="stat-cell s-overdue">
                    <div class="stat-eyebrow">Overdue</div>
                    <div class="stat-val" id="countOverdue">—</div>
                    <div class="stat-sub">past return date</div>
                </div>
                <div class="stat-cell s-cancelled">
                    <div class="stat-eyebrow">Cancelled</div>
                    <div class="stat-val" id="countCancelled">—</div>
                    <div class="stat-sub">voided records</div>
                </div>
            </div>

            <!-- ── Borrowing Table ── -->
            <div class="brw-table-wrap">
                <table id="borrowTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Borrower</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Borrow Date</th>
                            <th>Return Date</th>
                            <th>Actual Return</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ════════════════
         MODAL: ADD/EDIT
    ════════════════ -->
    <div id="borrowDialog" title="New Borrowing Entry" class="hidden">
        <form id="borrowForm" class="modal-form">
            <input type="hidden" id="borrowId"        name="id">
            <input type="hidden" name="csrf_token"    value="<?= $csrf_token ?>">

            <div class="form-section">
                <div class="form-section-lbl">Borrower Information</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Borrower Name <span class="req">*</span></label>
                            <input type="text" id="borrowerName"  name="borrower_name"  class="fg-input" placeholder="Full name" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Contact No.</label>
                            <input type="text" id="borrowerContact" name="borrower_contact" class="fg-input" placeholder="09XXXXXXXXX">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Item Details</div>
                <div class="form-section-body">

                    <!-- Inventory search replaces the old <select> -->
                    <div class="fg">
                        <label class="fg-label">Search Inventory Item</label>
                        <div class="inv-search-wrap">
                            <input type="text" id="invItemSearch" class="fg-input"
                                placeholder="Type asset code or name…" autocomplete="off">
                            <div id="invItemDropdown" class="inv-search-dd">
                                <div class="inv-dd-empty">Start typing to search…</div>
                            </div>
                        </div>
                        <!-- Hidden fields populated on selection -->
                        <input type="hidden" id="inventoryItemId" name="inventory_id">
                        <!-- Selected item tag (shown after pick) -->
                        <div id="invSelectedTag" class="inv-selected-tag" style="display:none;">
                            <span id="invSelectedLabel"></span>
                            <button type="button" class="inv-clear-btn" id="invClearBtn" title="Clear selection">✕</button>
                        </div>
                        <!-- Availability hint -->
                        <div id="availHint" style="display:none;margin-top:7px;padding:8px 12px;
                             border-radius:2px;font-size:12px;font-family:var(--f-mono);border:1px solid;"></div>
                    </div>

                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Item Name <span class="req">*</span></label>
                            <input type="text" id="itemName"      name="item_name"      class="fg-input"
                                placeholder="Auto-filled or type manually" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Quantity <span class="req">*</span></label>
                            <input type="number" id="borrowQty"     name="quantity"       class="fg-input" value="1" min="1">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Condition (Out)</label>
                            <input type="text" id="conditionOut"  name="condition_out"  class="fg-input" placeholder="e.g. Good condition">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Condition (Returned)</label>
                            <input type="text" id="conditionIn"   name="condition_in"   class="fg-input" placeholder="Fill on return">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Schedule</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Borrow Date <span class="req">*</span></label>
                            <input type="date" id="borrowDate"    name="borrow_date"    class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Expected Return <span class="req">*</span></label>
                            <input type="date" id="returnDate"    name="return_date"    class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Actual Return Date</label>
                            <input type="date" id="actualReturn"  name="actual_return"  class="fg-input">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Purpose</label>
                            <input type="text" id="borrowPurpose" name="purpose"        class="fg-input" placeholder="Purpose of borrowing" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select id="borrowStatus"  name="status"         class="fg-select">
                                <option value="borrowed">Borrowed</option>
                                <option value="returned">Returned</option>
                                <option value="overdue">Overdue</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Notes</label>
                        <textarea id="borrowNotes"   name="notes"          class="fg-textarea" placeholder="Additional notes…" style="min-height:60px;"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- MODAL: VIEW -->
    <div id="viewBorrowDialog" title="Borrowing Record" class="hidden">
        <div id="viewBorrowContent" style="padding:18px 20px;"></div>
    </div>

    <!-- MODAL: DELETE -->
    <div id="deleteBorrowDialog" title="Confirm Delete" class="hidden">
        <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid var(--danger-fg);background:var(--paper);">
            Delete this borrowing record? This action cannot be undone.
        </div>
        <input type="hidden" id="deleteBorrowId">
    </div>

    <script src="js/index.js"></script>
    <script>
    $(function() {

        /* ══════════════════════════════════════════
           INVENTORY ITEM SEARCH-WITH-DROPDOWN
        ══════════════════════════════════════════ */
        let invSearchTimer;
        // No caching — availability changes every time someone borrows or returns

        const $search  = $('#invItemSearch');
        const $dd      = $('#invItemDropdown');
        const $idField = $('#inventoryItemId');
        const $tag     = $('#invSelectedTag');
        const $tagLbl  = $('#invSelectedLabel');
        const $hint    = $('#availHint');
        const $qty     = $('#borrowQty');

        function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

        /* ── Fetch and render dropdown results ── */
        function searchInventory(q) {
            if (q.length < 1) {
                $dd.removeClass('open').html('<div class="inv-dd-empty">Start typing to search…</div>');
                return;
            }

            // Always fetch fresh — availability changes with every borrow/return
            $.getJSON('../inventory/api/inventory_api.php', {
                action: 'list',
                search: q
            }, function(res) {
                renderDropdown(res.data || []);
            }).fail(function() {
                $dd.html('<div class="inv-dd-empty">Search failed. Try again.</div>').addClass('open');
            });
        }

        function renderDropdown(items) {
            $dd.empty();
            if (!items.length) {
                $dd.html('<div class="inv-dd-empty">No items found.</div>').addClass('open');
                return;
            }
            items.forEach(function(item) {
                const total     = parseInt(item.quantity)           || 0;
                const available = parseInt(item.available_quantity) || 0;

                let availCls, availTxt;
                if (available <= 0) {
                    availCls = 'none'; availTxt = '✕ None available';
                } else if (available <= Math.ceil(total * 0.2)) {
                    availCls = 'warn'; availTxt = '⚠ ' + available + ' of ' + total + ' available';
                } else {
                    availCls = 'ok';   availTxt = '✓ ' + available + ' of ' + total + ' available';
                }

                $dd.append(
                    $(`<div class="inv-dd-row" tabindex="0"></div>`)
                        .data('item', item)
                        .html(`
                            <span class="inv-dd-code">${esc(item.asset_code)}</span>
                            <div class="inv-dd-body">
                                <div class="inv-dd-name">${esc(item.name)}</div>
                                <div class="inv-dd-avail ${availCls}">${availTxt}</div>
                            </div>
                        `)
                );
            });
            $dd.addClass('open');
        }

        /* ── Select an item from the dropdown ── */
        $dd.on('click', '.inv-dd-row', function() {
            selectItem($(this).data('item'));
        });

        // Keyboard navigation
        $dd.on('keydown', '.inv-dd-row', function(e) {
            if (e.key === 'Enter' || e.key === ' ') selectItem($(this).data('item'));
            if (e.key === 'ArrowDown') $(this).next('.inv-dd-row').focus();
            if (e.key === 'ArrowUp')   $(this).prev('.inv-dd-row').focus();
        });
        $search.on('keydown', function(e) {
            if (e.key === 'ArrowDown') $dd.find('.inv-dd-row:first').focus();
        });

        function selectItem(item) {
            $dd.removeClass('open');
            $search.val('');

            const total     = parseInt(item.quantity)           || 0;
            const available = parseInt(item.available_quantity) || 0;

            $idField.val(item.id);
            $('#itemName').val(item.name);
            $qty.attr('max', available);
            if (parseInt($qty.val()) > available) $qty.val(available || 1);

            // Show selected tag
            $tagLbl.text(item.asset_code + ' — ' + item.name);
            $tag.show();

            // Show availability hint
            showHint(total, item.currently_using || (total - available), available);

            // Fetch live availability to confirm
            refreshAvailability(item.id);
        }

        /* ── Clear selection ── */
        $('#invClearBtn').on('click', function() {
            clearSelection();
        });

        function clearSelection() {
            $idField.val('');
            $('#itemName').val('');
            $qty.removeAttr('max');
            $tag.hide();
            $hint.hide();
            $search.val('').focus();
        }
        // Expose for js/index.js to call on form reset
        window.clearBorrowInvSelection = clearSelection;

        /* ── Availability hint renderer ── */
        function showHint(total, borrowed, available) {
            if (available <= 0) {
                $hint.css({
                    background:  'var(--danger-bg)', color: 'var(--danger-fg)',
                    borderColor: 'color-mix(in srgb,var(--danger-fg) 30%,transparent)'
                }).html('⚠ No stock available — Total: ' + total +
                        ' | Borrowed: ' + borrowed + ' | Available: <strong>0</strong>').show();
            } else if (available <= Math.ceil(total * 0.2)) {
                $hint.css({
                    background:  'var(--warn-bg)', color: 'var(--warn-fg)',
                    borderColor: 'color-mix(in srgb,var(--warn-fg) 30%,transparent)'
                }).html('⚠ Low stock — Total: ' + total +
                        ' | Borrowed: ' + borrowed + ' | Available: <strong>' + available + '</strong>').show();
            } else {
                $hint.css({
                    background:  'var(--ok-bg)', color: 'var(--ok-fg)',
                    borderColor: 'color-mix(in srgb,var(--ok-fg) 30%,transparent)'
                }).html('✓ Total: ' + total +
                        ' | Borrowed: ' + borrowed + ' | Available: <strong>' + available + '</strong>').show();
            }
        }

        /* ── Live availability refresh from borrow_api ── */
        function refreshAvailability(invId) {
            $.getJSON('actions/borrow_api.php', {
                action:       'check_availability',
                inventory_id: invId,
                exclude_id:   $('#borrowId').val() || 0
            }, function(res) {
                if (res.status !== 'ok') return;
                const d = res.data;
                $qty.attr('max', d.available);
                if (parseInt($qty.val()) > d.available) $qty.val(d.available || 1);
                showHint(d.total, d.borrowed, d.available);
            });
        }

        /* ── Search input events ── */
        $search.on('input', function() {
            const q = $.trim($(this).val());
            clearTimeout(invSearchTimer);
            if (!q) {
                $dd.removeClass('open');
                return;
            }
            invSearchTimer = setTimeout(() => searchInventory(q), 280);
        });

        /* ── Close dropdown on outside click ── */
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#invItemSearch, #invItemDropdown').length) {
                $dd.removeClass('open');
            }
        });

        /* ── When edit form opens, re-populate the selected tag if inv_id exists ── */
        // (index.js calls window.populateInvSearch after filling form fields)
        window.setInvItem = function(id, name, assetCode) {
            if (!id) { clearSelection(); return; }
            $idField.val(id);
            $('#itemName').val(name);
            $tagLbl.text((assetCode ? assetCode + ' — ' : '') + name);
            $tag.show();
            $hint.hide();
            refreshAvailability(id);
        };

        /* ── Reset when dialog closes ── */
        $(document).on('dialogclose', '#borrowDialog', function() {
            clearSelection();
        });

    });
    </script>
</body>
</html>
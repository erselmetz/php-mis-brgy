<?php
/**
 * Court / Facility Borrowing Schedule — Redesigned
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
    <title>Court / Facility Schedule — MIS Barangay</title>
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

        /* Facility color tokens */
        --court-bg:  #eff6ff; --court-fg: #1d4ed8;
        --multi-bg:  #fdf4ff; --multi-fg: #7e22ce;
        --gym-bg:    #fff7ed; --gym-fg:   #c2410c;

        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select, textarea { font-family: var(--f-sans); }

    .crt-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

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
    .crt-toolbar {
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
    .btn-sm { padding: 5px 11px; font-size: 10px; }

    /* ── Search/Filter ── */
    .crt-input {
        padding: 7px 12px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13px; color: var(--ink);
        background: #fff; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .crt-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .crt-input::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }

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
    .stat-cell.s-pending  .stat-val { color: var(--warn-fg); }
    .stat-cell.s-approved .stat-val { color: var(--ok-fg); }
    .stat-cell.s-denied   .stat-val { color: var(--danger-fg); }
    .stat-cell.s-done     .stat-val { color: var(--neu-fg); }

    /* ═══════════════════════════════════════
       FACILITY AVAILABILITY STRIP
    ═══════════════════════════════════════ */
    .facility-strip {
        margin: 18px 28px 0;
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    .fac-card {
        background: var(--paper);
        border: 1px solid var(--rule);
        border-radius: 2px;
        box-shadow: var(--shadow);
        padding: 14px 18px;
        display: flex; align-items: center; gap: 14px;
        position: relative; overflow: hidden;
    }
    .fac-card::before {
        content:''; position:absolute; top:0; left:0; right:0; height:3px;
    }
    .fac-card.fac-court::before  { background: var(--court-fg); }
    .fac-card.fac-multi::before  { background: var(--multi-fg); }
    .fac-card.fac-gym::before    { background: var(--gym-fg); }
    .fac-icon {
        width: 44px; height: 44px; border-radius: 2px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .fac-court .fac-icon  { background: var(--court-bg); }
    .fac-multi .fac-icon  { background: var(--multi-bg); }
    .fac-gym   .fac-icon  { background: var(--gym-bg); }
    .fac-name { font-size: 11px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--ink-muted); margin-bottom: 3px; }
    .fac-sub  { font-size: 10.5px; color: var(--ink-faint); }
    .fac-status-badge {
        margin-left: auto; flex-shrink: 0;
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid;
    }
    .fac-status-badge.avail   { background:var(--ok-bg);   color:var(--ok-fg);   border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .fac-status-badge.booked  { background:var(--warn-bg);  color:var(--warn-fg); border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent); }

    /* ═══════════════════════════════════════
       TABLE
    ═══════════════════════════════════════ */
    .crt-table-wrap {
        margin: 18px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .crt-table-wrap .dataTables_wrapper { padding: 0; font-family: var(--f-sans); }
    .crt-table-wrap .dataTables_filter,
    .crt-table-wrap .dataTables_length { display: none; }
    .crt-table-wrap .dataTables_info {
        padding: 10px 18px; font-size: 11px; color: var(--ink-faint);
        font-family: var(--f-mono); letter-spacing: .3px;
        border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .crt-table-wrap .dataTables_paginate {
        padding: 10px 18px; border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .crt-table-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important; border-radius: 2px;
        font-size: 11px; font-weight: 600; color: var(--ink-muted) !important;
        background: #fff !important; cursor: pointer; margin: 0 2px; transition: all .13s;
    }
    .crt-table-wrap .paginate_button:hover { border-color: var(--accent) !important; color: var(--accent) !important; background: var(--accent-lt) !important; }
    .crt-table-wrap .paginate_button.current { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
    .crt-table-wrap .paginate_button.disabled { opacity: .35 !important; }

    #courtTable { width: 100% !important; border-collapse: collapse; }
    #courtTable thead th {
        padding: 10px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        cursor: pointer; user-select: none;
    }
    #courtTable thead th:hover { color: var(--accent); }
    #courtTable thead th.sorting_asc::after  { content:' ↑'; color:var(--accent); }
    #courtTable thead th.sorting_desc::after { content:' ↓'; color:var(--accent); }
    #courtTable tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    #courtTable tbody tr:last-child { border-bottom: none; }
    #courtTable tbody tr:hover { background: var(--accent-lt); }
    #courtTable td { padding: 10px 14px; font-size: 12.5px; color: var(--ink); vertical-align: middle; }

    /* Code cell */
    .td-res-code {
        font-family: var(--f-mono); font-size: 11px; font-weight: 700;
        color: var(--accent); letter-spacing: .5px; white-space: nowrap;
    }

    /* Facility badge */
    .fac-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .3px;
        text-transform: uppercase; border: 1px solid; white-space: nowrap;
    }
    .fb-basketball { background:var(--court-bg); color:var(--court-fg); border-color:color-mix(in srgb,var(--court-fg) 25%,transparent); }
    .fb-multi      { background:var(--multi-bg);  color:var(--multi-fg);  border-color:color-mix(in srgb,var(--multi-fg) 25%,transparent); }
    .fb-gym        { background:var(--gym-bg);    color:var(--gym-fg);    border-color:color-mix(in srgb,var(--gym-fg) 25%,transparent); }

    /* Status badge */
    .res-status {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid;
    }
    .res-status::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; flex-shrink:0; }
    .rs-pending   { background:var(--warn-bg);   color:var(--warn-fg);   border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent); }
    .rs-approved  { background:var(--ok-bg);     color:var(--ok-fg);     border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .rs-denied    { background:var(--danger-bg); color:var(--danger-fg); border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent); }
    .rs-completed { background:var(--neu-bg);    color:var(--neu-fg);    border-color:var(--rule); }
    .rs-cancelled { background:var(--paper-dk);  color:var(--ink-faint); border-color:var(--rule); }

    /* Time range */
    .td-time { font-family: var(--f-mono); font-size: 11px; color: var(--ink-muted); white-space: nowrap; }
    .td-date { font-family: var(--f-mono); font-size: 11.5px; color: var(--ink-muted); white-space: nowrap; }

    /* Actions */
    .td-actions { display: flex; gap: 5px; }
    .act-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 2px; font-size: 9.5px;
        font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid var(--rule-dk); font-family: var(--f-sans);
        transition: all .13s; white-space: nowrap; background: #fff; color: var(--ink-muted);
    }
    .act-view:hover   { border-color:var(--info-fg);   color:var(--info-fg);   background:var(--info-bg); }
    .act-edit:hover   { border-color:var(--accent);    color:var(--accent);    background:var(--accent-lt); }
    .act-delete:hover { border-color:var(--danger-fg); color:var(--danger-fg); background:var(--danger-bg); }

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
        border-radius: 2px !important; cursor: pointer !important; transition: all .13s !important;
    }
    .ui-dialog-buttonpane .ui-button:first-child {
        background: var(--accent) !important; border: 1.5px solid var(--accent) !important; color: #fff !important;
    }
    .ui-dialog-buttonpane .ui-button:first-child:hover { filter: brightness(1.1) !important; }
    .ui-dialog-buttonpane .ui-button:not(:first-child) {
        background: #fff !important; border: 1.5px solid var(--rule-dk) !important; color: var(--ink-muted) !important;
    }

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

    /* Conflict warning banner */
    .conflict-banner {
        padding: 10px 14px; margin: 0 18px 14px;
        background: var(--danger-bg); border: 1px solid color-mix(in srgb,var(--danger-fg) 25%,transparent);
        border-radius: 2px; font-size: 12px; color: var(--danger-fg);
        font-weight: 500; display: flex; align-items: center; gap: 8px;
    }
    .conflict-banner.hidden { display: none; }

    /* Facility selector cards inside form */
    .fac-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; }
    .fac-sel-opt {
        padding: 10px 12px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        cursor: pointer; text-align: center; transition: all .13s; background: #fff;
    }
    .fac-sel-opt:hover { border-color: var(--accent); background: var(--accent-lt); }
    .fac-sel-opt.selected { border-color: var(--accent); background: var(--accent-lt); }
    .fac-sel-opt .fso-icon { font-size: 18px; margin-bottom: 4px; }
    .fac-sel-opt .fso-label { font-size: 9.5px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase; color: var(--ink-muted); }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto crt-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Facilities Management</div>
                        <div class="doc-title">Court &amp; Facility Schedule</div>
                        <div class="doc-sub">Reservation register for barangay facilities — basketball court</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnPrintCourt">↗ Print</button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="crt-toolbar">
                    <div class="toolbar-left">
                        <input type="text"  class="crt-input" id="searchInput"     placeholder="Search by borrower, code, purpose…" style="width:260px;">
                        <input type="date"  class="crt-input" id="filterDate"      title="Filter by date">
                        <select            class="crt-input" id="filterFacility"  style="width:160px;">
                            <option value="">All Facilities</option>
                            <option value="basketball_court">🏀 Basketball Court</option>
                            <!-- <option value="multipurpose_area">🏛 Multipurpose Area</option> -->
                            <!-- <option value="gym">🏋 Gym</option> -->
                        </select>
                        <select            class="crt-input" id="filterStatus"    style="width:130px;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="denied">Denied</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;" id="resCount">
                        — RESERVATIONS
                    </div>
                </div>
            </div>

            <!-- ── Stat Ledger ── -->
            <div class="stat-ledger">
                <div class="stat-cell s-pending">
                    <div class="stat-eyebrow">Pending Review</div>
                    <div class="stat-val" id="countPending">—</div>
                    <div class="stat-sub">awaiting approval</div>
                </div>
                <div class="stat-cell s-approved">
                    <div class="stat-eyebrow">Approved</div>
                    <div class="stat-val" id="countApproved">—</div>
                    <div class="stat-sub">confirmed reservations</div>
                </div>
                <div class="stat-cell s-denied">
                    <div class="stat-eyebrow">Denied</div>
                    <div class="stat-val" id="countDenied">—</div>
                    <div class="stat-sub">rejected requests</div>
                </div>
                <div class="stat-cell s-done">
                    <div class="stat-eyebrow">Completed</div>
                    <div class="stat-val" id="countCompleted">—</div>
                    <div class="stat-sub">fulfilled reservations</div>
                </div>
            </div>

            <!-- ── Facility Availability Strip ── -->
            <div class="facility-strip">
                <div class="fac-card fac-court">
                    <div class="fac-icon">🏀</div>
                    <div>
                        <div class="fac-name">Basketball Court</div>
                        <div class="fac-sub" id="courtSub">Open court · outdoor</div>
                    </div>
                    <span class="fac-status-badge avail" id="courtBadge">Available</span>
                </div>
                <!-- <div class="fac-card fac-multi">
                    <div class="fac-icon">🏛</div>
                    <div>
                        <div class="fac-name">Multipurpose Area</div>
                        <div class="fac-sub" id="multiSub">Events · meetings · gatherings</div>
                    </div>
                    <span class="fac-status-badge avail" id="multiBadge">Available</span>
                </div>
                <div class="fac-card fac-gym">
                    <div class="fac-icon">🏋</div>
                    <div>
                        <div class="fac-name">Gym</div>
                        <div class="fac-sub" id="gymSub">Fitness · training sessions</div>
                    </div>
                    <span class="fac-status-badge avail" id="gymBadge">Available</span>
                </div> -->
            </div>

            <!-- ── Reservations Table ── -->
            <div class="crt-table-wrap">
                <table id="courtTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Facility</th>
                            <th>Borrower / Org</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Purpose</th>
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
    <div id="reservationDialog" title="New Reservation" class="hidden">
        <form id="reservationForm" class="modal-form">
            <input type="hidden" id="resId">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="form-section">
                <div class="form-section-lbl">Select Facility</div>
                <div class="form-section-body">
                    <div class="fac-selector">
                        <div class="fac-sel-opt" data-val="basketball_court">
                            <div class="fso-icon">🏀</div>
                            <div class="fso-label">Basketball Court</div>
                        </div>
                        <!-- <div class="fac-sel-opt" data-val="multipurpose_area">
                            <div class="fso-icon">🏛</div>
                            <div class="fso-label">Multipurpose</div>
                        </div>
                        <div class="fac-sel-opt" data-val="gym">
                            <div class="fso-icon">🏋</div>
                            <div class="fso-label">Gym</div>
                        </div> -->
                    </div>
                    <select id="resFacility" name="facility" class="fg-input hidden">
                        <option value="basketball_court">Basketball Court</option>
                        <!-- <option value="multipurpose_area">Multipurpose Area</option> -->
                        <!-- <option value="gym">Gym</option> -->
                    </select>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Borrower Details</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Borrower Name <span class="req">*</span></label>
                            <input type="text" id="resBorrower" class="fg-input" placeholder="Full name" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Contact No.</label>
                            <input type="text" id="resBorrowerContact" class="fg-input" placeholder="09XXXXXXXXX">
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Organization / Group</label>
                        <input type="text" id="resOrganization" class="fg-input" placeholder="e.g. Basketball League, Youth Org">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Schedule</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Date <span class="req">*</span></label>
                            <input type="date" id="resDate" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Time Start <span class="req">*</span></label>
                            <input type="time" id="resTimeStart" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Time End <span class="req">*</span></label>
                            <input type="time" id="resTimeEnd" class="fg-input">
                        </div>
                    </div>
                    <!-- Conflict warning -->
                    <div id="conflictWarning" class="conflict-banner hidden">
                        ⚠ Schedule conflict detected — another reservation exists for this facility and time slot.
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Purpose <span class="req">*</span></label>
                            <input type="text" id="resPurpose" class="fg-input" placeholder="e.g. Basketball practice, Community meeting" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select id="resStatus" class="fg-select">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="denied">Denied</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Remarks</label>
                        <input type="text" id="resRemarks" class="fg-input" placeholder="Optional remarks">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- MODAL: VIEW -->
    <div id="viewResDialog" title="Reservation Details" class="hidden">
        <div id="viewResContent" class="p-4"></div>
    </div>

    <!-- MODAL: DELETE -->
    <div id="deleteResDialog" title="Confirm Delete" class="hidden">
        <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid var(--danger-fg);background:var(--paper);">
            Delete this reservation? This action cannot be undone.
        </div>
        <input type="hidden" id="deleteResId">
    </div>

    <script src="js/index.js"></script>
    <script>
    // Facility visual selector
    $(function() {
        $('.fac-sel-opt').on('click', function() {
            $('.fac-sel-opt').removeClass('selected');
            $(this).addClass('selected');
            $('#resFacility').val($(this).data('val'));
            // trigger conflict check
            $('#resFacility, #resDate, #resTimeStart, #resTimeEnd').trigger('change');
        });
        // Set default
        $('.fac-sel-opt[data-val="basketball_court"]').addClass('selected');

        // Update facility availability badges from counts
        function updateFacilityBadges(data) {
            const today = new Date().toISOString().split('T')[0];
            // Quick check if any approved reservation for today per facility
            ['basketball_court','multipurpose_area','gym'].forEach(fac => {
                const hasToday = (data || []).some(r => r.reservation_date === today && r.facility === fac && r.status === 'approved');
                const idMap = { basketball_court:'court', multipurpose_area:'multi', gym:'gym' };
                const k = idMap[fac];
                if (hasToday) {
                    $(`#${k}Badge`).text('Booked Today').removeClass('avail').addClass('booked');
                } else {
                    $(`#${k}Badge`).text('Available').removeClass('booked').addClass('avail');
                }
            });
        }
        // Hook into the existing table ajax callback
        $(document).on('courtDataLoaded', function(e, data) { updateFacilityBadges(data); });
    });
    </script>
</body>
</html>
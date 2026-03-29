<?php
require_once __DIR__ . '/../../includes/app.php';
requireSecretary();
$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tanod Duty Schedule — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
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
        --ok-bg:     #edfaf3; --ok-fg:     #1a5c35;
        --warn-bg:   #fef9ec; --warn-fg:   #7a5700;
        --info-bg:   #edf3fa; --info-fg:   #1a3a5c;
        --danger-bg: #fdeeed; --danger-fg: #7a1f1a;
        --neu-bg:    #f3f1ec; --neu-fg:    #5a5a5a;
        --morning-bg:#fffbeb; --morning-fg:#92400e;
        --afternoon-bg:#eff6ff; --afternoon-fg:#1e40af;
        --night-bg:  #f5f3ff; --night-fg:  #4c1d95;
        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select, textarea { font-family: var(--f-sans); }

    .td-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

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
    .td-toolbar {
        background: var(--paper-lt); border-bottom: 3px solid var(--accent);
        padding: 12px 28px; display: flex; align-items: center;
        justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .toolbar-left  { display: flex; gap: 8px; align-items: center; }

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

    /* ── Search / Filter ── */
    .td-input {
        padding: 7px 12px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13px; color: var(--ink);
        background: #fff; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .td-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .td-input::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }

    /* ═══════════════════════════════════════
       SHIFT LEDGER (3 cells)
    ═══════════════════════════════════════ */
    .shift-ledger {
        display: grid; grid-template-columns: repeat(3, 1fr);
        margin: 22px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .shift-cell {
        padding: 20px 22px; border-right: 1px solid var(--rule);
        display: flex; align-items: center; gap: 16px;
        position: relative; transition: background .12s; overflow: hidden;
    }
    .shift-cell:last-child { border-right: none; }
    .shift-cell::before {
        content:''; position:absolute; top:0; left:0; right:0; height:3px;
    }
    .shift-cell.morning::before   { background: #f59e0b; }
    .shift-cell.afternoon::before { background: #3b82f6; }
    .shift-cell.night::before     { background: #7c3aed; }
    .shift-icon {
        width: 48px; height: 48px; border-radius: 2px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
    }
    .shift-cell.morning   .shift-icon { background: var(--morning-bg); }
    .shift-cell.afternoon .shift-icon { background: var(--afternoon-bg); }
    .shift-cell.night     .shift-icon { background: var(--night-bg); }
    .shift-info {}
    .shift-eyebrow {
        font-size: 8px; font-weight: 700; letter-spacing: 1.4px;
        text-transform: uppercase; color: var(--ink-faint); margin-bottom: 6px;
    }
    .shift-val {
        font-family: var(--f-mono); font-size: 28px; font-weight: 600;
        color: var(--ink); line-height: 1; margin-bottom: 3px; letter-spacing: -1px;
    }
    .shift-sub { font-size: 10.5px; color: var(--ink-faint); }

    /* ═══════════════════════════════════════
       TABLE
    ═══════════════════════════════════════ */
    .td-table-wrap {
        margin: 18px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .td-table-wrap .dataTables_wrapper { padding: 0; font-family: var(--f-sans); }
    .td-table-wrap .dataTables_filter,
    .td-table-wrap .dataTables_length { display: none; }
    .td-table-wrap .dataTables_info {
        padding: 10px 18px; font-size: 11px; color: var(--ink-faint);
        font-family: var(--f-mono); letter-spacing: .3px;
        border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .td-table-wrap .dataTables_paginate {
        padding: 10px 18px; border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .td-table-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important; border-radius: 2px;
        font-size: 11px; font-weight: 600;
        color: var(--ink-muted) !important; background: #fff !important;
        cursor: pointer; margin: 0 2px; transition: all .13s;
    }
    .td-table-wrap .paginate_button:hover { border-color: var(--accent) !important; color: var(--accent) !important; background: var(--accent-lt) !important; }
    .td-table-wrap .paginate_button.current { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
    .td-table-wrap .paginate_button.disabled { opacity: .35 !important; }

    #dutyTable { width: 100% !important; border-collapse: collapse; }
    #dutyTable thead th {
        padding: 10px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        cursor: pointer; user-select: none;
    }
    #dutyTable thead th:hover { color: var(--accent); }
    #dutyTable thead th.sorting_asc::after  { content:' ↑'; color:var(--accent); }
    #dutyTable thead th.sorting_desc::after { content:' ↓'; color:var(--accent); }
    #dutyTable tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    #dutyTable tbody tr:last-child { border-bottom: none; }
    #dutyTable tbody tr:hover { background: var(--accent-lt); }
    #dutyTable td { padding: 11px 14px; font-size: 12.5px; color: var(--ink); vertical-align: middle; }

    /* Duty code */
    .td-duty-code {
        font-family: var(--f-mono); font-size: 11px; font-weight: 700;
        color: var(--accent); letter-spacing: .5px; white-space: nowrap;
    }
    /* Tanod name */
    .td-name { font-weight: 600; font-size: 13px; }
    /* Date */
    .td-date { font-family: var(--f-mono); font-size: 11.5px; color: var(--ink-muted); white-space: nowrap; }

    /* Shift badges */
    .shift-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .4px;
        text-transform: uppercase; border: 1px solid; white-space: nowrap;
    }
    .sb-morning   { background: var(--morning-bg);   color: var(--morning-fg);   border-color: color-mix(in srgb,var(--morning-fg) 25%,transparent); }
    .sb-afternoon { background: var(--afternoon-bg); color: var(--afternoon-fg); border-color: color-mix(in srgb,var(--afternoon-fg) 25%,transparent); }
    .sb-night     { background: var(--night-bg);     color: var(--night-fg);     border-color: color-mix(in srgb,var(--night-fg) 25%,transparent); }

    /* Status badges */
    .status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid;
    }
    .status-badge::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; flex-shrink:0; }
    .ss-active    { background: var(--ok-bg);     color: var(--ok-fg);     border-color: color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .ss-completed { background: var(--neu-bg);    color: var(--neu-fg);    border-color: var(--rule); }
    .ss-cancelled { background: var(--danger-bg); color: var(--danger-fg); border-color: color-mix(in srgb,var(--danger-fg) 25%,transparent); }

    /* Actions */
    .td-actions { display: flex; gap: 5px; }
    .act-btn {
        display: inline-flex; align-items: center;
        padding: 4px 10px; border-radius: 2px; font-size: 9.5px;
        font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid var(--rule-dk);
        font-family: var(--f-sans); transition: all .13s;
        background: #fff; color: var(--ink-muted); white-space: nowrap;
    }
    .act-view:hover   { border-color: var(--info-fg);   color: var(--info-fg);   background: var(--info-bg); }
    .act-edit:hover   { border-color: var(--accent);    color: var(--accent);    background: var(--accent-lt); }
    .act-delete:hover { border-color: var(--danger-fg); color: var(--danger-fg); background: var(--danger-bg); }

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
        background: rgba(255,255,255,.15) !important; border: 1px solid rgba(255,255,255,.25) !important;
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
    .modal-form { max-height: 68vh; overflow-y: auto; }
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

    /* ═══════════════════════════════════════
       VIEW MODAL — Duty Record
    ═══════════════════════════════════════ */
    .duty-header {
        display: flex; align-items: center; gap: 18px;
        padding: 18px 22px;
        background: linear-gradient(to right, var(--accent-lt), var(--paper));
        border-bottom: 1px solid var(--rule);
    }
    .duty-code-block {
        background: var(--accent); color: #fff;
        padding: 10px 16px; border-radius: 2px; text-align: center; flex-shrink: 0;
    }
    .duty-code-lbl { font-size: 7.5px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; opacity: .65; margin-bottom: 4px; }
    .duty-code-val { font-family: var(--f-mono); font-size: 14px; font-weight: 700; letter-spacing: .5px; }
    .duty-name-block {}
    .duty-tanod-name {
        font-family: var(--f-serif); font-size: 17px; font-weight: 600;
        color: var(--ink); margin-bottom: 5px;
    }
    .duty-meta { font-size: 11px; color: var(--ink-faint); }
    .duty-meta span { margin-right: 10px; }

    /* View detail grid */
    .view-detail-grid {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 0; padding: 18px 22px;
    }
    .vd-row { margin-bottom: 12px; padding-right: 20px; }
    .vd-lbl {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.1px;
        text-transform: uppercase; color: var(--ink-faint); margin-bottom: 3px;
    }
    .vd-val { font-size: 13px; font-weight: 500; color: var(--ink); line-height: 1.5; }
    .vd-notes { grid-column: 1 / -1; padding-right: 0; }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto td-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Public Safety</div>
                        <div class="doc-title">Tanod Duty Schedule</div>
                        <div class="doc-sub">Daily and weekly duty roster for Barangay Tanod personnel</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnPrintDuty">↗ Print</button>
                        <button class="btn btn-primary" id="btnNewDuty">+ Assign Duty</button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="td-toolbar">
                    <div class="toolbar-left">
                        <input type="text"  class="td-input" id="searchInput"  placeholder="Search tanod name, post, or duty code…" style="width:260px;">
                        <input type="date"  class="td-input" id="filterDate"   title="Filter by date">
                        <select            class="td-input" id="filterShift"  style="width:130px;">
                            <option value="">All Shifts</option>
                            <option value="morning">☀ Morning</option>
                            <option value="afternoon">🌤 Afternoon</option>
                            <option value="night">🌙 Night</option>
                        </select>
                    </div>
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;" id="dutyCount">
                        — ASSIGNMENTS
                    </div>
                </div>
            </div>

            <!-- ── Shift Ledger ── -->
            <div class="shift-ledger">
                <div class="shift-cell morning">
                    <div class="shift-icon">☀️</div>
                    <div class="shift-info">
                        <div class="shift-eyebrow">Morning Shift</div>
                        <div class="shift-val" id="cntMorning">—</div>
                        <div class="shift-sub">6:00 AM – 2:00 PM · Today</div>
                    </div>
                </div>
                <div class="shift-cell afternoon">
                    <div class="shift-icon">🌤️</div>
                    <div class="shift-info">
                        <div class="shift-eyebrow">Afternoon Shift</div>
                        <div class="shift-val" id="cntAfternoon">—</div>
                        <div class="shift-sub">2:00 PM – 10:00 PM · Today</div>
                    </div>
                </div>
                <div class="shift-cell night">
                    <div class="shift-icon">🌙</div>
                    <div class="shift-info">
                        <div class="shift-eyebrow">Night Shift</div>
                        <div class="shift-val" id="cntNight">—</div>
                        <div class="shift-sub">10:00 PM – 6:00 AM · Today</div>
                    </div>
                </div>
            </div>

            <!-- ── Duty Table ── -->
            <div class="td-table-wrap">
                <table id="dutyTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Duty Code</th>
                            <th>Tanod Name</th>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Post / Location</th>
                            <th>Status</th>
                            <th>Assigned By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ════════════════════════════
         MODAL: ASSIGN / EDIT DUTY
    ════════════════════════════ -->
    <div id="dutyModal" title="Assign Duty Schedule" class="hidden">
        <form id="dutyForm" class="modal-form">
            <input type="hidden" name="id" id="dutyId">

            <div class="form-section">
                <div class="form-section-lbl">Personnel &amp; Schedule</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Tanod Name <span class="req">*</span></label>
                            <input type="text" name="tanod_name" id="dutyTanodName" class="fg-input" required autocomplete="off" placeholder="Full name of tanod officer">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Duty Date <span class="req">*</span></label>
                            <input type="date" name="duty_date" id="dutyDate" class="fg-input" required>
                        </div>
                    </div>
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Shift <span class="req">*</span></label>
                            <select name="shift" id="dutyShift" class="fg-select">
                                <option value="morning">☀️ Morning (6AM–2PM)</option>
                                <option value="afternoon">🌤️ Afternoon (2PM–10PM)</option>
                                <option value="night">🌙 Night (10PM–6AM)</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select name="status" id="dutyStatus" class="fg-select">
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Post / Location</label>
                            <input type="text" name="post_location" id="dutyPost" class="fg-input" autocomplete="off" placeholder="e.g., Main Gate">
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Notes / Instructions</label>
                        <textarea name="notes" id="dutyNotes" class="fg-textarea" placeholder="Patrol instructions, special assignments…"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════
         MODAL: VIEW DUTY RECORD
    ════════════════════════════ -->
    <div id="viewDutyModal" title="Duty Record" class="hidden">
        <div class="duty-header">
            <div class="duty-code-block">
                <div class="duty-code-lbl">Duty Code</div>
                <div class="duty-code-val" id="vd-code">—</div>
            </div>
            <div class="duty-name-block">
                <div class="duty-tanod-name" id="vd-name">—</div>
                <div class="duty-meta">
                    <span id="vd-date"></span>
                    <span id="vd-shift-badge"></span>
                    <span id="vd-status-badge"></span>
                </div>
            </div>
        </div>
        <div class="view-detail-grid">
            <div class="vd-row">
                <div class="vd-lbl">Post / Location</div>
                <div class="vd-val" id="vd-post">—</div>
            </div>
            <div class="vd-row">
                <div class="vd-lbl">Assigned By</div>
                <div class="vd-val" id="vd-by">—</div>
            </div>
            <div class="vd-row">
                <div class="vd-lbl">Date Created</div>
                <div class="vd-val" id="vd-created">—</div>
            </div>
            <div class="vd-row">
                <div class="vd-lbl">Last Updated</div>
                <div class="vd-val" id="vd-updated">—</div>
            </div>
            <div class="vd-row vd-notes">
                <div class="vd-lbl">Notes</div>
                <div class="vd-val" id="vd-notes" style="font-style:italic;color:var(--ink-muted);">—</div>
            </div>
        </div>
    </div>

    <script src="js/index.js"></script>
</body>
</html>
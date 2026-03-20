<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Immunization — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --paper:      #fdfcf9;
        --paper-lt:   #f9f7f3;
        --paper-dk:   #f0ede6;
        --ink:        #1a1a1a;
        --ink-muted:  #5a5a5a;
        --ink-faint:  #a0a0a0;
        --rule:       #d8d4cc;
        --rule-dk:    #b8b4ac;
        --bg:         #edeae4;
        --accent:     var(--theme-primary, #2d5a27);
        --accent-lt:  color-mix(in srgb, var(--accent) 8%, white);
        --ok-bg:      #edfaf3; --ok-fg:     #1a5c35;
        --warn-bg:    #fef9ec; --warn-fg:   #7a5700;
        --danger-bg:  #fdeeed; --danger-fg: #7a1f1a;
        --info-bg:    #edf3fa; --info-fg:   #1a3a5c;
        --neu-bg:     #f3f1ec; --neu-fg:    #5a5a5a;
        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 14px rgba(0,0,0,.05);
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body, input, button, select, textarea { font-family:var(--f-sans); }
    .imm-page { background:var(--bg); min-height:100%; padding-bottom:56px; }

    /* ── Doc header ── */
    .doc-header { background:var(--paper); border-bottom:1px solid var(--rule); }
    .doc-header-inner {
        padding:20px 28px 18px;
        display:flex; align-items:flex-end; justify-content:space-between; gap:20px; flex-wrap:wrap;
    }
    .doc-eyebrow {
        font-size:8.5px; font-weight:700; letter-spacing:1.8px;
        text-transform:uppercase; color:var(--ink-faint);
        display:flex; align-items:center; gap:8px; margin-bottom:6px;
    }
    .doc-eyebrow::before { content:''; width:18px; height:2px; background:var(--accent); display:inline-block; }
    .doc-title {
        font-family:var(--f-serif); font-size:22px; font-weight:700;
        color:var(--ink); letter-spacing:-.3px; margin-bottom:3px;
    }
    .doc-sub { font-size:12px; color:var(--ink-faint); font-style:italic; }
    .doc-accent-bar { height:3px; background:linear-gradient(to right, var(--accent), transparent); }

    /* ════════════════════════════════
       TWO-PANE LAYOUT
    ════════════════════════════════ */
    .imm-layout {
        display:grid;
        grid-template-columns: 260px 1fr;
        gap:0;
        margin:22px 28px 0;
        background:var(--paper);
        border:1px solid var(--rule); border-top:3px solid var(--accent);
        border-radius:2px; box-shadow:var(--shadow);
        overflow:hidden; min-height:600px;
    }

    /* ── Left pane: resident list ── */
    .res-pane {
        border-right:1px solid var(--rule);
        display:flex; flex-direction:column;
        background:var(--paper-lt);
    }
    .res-pane-head {
        padding:12px 14px; border-bottom:1px solid var(--rule);
        background:var(--paper);
    }
    .res-pane-title {
        font-size:8px; font-weight:700; letter-spacing:1.5px;
        text-transform:uppercase; color:var(--ink-faint);
        margin-bottom:8px; display:flex; align-items:center; gap:8px;
    }
    .res-pane-title::after { content:''; flex:1; height:1px; background:var(--rule); }
    .res-search {
        width:100%; padding:7px 10px;
        border:1.5px solid var(--rule-dk); border-radius:2px;
        font-size:12.5px; color:var(--ink); background:#fff; outline:none;
        transition:border-color .14s, box-shadow .14s;
    }
    .res-search:focus {
        border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
    }
    .res-search::placeholder { color:var(--ink-faint); font-style:italic; font-size:11.5px; }
    .res-list {
        flex:1; overflow-y:auto;
    }
    .res-item {
        padding:10px 14px; cursor:pointer;
        border-bottom:1px solid #f0ede8;
        transition:background .1s;
        border-left:3px solid transparent;
    }
    .res-item:last-child { border-bottom:none; }
    .res-item:hover { background:var(--accent-lt); }
    .res-item.selected {
        background:var(--accent-lt);
        border-left-color:var(--accent);
    }
    .res-item-name { font-weight:600; font-size:12.5px; color:var(--ink); }
    .res-empty {
        padding:24px 14px; text-align:center;
        color:var(--ink-faint); font-size:12px; font-style:italic;
    }

    /* ── Right pane ── */
    .rec-pane {
        display:flex; flex-direction:column;
        min-width:0;
    }

    /* Records header */
    .rec-pane-head {
        padding:12px 18px; border-bottom:1px solid var(--rule);
        background:var(--paper);
        display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .rec-pane-title {
        font-size:8px; font-weight:700; letter-spacing:1.5px;
        text-transform:uppercase; color:var(--ink-muted);
        display:flex; align-items:center; gap:8px;
    }
    .rec-pane-title::before { content:''; display:inline-block; width:3px; height:12px; background:var(--accent); border-radius:1px; flex-shrink:0; }
    .rec-selected-name {
        font-family:var(--f-serif); font-size:15px; font-weight:600;
        color:var(--ink); margin-left:4px;
    }

    /* ── Records table ── */
    .rec-table-wrap { flex:0; }
    #immRecordsTable { width:100% !important; border-collapse:collapse; font-size:12.5px; }
    #immRecordsTable thead th {
        padding:9px 14px; background:var(--paper-lt); text-align:left;
        font-size:8.5px; font-weight:700; letter-spacing:1.1px;
        text-transform:uppercase; color:var(--ink-muted);
        border-bottom:1px solid var(--rule-dk); white-space:nowrap;
    }
    #immRecordsTable tbody tr { border-bottom:1px solid #f0ede8; transition:background .1s; }
    #immRecordsTable tbody tr:last-child { border-bottom:none; }
    #immRecordsTable tbody tr:hover { background:var(--paper-lt); }
    #immRecordsTable td { padding:9px 14px; vertical-align:middle; }
    .imm-vaccine { font-weight:600; font-size:12.5px; color:var(--ink); }
    .imm-dose    { font-size:10.5px; color:var(--ink-faint); margin-top:1px; font-style:italic; }
    .imm-date    { font-family:var(--f-mono); font-size:11.5px; color:var(--ink-muted); white-space:nowrap; }
    .imm-next    { font-family:var(--f-mono); font-size:11.5px; color:var(--ink-muted); white-space:nowrap; }
    /* status */
    .imm-status {
        display:inline-flex; align-items:center; gap:4px;
        padding:3px 9px; border-radius:2px;
        font-size:9px; font-weight:700; letter-spacing:.5px;
        text-transform:uppercase; border:1px solid;
    }
    .imm-status::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; flex-shrink:0; }
    .iss-ok  { background:var(--ok-bg);   color:var(--ok-fg);    border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .iss-due { background:var(--warn-bg); color:var(--warn-fg);  border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent); }
    .iss-unk { background:var(--neu-bg);  color:var(--neu-fg);   border-color:var(--rule); }

    /* ── No selection state ── */
    .no-selection {
        flex:1; display:flex; flex-direction:column;
        align-items:center; justify-content:center;
        padding:48px 24px; text-align:center;
        color:var(--ink-faint);
    }
    .no-selection-icon { font-size:40px; margin-bottom:14px; opacity:.4; }
    .no-selection-text { font-size:13px; font-style:italic; }

    /* ── Add form ── */
    .add-form-wrap {
        border-top:1px solid var(--rule);
        background:var(--paper-lt);
        padding:18px 20px;
    }
    .add-form-title {
        font-size:8px; font-weight:700; letter-spacing:1.5px;
        text-transform:uppercase; color:var(--ink-faint);
        display:flex; align-items:center; gap:8px; margin-bottom:14px;
    }
    .add-form-title::after { content:''; flex:1; height:1px; background:var(--rule); }
    .add-form-grid {
        display:grid;
        grid-template-columns: 1fr 1fr 1fr 1fr auto;
        gap:10px; align-items:end;
    }
    .fg-mini { }
    .fg-mini label {
        display:block; font-size:8.5px; font-weight:700;
        letter-spacing:1.1px; text-transform:uppercase;
        color:var(--ink-muted); margin-bottom:5px;
    }
    .fg-input {
        width:100%; padding:8px 11px;
        border:1.5px solid var(--rule-dk); border-radius:2px;
        font-family:var(--f-sans); font-size:12.5px; color:var(--ink);
        background:#fff; outline:none; transition:border-color .14s, box-shadow .14s;
    }
    .fg-input:focus {
        border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
    }
    .fg-input::placeholder { color:var(--ink-faint); font-style:italic; font-size:11.5px; }
    .fg-input:disabled { background:var(--paper-dk); color:var(--ink-faint); cursor:not-allowed; }
    .add-form-notes {
        margin-top:10px;
    }
    .notes-label {
        display:block; font-size:8.5px; font-weight:700;
        letter-spacing:1.1px; text-transform:uppercase;
        color:var(--ink-muted); margin-bottom:5px;
    }
    .fg-textarea {
        width:100%; padding:8px 11px;
        border:1.5px solid var(--rule-dk); border-radius:2px;
        font-family:var(--f-sans); font-size:12.5px; color:var(--ink);
        background:#fff; outline:none; resize:vertical; min-height:62px;
        transition:border-color .14s, box-shadow .14s;
    }
    .fg-textarea:focus {
        border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
    }
    .fg-textarea::placeholder { color:var(--ink-faint); font-style:italic; font-size:11.5px; }
    .fg-textarea:disabled { background:var(--paper-dk); color:var(--ink-faint); cursor:not-allowed; }

    /* Submit button */
    .btn-save {
        padding:8px 18px; border-radius:2px;
        background:var(--accent); border:1.5px solid var(--accent);
        color:#fff; font-size:11px; font-weight:700;
        letter-spacing:.5px; text-transform:uppercase;
        cursor:pointer; font-family:var(--f-sans);
        transition:filter .13s; white-space:nowrap;
        align-self:end;
    }
    .btn-save:hover   { filter:brightness(1.1); }
    .btn-save:disabled { opacity:.5; cursor:not-allowed; filter:none; }
    .btn-print {
        padding:7px 14px; border-radius:2px;
        background:#fff; border:1.5px solid var(--rule-dk);
        color:var(--ink-muted); font-size:11px; font-weight:700;
        letter-spacing:.4px; text-transform:uppercase;
        cursor:pointer; font-family:var(--f-sans); transition:all .13s;
    }
    .btn-print:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-lt); }
    .btn-print:disabled { opacity:.4; cursor:not-allowed; }

    /* DT overrides (minimal — no wrapper pagination needed here) */
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate { display:none !important; }

    /* Dialog overrides */
    .ui-dialog {
        border:1px solid var(--rule-dk) !important; border-radius:2px !important;
        box-shadow:0 8px 48px rgba(0,0,0,.18) !important;
        padding:0 !important; font-family:var(--f-sans) !important;
    }
    .ui-dialog-titlebar {
        background:var(--accent) !important; border:none !important;
        padding:12px 16px !important;
    }
    .ui-dialog-title {
        font-family:var(--f-sans) !important; font-size:11px !important;
        font-weight:700 !important; letter-spacing:1px !important;
        text-transform:uppercase !important; color:#fff !important;
    }
    .ui-dialog-titlebar-close {
        background:rgba(255,255,255,.15) !important;
        border:1px solid rgba(255,255,255,.25) !important;
        border-radius:2px !important; color:#fff !important;
        width:24px !important; height:24px !important;
        top:50% !important; transform:translateY(-50%) !important;
    }
    .ui-dialog-content { padding:0 !important; }
    .ui-dialog-buttonpane {
        background:var(--paper-lt) !important; border-top:1px solid var(--rule) !important;
        padding:12px 16px !important; margin:0 !important;
    }
    .ui-dialog-buttonpane .ui-button {
        font-family:var(--f-sans) !important; font-size:11px !important;
        font-weight:700 !important; letter-spacing:.5px !important;
        text-transform:uppercase !important; padding:7px 18px !important;
        border-radius:2px !important; cursor:pointer !important;
    }
    .ui-dialog-buttonpane .ui-button:first-child {
        background:var(--accent) !important; border:1.5px solid var(--accent) !important; color:#fff !important;
    }
    .ui-dialog-buttonpane .ui-button:first-child:hover { filter:brightness(1.1) !important; }
    .ui-dialog-buttonpane .ui-button:not(:first-child) {
        background:#fff !important; border:1.5px solid var(--rule-dk) !important; color:var(--ink-muted) !important;
    }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto imm-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Health Center</div>
                        <div class="doc-title">Immunization Records</div>
                        <div class="doc-sub">Vaccine administration and schedule tracking per resident</div>
                    </div>
                    <button id="btnPrintReport" class="btn-print" disabled>↗ Print Report</button>
                </div>
            </div>
            <div class="doc-accent-bar"></div>

            <!-- ════════════════════════════════
                 TWO-PANE LAYOUT
            ════════════════════════════════ -->
            <div class="imm-layout">

                <!-- ── Left: Resident List ── -->
                <div class="res-pane">
                    <div class="res-pane-head">
                        <div class="res-pane-title">Residents</div>
                        <input type="text" class="res-search" id="immResSearch" placeholder="Search residents…">
                    </div>
                    <div class="res-list" id="immResList">
                        <div class="res-empty">Loading residents…</div>
                    </div>
                </div>

                <!-- ── Right: Records + Form ── -->
                <div class="rec-pane">

                    <!-- Records header -->
                    <div class="rec-pane-head">
                        <div>
                            <div class="rec-pane-title">
                                Immunization Records for
                                <span class="rec-selected-name" id="immSelectedName">—</span>
                            </div>
                        </div>
                        <button class="btn-print" id="btnPrint" disabled>↗ Print</button>
                    </div>

                    <!-- No selection state -->
                    <div class="no-selection" id="immNoSelection">
                        <div class="no-selection-icon">💉</div>
                        <div class="no-selection-text">Select a resident from the list<br>to view and manage immunization records.</div>
                    </div>

                    <!-- Records table (hidden until a resident is selected) -->
                    <div class="rec-table-wrap" id="immTableWrap" style="display:none;">
                        <table id="immRecordsTable" class="display" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Vaccine</th>
                                    <th>Date Given</th>
                                    <th>Next Schedule</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="immRecordsTbody"></tbody>
                        </table>
                    </div>

                    <!-- Add form (hidden until a resident is selected) -->
                    <div class="add-form-wrap" id="immAddWrap" style="display:none;">
                        <div class="add-form-title">Add New Immunization</div>
                        <form id="immAddForm">
                            <input type="hidden" name="action"      value="add_immunization">
                            <input type="hidden" name="resident_id" id="immResidentId">
                            <div class="add-form-grid">
                                <div class="fg-mini">
                                    <label>Vaccine Type <span style="color:var(--danger-fg);">*</span></label>
                                    <input type="text" name="vaccine_name" id="immVaccineName" class="fg-input"
                                           placeholder="e.g. BCG, Measles, COVID-19…" autocomplete="off">
                                </div>
                                <div class="fg-mini">
                                    <label>Dose</label>
                                    <input type="text" name="dose" id="immDose" class="fg-input"
                                           placeholder="e.g. Dose 1, Booster…" autocomplete="off">
                                </div>
                                <div class="fg-mini">
                                    <label>Date Given <span style="color:var(--danger-fg);">*</span></label>
                                    <input type="text" name="date_given" id="immDateGiven" class="fg-input" placeholder="mm/dd/yyyy">
                                </div>
                                <div class="fg-mini">
                                    <label>Next Due Date</label>
                                    <input type="text" name="next_schedule" id="immNextSchedule" class="fg-input" placeholder="mm/dd/yyyy">
                                </div>
                                <button type="submit" class="btn-save" id="immSaveBtn" disabled>+ Add</button>
                            </div>
                            <div class="add-form-notes">
                                <label class="notes-label">Notes / Remarks</label>
                                <textarea name="remarks" id="immRemarks" class="fg-textarea"
                                          placeholder="Adverse reactions, observations…"></textarea>
                            </div>
                        </form>
                    </div>

                </div><!-- /rec-pane -->
            </div><!-- /imm-layout -->

        </main>
    </div>

    <script src="js/index.js"></script>
</body>
</html>
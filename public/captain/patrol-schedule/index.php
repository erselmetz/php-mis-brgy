<?php
/**
 * Mobile Patrol / Roving Tanod Schedule — Redesigned
 * Matches the government-official design system used across the app
 */

require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();
$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patrol Schedule — MIS Barangay</title>
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
        --weekly-bg: #f5f3ff; --weekly-fg: #4c1d95;
        --ongoing-bg:#fffbeb; --ongoing-fg:#92400e;

        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select, textarea { font-family: var(--f-sans); }

    .ptrl-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

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
    .ptrl-toolbar {
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
    .ptrl-input {
        padding: 7px 12px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13px; color: var(--ink);
        background: #fff; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .ptrl-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent); }
    .ptrl-input::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }

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
    .s-scheduled .stat-val { color: var(--info-fg); }
    .s-ongoing   .stat-val { color: var(--ongoing-fg); }
    .s-completed .stat-val { color: var(--ok-fg); }
    .s-weekly    .stat-val { color: var(--weekly-fg); }

    /* ═══════════════════════════════════════
       TABLE
    ═══════════════════════════════════════ */
    .ptrl-table-wrap {
        margin: 18px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px; box-shadow: var(--shadow); overflow: hidden;
    }
    .ptrl-table-wrap .dataTables_wrapper { padding: 0; font-family: var(--f-sans); }
    .ptrl-table-wrap .dataTables_filter,
    .ptrl-table-wrap .dataTables_length  { display: none; }
    .ptrl-table-wrap .dataTables_info {
        padding: 10px 18px; font-size: 11px; color: var(--ink-faint);
        font-family: var(--f-mono); letter-spacing: .3px;
        border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .ptrl-table-wrap .dataTables_paginate {
        padding: 10px 18px; border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .ptrl-table-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important; border-radius: 2px;
        font-size: 11px; font-weight: 600; color: var(--ink-muted) !important;
        background: #fff !important; cursor: pointer; margin: 0 2px; transition: all .13s;
    }
    .ptrl-table-wrap .paginate_button:hover { border-color: var(--accent) !important; color: var(--accent) !important; background: var(--accent-lt) !important; }
    .ptrl-table-wrap .paginate_button.current { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
    .ptrl-table-wrap .paginate_button.disabled { opacity: .35 !important; }

    #patrolTable { width: 100% !important; border-collapse: collapse; }
    #patrolTable thead th {
        padding: 10px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        cursor: pointer; user-select: none;
    }
    #patrolTable thead th:hover { color: var(--accent); }
    #patrolTable thead th.sorting_asc::after  { content:' ↑'; color:var(--accent); }
    #patrolTable thead th.sorting_desc::after { content:' ↓'; color:var(--accent); }
    #patrolTable tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    #patrolTable tbody tr:last-child { border-bottom: none; }
    #patrolTable tbody tr:hover { background: var(--accent-lt); }
    #patrolTable td { padding: 10px 14px; font-size: 12.5px; color: var(--ink); vertical-align: middle; }

    .td-patrol-code { font-family:var(--f-mono); font-size:11px; font-weight:700; color:var(--accent); letter-spacing:.5px; white-space:nowrap; }
    .td-team-name   { font-weight:600; font-size:13px; }
    .td-date        { font-family:var(--f-mono); font-size:11.5px; color:var(--ink-muted); white-space:nowrap; }
    .td-time        { font-family:var(--f-mono); font-size:11px; color:var(--ink-muted); white-space:nowrap; }

    /* Member tags */
    .member-tag {
        display: inline-block; padding: 1.5px 7px;
        background: var(--paper-dk); border: 1px solid var(--rule);
        border-radius: 2px; font-size: 10px; color: var(--ink-muted);
        margin: 1px; white-space: nowrap;
    }

    /* Route/area */
    .td-route { font-size: 11.5px; color: var(--ink-muted); }

    /* Type badges */
    .type-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9px; font-weight: 700; letter-spacing: .4px;
        text-transform: uppercase; border: 1px solid; white-space: nowrap;
    }
    .tb-weekly   { background:var(--weekly-bg); color:var(--weekly-fg); border-color:color-mix(in srgb,var(--weekly-fg) 25%,transparent); }
    .tb-onetime  { background:var(--paper-dk);  color:var(--ink-muted); border-color:var(--rule); }

    /* Status badges */
    .ptrl-status {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid;
    }
    .ptrl-status::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; flex-shrink:0; }
    .ps-scheduled { background:var(--info-bg);    color:var(--info-fg);    border-color:color-mix(in srgb,var(--info-fg) 25%,transparent); }
    .ps-ongoing   { background:var(--ongoing-bg); color:var(--ongoing-fg); border-color:color-mix(in srgb,var(--ongoing-fg) 25%,transparent); }
    .ps-completed { background:var(--ok-bg);      color:var(--ok-fg);      border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .ps-cancelled { background:var(--neu-bg);     color:var(--neu-fg);     border-color:var(--rule); }

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
    .fg-textarea { resize:vertical; min-height:64px; }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }

    /* Weekly toggle */
    .weekly-toggle-row { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
    .toggle-label { font-size:11px; font-weight:600; color:var(--ink-muted); }
    .weekly-indicator {
        display:inline-block; padding:2px 8px; border-radius:2px;
        font-size:9px; font-weight:700; letter-spacing:.5px;
        text-transform:uppercase;
        background:var(--weekly-bg); color:var(--weekly-fg);
        border:1px solid color-mix(in srgb,var(--weekly-fg) 25%,transparent);
    }

    /* View modal detail rows */
    .vd-section { padding: 14px 18px; border-bottom: 1px solid var(--rule); }
    .vd-section:last-child { border-bottom: none; }
    .vd-section-title {
        font-size:8px; font-weight:700; letter-spacing:1.4px; text-transform:uppercase;
        color:var(--ink-faint); margin-bottom:10px; display:flex; align-items:center; gap:8px;
    }
    .vd-section-title::after { content:''; flex:1; height:1px; background:var(--rule); }
    .vd-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .vd-item {}
    .vd-lbl { font-size:8.5px; font-weight:700; letter-spacing:1.1px; text-transform:uppercase; color:var(--ink-faint); margin-bottom:3px; }
    .vd-val { font-size:12.5px; font-weight:500; color:var(--ink); }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto ptrl-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Public Safety</div>
                        <div class="doc-title">Mobile Patrol Schedule</div>
                        <div class="doc-sub">Patrol route register for Barangay Tanod — roving teams, time blocks, and area coverage</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnPrintPatrol">↗ Print</button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="ptrl-toolbar">
                    <div class="toolbar-left">
                        <input type="text"  class="ptrl-input" id="searchInput"   placeholder="Search team, route, or patrol code…" style="width:260px;">
                        <input type="date"  class="ptrl-input" id="filterDate"    title="Filter by date">
                        <select            class="ptrl-input" id="filterStatus"  style="width:135px;">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <select            class="ptrl-input" id="filterWeekly"  style="width:130px;">
                            <option value="">All Types</option>
                            <option value="0">One-time</option>
                            <option value="1">🔁 Weekly</option>
                        </select>
                    </div>
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;" id="patrolCount">
                        — PATROLS
                    </div>
                </div>
            </div>

            <!-- ── Stat Ledger ── -->
            <div class="stat-ledger">
                <div class="stat-cell s-scheduled">
                    <div class="stat-eyebrow">Scheduled</div>
                    <div class="stat-val" id="countScheduled">—</div>
                    <div class="stat-sub">upcoming patrols</div>
                </div>
                <div class="stat-cell s-ongoing">
                    <div class="stat-eyebrow">Ongoing</div>
                    <div class="stat-val" id="countOngoing">—</div>
                    <div class="stat-sub">active right now</div>
                </div>
                <div class="stat-cell s-completed">
                    <div class="stat-eyebrow">Completed</div>
                    <div class="stat-val" id="countCompleted">—</div>
                    <div class="stat-sub">finished patrols</div>
                </div>
                <div class="stat-cell s-weekly">
                    <div class="stat-eyebrow">Weekly Repeats</div>
                    <div class="stat-val" id="countWeekly">—</div>
                    <div class="stat-sub">recurring schedules</div>
                </div>
            </div>

            <!-- ── Patrol Table ── -->
            <div class="ptrl-table-wrap">
                <table id="patrolTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Team</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Route / Area</th>
                            <th>Members</th>
                            <th>Type</th>
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
    <div id="patrolDialog" title="New Patrol Schedule" class="hidden">
        <form id="patrolForm" class="modal-form">
            <input type="hidden" id="patrolId">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="form-section">
                <div class="form-section-lbl">Team &amp; Schedule</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Team Name <span class="req">*</span></label>
                            <input type="text" id="patrolTeam" class="fg-input" placeholder="e.g. Alpha Team, Grupo 1" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Patrol Date <span class="req">*</span></label>
                            <input type="date" id="patrolDate" class="fg-input">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Time Start <span class="req">*</span></label>
                            <input type="time" id="patrolTimeStart" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Time End <span class="req">*</span></label>
                            <input type="time" id="patrolTimeEnd" class="fg-input">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Coverage</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Patrol Route</label>
                        <input type="text" id="patrolRoute" class="fg-input" placeholder="e.g. Purok 1 → Purok 2 → Main Road" autocomplete="off">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Area Covered</label>
                        <input type="text" id="patrolArea" class="fg-input" placeholder="e.g. Sitio Mabini, Perimeter, Covered Court Area" autocomplete="off">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Tanod Members</label>
                        <input type="text" id="patrolMembers" class="fg-input" placeholder="Names separated by comma, e.g. Juan Dela Cruz, Pedro Reyes" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Settings</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select id="patrolStatus" class="fg-select">
                                <option value="scheduled">Scheduled</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Schedule Type</label>
                            <select id="patrolIsWeekly" class="fg-select">
                                <option value="0">One-time</option>
                                <option value="1">🔁 Weekly Repeat</option>
                            </select>
                        </div>
                    </div>

                    <!-- Week day selector — shown only for weekly -->
                    <div id="weekDayWrapper" class="hidden">
                        <div class="fg">
                            <label class="fg-label">Repeat on Day</label>
                            <select id="patrolWeekDay" class="fg-select">
                                <option value="0">Sunday</option>
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                            </select>
                        </div>
                    </div>

                    <div class="fg">
                        <label class="fg-label">Notes / Instructions</label>
                        <textarea id="patrolNotes" class="fg-textarea" placeholder="Patrol instructions, special assignments…"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- MODAL: VIEW -->
    <div id="viewPatrolDialog" title="Patrol Record" class="hidden">
        <div id="viewPatrolContent" class="p-4"></div>
    </div>

    <!-- MODAL: DELETE -->
    <div id="deletePatrolDialog" title="Confirm Delete" class="hidden">
        <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid var(--danger-fg);background:var(--paper);">
            Delete this patrol schedule? This action cannot be undone.
        </div>
        <input type="hidden" id="deletePatrolId">
    </div>

    <script src="js/index.js"></script>
</body>
</html>
<?php
require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();
$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events &amp; Scheduling — MIS Barangay</title>
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
        --p-normal:  #94a3b8;
        --p-important:#22c55e;
        --p-urgent:  #ef4444;
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
    .ev-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

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
    .doc-eyebrow::before { content:''; display:inline-block; width:18px; height:2px; background:var(--accent); }
    .doc-title {
        font-family: var(--f-serif); font-size: 22px; font-weight: 700;
        color: var(--ink); letter-spacing: -.2px; margin-bottom: 3px;
    }
    .doc-sub { font-size: 12px; color: var(--ink-faint); font-style: italic; }
    .header-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

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
    .btn-danger { background: var(--danger-bg); border-color: var(--danger-fg); color: var(--danger-fg); }
    .btn-danger:hover { background: var(--danger-fg); color: #fff; }

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
        padding: 16px 18px; border-right: 1px solid var(--rule);
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
        text-transform: uppercase; color: var(--ink-faint); margin-bottom: 8px;
    }
    .stat-val {
        font-family: var(--f-mono); font-size: 26px; font-weight: 600;
        color: var(--ink); line-height: 1; margin-bottom: 4px; letter-spacing: -1px;
    }
    .stat-sub { font-size: 10.5px; color: var(--ink-faint); }

    /* ═══════════════════════════════════════
       TAB STRIP
    ═══════════════════════════════════════ */
    .tab-strip {
        display: flex; gap: 0;
        margin: 22px 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-bottom: none;
        border-radius: 2px 2px 0 0;
        overflow: hidden;
    }
    .tab-btn {
        padding: 12px 22px; font-size: 10.5px; font-weight: 700;
        letter-spacing: .8px; text-transform: uppercase;
        color: var(--ink-faint); cursor: pointer; border: none;
        background: transparent; border-bottom: 3px solid transparent;
        transition: all .14s; white-space: nowrap;
        border-right: 1px solid var(--rule);
    }
    .tab-btn:last-child { border-right: none; }
    .tab-btn:hover { color: var(--accent); background: var(--accent-lt); }
    .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); background: var(--paper-lt); font-weight: 700; }

    /* ── Tab Panes ── */
    .tab-panes {
        margin: 0 28px 0;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: none;
        border-radius: 0 0 2px 2px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    /* ═══════════════════════════════════════
       CALENDAR TAB
    ═══════════════════════════════════════ */
    .cal-layout { display: grid; grid-template-columns: 280px 1fr; height: 100%; }
    .cal-sidebar {
        border-right: 1px solid var(--rule);
        display: flex; flex-direction: column;
        max-height: 520px; overflow: hidden;
    }
    .cal-sidebar-head {
        padding: 14px 16px; border-bottom: 1px solid var(--rule);
        background: var(--paper-lt);
        display: flex; align-items: center; justify-content: space-between;
    }
    .cal-sidebar-title {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.4px;
        text-transform: uppercase; color: var(--ink-muted);
    }
    .ev-count-badge {
        font-family: var(--f-mono); font-size: 10px; font-weight: 600;
        background: var(--accent); color: #fff;
        padding: 2px 8px; border-radius: 2px;
    }
    .cal-sidebar-search {
        padding: 10px 12px; border: none; border-bottom: 1px solid var(--rule);
        font-size: 12.5px; font-family: var(--f-sans); color: var(--ink);
        background: var(--paper); width: 100%; outline: none;
    }
    .cal-sidebar-search::placeholder { color: var(--ink-faint); font-style: italic; font-size: 11.5px; }
    .priority-filter {
        padding: 8px 12px; border-bottom: 1px solid var(--rule); background: var(--paper-lt);
        display: flex; gap: 4px;
    }
    .pf-btn {
        padding: 3px 9px; border-radius: 2px; border: 1.5px solid var(--rule-dk);
        background: #fff; font-size: 9px; font-weight: 700; letter-spacing: .3px;
        text-transform: uppercase; color: var(--ink-muted); cursor: pointer; transition: all .12s;
    }
    .pf-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
    .pf-btn.pf-normal.active    { background: #94a3b8; border-color: #94a3b8; }
    .pf-btn.pf-important.active { background: #22c55e; border-color: #22c55e; }
    .pf-btn.pf-urgent.active    { background: #ef4444; border-color: #ef4444; }
    .ev-list { flex: 1; overflow-y: auto; padding: 8px; }

    /* Event card in sidebar */
    .ev-card {
        padding: 10px 12px; border-radius: 2px; margin-bottom: 6px;
        border: 1px solid var(--rule); background: #fff;
        border-left: 3px solid var(--p-normal);
        cursor: pointer; transition: box-shadow .12s, border-color .12s;
        position: relative;
    }
    .ev-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.1); }
    .ev-card.priority-important { border-left-color: var(--p-important); }
    .ev-card.priority-urgent    { border-left-color: var(--p-urgent); }
    .ev-card-date { font-family: var(--f-mono); font-size: 9.5px; color: var(--ink-faint); margin-bottom: 4px; }
    .ev-card-title { font-weight: 600; font-size: 12.5px; color: var(--ink); margin-bottom: 2px; }
    .ev-card-loc   { font-size: 10.5px; color: var(--ink-faint); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ev-card-actions {
        display: none; position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        gap: 4px;
    }
    .ev-card:hover .ev-card-actions { display: flex; }

    /* Calendar grid */
    .cal-main { padding: 16px 20px; }
    .cal-nav {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 14px;
    }
    .cal-month-label {
        font-family: var(--f-serif); font-size: 16px; font-weight: 600;
        color: var(--ink); letter-spacing: -.2px; min-width: 180px; text-align: center;
    }
    .cal-nav-btn {
        width: 30px; height: 30px; border-radius: 2px;
        border: 1.5px solid var(--rule-dk); background: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 16px; color: var(--ink-muted);
        transition: all .12s;
    }
    .cal-nav-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
    .cal-today-btn {
        padding: 4px 12px; border-radius: 2px; border: 1.5px solid var(--rule-dk);
        background: #fff; font-size: 10px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; color: var(--ink-muted); cursor: pointer; transition: all .12s;
    }
    .cal-today-btn:hover { border-color: var(--accent); color: var(--accent); }
    .cal-dow {
        display: grid; grid-template-columns: repeat(7, 1fr);
        gap: 2px; margin-bottom: 4px;
    }
    .cal-dow-cell {
        text-align: center; font-size: 8px; font-weight: 700;
        letter-spacing: 1px; text-transform: uppercase; color: var(--ink-faint);
        padding: 4px 0;
    }
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
    .cal-day {
        min-height: 64px; border-radius: 2px; padding: 5px;
        border: 1px solid transparent; cursor: pointer;
        transition: background .12s, border-color .12s;
    }
    .cal-day:hover { background: var(--accent-lt); border-color: var(--rule); }
    .cal-day.today { border: 2px solid var(--accent); }
    .cal-day.other-month { opacity: .3; cursor: default; }
    .cal-day.other-month:hover { background: transparent; border-color: transparent; }
    .cal-day-num {
        font-size: 11px; font-weight: 600; color: var(--ink-muted);
        margin-bottom: 3px;
    }
    .cal-day.today .cal-day-num { color: var(--accent); font-weight: 700; }
    .cal-dots { display: flex; flex-wrap: wrap; gap: 2px; }
    .cal-dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--p-normal); flex-shrink: 0;
    }
    .cal-dot.important { background: var(--p-important); }
    .cal-dot.urgent    { background: var(--p-urgent); }
    .cal-legend {
        display: flex; gap: 12px; margin-top: 12px; padding-top: 10px;
        border-top: 1px solid var(--rule);
    }
    .legend-item {
        display: flex; align-items: center; gap: 5px;
        font-size: 10px; color: var(--ink-faint);
    }
    .legend-dot {
        width: 7px; height: 7px; border-radius: 50%;
    }

    /* ═══════════════════════════════════════
       LIST TAB (DataTable)
    ═══════════════════════════════════════ */
    .list-toolbar {
        padding: 12px 16px; border-bottom: 1px solid var(--rule); background: var(--paper-lt);
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
    }
    .list-search {
        padding: 7px 12px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-size: 13px; font-family: var(--f-sans); color: var(--ink);
        background: #fff; outline: none; width: 220px; transition: border-color .15s;
    }
    .list-search:focus { border-color: var(--accent); }
    .list-search::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }

    /* DataTable wrapper */
    .ev-dt-wrap .dataTables_wrapper { padding: 0; font-family: var(--f-sans); }
    .ev-dt-wrap .dataTables_filter,
    .ev-dt-wrap .dataTables_length { display: none; }
    .ev-dt-wrap .dataTables_info {
        padding: 10px 18px; font-size: 11px; color: var(--ink-faint);
        font-family: var(--f-mono); letter-spacing: .3px;
        border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .ev-dt-wrap .dataTables_paginate {
        padding: 10px 18px; border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .ev-dt-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important; border-radius: 2px;
        font-size: 11px; font-weight: 600; color: var(--ink-muted) !important;
        background: #fff !important; cursor: pointer; margin: 0 2px; transition: all .13s;
    }
    .ev-dt-wrap .paginate_button:hover { border-color: var(--accent) !important; color: var(--accent) !important; background: var(--accent-lt) !important; }
    .ev-dt-wrap .paginate_button.current { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
    .ev-dt-wrap .paginate_button.disabled { opacity: .35 !important; }

    #eventsTable { width: 100% !important; border-collapse: collapse; }
    #eventsTable thead th {
        padding: 10px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        cursor: pointer; user-select: none;
    }
    #eventsTable thead th:hover { color: var(--accent); }
    #eventsTable thead th.sorting_asc::after  { content:' ↑'; color:var(--accent); }
    #eventsTable thead th.sorting_desc::after { content:' ↓'; color:var(--accent); }
    #eventsTable tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    #eventsTable tbody tr:hover { background: var(--accent-lt); }
    #eventsTable td { padding: 10px 14px; font-size: 12.5px; color: var(--ink); vertical-align: middle; }

    /* Priority badge */
    .pri-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 8px; border-radius: 2px;
        font-size: 9px; font-weight: 700; letter-spacing: .4px;
        text-transform: uppercase; border: 1px solid;
    }
    .pri-badge::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; }
    .pri-normal    { background:#f1f5f9; color:#64748b; border-color:#cbd5e1; }
    .pri-important { background:var(--ok-bg);   color:var(--ok-fg);   border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .pri-urgent    { background:var(--danger-bg);color:var(--danger-fg);border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent); }

    /* Status badge */
    .ev-status {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid;
    }
    .ev-status::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; }
    .es-scheduled  { background:var(--info-bg); color:var(--info-fg); border-color:color-mix(in srgb,var(--info-fg) 25%,transparent); }
    .es-completed  { background:var(--ok-bg);   color:var(--ok-fg);   border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .es-cancelled  { background:var(--neu-bg);  color:var(--neu-fg);  border-color:var(--rule); }

    /* Table actions */
    .td-actions { display: flex; gap: 5px; }
    .act-btn {
        display: inline-flex; align-items: center;
        padding: 4px 10px; border-radius: 2px; font-size: 9.5px;
        font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid var(--rule-dk); font-family: var(--f-sans);
        transition: all .13s; background: #fff; color: var(--ink-muted); white-space: nowrap;
    }
    .act-edit:hover   { border-color: var(--accent);    color: var(--accent);    background: var(--accent-lt); }
    .act-delete:hover { border-color: var(--danger-fg); color: var(--danger-fg); background: var(--danger-bg); }

    /* ═══════════════════════════════════════
       HISTORY TAB
    ═══════════════════════════════════════ */
    .hist-toolbar {
        padding: 12px 16px; border-bottom: 1px solid var(--rule); background: var(--paper-lt);
        display: flex; align-items: center; gap: 10px;
    }
    .hist-list { padding: 12px 16px; display: flex; flex-direction: column; gap: 8px; max-height: 440px; overflow-y: auto; }
    .hist-card {
        padding: 12px 14px; border-radius: 2px;
        border: 1px solid var(--rule); background: #fff;
        display: flex; align-items: flex-start; gap: 14px;
    }
    .hist-status-col { flex-shrink: 0; padding-top: 2px; }
    .hist-body { flex: 1; min-width: 0; }
    .hist-card-date { font-family: var(--f-mono); font-size: 10px; color: var(--ink-faint); margin-bottom: 3px; }
    .hist-card-title { font-weight: 600; font-size: 13px; color: var(--ink); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hist-card-meta  { font-size: 10.5px; color: var(--ink-faint); }
    .hist-card-loc   { font-size: 11px; color: var(--ink-muted); margin-top: 2px; }

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
    .fg-textarea { resize: vertical; min-height: 80px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

    /* skeleton */
    .skel {
        background: linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);
        background-size: 400px 100%; animation: shimmer 1.2s infinite;
        border-radius: 2px; height: 60px; margin-bottom: 8px;
    }
    @keyframes shimmer { 0%{background-position:-400px 0} 100%{background-position:400px 0} }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto ev-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Community Affairs</div>
                        <div class="doc-title">Events &amp; Scheduling</div>
                        <div class="doc-sub">Official barangay event calendar and scheduling register</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnPrintEvents">↗ Print</button>
                    </div>
                </div>
            </div>

            <!-- ── Stat Ledger ── -->
            <div class="stat-ledger">
                <div class="stat-cell">
                    <div class="stat-eyebrow">This Month</div>
                    <div class="stat-val" id="stTotal">—</div>
                    <div class="stat-sub">total events</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-eyebrow">Scheduled</div>
                    <div class="stat-val" id="stScheduled" style="color:var(--info-fg);">—</div>
                    <div class="stat-sub">upcoming</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-eyebrow">Urgent</div>
                    <div class="stat-val" id="stUrgent" style="color:var(--danger-fg);">—</div>
                    <div class="stat-sub">high priority</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-eyebrow">Next 7 Days</div>
                    <div class="stat-val" id="stUpcoming" style="color:var(--ok-fg);">—</div>
                    <div class="stat-sub">coming up soon</div>
                </div>
            </div>

            <!-- ── Tab Strip ── -->
            <div class="tab-strip" style="margin-top:22px;">
                <button class="tab-btn active" data-tab="calendar">📅 Calendar</button>
                <button class="tab-btn" data-tab="list">📋 Event List</button>
                <button class="tab-btn" data-tab="history">🕓 History</button>
            </div>

            <!-- ── Tab Panes ── -->
            <div class="tab-panes">

                <!-- ─── CALENDAR TAB ─── -->
                <div class="tab-pane active" id="tab-calendar">
                    <div class="cal-layout">

                        <!-- Sidebar: upcoming list -->
                        <div class="cal-sidebar">
                            <div class="cal-sidebar-head">
                                <span class="cal-sidebar-title">Upcoming Events</span>
                                <span class="ev-count-badge" id="evCount">—</span>
                            </div>
                            <input type="text" class="cal-sidebar-search" id="evSearch" placeholder="Search events…">
                            <div class="priority-filter">
                                <button class="pf-btn active" data-priority="">All</button>
                                <button class="pf-btn pf-normal"    data-priority="normal">Normal</button>
                                <button class="pf-btn pf-important" data-priority="important">Important</button>
                                <button class="pf-btn pf-urgent"    data-priority="urgent">Urgent</button>
                            </div>
                            <div class="ev-list" id="evList">
                                <div class="skel"></div><div class="skel" style="height:50px;"></div><div class="skel" style="height:66px;"></div>
                            </div>
                        </div>

                        <!-- Calendar grid -->
                        <div class="cal-main">
                            <div class="cal-nav">
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <button class="cal-nav-btn" id="prevMonth">‹</button>
                                    <div class="cal-month-label" id="calMonthLabel">—</div>
                                    <button class="cal-nav-btn" id="nextMonth">›</button>
                                </div>
                                <button class="cal-today-btn" id="todayBtn">Today</button>
                            </div>
                            <div class="cal-dow">
                                <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                                <div class="cal-dow-cell"><?= $d ?></div>
                                <?php endforeach; ?>
                            </div>
                            <div class="cal-grid" id="calGrid"></div>
                            <div class="cal-legend">
                                <div class="legend-item"><div class="legend-dot" style="background:var(--p-normal);"></div> Normal</div>
                                <div class="legend-item"><div class="legend-dot" style="background:var(--p-important);"></div> Important</div>
                                <div class="legend-item"><div class="legend-dot" style="background:var(--p-urgent);"></div> Urgent</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── LIST TAB ─── -->
                <div class="tab-pane ev-dt-wrap" id="tab-list">
                    <div class="list-toolbar">
                        <input type="text" class="list-search" id="listSearch" placeholder="Search events…">
                        <select class="list-search" id="listStatusFilter" style="width:140px;">
                            <option value="scheduled">Scheduled</option>
                            <option value="all">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <table id="eventsTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- ─── HISTORY TAB ─── -->
                <div class="tab-pane" id="tab-history">
                    <div class="hist-toolbar">
                        <input type="text" class="list-search" id="histSearch" placeholder="Search history…" style="flex:1;">
                    </div>
                    <div class="hist-list" id="histList">
                        <div style="padding:24px;text-align:center;color:var(--ink-faint);font-style:italic;">Click History tab to load records.</div>
                    </div>
                </div>

            </div><!-- /tab-panes -->

        </main>
    </div>

    <!-- ════════════════════════════
         MODAL: ADD / EDIT EVENT
    ════════════════════════════ -->
    <div id="eventModal" title="New Event" class="hidden">
        <form id="eventForm" class="modal-form">
            <input type="hidden" name="id"         id="evId">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-section">
                <div class="form-section-lbl">Event Information</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Event Title <span class="req">*</span></label>
                        <input type="text" name="title" id="evTitle" class="fg-input" required autocomplete="off" placeholder="e.g., Barangay Assembly, Clean-Up Drive…">
                    </div>
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Date <span class="req">*</span></label>
                            <input type="date" name="event_date" id="evDate" class="fg-input" required>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Time</label>
                            <input type="time" name="event_time" id="evTime" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Priority</label>
                            <select name="priority" id="evPriority" class="fg-select">
                                <option value="normal">Normal</option>
                                <option value="important">Important</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Location / Venue</label>
                            <input type="text" name="location" id="evLocation" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select name="status" id="evStatus" class="fg-select">
                                <option value="scheduled">Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Description</label>
                        <textarea name="description" id="evDesc" class="fg-textarea" placeholder="Event details, agenda, notes…"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="js/index.js"></script>
</body>
</html>
<?php
require_once __DIR__ . '/../../includes/app.php';
requireSecretary();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    include_once __DIR__ . '/add.php';
}

$stmt = $conn->prepare("SELECT * FROM residents WHERE deleted_at IS NULL ORDER BY last_name ASC, first_name ASC");
if ($stmt === false) { $result = false; }
else { $stmt->execute(); $result = $stmt->get_result(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="autocomplete" content="off">
    <title>Resident Register — MIS Barangay</title>
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
    /* ═══════════════════════════════════════════════
       TOKENS
    ═══════════════════════════════════════════════ */
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
        --accent-md: color-mix(in srgb, var(--accent) 18%, white);
        --accent-dk: color-mix(in srgb, var(--accent) 65%, black);
        --danger-bg: #fdeeed; --danger-fg: #7a1f1a;
        --warn-bg:   #fef9ec; --warn-fg:   #7a5700;
        --ok-bg:     #edfaf3; --ok-fg:     #1a5c35;
        --info-bg:   #edf3fa; --info-fg:   #1a3a5c;
        --f-serif:  'Source Serif 4', Georgia, serif;
        --f-sans:   'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:   'Source Code Pro', 'Courier New', monospace;
        --shadow:   0 1px 2px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select, textarea { font-family: var(--f-sans); }

    /* ═══════════════════════════════════════════════
       PAGE
    ═══════════════════════════════════════════════ */
    .res-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

    /* ── Document Header ── */
    .doc-header {
        background: var(--paper);
        border-bottom: 1px solid var(--rule);
    }
    .doc-header-inner {
        padding: 20px 28px 18px;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }
    .doc-eyebrow {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.8px;
        text-transform: uppercase; color: var(--ink-faint);
        margin-bottom: 6px;
        display: flex; align-items: center; gap: 8px;
    }
    .doc-eyebrow::before {
        content: ''; display: inline-block;
        width: 18px; height: 2px; background: var(--accent);
    }
    .doc-title {
        font-family: var(--f-serif);
        font-size: 22px; font-weight: 700; color: var(--ink);
        letter-spacing: -.2px; margin-bottom: 3px;
    }
    .doc-sub { font-size: 12px; color: var(--ink-faint); font-style: italic; }

    /* Action buttons in header */
    .header-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    /* ── Toolbar ── */
    .res-toolbar {
        background: var(--paper-lt);
        border-bottom: 3px solid var(--accent);
        padding: 12px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .toolbar-left { display: flex; gap: 8px; align-items: center; }
    .toolbar-right { display: flex; gap: 8px; align-items: center; }

    /* ── Buttons ── */
    .btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 16px; border-radius: 2px;
        font-family: var(--f-sans); font-size: 11.5px; font-weight: 700;
        letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid; transition: all .14s;
        white-space: nowrap; text-decoration: none;
    }
    .btn-primary {
        background: var(--accent); border-color: var(--accent); color: #fff;
    }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-ghost {
        background: #fff; border-color: var(--rule-dk); color: var(--ink-muted);
    }
    .btn-ghost:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
    .btn-sm { padding: 5px 11px; font-size: 10px; }

    /* ── Search input ── */
    .res-search {
        padding: 7px 12px; border: 1.5px solid var(--rule-dk);
        border-radius: 2px; font-family: var(--f-sans); font-size: 13px;
        color: var(--ink); background: #fff; outline: none;
        width: 220px; transition: border-color .15s, box-shadow .15s;
    }
    .res-search:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .res-search::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }

    /* ═══════════════════════════════════════════════
       TABLE AREA
    ═══════════════════════════════════════════════ */
    .res-table-wrap {
        margin: 22px 28px;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    /* Override DataTables styling to match our aesthetic */
    .res-table-wrap .dataTables_wrapper {
        padding: 0;
        font-family: var(--f-sans);
    }
    .res-table-wrap .dataTables_filter,
    .res-table-wrap .dataTables_length { display: none; } /* we use our own search */

    .res-table-wrap .dataTables_info {
        padding: 10px 18px;
        font-size: 11px; color: var(--ink-faint); font-family: var(--f-mono);
        letter-spacing: .3px; border-top: 1px solid var(--rule);
        background: var(--paper-lt);
    }
    .res-table-wrap .dataTables_paginate {
        padding: 10px 18px;
        border-top: 1px solid var(--rule);
        background: var(--paper-lt);
    }
    .res-table-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important;
        border-radius: 2px; font-size: 11px; font-weight: 600;
        color: var(--ink-muted) !important; background: #fff !important;
        cursor: pointer; margin: 0 2px;
        transition: all .13s;
    }
    .res-table-wrap .paginate_button:hover {
        border-color: var(--accent) !important;
        color: var(--accent) !important;
        background: var(--accent-lt) !important;
    }
    .res-table-wrap .paginate_button.current {
        background: var(--accent) !important;
        border-color: var(--accent) !important;
        color: #fff !important;
    }
    .res-table-wrap .paginate_button.disabled {
        opacity: .35 !important; cursor: not-allowed;
    }

    /* Main table */
    #residentsTable { width: 100% !important; border-collapse: collapse; }
    #residentsTable thead th {
        padding: 10px 14px;
        background: var(--paper-lt);
        text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk);
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
    }
    #residentsTable thead th:hover { color: var(--accent); }
    #residentsTable thead th.sorting_asc::after  { content: ' ↑'; color: var(--accent); }
    #residentsTable thead th.sorting_desc::after { content: ' ↓'; color: var(--accent); }

    #residentsTable tbody tr {
        border-bottom: 1px solid #f0ede8;
        transition: background .1s;
    }
    #residentsTable tbody tr:last-child { border-bottom: none; }
    #residentsTable tbody tr:hover { background: var(--accent-lt); }

    #residentsTable td {
        padding: 10px 14px;
        font-size: 12.5px; color: var(--ink);
        vertical-align: middle;
    }

    /* Name cell — primary */
    .td-name-block .td-name {
        font-weight: 600; color: var(--ink); font-size: 13px; margin-bottom: 2px;
    }
    .td-name-block .td-id {
        font-family: var(--f-mono); font-size: 9.5px; color: var(--ink-faint);
        letter-spacing: .5px;
    }

    /* Gender/age chip */
    .td-chip {
        display: inline-block; padding: 2px 7px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .4px;
        text-transform: uppercase; border: 1px solid;
    }
    .chip-m   { background: var(--info-bg); color: var(--info-fg); border-color: color-mix(in srgb,var(--info-fg) 20%,transparent); }
    .chip-f   { background: var(--warn-bg); color: var(--warn-fg); border-color: color-mix(in srgb,var(--warn-fg) 20%,transparent); }
    .chip-neu { background: var(--paper-dk); color: var(--ink-muted); border-color: var(--rule); }

    /* Voter/PWD badge */
    .td-badge {
        display: inline-block; width: 7px; height: 7px; border-radius: 50%;
        margin-right: 3px; vertical-align: middle;
    }
    .badge-yes { background: var(--ok-fg); }
    .badge-no  { background: var(--rule-dk); }

    /* Age — monospaced */
    .td-age { font-family: var(--f-mono); font-size: 12px; }

    /* Actions */
    .td-actions { display: flex; gap: 5px; align-items: center; }
    .act-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 2px; font-size: 9.5px;
        font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid; font-family: var(--f-sans);
        transition: all .13s; white-space: nowrap;
    }
    .act-view    { background:#fff; border-color:var(--rule-dk); color:var(--ink-muted); }
    .act-view:hover { border-color:var(--info-fg); color:var(--info-fg); background:var(--info-bg); }
    .act-edit    { background:#fff; border-color:var(--rule-dk); color:var(--ink-muted); }
    .act-edit:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-lt); }
    .act-archive { background:#fff; border-color:var(--rule-dk); color:var(--ink-muted); }
    .act-archive:hover { border-color:var(--danger-fg); color:var(--danger-fg); background:var(--danger-bg); }

    /* ═══════════════════════════════════════════════
       DIALOG / MODAL OVERRIDES
    ═══════════════════════════════════════════════ */
    .ui-dialog {
        border: 1px solid var(--rule-dk) !important;
        border-radius: 2px !important;
        box-shadow: 0 8px 48px rgba(0,0,0,.18) !important;
        padding: 0 !important;
        font-family: var(--f-sans) !important;
    }
    .ui-dialog-titlebar {
        background: var(--accent) !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 12px 16px !important;
    }
    .ui-dialog-title {
        font-family: var(--f-sans) !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        letter-spacing: 1px !important;
        text-transform: uppercase !important;
        color: #fff !important;
    }
    .ui-dialog-titlebar-close {
        background: rgba(255,255,255,.15) !important;
        border: 1px solid rgba(255,255,255,.25) !important;
        border-radius: 2px !important;
        color: #fff !important;
        width: 24px !important; height: 24px !important;
        top: 50% !important; transform: translateY(-50%) !important;
    }
    .ui-dialog-content { padding: 0 !important; }
    .ui-dialog-buttonpane {
        background: var(--paper-lt) !important;
        border-top: 1px solid var(--rule) !important;
        padding: 12px 16px !important;
        margin: 0 !important;
    }
    .ui-dialog-buttonpane .ui-button {
        font-family: var(--f-sans) !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        letter-spacing: .5px !important;
        text-transform: uppercase !important;
        padding: 7px 18px !important;
        border-radius: 2px !important;
        border: 1.5px solid var(--accent) !important;
        background: var(--accent) !important;
        color: #fff !important;
        cursor: pointer !important;
        transition: filter .13s !important;
    }
    .ui-dialog-buttonpane .ui-button:hover { filter: brightness(1.1) !important; }
    .ui-dialog-buttonpane .ui-button:last-child {
        background: #fff !important;
        border-color: var(--rule-dk) !important;
        color: var(--ink-muted) !important;
    }
    .ui-dialog-buttonpane .ui-button:last-child:hover {
        border-color: var(--ink-muted) !important;
        color: var(--ink) !important;
    }

    /* ═══════════════════════════════════════════════
       FORM INSIDE MODALS
    ═══════════════════════════════════════════════ */
    .modal-form { max-height: 68vh; overflow-y: auto; }

    /* Field group */
    .fg { margin-bottom: 14px; }
    .fg-label {
        display: block; font-size: 8.5px; font-weight: 700;
        letter-spacing: 1.2px; text-transform: uppercase;
        color: var(--ink-muted); margin-bottom: 5px;
    }
    .fg-label .req { color: var(--danger-fg); margin-left: 2px; }
    .fg-input, .fg-select, .fg-textarea {
        width: 100%; padding: 9px 12px;
        border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13px; color: var(--ink);
        background: #fff; outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .fg-input:focus, .fg-select:focus, .fg-textarea:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .fg-input::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }
    .fg-textarea { resize: vertical; min-height: 72px; }

    /* Form grid */
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

    /* Section divider in form */
    .form-section {
        padding: 14px 18px 0;
        border-top: 1px solid var(--rule);
        margin-top: 4px;
    }
    .form-section:first-child { border-top: none; padding-top: 18px; }
    .form-section-lbl {
        font-size: 8px; font-weight: 700; letter-spacing: 1.6px;
        text-transform: uppercase; color: var(--ink-faint);
        margin-bottom: 12px;
        display: flex; align-items: center; gap: 8px;
    }
    .form-section-lbl::after {
        content: ''; flex: 1; height: 1px; background: var(--rule);
    }
    .form-section-body { padding-bottom: 14px; }

    /* Household search */
    .hh-search-wrap { position: relative; }
    .hh-dropdown {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        box-shadow: 0 4px 20px rgba(0,0,0,.12);
        max-height: 200px; overflow-y: auto; z-index: 9999; display: none;
    }
    .hh-dropdown.open { display: block; }
    .hh-opt {
        padding: 9px 13px; cursor: pointer; border-bottom: 1px solid #f0ede8;
        font-size: 12.5px; transition: background .1s;
    }
    .hh-opt:last-child { border-bottom: none; }
    .hh-opt:hover { background: var(--accent-lt); }
    .hh-opt .opt-main { font-weight: 600; color: var(--ink); }
    .hh-opt .opt-sub { font-size: 10.5px; color: var(--ink-faint); margin-top: 2px; }

    /* ═══════════════════════════════════════════════
       VIEW MODAL — Resident Detail Panel
    ═══════════════════════════════════════════════ */
    .view-modal-head {
        background: linear-gradient(to right, var(--accent-lt), var(--paper));
        border-bottom: 1px solid var(--rule);
        padding: 18px 20px;
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 16px;
        align-items: center;
    }
    .view-id-badge {
        background: var(--accent); color: #fff;
        padding: 10px 14px; border-radius: 2px; text-align: center;
    }
    .view-id-lbl { font-size: 7.5px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; opacity: .65; margin-bottom: 4px; }
    .view-id-num { font-family: var(--f-mono); font-size: 17px; font-weight: 700; letter-spacing: 1px; }
    .view-name { font-family: var(--f-serif); font-size: 18px; font-weight: 700; color: var(--ink); margin-bottom: 5px; }
    .view-tags { display: flex; flex-wrap: wrap; gap: 5px; }
    .view-tag {
        display: inline-block; padding: 2px 8px;
        border: 1px solid var(--rule-dk); border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .4px;
        text-transform: uppercase; color: var(--ink-muted); background: #fff;
    }
    .view-tag.accent { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }

    .view-data-grid {
        display: grid; grid-template-columns: repeat(3, 1fr);
    }
    .vd-cell {
        padding: 11px 18px;
        border-right: 1px solid var(--rule);
        border-bottom: 1px solid var(--rule);
    }
    .vd-cell:nth-child(3n) { border-right: none; }
    .vd-lbl { font-size: 8.5px; font-weight: 700; letter-spacing: 1.1px; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 3px; }
    .vd-val { font-size: 12.5px; font-weight: 500; color: var(--ink); }

    /* ═══════════════════════════════════════════════
       ARCHIVE MODAL
    ═══════════════════════════════════════════════ */
    .archive-modal-wrap { font-size: 13px; }
    .archive-search-bar {
        padding: 12px 16px; border-bottom: 1px solid var(--rule);
        background: var(--paper-lt); display: flex; align-items: center; gap: 10px;
    }
    .archive-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .archive-table thead th {
        padding: 8px 16px; background: var(--paper-lt);
        text-align: left; font-size: 8.5px; font-weight: 700;
        letter-spacing: 1.1px; text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk);
    }
    .archive-table tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    .archive-table tbody tr:hover { background: var(--paper-lt); }
    .archive-table td { padding: 10px 16px; color: var(--ink); }
    .archive-empty { padding: 36px; text-align: center; color: var(--ink-faint); font-style: italic; font-size: 12px; }
    .archive-footer {
        padding: 8px 16px; border-top: 1px solid var(--rule);
        background: var(--paper-lt); font-family: var(--f-mono);
        font-size: 9px; color: var(--ink-faint); letter-spacing: .5px;
    }

    /* Household management modal */
    .hh-manage-toolbar {
        padding: 12px 16px; border-bottom: 1px solid var(--rule);
        background: var(--paper-lt); display: flex; align-items: center;
        justify-content: space-between; gap: 10px;
    }
    .hh-list { max-height: 360px; overflow-y: auto; }
    .hh-item {
        padding: 12px 16px; border-bottom: 1px solid #f0ede8;
        display: flex; align-items: flex-start;
        justify-content: space-between; gap: 12px; transition: background .1s;
    }
    .hh-item:hover { background: var(--paper-lt); }
    .hh-no { font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: var(--accent); }
    .hh-addr { font-size: 12px; color: var(--ink-muted); margin-top: 2px; }
    .hh-head { font-size: 11px; color: var(--ink-faint); margin-top: 1px; }
    .hh-actions { display: flex; gap: 5px; flex-shrink: 0; }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto res-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Population Records</div>
                        <div class="doc-title">Resident Register</div>
                        <div class="doc-sub">Official registry of all barangay residents — sorted alphabetically by surname</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="manageHouseholdsBtn">
                            🏠 Households
                        </button>
                        <button class="btn btn-ghost" id="archiveResidentsBtn">
                            ▤ Archive
                        </button>
                        <button class="btn btn-primary" id="openResidentModalBtn">
                            + Add Resident
                        </button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="res-toolbar">
                    <div class="toolbar-left">
                        <input type="text" class="res-search" id="resTableSearch" placeholder="Search by name, address, occupation…">
                    </div>
                    <div class="toolbar-right" style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;">
                        <?php
                        // Count
                        $countStmt = $conn->prepare("SELECT COUNT(*) as c FROM residents WHERE deleted_at IS NULL");
                        $countStmt->execute();
                        $totalCount = $countStmt->get_result()->fetch_assoc()['c'] ?? 0;
                        ?>
                        <?= number_format($totalCount) ?> REGISTERED RESIDENTS
                    </div>
                </div>
            </div>

            <!-- ── Resident Table ── -->
            <div class="res-table-wrap" style="margin:22px 28px;">
                <table id="residentsTable" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Civil Status</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Voter</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result !== false): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                $fullName = trim($row['first_name'].' '.($row['middle_name'] ? $row['middle_name'].' ' : '').$row['last_name'].($row['suffix'] ? ', '.$row['suffix'] : ''));
                                $age      = AutoComputeAge($row['birthdate']);
                                $genderClass = $row['gender'] === 'Male' ? 'chip-m' : ($row['gender'] === 'Female' ? 'chip-f' : 'chip-neu');
                                $genderLabel = $row['gender'] === 'Male' ? 'M' : ($row['gender'] === 'Female' ? 'F' : '—');
                            ?>
                            <tr>
                                <td>
                                    <div class="td-name-block">
                                        <div class="td-name"><?= htmlspecialchars($fullName) ?></div>
                                        <div class="td-id"># <?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                    </div>
                                </td>
                                <td><span class="td-chip <?= $genderClass ?>"><?= $genderLabel ?></span></td>
                                <td><span class="td-age"><?= $age ?></span></td>
                                <td style="font-size:12px;color:var(--ink-muted);"><?= htmlspecialchars($row['civil_status'] ?: '—') ?></td>
                                <td style="font-size:12px;color:var(--ink-muted);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($row['address'] ?: '—') ?></td>
                                <td style="font-family:var(--f-mono);font-size:11.5px;color:var(--ink-muted);"><?= htmlspecialchars($row['contact_no'] ?: '—') ?></td>
                                <td>
                                    <span class="td-badge <?= $row['voter_status']==='Yes' ? 'badge-yes' : 'badge-no' ?>"></span>
                                    <span style="font-size:11px;color:var(--ink-faint);"><?= $row['voter_status']==='Yes' ? 'Yes' : 'No' ?></span>
                                </td>
                                <td>
                                    <div class="td-actions">
                                        <button class="act-btn act-view view-resident-btn" data-id="<?= $row['id'] ?>">View</button>
                                        <button class="act-btn act-edit edit-resident-btn" data-id="<?= $row['id'] ?>">Edit</button>
                                        <button class="act-btn act-archive archive-resident-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($fullName) ?>">Archive</button>
                                        <!-- No delete — records are archived only, not permanently removed -->
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--ink-faint);font-style:italic;">Error loading residents.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ════════════════════════════════════════════
         MODAL: VIEW RESIDENT
    ════════════════════════════════════════════ -->
    <div id="viewResidentModal" title="Resident Record" class="hidden">
        <div class="view-modal-head">
            <div class="view-id-badge">
                <div class="view-id-lbl">Res. No.</div>
                <div class="view-id-num" id="view-id-num">—</div>
            </div>
            <div>
                <div class="view-name" id="view-full-name">—</div>
                <div class="view-tags" id="view-tags"></div>
            </div>
        </div>
        <div class="view-data-grid">
            <div class="vd-cell"><div class="vd-lbl">Birthdate</div><div class="vd-val" id="view-birthdate">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Age</div><div class="vd-val" id="view-age">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Birthplace</div><div class="vd-val" id="view-birthplace">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Civil Status</div><div class="vd-val" id="view-civil-status">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Religion</div><div class="vd-val" id="view-religion">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Citizenship</div><div class="vd-val" id="view-citizenship">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Contact No.</div><div class="vd-val" id="view-contact">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Address</div><div class="vd-val" id="view-address">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Occupation</div><div class="vd-val" id="view-occupation">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Voter Status</div><div class="vd-val" id="view-voter-status">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Disability</div><div class="vd-val" id="view-disability-status">—</div></div>
            <div class="vd-cell"><div class="vd-lbl">Household</div><div class="vd-val" id="view-household-id">—</div></div>
        </div>
        <div style="padding:12px 18px;border-top:1px solid var(--rule);background:var(--paper-lt);">
            <div class="vd-lbl" style="margin-bottom:4px;">Remarks</div>
            <div class="vd-val" id="view-remarks" style="font-size:12px;color:var(--ink-muted);">—</div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════
         MODAL: EDIT RESIDENT
    ════════════════════════════════════════════ -->
    <div id="editResidentModal" title="Edit Resident Record" class="hidden">
        <form id="editResidentForm" class="modal-form">
            <input type="hidden" id="edit-resident-id" name="id">

            <div class="form-section">
                <div class="form-section-lbl">Household Assignment</div>
                <div class="form-section-body">
                    <div class="fg hh-search-wrap">
                        <label class="fg-label">Search Household</label>
                        <input type="text" id="edit-household-search" class="fg-input" placeholder="Type household number or head name…" autocomplete="off">
                        <select id="edit-household-id" name="household_id" class="hidden"></select>
                        <div id="edit-household-dropdown" class="hh-dropdown"></div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Personal Information</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">First Name <span class="req">*</span></label>
                            <input type="text" id="edit-first-name" name="first_name" class="fg-input" required autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Middle Name</label>
                            <input type="text" id="edit-middle-name" name="middle_name" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Last Name <span class="req">*</span></label>
                            <input type="text" id="edit-last-name" name="last_name" class="fg-input" required autocomplete="off">
                        </div>
                    </div>
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Suffix</label>
                            <input type="text" id="edit-suffix" name="suffix" class="fg-input" placeholder="Jr., Sr., III…" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Gender <span class="req">*</span></label>
                            <select id="edit-gender" name="gender" class="fg-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Birthdate <span class="req">*</span></label>
                            <input type="date" id="edit-birthdate" name="birthdate" class="fg-input" required>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Birthplace</label>
                            <input type="text" id="edit-birthplace" name="birthplace" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Civil Status</label>
                            <select id="edit-civil-status" name="civil_status" class="fg-select">
                                <option>Single</option><option>Married</option>
                                <option>Widowed</option><option>Separated</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Contact &amp; Address</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Contact No.</label>
                            <input type="text" id="edit-contact-no" name="contact_no" class="fg-input" placeholder="09XXXXXXXXX" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Address</label>
                            <input type="text" id="edit-address" name="address" class="fg-input" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Other Information</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Religion</label>
                            <input type="text" id="edit-religion" name="religion" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Occupation</label>
                            <input type="text" id="edit-occupation" name="occupation" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Citizenship</label>
                            <input type="text" id="edit-citizenship" name="citizenship" class="fg-input" value="Filipino" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Voter Status</label>
                            <select id="edit-voter-status" name="voter_status" class="fg-select">
                                <option value="No">No</option><option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Disability Status</label>
                            <select id="edit-disability-status" name="disability_status" class="fg-select">
                                <option value="No">No</option><option value="Yes">Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Remarks</label>
                        <textarea id="edit-remarks" name="remarks" class="fg-textarea"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════════════════════
         MODAL: ADD RESIDENT
    ════════════════════════════════════════════ -->
    <div id="addResidentModal" title="New Resident Record" class="hidden">
        <form method="POST" class="modal-form" id="addResidentForm">
            <input type="hidden" name="action" value="add_resident">
            <?php if (isset($error)): ?>
                <div style="padding:12px 18px;background:var(--danger-bg);color:var(--danger-fg);font-size:12px;border-bottom:1px solid color-mix(in srgb,var(--danger-fg) 20%,transparent);">
                    ⚠ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="form-section">
                <div class="form-section-lbl">Household Assignment</div>
                <div class="form-section-body">
                    <div class="fg hh-search-wrap">
                        <label class="fg-label">Household <span style="font-weight:400;text-transform:none;font-size:9px;color:var(--ink-faint);">(optional)</span></label>
                        <input type="text" id="add-household-search" class="fg-input" placeholder="Search household number or head name…" autocomplete="off">
                        <select id="add-household-id" name="household_id" class="hidden"></select>
                        <div id="add-household-dropdown" class="hh-dropdown"></div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Personal Information</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg"><label class="fg-label">First Name <span class="req">*</span></label><input type="text" name="first_name" class="fg-input" required autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Middle Name</label><input type="text" name="middle_name" class="fg-input" autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Last Name <span class="req">*</span></label><input type="text" name="last_name" class="fg-input" required autocomplete="off"></div>
                    </div>
                    <div class="form-grid-3">
                        <div class="fg"><label class="fg-label">Suffix</label><input type="text" name="suffix" class="fg-input" placeholder="Jr., Sr., III…" autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Gender <span class="req">*</span></label>
                            <select name="gender" class="fg-select" required>
                                <option value="">— Select —</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="fg"><label class="fg-label">Birthdate <span class="req">*</span></label><input type="date" name="birthdate" class="fg-input" required></div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg"><label class="fg-label">Birthplace</label><input type="text" name="birthplace" class="fg-input" autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Civil Status</label>
                            <select name="civil_status" class="fg-select">
                                <option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Contact &amp; Address</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg"><label class="fg-label">Contact No.</label><input type="text" name="contact_no" class="fg-input" placeholder="09XXXXXXXXX" autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Address</label><input type="text" name="address" class="fg-input" autocomplete="off"></div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Other Information</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg"><label class="fg-label">Religion</label><input type="text" name="religion" class="fg-input" autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Occupation</label><input type="text" name="occupation" class="fg-input" autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Citizenship</label><input type="text" name="citizenship" class="fg-input" value="Filipino" autocomplete="off"></div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg"><label class="fg-label">Voter Status</label>
                            <select name="voter_status" class="fg-select"><option value="No">No</option><option value="Yes">Yes</option></select>
                        </div>
                        <div class="fg"><label class="fg-label">Disability Status</label>
                            <select name="disability_status" class="fg-select"><option value="No">No</option><option value="Yes">Yes</option></select>
                        </div>
                    </div>
                    <div class="fg"><label class="fg-label">Remarks</label><textarea name="remarks" class="fg-textarea"></textarea></div>
                    <div style="padding-top:4px;">
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;">
                            ＋ Add to Register
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════════════════════
         MODAL: ARCHIVE REGISTRY (Residents + Households)
    ════════════════════════════════════════════ -->
    <div id="archivedResidentsDialog" title="Archive Registry" class="hidden">
        <style>
        .arc-tab-bar{display:flex;background:var(--paper-lt);border-bottom:1px solid var(--rule);}
        .arc-tab{
            display:flex;align-items:center;gap:7px;padding:12px 22px;
            font-family:var(--f-sans);font-size:10px;font-weight:700;
            letter-spacing:1px;text-transform:uppercase;
            color:var(--ink-faint);background:none;border:none;
            border-bottom:2px solid transparent;cursor:pointer;
            transition:all .14s;margin-bottom:-1px;
        }
        .arc-tab:hover{color:var(--ink-muted);}
        .arc-tab.active{color:var(--accent);border-bottom-color:var(--accent);background:var(--paper);}
        .arc-tab-count{
            display:inline-flex;align-items:center;justify-content:center;
            min-width:20px;height:16px;padding:0 5px;
            background:var(--rule);border-radius:2px;
            font-family:var(--f-mono);font-size:9px;font-weight:700;
            color:var(--ink-muted);transition:all .14s;
        }
        .arc-tab.active .arc-tab-count{background:var(--accent-md);color:var(--accent);}
        .arc-pane{display:none;}
        .arc-pane.active{display:block;}
        .arc-stats{display:grid;grid-template-columns:repeat(3,1fr);border-bottom:1px solid var(--rule);}
        .arc-stat-cell{padding:13px 18px;border-right:1px solid var(--rule);text-align:center;}
        .arc-stat-cell:last-child{border-right:none;}
        .arc-stat-val{font-family:var(--f-mono);font-size:24px;font-weight:600;color:var(--ink);line-height:1;margin-bottom:5px;}
        .arc-stat-lbl{font-size:8px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-faint);}
        .arc-search-bar{padding:11px 16px;border-bottom:1px solid var(--rule);background:var(--paper-lt);}
        .arc-table-scroll{overflow-y:auto;max-height:288px;}
        </style>

        <!-- Tab bar -->
        <div class="arc-tab-bar">
            <button class="arc-tab active" data-tab="residents">
                <span style="font-size:12px;opacity:.65;">⊞</span>
                Residents
                <span class="arc-tab-count" id="arc-res-count">—</span>
            </button>
            <button class="arc-tab" data-tab="households">
                <span style="font-size:12px;opacity:.65;">▣</span>
                Households
                <span class="arc-tab-count" id="arc-hh-count">—</span>
            </button>
        </div>

        <!-- ── RESIDENTS PANE ── -->
        <div class="arc-pane active" id="arc-pane-residents">
            <div class="arc-stats">
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-res-total">—</div>
                    <div class="arc-stat-lbl">Total Archived</div>
                </div>
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-res-month">—</div>
                    <div class="arc-stat-lbl">This Month</div>
                </div>
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-res-latest">—</div>
                    <div class="arc-stat-lbl">Last Archived</div>
                </div>
            </div>
            <div class="arc-search-bar">
                <input type="text" class="res-search" id="arcResSearch"
                    placeholder="Search by name or ID…" style="width:100%;">
            </div>
            <div class="arc-table-scroll">
                <table class="archive-table">
                    <thead><tr>
                        <th style="width:52px;font-family:var(--f-mono);">No.</th>
                        <th>Full Name</th>
                        <th>Date Archived</th>
                        <th style="text-align:center;">Action</th>
                    </tr></thead>
                    <tbody id="arcResBody">
                        <tr><td colspan="4" class="archive-empty">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="archive-footer" id="arcResFooter">—</div>
        </div>

        <!-- ── HOUSEHOLDS PANE ── -->
        <div class="arc-pane" id="arc-pane-households">
            <div class="arc-stats">
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-hh-total">—</div>
                    <div class="arc-stat-lbl">Total Archived</div>
                </div>
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-hh-members">—</div>
                    <div class="arc-stat-lbl">Total Members</div>
                </div>
                <div class="arc-stat-cell">
                    <div class="arc-stat-val" id="arc-hh-latest">—</div>
                    <div class="arc-stat-lbl">Last Archived</div>
                </div>
            </div>
            <div class="arc-search-bar">
                <input type="text" class="res-search" id="arcHhSearch"
                    placeholder="Search by household number, head, or address…" style="width:100%;">
            </div>
            <div class="arc-table-scroll">
                <table class="archive-table">
                    <thead><tr>
                        <th style="width:36px;font-family:var(--f-mono);">#</th>
                        <th>Household No.</th>
                        <th>Head of Household</th>
                        <th>Address</th>
                        <th style="text-align:center;">Members</th>
                        <th style="text-align:center;">Action</th>
                    </tr></thead>
                    <tbody id="arcHhBody">
                        <tr><td colspan="6" class="archive-empty">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="archive-footer" id="arcHhFooter">—</div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════
         MODAL: HOUSEHOLD MANAGEMENT
    ════════════════════════════════════════════ -->
    <div id="householdManagementModal" title="Household Management" class="hidden">
        <div class="hh-manage-toolbar">
            <button class="btn btn-primary btn-sm" id="createHouseholdBtn">+ New Household</button>
            <input type="text" class="res-search" id="householdSearchInput" placeholder="Search households…" style="width:200px;">
        </div>
        <div class="hh-list" id="householdList">
            <div style="padding:32px;text-align:center;color:var(--ink-faint);font-style:italic;font-size:12px;">Loading households…</div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════
         MODAL: CREATE/EDIT HOUSEHOLD
    ════════════════════════════════════════════ -->
    <div id="householdFormModal" title="Household Details" class="hidden">
        <form id="householdForm" class="modal-form">
            <input type="hidden" id="householdFormId" name="id">
            <div class="form-section">
                <div class="form-section-lbl">Household Record</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Household Number <span class="req">*</span></label>
                            <input type="text" id="householdFormNo" name="household_no" class="fg-input" required autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Address <span class="req">*</span></label>
                            <input type="text" id="householdFormAddress" name="address" class="fg-input" required autocomplete="off">
                        </div>
                    </div>
                    <div id="householdFormHeadContainer" class="fg hh-search-wrap">
                        <label class="fg-label">Head of Household <span class="req">*</span></label>
                        <input type="text" id="householdFormHeadSearch" class="fg-input" placeholder="Search resident…" required autocomplete="off">
                        <input type="hidden" id="householdFormHeadId" name="head_resident_id">
                        <div id="householdFormHeadDropdown" class="hh-dropdown"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Success dialog -->
    <?php if (isset($_GET['success']) && $_GET['success'] == '1') echo DialogMessage("Resident added to the register successfully.", "Record Created"); ?>

    <script src="js/index.js"></script>
</body>
</html>
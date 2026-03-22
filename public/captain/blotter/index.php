<?php
require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();

$success = '';
$error   = '';

// ── Handle new blotter submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_blotter') {
    $complainant_name    = sanitizeString($_POST['complainant_name']    ?? '', false);
    $complainant_address = sanitizeString($_POST['complainant_address'] ?? '');
    $complainant_contact = sanitizeString($_POST['complainant_contact'] ?? '');
    $respondent_name     = sanitizeString($_POST['respondent_name']     ?? '', false);
    $respondent_address  = sanitizeString($_POST['respondent_address']  ?? '');
    $respondent_contact  = sanitizeString($_POST['respondent_contact']  ?? '');
    $incident_date       = $_POST['incident_date'] ?? '';
    $incident_time       = $_POST['incident_time'] ?? '';
    $incident_location   = sanitizeString($_POST['incident_location']   ?? '', false);
    $incident_description= sanitizeString($_POST['incident_description']?? '', false);
    $status              = $_POST['status'] ?? 'pending';

    if (empty($complainant_name) || empty($respondent_name) || empty($incident_date) || empty($incident_location) || empty($incident_description)) {
        $error = "Please fill in all required fields.";
    } elseif (!validateDateFormat($incident_date)) {
        $error = "Invalid incident date format.";
    } else {
        $year    = date('Y');
        $stmt    = $conn->prepare("SELECT COUNT(*) as count FROM blotter WHERE case_number LIKE ?");
        $pattern = "BLT-$year-%";
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $row     = $stmt->get_result()->fetch_assoc();
        $count   = ($row['count'] ?? 0) + 1;
        $case_number = "BLT-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);

        $allowed = ['pending','under_investigation','resolved','dismissed'];
        if (!in_array($status, $allowed)) $status = 'pending';

        $stmt = $conn->prepare("
            INSERT INTO blotter (
                case_number, complainant_name, complainant_address, complainant_contact,
                respondent_name, respondent_address, respondent_contact,
                incident_date, incident_time, incident_location, incident_description,
                status, created_by
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $created_by = $_SESSION['user_id'];
        $stmt->bind_param("ssssssssssssi",
            $case_number, $complainant_name, $complainant_address, $complainant_contact,
            $respondent_name, $respondent_address, $respondent_contact,
            $incident_date, $incident_time, $incident_location, $incident_description,
            $status, $created_by
        );
        if ($stmt->execute()) {
            $success = "Case $case_number recorded successfully.";
        } else {
            $error = "Error recording case. Please try again.";
        }
        $stmt->close();
    }
}

// ── Fetch active (non-archived) blotter cases ──
$stmt = $conn->prepare("
    SELECT b.*, u.name as created_by_name
    FROM blotter b
    LEFT JOIN users u ON b.created_by = u.id
    WHERE b.archived_at IS NULL
    ORDER BY b.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();

$statusConf = [
    'pending'           => ['label' => 'Pending',            'cls' => 'bs-pending'],
    'under_investigation'=> ['label' => 'Under Investigation','cls' => 'bs-invest'],
    'resolved'          => ['label' => 'Resolved',           'cls' => 'bs-resolved'],
    'dismissed'         => ['label' => 'Dismissed',          'cls' => 'bs-dismissed'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="autocomplete" content="off">
    <title>Blotter Register — MIS Barangay</title>
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
    .blt-page { background: var(--bg); min-height: 100%; padding-bottom: 56px; }

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
    .blt-toolbar {
        background: var(--paper-lt); border-bottom: 3px solid var(--accent);
        padding: 12px 28px; display: flex; align-items: center;
        justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .toolbar-left { display: flex; gap: 8px; align-items: center; }

    /* ── Status filter pills ── */
    .status-filters { display: flex; gap: 4px; }
    .sf-pill {
        padding: 5px 12px; border-radius: 2px;
        border: 1.5px solid var(--rule-dk); background: #fff;
        font-family: var(--f-sans); font-size: 10.5px; font-weight: 700;
        letter-spacing: .3px; text-transform: uppercase;
        color: var(--ink-muted); cursor: pointer; transition: all .13s;
    }
    .sf-pill:hover, .sf-pill.active { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
    .sf-pill.all.active { background: var(--accent); color: #fff; border-color: var(--accent); }

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
    .blt-search {
        padding: 7px 12px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13px; color: var(--ink);
        background: #fff; outline: none; width: 220px;
        transition: border-color .15s, box-shadow .15s;
    }
    .blt-search:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .blt-search::placeholder { color: var(--ink-faint); font-style: italic; font-size: 12px; }

    /* ═══════════════════════════════════════
       STATUS BADGE STAMPS
    ═══════════════════════════════════════ */
    .bs {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 9px; border-radius: 2px;
        font-size: 9.5px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; border: 1px solid; white-space: nowrap;
    }
    .bs::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; display: inline-block; }
    .bs-pending   { background: var(--warn-bg); color: var(--warn-fg);   border-color: color-mix(in srgb,var(--warn-fg) 25%,transparent); }
    .bs-invest    { background: var(--info-bg); color: var(--info-fg);   border-color: color-mix(in srgb,var(--info-fg) 25%,transparent); }
    .bs-resolved  { background: var(--ok-bg);   color: var(--ok-fg);     border-color: color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .bs-dismissed { background: var(--neu-bg);  color: var(--neu-fg);    border-color: var(--rule); }

    /* ═══════════════════════════════════════
       TABLE
    ═══════════════════════════════════════ */
    .blt-table-wrap {
        margin: 22px 28px;
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .blt-table-wrap .dataTables_wrapper { padding: 0; font-family: var(--f-sans); }
    .blt-table-wrap .dataTables_filter,
    .blt-table-wrap .dataTables_length { display: none; }
    .blt-table-wrap .dataTables_info {
        padding: 10px 18px; font-size: 11px; color: var(--ink-faint);
        font-family: var(--f-mono); letter-spacing: .3px;
        border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .blt-table-wrap .dataTables_paginate {
        padding: 10px 18px; border-top: 1px solid var(--rule); background: var(--paper-lt);
    }
    .blt-table-wrap .paginate_button {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1.5px solid var(--rule-dk) !important; border-radius: 2px;
        font-size: 11px; font-weight: 600;
        color: var(--ink-muted) !important; background: #fff !important;
        cursor: pointer; margin: 0 2px; transition: all .13s;
    }
    .blt-table-wrap .paginate_button:hover { border-color: var(--accent) !important; color: var(--accent) !important; background: var(--accent-lt) !important; }
    .blt-table-wrap .paginate_button.current { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
    .blt-table-wrap .paginate_button.disabled { opacity: .35 !important; cursor: not-allowed; }

    #blotterTable { width: 100% !important; border-collapse: collapse; }
    #blotterTable thead th {
        padding: 10px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        cursor: pointer; user-select: none;
    }
    #blotterTable thead th:hover { color: var(--accent); }
    #blotterTable thead th.sorting_asc::after  { content: ' ↑'; color: var(--accent); }
    #blotterTable thead th.sorting_desc::after { content: ' ↓'; color: var(--accent); }
    #blotterTable tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    #blotterTable tbody tr:last-child { border-bottom: none; }
    #blotterTable tbody tr:hover { background: var(--accent-lt); }
    #blotterTable td { padding: 10px 14px; font-size: 12.5px; color: var(--ink); vertical-align: middle; }

    /* Case number cell */
    .td-case-no {
        font-family: var(--f-mono); font-size: 12px; font-weight: 700;
        color: var(--accent); letter-spacing: .5px; white-space: nowrap;
        cursor: pointer; text-decoration: none;
        border-bottom: 1px dashed color-mix(in srgb,var(--accent) 40%,transparent);
        display: inline-block;
    }
    .td-case-no:hover { border-bottom-style: solid; }
    .td-parties .party-name { font-weight: 600; font-size: 12.5px; color: var(--ink); }
    .td-parties .party-sub  { font-size: 10.5px; color: var(--ink-faint); margin-top: 1px; }
    .td-date { font-family: var(--f-mono); font-size: 11.5px; color: var(--ink-muted); white-space: nowrap; }
    .td-location { font-size: 12px; color: var(--ink-muted); max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .td-filed-by { font-size: 11.5px; color: var(--ink-faint); }

    /* Actions */
    .td-actions { display: flex; gap: 5px; }
    .act-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 2px; font-size: 9.5px;
        font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; border: 1.5px solid; font-family: var(--f-sans);
        transition: all .13s; white-space: nowrap; background: #fff;
        border-color: var(--rule-dk); color: var(--ink-muted);
    }
    .act-view:hover  { border-color: var(--info-fg); color: var(--info-fg); background: var(--info-bg); }
    .act-arch:hover  { border-color: var(--danger-fg); color: var(--danger-fg); background: var(--danger-bg); }

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
    .ui-dialog-buttonpane .ui-button:not(:first-child):hover { border-color: var(--ink-muted) !important; color: var(--ink) !important; }

    /* ═══════════════════════════════════════
       FORM FIELDS
    ═══════════════════════════════════════ */
    .modal-form  { max-height: 72vh; overflow-y: auto; }
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
    .fg-textarea { resize: vertical; min-height: 80px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

    /* ═══════════════════════════════════════
       VIEW MODAL — Case Record
    ═══════════════════════════════════════ */
    .case-header {
        display: grid; grid-template-columns: auto 1fr auto;
        align-items: center; gap: 18px;
        padding: 18px 22px;
        background: linear-gradient(to right, var(--accent-lt), var(--paper));
        border-bottom: 1px solid var(--rule);
    }
    .case-no-block {
        background: var(--accent); color: #fff;
        padding: 10px 16px; border-radius: 2px; text-align: center; flex-shrink: 0;
    }
    .case-no-lbl  { font-size: 7.5px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; opacity: .65; margin-bottom: 4px; }
    .case-no-num  { font-family: var(--f-mono); font-size: 15px; font-weight: 700; letter-spacing: 1px; }
    .case-title   { font-family: var(--f-serif); font-size: 14px; font-weight: 600; color: var(--ink-muted); margin-bottom: 6px; }
    .case-meta    { font-size: 11px; color: var(--ink-faint); }
    .case-meta span { margin-right: 12px; }

    /* Two-pane layout inside view modal */
    .view-two-col {
        display: grid; grid-template-columns: 1fr 1fr;
        border-bottom: 1px solid var(--rule);
    }
    .view-pane { padding: 16px 20px; }
    .view-pane + .view-pane { border-left: 1px solid var(--rule); }
    .vp-title {
        font-size: 8px; font-weight: 700; letter-spacing: 1.4px;
        text-transform: uppercase; color: var(--ink-faint);
        margin-bottom: 10px; display: flex; align-items: center; gap: 8px;
    }
    .vp-title::after { content: ''; flex: 1; height: 1px; background: var(--rule); }
    .vd-row { margin-bottom: 8px; }
    .vd-lbl { font-size: 8.5px; font-weight: 700; letter-spacing: 1.1px; text-transform: uppercase; color: var(--ink-faint); margin-bottom: 2px; }
    .vd-val { font-size: 12.5px; font-weight: 500; color: var(--ink); line-height: 1.5; }

    /* Inline status update */
    .status-update-bar {
        padding: 14px 20px;
        background: var(--paper-lt);
        border-bottom: 1px solid var(--rule);
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .sub-label {
        font-size: 9px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-muted); white-space: nowrap;
    }

    /* ═══════════════════════════════════════
       ARCHIVE + HISTORY DIALOGS
    ═══════════════════════════════════════ */
    .arc-stats-row {
        display: grid; grid-template-columns: repeat(4, 1fr);
        border-bottom: 1px solid var(--rule);
    }
    .arc-stat-cell {
        padding: 13px 16px; border-right: 1px solid var(--rule); text-align: center;
    }
    .arc-stat-cell:last-child { border-right: none; }
    .arc-stat-val {
        font-family: var(--f-mono); font-size: 20px; font-weight: 600;
        color: var(--ink); line-height: 1; margin-bottom: 4px;
    }
    .arc-stat-lbl {
        font-size: 7.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--ink-faint);
    }
    .arc-toolbar {
        padding: 11px 16px; background: var(--paper-lt); border-bottom: 1px solid var(--rule);
    }
    .arc-table-scroll { overflow-y: auto; max-height: 300px; }
    .arc-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .arc-table thead th {
        padding: 9px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.1px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        position: sticky; top: 0;
    }
    .arc-table tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    .arc-table tbody tr:hover { background: var(--paper-lt); }
    .arc-table td { padding: 10px 14px; vertical-align: middle; }
    .arc-footer {
        padding: 9px 16px; border-top: 1px solid var(--rule); background: var(--paper-lt);
        font-family: var(--f-mono); font-size: 9px; color: var(--ink-faint); letter-spacing: .5px;
    }

    /* History */
    .hist-toolbar { padding: 11px 16px; background: var(--paper-lt); border-bottom: 1px solid var(--rule); }
    .hist-scroll  { overflow-y: auto; max-height: 400px; }
    .hist-table   { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .hist-table thead th {
        padding: 9px 14px; background: var(--paper-lt); text-align: left;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.1px;
        text-transform: uppercase; color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk); white-space: nowrap;
        position: sticky; top: 0;
    }
    .hist-table tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    .hist-table tbody tr:hover { background: var(--paper-lt); }
    .hist-table td { padding: 10px 14px; vertical-align: middle; }
    .hist-footer { padding: 9px 16px; border-top: 1px solid var(--rule); background: var(--paper-lt); font-family: var(--f-mono); font-size: 9px; color: var(--ink-faint); letter-spacing: .5px; }

    /* Status flow arrows */
    .status-flow { display: flex; align-items: center; gap: 5px; }
    .sf-badge { display: inline-block; padding: 2px 7px; border-radius: 2px; font-size: 9px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; }
    .sf-pending    { background: var(--warn-bg); color: var(--warn-fg); }
    .sf-invest     { background: var(--info-bg); color: var(--info-fg); }
    .sf-resolved   { background: var(--ok-bg);   color: var(--ok-fg); }
    .sf-dismissed  { background: var(--neu-bg);  color: var(--neu-fg); }
    .sf-arrow      { color: var(--ink-faint); font-size: 11px; }

    .action-pill {
        display: inline-block; padding: 2px 8px; border-radius: 2px;
        font-size: 9px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; border: 1px solid;
    }
    .ap-status   { background: var(--info-bg); color: var(--info-fg); border-color: color-mix(in srgb,var(--info-fg) 25%,transparent); }
    .ap-archived { background: var(--warn-bg); color: var(--warn-fg); border-color: color-mix(in srgb,var(--warn-fg) 25%,transparent); }
    .ap-restored { background: var(--ok-bg);   color: var(--ok-fg);   border-color: color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .ap-other    { background: var(--neu-bg);  color: var(--neu-fg);  border-color: var(--rule); }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto blt-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Public Safety</div>
                        <div class="doc-title">Blotter Case Register</div>
                        <div class="doc-sub">Official record of barangay dispute and incident cases</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnArchivedCases">▤ Archived Cases</button>
                        <button class="btn btn-ghost" id="btnCaseHistory">◷ Case History</button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="blt-toolbar">
                    <div class="toolbar-left">
                        <input type="text" class="blt-search" id="bltTableSearch" placeholder="Search by case no., parties, location…">
                        <div class="status-filters" id="statusFilters">
                            <button class="sf-pill all active" data-status="">All</button>
                            <button class="sf-pill" data-status="pending">Pending</button>
                            <button class="sf-pill" data-status="under_investigation">Under Inv.</button>
                            <button class="sf-pill" data-status="resolved">Resolved</button>
                            <button class="sf-pill" data-status="dismissed">Dismissed</button>
                        </div>
                    </div>
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;" id="bltCount">
                        <?php
                        $cntS = $conn->prepare("SELECT COUNT(*) as c FROM blotter WHERE archived_at IS NULL");
                        $cntS->execute();
                        echo number_format($cntS->get_result()->fetch_assoc()['c'] ?? 0) . ' ACTIVE CASES';
                        ?>
                    </div>
                </div>
            </div>

            <?php if ($success): ?>
            <div style="margin:16px 28px 0;padding:12px 16px;background:var(--ok-bg);border:1px solid color-mix(in srgb,var(--ok-fg) 25%,transparent);border-radius:2px;font-size:12.5px;color:var(--ok-fg);font-weight:500;">
                ✓ <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div style="margin:16px 28px 0;padding:12px 16px;background:var(--danger-bg);border:1px solid color-mix(in srgb,var(--danger-fg) 25%,transparent);border-radius:2px;font-size:12.5px;color:var(--danger-fg);font-weight:500;">
                ⚠ <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- ── Blotter Table ── -->
            <div class="blt-table-wrap" style="margin:22px 28px;">
                <table id="blotterTable" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Case No.</th>
                            <th>Complainant</th>
                            <th>Respondent</th>
                            <th>Incident Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Filed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result !== false): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $sc = $statusConf[$row['status']] ?? ['label' => $row['status'], 'cls' => 'bs-dismissed'];
                        ?>
                        <tr data-status="<?= $row['status'] ?>">
                            <td>
                                <span class="td-case-no view-blotter-btn" data-id="<?= $row['id'] ?>">
                                    <?= htmlspecialchars($row['case_number']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="td-parties">
                                    <div class="party-name"><?= htmlspecialchars($row['complainant_name']) ?></div>
                                    <?php if ($row['complainant_contact']): ?>
                                    <div class="party-sub"><?= htmlspecialchars($row['complainant_contact']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="td-parties">
                                    <div class="party-name"><?= htmlspecialchars($row['respondent_name']) ?></div>
                                    <?php if ($row['respondent_contact']): ?>
                                    <div class="party-sub"><?= htmlspecialchars($row['respondent_contact']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="td-date"><?= date('M d, Y', strtotime($row['incident_date'])) ?></td>
                            <td class="td-location" title="<?= htmlspecialchars($row['incident_location']) ?>">
                                <?= htmlspecialchars($row['incident_location']) ?>
                            </td>
                            <td><span class="bs <?= $sc['cls'] ?>"><?= $sc['label'] ?></span></td>
                            <td class="td-filed-by"><?= htmlspecialchars($row['created_by_name'] ?? '—') ?></td>
                            <td>
                                <div class="td-actions">
                                    <button class="act-btn act-view view-blotter-btn" data-id="<?= $row['id'] ?>">View</button>
                                    <button class="act-btn act-arch archive-case-btn"
                                        data-id="<?= $row['id'] ?>"
                                        data-case="<?= htmlspecialchars($row['case_number']) ?>">Archive</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--ink-faint);font-style:italic;">Error loading cases.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ════════════════════════════
         MODAL: FILE NEW CASE
    ════════════════════════════ -->
    <div id="addBlotterModal" title="File New Case" class="hidden">
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="add_blotter">

            <div class="form-section">
                <div class="form-section-lbl">Complainant</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg"><label class="fg-label">Full Name <span class="req">*</span></label><input type="text" name="complainant_name" class="fg-input" required autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Contact No.</label><input type="text" name="complainant_contact" class="fg-input" autocomplete="off"></div>
                    </div>
                    <div class="fg"><label class="fg-label">Address</label><textarea name="complainant_address" class="fg-textarea" style="min-height:52px;"></textarea></div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Respondent</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg"><label class="fg-label">Full Name <span class="req">*</span></label><input type="text" name="respondent_name" class="fg-input" required autocomplete="off"></div>
                        <div class="fg"><label class="fg-label">Contact No.</label><input type="text" name="respondent_contact" class="fg-input" autocomplete="off"></div>
                    </div>
                    <div class="fg"><label class="fg-label">Address</label><textarea name="respondent_address" class="fg-textarea" style="min-height:52px;"></textarea></div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Incident Details</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg"><label class="fg-label">Date <span class="req">*</span></label><input type="date" name="incident_date" class="fg-input" required></div>
                        <div class="fg"><label class="fg-label">Time</label><input type="time" name="incident_time" class="fg-input"></div>
                        <div class="fg"><label class="fg-label">Initial Status</label>
                            <select name="status" class="fg-select">
                                <option value="pending">Pending</option>
                                <option value="under_investigation">Under Investigation</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg"><label class="fg-label">Location <span class="req">*</span></label><input type="text" name="incident_location" class="fg-input" required autocomplete="off"></div>
                    <div class="fg"><label class="fg-label">Description <span class="req">*</span></label><textarea name="incident_description" class="fg-textarea" required></textarea></div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;margin-top:4px;">
                        File Case &amp; Assign Case Number
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════
         MODAL: VIEW / EDIT CASE
    ════════════════════════════ -->
    <div id="viewBlotterModal" title="Case Record" class="hidden">
        <!-- Case header -->
        <div class="case-header">
            <div class="case-no-block">
                <div class="case-no-lbl">Case No.</div>
                <div class="case-no-num" id="vc-case-no">—</div>
            </div>
            <div>
                <div class="case-title" id="vc-parties">—</div>
                <div class="case-meta">
                    <span id="vc-date"></span>
                    <span id="vc-filed"></span>
                </div>
            </div>
            <div id="vc-status-badge"></div>
        </div>

        <!-- Inline status update -->
        <div class="status-update-bar">
            <span class="sub-label">Update Status</span>
            <select id="vc-status-select" class="fg-select" style="width:auto;padding:5px 10px;font-size:12px;">
                <option value="pending">Pending</option>
                <option value="under_investigation">Under Investigation</option>
                <option value="resolved">Resolved</option>
                <option value="dismissed">Dismissed</option>
            </select>
            <input type="date" id="vc-resolved-date" class="fg-input" style="width:auto;padding:5px 10px;font-size:12px;" placeholder="Resolved date (optional)">
            <button class="btn btn-primary btn-sm" id="vcSaveStatus">Save Status</button>
        </div>

        <form id="blotterForm" class="modal-form">
            <?= csrfTokenField() ?>
            <input type="hidden" name="id" id="vc-id">

            <!-- Parties -->
            <div class="view-two-col">
                <div class="view-pane">
                    <div class="vp-title">Complainant</div>
                    <div class="fg"><label class="fg-label">Full Name <span class="req">*</span></label><input type="text" name="complainant_name" id="vc-comp-name" class="fg-input" required></div>
                    <div class="fg"><label class="fg-label">Contact</label><input type="text" name="complainant_contact" id="vc-comp-contact" class="fg-input"></div>
                    <div class="fg"><label class="fg-label">Address</label><textarea name="complainant_address" id="vc-comp-addr" class="fg-textarea" style="min-height:52px;"></textarea></div>
                </div>
                <div class="view-pane">
                    <div class="vp-title">Respondent</div>
                    <div class="fg"><label class="fg-label">Full Name <span class="req">*</span></label><input type="text" name="respondent_name" id="vc-resp-name" class="fg-input" required></div>
                    <div class="fg"><label class="fg-label">Contact</label><input type="text" name="respondent_contact" id="vc-resp-contact" class="fg-input"></div>
                    <div class="fg"><label class="fg-label">Address</label><textarea name="respondent_address" id="vc-resp-addr" class="fg-textarea" style="min-height:52px;"></textarea></div>
                </div>
            </div>

            <!-- Incident -->
            <div class="form-section">
                <div class="form-section-lbl">Incident Details</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg"><label class="fg-label">Date <span class="req">*</span></label><input type="date" name="incident_date" id="vc-inc-date" class="fg-input" required></div>
                        <div class="fg"><label class="fg-label">Time</label><input type="time" name="incident_time" id="vc-inc-time" class="fg-input"></div>
                        <div class="fg"><label class="fg-label">Status</label>
                            <select name="status" id="vc-status" class="fg-select">
                                <option value="pending">Pending</option>
                                <option value="under_investigation">Under Investigation</option>
                                <option value="resolved">Resolved</option>
                                <option value="dismissed">Dismissed</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg"><label class="fg-label">Location <span class="req">*</span></label><input type="text" name="incident_location" id="vc-inc-loc" class="fg-input" required></div>
                    <div class="fg"><label class="fg-label">Description <span class="req">*</span></label><textarea name="incident_description" id="vc-inc-desc" class="fg-textarea" required></textarea></div>
                </div>
            </div>

            <!-- Resolution -->
            <div class="form-section">
                <div class="form-section-lbl">Resolution</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg"><label class="fg-label">Resolved Date</label><input type="date" name="resolved_date" id="vc-res-date" class="fg-input"></div>
                    </div>
                    <div class="fg"><label class="fg-label">Resolution Notes</label><textarea name="resolution" id="vc-resolution" class="fg-textarea"></textarea></div>
                </div>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════
         MODAL: ARCHIVED CASES
    ════════════════════════════ -->
    <div id="archivedCasesDialog" title="Archived Cases" class="hidden">
        <!-- Stats row -->
        <div class="arc-stats-row">
            <div class="arc-stat-cell"><div class="arc-stat-val" id="arc-total">—</div><div class="arc-stat-lbl">Total Archived</div></div>
            <div class="arc-stat-cell"><div class="arc-stat-val" id="arc-resolved">—</div><div class="arc-stat-lbl">Resolved</div></div>
            <div class="arc-stat-cell"><div class="arc-stat-val" id="arc-dismissed">—</div><div class="arc-stat-lbl">Dismissed</div></div>
            <div class="arc-stat-cell"><div class="arc-stat-val" id="arc-latest">—</div><div class="arc-stat-lbl">Last Archived</div></div>
        </div>
        <div class="arc-toolbar">
            <input type="text" class="blt-search" id="arcSearch" placeholder="Search archived cases…" style="width:100%;">
        </div>
        <div class="arc-table-scroll">
            <table class="arc-table">
                <thead><tr>
                    <th style="width:36px;">#</th>
                    <th>Case No.</th>
                    <th>Complainant vs Respondent</th>
                    <th>Status</th>
                    <th>Date Archived</th>
                    <th style="text-align:center;">Action</th>
                </tr></thead>
                <tbody id="arcBody">
                    <tr><td colspan="6" style="padding:24px;text-align:center;color:var(--ink-faint);font-style:italic;">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div class="arc-footer" id="arcFooter">—</div>
    </div>

    <!-- ════════════════════════════
         MODAL: CASE HISTORY
    ════════════════════════════ -->
    <div id="caseHistoryDialog" title="Case History Log" class="hidden">
        <div class="hist-toolbar">
            <input type="text" class="blt-search" id="histSearch" placeholder="Search by case number…" style="width:100%;">
        </div>
        <div class="hist-scroll">
            <table class="hist-table">
                <thead><tr>
                    <th>Case No.</th>
                    <th>Action</th>
                    <th>Status Change</th>
                    <th>Changed By</th>
                    <th>Date &amp; Time</th>
                </tr></thead>
                <tbody id="histBody">
                    <tr><td colspan="5" style="padding:24px;text-align:center;color:var(--ink-faint);font-style:italic;">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div class="hist-footer" id="histFooter">—</div>
    </div>

    <script src="js/index.js"></script>
</body>
</html>
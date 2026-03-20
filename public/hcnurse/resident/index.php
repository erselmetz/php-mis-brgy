<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$stmt = $conn->prepare("SELECT * FROM residents WHERE deleted_at IS NULL ORDER BY last_name ASC, first_name ASC");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Residents — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --paper:      #fdfcf9;
        --paper-lt:   #f9f7f3;
        --ink:        #1a1a1a;
        --ink-muted:  #5a5a5a;
        --ink-faint:  #a0a0a0;
        --rule:       #d8d4cc;
        --rule-dk:    #b8b4ac;
        --bg:         #edeae4;
        --accent:     var(--theme-primary, #2d5a27);
        --accent-lt:  color-mix(in srgb, var(--accent) 8%, white);
        --ok-bg:      #edfaf3; --ok-fg:   #1a5c35;
        --danger-bg:  #fdeeed; --danger-fg:#7a1f1a;
        --info-bg:    #edf3fa; --info-fg:  #1a3a5c;
        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 14px rgba(0,0,0,.05);
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body, input, button, select, textarea { font-family:var(--f-sans); }
    .res-page { background:var(--bg); min-height:100%; padding-bottom:56px; }

    /* ── Doc header ── */
    .doc-header { background:var(--paper); border-bottom:1px solid var(--rule); }
    .doc-header-inner {
        padding:20px 28px 18px;
        display:flex; align-items:flex-end;
        justify-content:space-between; gap:20px; flex-wrap:wrap;
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

    /* ── Toolbar ── */
    .res-toolbar {
        background:var(--paper-lt); border-bottom:3px solid var(--accent);
        padding:11px 28px;
        display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .res-search {
        padding:7px 12px; border:1.5px solid var(--rule-dk);
        border-radius:2px; font-size:13px; color:var(--ink);
        background:#fff; outline:none; width:280px;
        transition:border-color .15s, box-shadow .15s;
    }
    .res-search:focus {
        border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
    }
    .res-search::placeholder { color:var(--ink-faint); font-style:italic; font-size:12px; }

    /* ── Table wrapper ── */
    .res-table-wrap {
        margin:22px 28px;
        background:var(--paper);
        border:1px solid var(--rule); border-top:3px solid var(--accent);
        border-radius:2px; box-shadow:var(--shadow); overflow:hidden;
    }
    .res-table-wrap .dataTables_wrapper { padding:0; font-family:var(--f-sans); }
    .res-table-wrap .dataTables_filter,
    .res-table-wrap .dataTables_length { display:none; }
    .res-table-wrap .dataTables_info {
        padding:10px 18px; font-size:11px; color:var(--ink-faint);
        font-family:var(--f-mono); letter-spacing:.3px;
        border-top:1px solid var(--rule); background:var(--paper-lt);
    }
    .res-table-wrap .dataTables_paginate {
        padding:10px 18px; border-top:1px solid var(--rule);
        background:var(--paper-lt);
    }
    .res-table-wrap .paginate_button {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:30px; height:28px; padding:0 8px;
        border:1.5px solid var(--rule-dk) !important; border-radius:2px;
        font-size:11px; font-weight:600;
        color:var(--ink-muted) !important; background:#fff !important;
        cursor:pointer; margin:0 2px; transition:all .13s;
    }
    .res-table-wrap .paginate_button:hover   { border-color:var(--accent) !important; color:var(--accent) !important; background:var(--accent-lt) !important; }
    .res-table-wrap .paginate_button.current { background:var(--accent) !important; border-color:var(--accent) !important; color:#fff !important; }
    .res-table-wrap .paginate_button.disabled { opacity:.35 !important; }

    #residentsTable { width:100% !important; border-collapse:collapse; }
    #residentsTable thead th {
        padding:10px 14px; background:var(--paper-lt); text-align:left;
        font-size:8.5px; font-weight:700; letter-spacing:1.2px;
        text-transform:uppercase; color:var(--ink-muted);
        border-bottom:1px solid var(--rule-dk); white-space:nowrap;
        cursor:pointer; user-select:none;
    }
    #residentsTable thead th:hover { color:var(--accent); }
    #residentsTable thead th.sorting_asc::after  { content:' ↑'; color:var(--accent); }
    #residentsTable thead th.sorting_desc::after { content:' ↓'; color:var(--accent); }
    #residentsTable tbody tr { border-bottom:1px solid #f0ede8; transition:background .1s; }
    #residentsTable tbody tr:last-child { border-bottom:none; }
    #residentsTable tbody tr:hover { background:var(--accent-lt); }
    #residentsTable td { padding:10px 14px; font-size:12.5px; color:var(--ink); vertical-align:middle; }

    .td-name { font-weight:600; font-size:13px; color:var(--ink); margin-bottom:2px; }
    .td-id   { font-family:var(--f-mono); font-size:9.5px; color:var(--ink-faint); letter-spacing:.5px; }
    .gender-chip {
        display:inline-block; padding:2px 8px; border-radius:2px;
        font-size:9.5px; font-weight:700; letter-spacing:.4px;
        text-transform:uppercase; border:1px solid;
    }
    .gc-male   { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .gc-female { background:#fdf2f8; color:#9d174d; border-color:#f9a8d4; }
    .td-mono   { font-family:var(--f-mono); font-size:11.5px; color:var(--ink-muted); }
    .voter-dot { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; }
    .voter-dot::before { content:''; width:7px; height:7px; border-radius:50%; flex-shrink:0; }
    .vd-yes::before { background:var(--ok-fg); }
    .vd-no::before  { background:var(--rule-dk); }

    /* Actions */
    .td-actions { display:flex; gap:5px; }
    .act-btn {
        display:inline-flex; align-items:center;
        padding:4px 10px; border-radius:2px; font-size:9.5px;
        font-weight:700; letter-spacing:.4px; text-transform:uppercase;
        cursor:pointer; border:1.5px solid var(--rule-dk);
        font-family:var(--f-sans); transition:all .13s;
        background:#fff; color:var(--ink-muted); white-space:nowrap;
    }
    .act-view:hover { border-color:var(--info-fg); color:var(--info-fg); background:var(--info-bg); }
    .act-edit:hover { border-color:var(--accent);  color:var(--accent);  background:var(--accent-lt); }

    /* ════════════════════════════════
       DIALOG OVERRIDES
    ════════════════════════════════ */
    .ui-dialog {
        border:1px solid var(--rule-dk) !important; border-radius:2px !important;
        box-shadow:0 8px 48px rgba(0,0,0,.18) !important;
        padding:0 !important; font-family:var(--f-sans) !important;
    }
    .ui-dialog-titlebar {
        background:var(--accent) !important; border:none !important;
        border-radius:0 !important; padding:12px 16px !important;
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

    /* ════════════════════════════════
       VIEW MODAL
    ════════════════════════════════ */
    .view-body { padding:20px 22px; }
    .view-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .view-section-title {
        font-size:8px; font-weight:700; letter-spacing:1.6px;
        text-transform:uppercase; color:var(--ink-faint);
        display:flex; align-items:center; gap:8px; margin-bottom:12px;
    }
    .view-section-title::after { content:''; flex:1; height:1px; background:var(--rule); }
    .view-row { margin-bottom:10px; }
    .view-lbl { font-size:8.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--ink-faint); margin-bottom:2px; }
    .view-val { font-size:13px; font-weight:500; color:var(--ink); line-height:1.5; }
    .view-val-mono { font-family:var(--f-mono); font-size:12px; color:var(--ink-muted); }

    /* ════════════════════════════════
       EDIT MODAL
    ════════════════════════════════ */
    .modal-scroll   { max-height:68vh; overflow-y:auto; }
    .form-section   { padding:14px 18px 0; border-top:1px solid var(--rule); }
    .form-section:first-child { border-top:none; padding-top:18px; }
    .form-section-lbl {
        font-size:8px; font-weight:700; letter-spacing:1.6px;
        text-transform:uppercase; color:var(--ink-faint);
        margin-bottom:12px; display:flex; align-items:center; gap:8px;
    }
    .form-section-lbl::after { content:''; flex:1; height:1px; background:var(--rule); }
    .form-section-body { padding-bottom:14px; }
    .fg { margin-bottom:12px; }
    .fg-label {
        display:block; font-size:8.5px; font-weight:700;
        letter-spacing:1.2px; text-transform:uppercase;
        color:var(--ink-muted); margin-bottom:5px;
    }
    .fg-label .req { color:var(--danger-fg); }
    .fg-input, .fg-select, .fg-textarea {
        width:100%; padding:9px 12px;
        border:1.5px solid var(--rule-dk); border-radius:2px;
        font-family:var(--f-sans); font-size:13px; color:var(--ink);
        background:#fff; outline:none; transition:border-color .15s, box-shadow .15s;
    }
    .fg-input:focus, .fg-select:focus, .fg-textarea:focus {
        border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
    }
    .fg-input::placeholder { color:var(--ink-faint); font-style:italic; font-size:12px; }
    .fg-textarea { resize:vertical; min-height:70px; }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
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
                        <div class="doc-eyebrow">Barangay Bombongan — Health Center</div>
                        <div class="doc-title">Resident Registry</div>
                        <div class="doc-sub">Active registered residents — community health records</div>
                    </div>
                </div>
                <!-- Toolbar -->
                <div class="res-toolbar">
                    <input type="text" class="res-search" id="resSearch" placeholder="Search by name, address, contact…">
                    <span style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;">
                        <?php
                        $cnt   = $conn->query("SELECT COUNT(*) c FROM residents WHERE deleted_at IS NULL");
                        $total = $cnt ? (int)$cnt->fetch_assoc()['c'] : 0;
                        echo number_format($total) . ' RESIDENTS ON FILE';
                        ?>
                    </span>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="res-table-wrap">
                <table id="residentsTable" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Gender</th>
                            <th>Birthdate</th>
                            <th>Age</th>
                            <th>Civil Status</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Voter</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result): while ($row = $result->fetch_assoc()):
                            $name   = trim(implode(' ', array_filter([$row['first_name']??'', $row['middle_name']??'', $row['last_name']??'', $row['suffix']??''])));
                            $age    = AutoComputeAge($row['birthdate'] ?? '');
                            $gender = strtolower($row['gender'] ?? '');
                        ?>
                        <tr>
                            <td>
                                <div class="td-name"><?= htmlspecialchars($name) ?></div>
                                <div class="td-id">#<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></div>
                            </td>
                            <td>
                                <span class="gender-chip <?= $gender === 'male' ? 'gc-male' : 'gc-female' ?>">
                                    <?= htmlspecialchars($row['gender'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="td-mono"><?= htmlspecialchars($row['birthdate'] ?? '—') ?></td>
                            <td class="td-mono"><?= $age ?></td>
                            <td style="font-size:12px;color:var(--ink-muted);"><?= htmlspecialchars($row['civil_status'] ?? '—') ?></td>
                            <td class="td-mono" style="font-size:11px;"><?= htmlspecialchars($row['contact_no'] ?? '—') ?></td>
                            <td style="font-size:12px;color:var(--ink-muted);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($row['address'] ?? '—') ?>
                            </td>
                            <td>
                                <span class="voter-dot <?= $row['voter_status'] === 'Yes' ? 'vd-yes' : 'vd-no' ?>">
                                    <?= $row['voter_status'] === 'Yes' ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td>
                                <div class="td-actions">
                                    <button class="act-btn act-view view-btn" data-id="<?= $row['id'] ?>">View</button>
                                    <button class="act-btn act-edit edit-btn" data-id="<?= $row['id'] ?>">Edit</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="9" style="padding:32px;text-align:center;color:var(--ink-faint);font-style:italic;">
                                No residents found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ════════════════════════════
         MODAL: VIEW RESIDENT
    ════════════════════════════ -->
    <div id="viewResidentModal" title="Resident Record" class="hidden">
        <div class="view-body">
            <div class="view-grid">
                <!-- Left column -->
                <div>
                    <div class="view-section-title">Personal Information</div>
                    <div class="view-row"><div class="view-lbl">Full Name</div><div class="view-val" id="vw-name">—</div></div>
                    <div class="view-row"><div class="view-lbl">Gender</div><div class="view-val" id="vw-gender">—</div></div>
                    <div class="view-row"><div class="view-lbl">Birthdate</div><div class="view-val view-val-mono" id="vw-birthdate">—</div></div>
                    <div class="view-row"><div class="view-lbl">Age</div><div class="view-val" id="vw-age">—</div></div>
                    <div class="view-row"><div class="view-lbl">Birthplace</div><div class="view-val" id="vw-birthplace">—</div></div>
                    <div class="view-row"><div class="view-lbl">Civil Status</div><div class="view-val" id="vw-civil">—</div></div>
                    <div class="view-row"><div class="view-lbl">Religion</div><div class="view-val" id="vw-religion">—</div></div>
                    <div class="view-section-title" style="margin-top:16px;">Contact</div>
                    <div class="view-row"><div class="view-lbl">Contact No.</div><div class="view-val view-val-mono" id="vw-contact">—</div></div>
                    <div class="view-row"><div class="view-lbl">Address</div><div class="view-val" id="vw-address">—</div></div>
                </div>
                <!-- Right column -->
                <div>
                    <div class="view-section-title">Additional</div>
                    <div class="view-row"><div class="view-lbl">Occupation</div><div class="view-val" id="vw-occupation">—</div></div>
                    <div class="view-row"><div class="view-lbl">Citizenship</div><div class="view-val" id="vw-citizenship">—</div></div>
                    <div class="view-row"><div class="view-lbl">Voter Status</div><div class="view-val" id="vw-voter">—</div></div>
                    <div class="view-row"><div class="view-lbl">Disability</div><div class="view-val" id="vw-disability">—</div></div>
                    <div class="view-row"><div class="view-lbl">Household</div><div class="view-val" id="vw-household">—</div></div>
                    <div class="view-section-title" style="margin-top:16px;">Remarks</div>
                    <div class="view-row"><div class="view-val" id="vw-remarks" style="font-style:italic;color:var(--ink-muted);">—</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════
         MODAL: EDIT RESIDENT
    ════════════════════════════ -->
    <div id="editResidentModal" title="Edit Resident" class="hidden">
        <form id="editResidentForm" class="modal-scroll">
            <input type="hidden" name="id" id="editId">

            <!-- Name -->
            <div class="form-section">
                <div class="form-section-lbl">Full Name</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" id="editFirstName" class="fg-input" required autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Middle Name</label>
                            <input type="text" name="middle_name" id="editMiddleName" class="fg-input" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Last Name <span class="req">*</span></label>
                            <input type="text" name="last_name" id="editLastName" class="fg-input" required autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Suffix</label>
                            <input type="text" name="suffix" id="editSuffix" class="fg-input" placeholder="Jr., Sr.…" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal -->
            <div class="form-section">
                <div class="form-section-lbl">Personal Information</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Gender <span class="req">*</span></label>
                            <select name="gender" id="editGender" class="fg-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Birthdate <span class="req">*</span></label>
                            <input type="date" name="birthdate" id="editBirthdate" class="fg-input" required>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Civil Status</label>
                            <select name="civil_status" id="editCivil" class="fg-select">
                                <option>Single</option><option>Married</option>
                                <option>Widowed</option><option>Separated</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Birthplace</label>
                            <input type="text" name="birthplace" id="editBirthplace" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Religion</label>
                            <input type="text" name="religion" id="editReligion" class="fg-input" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact & Identity -->
            <div class="form-section">
                <div class="form-section-lbl">Contact &amp; Identity</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Occupation</label>
                            <input type="text" name="occupation" id="editOccupation" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Citizenship</label>
                            <input type="text" name="citizenship" id="editCitizenship" class="fg-input" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Contact No.</label>
                            <input type="text" name="contact_no" id="editContact" class="fg-input" placeholder="09XXXXXXXXX" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Address</label>
                            <input type="text" name="address" id="editAddress" class="fg-input" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Voter Status</label>
                            <select name="voter_status" id="editVoter" class="fg-select">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Disability Status</label>
                            <select name="disability_status" id="editDisability" class="fg-select">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Remarks</label>
                        <textarea name="remarks" id="editRemarks" class="fg-textarea" placeholder="Additional notes…"></textarea>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <script>
    $(function () {
        $('body').show();

        /* ── DataTable ── */
        const table = $('#residentsTable').DataTable({
            pageLength: 25,
            order: [[0, 'asc']],
            dom: 'tip',
            language: {
                info: 'Showing _START_–_END_ of _TOTAL_ residents',
                paginate: { previous: '‹', next: '›' }
            }
        });
        $('#resSearch').on('input', function () { table.search(this.value).draw(); });

        /* ── Alert helper ── */
        function showAlert(title, msg, type) {
            const col = type === 'success' ? 'var(--ok-fg)' : 'var(--danger-fg)';
            const id  = 'al_' + Date.now();
            $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
                <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
            </div>`);
            $(`#${id}`).dialog({
                autoOpen:true, modal:true, width:400, resizable:false,
                buttons:{ 'OK': function(){ $(this).dialog('close').remove(); } }
            });
        }
        function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

        /* ── Age helper ── */
        function calcAge(bd){
            if(!bd) return '—';
            const b=new Date(bd+'T00:00:00'), t=new Date();
            let a=t.getFullYear()-b.getFullYear();
            if(t.getMonth()<b.getMonth()||(t.getMonth()===b.getMonth()&&t.getDate()<b.getDate())) a--;
            return a + ' yrs old';
        }

        /* ════════════════════════
           VIEW MODAL
        ════════════════════════ */
        $('#viewResidentModal').dialog({
            autoOpen: false, modal: true, width: 820, resizable: true,
            buttons: { 'Close': function(){ $(this).dialog('close'); } }
        });

        $(document).on('click', '.view-btn', function(){
            const id = $(this).data('id');
            $.getJSON('get_resident.php', { id }, function(d){
                if(d.error){ showAlert('Error', d.error, 'danger'); return; }
                const name = [d.first_name, d.middle_name, d.last_name, d.suffix].filter(Boolean).join(' ');
                $('#vw-name').text(name || '—');
                $('#vw-gender').text(d.gender || '—');
                $('#vw-birthdate').text(d.birthdate || '—');
                $('#vw-age').text(calcAge(d.birthdate));
                $('#vw-birthplace').text(d.birthplace || '—');
                $('#vw-civil').text(d.civil_status || '—');
                $('#vw-religion').text(d.religion || '—');
                $('#vw-contact').text(d.contact_no || '—');
                $('#vw-address').text(d.address || '—');
                $('#vw-occupation').text(d.occupation || '—');
                $('#vw-citizenship').text(d.citizenship || '—');
                $('#vw-voter').text(d.voter_status || '—');
                $('#vw-disability').text(d.disability_status || '—');
                $('#vw-household').text(d.household_display || '—');
                $('#vw-remarks').text(d.remarks || '—');
                $('#viewResidentModal')
                    .dialog('option', 'title', 'Resident — ' + name)
                    .dialog('open');
            }).fail(()=>showAlert('Error','Failed to load resident data.','danger'));
        });

        /* ════════════════════════
           EDIT MODAL
        ════════════════════════ */
        $('#editResidentModal').dialog({
            autoOpen: false, modal: true, width: 680, resizable: false,
            buttons: {
                'Save Changes': function(){ submitEdit(); },
                'Cancel':       function(){ $(this).dialog('close'); }
            }
        });

        $(document).on('click', '.edit-btn', function(){
            const id = $(this).data('id');
            $.getJSON('get_resident.php', { id }, function(d){
                if(d.error){ showAlert('Error', d.error, 'danger'); return; }
                $('#editId').val(d.id);
                $('#editFirstName').val(d.first_name || '');
                $('#editMiddleName').val(d.middle_name || '');
                $('#editLastName').val(d.last_name || '');
                $('#editSuffix').val(d.suffix || '');
                $('#editGender').val(d.gender || 'Male');
                $('#editBirthdate').val(d.birthdate || '');
                $('#editBirthplace').val(d.birthplace || '');
                $('#editCivil').val(d.civil_status || 'Single');
                $('#editReligion').val(d.religion || '');
                $('#editOccupation').val(d.occupation || '');
                $('#editCitizenship').val(d.citizenship || 'Filipino');
                $('#editContact').val(d.contact_no || '');
                $('#editAddress').val(d.address || '');
                $('#editVoter').val(d.voter_status || 'No');
                $('#editDisability').val(d.disability_status || 'No');
                $('#editRemarks').val(d.remarks || '');
                const name = [d.first_name, d.last_name].filter(Boolean).join(' ');
                $('#editResidentModal')
                    .dialog('option', 'title', 'Edit — ' + name)
                    .dialog('open');
            }).fail(()=>showAlert('Error','Failed to load resident data.','danger'));
        });

        function submitEdit(){
            const data = {};
            $('#editResidentForm').serializeArray().forEach(f => data[f.name] = f.value);
            $.post('update_resident.php', data, function(res){
                if(res.success){
                    $('#editResidentModal').dialog('close');
                    showAlert('Saved', 'Resident updated successfully.', 'success');
                    setTimeout(()=> location.reload(), 1300);
                } else {
                    showAlert('Error', res.message || 'Update failed.', 'danger');
                }
            }, 'json').fail(()=> showAlert('Error', 'Request failed.', 'danger'));
        }
    });
    </script>
</body>
</html>
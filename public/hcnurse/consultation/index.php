<?php
/**
 * Consultation Records Page
 * Replaces: public/hcnurse/consultation/index.php
 *
 * BUG FIXES:
 * 1. Add consultation modal: consultation_type field (was missing sub_type field for some types)
 * 2. view.php notes JSON parse is now safe (try/catch in JS)
 * 3. JS vc-type reads program from separate field (view API returns it unpacked)
 * 4. Consultation type badge colors for all 6 types
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$sql = "
    SELECT c.id, c.resident_id, c.complaint, c.diagnosis,
           c.treatment, c.notes, c.consultation_date,
           r.first_name, r.middle_name, r.last_name, r.suffix, r.birthdate
    FROM consultations c
    INNER JOIN residents r ON r.id = c.resident_id
    WHERE r.deleted_at IS NULL
    ORDER BY c.consultation_date DESC, c.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

function fullNameRow(array $r): string {
    return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
        $r['first_name'] ?? '', $r['middle_name'] ?? '',
        $r['last_name']  ?? '', $r['suffix']      ?? ''
    ]))));
}
function safeNotes(string $raw): array {
    $out = ['time' => '', 'health_worker' => '', 'status' => '', 'remarks' => '', 'program' => '', 'sub_type' => ''];
    if ($raw && $raw[0] === '{') {
        $d = json_decode($raw, true);
        if (is_array($d)) return array_merge($out, $d);
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --paper:#fdfcf9;--paper-lt:#f9f7f3;--ink:#1a1a1a;--ink-muted:#5a5a5a;--ink-faint:#a0a0a0;
        --rule:#d8d4cc;--rule-dk:#b8b4ac;--bg:#edeae4;
        --accent:var(--theme-primary,#2d5a27);--accent-lt:color-mix(in srgb,var(--accent) 8%,white);
        --ok-bg:#edfaf3;--ok-fg:#1a5c35;--warn-bg:#fef9ec;--warn-fg:#7a5700;
        --danger-bg:#fdeeed;--danger-fg:#7a1f1a;--info-bg:#edf3fa;--info-fg:#1a3a5c;
        --neu-bg:#f3f1ec;--neu-fg:#5a5a5a;
        --f-serif:'Source Serif 4',Georgia,serif;
        --f-sans:'Source Sans 3','Segoe UI',sans-serif;
        --f-mono:'Source Code Pro','Courier New',monospace;
        --shadow:0 1px 2px rgba(0,0,0,.07),0 3px 14px rgba(0,0,0,.05);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body,input,button,select,textarea{font-family:var(--f-sans);}
    .con-page{background:var(--bg);min-height:100%;padding-bottom:56px;}

    .doc-header{background:var(--paper);border-bottom:1px solid var(--rule);}
    .doc-header-inner{padding:20px 28px 18px;display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;}
    .doc-eyebrow{font-size:8.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--ink-faint);display:flex;align-items:center;gap:8px;margin-bottom:6px;}
    .doc-eyebrow::before{content:'';width:18px;height:2px;background:var(--accent);display:inline-block;}
    .doc-title{font-family:var(--f-serif);font-size:22px;font-weight:700;color:var(--ink);letter-spacing:-.3px;margin-bottom:3px;}
    .doc-sub{font-size:12px;color:var(--ink-faint);font-style:italic;}
    .header-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}

    .con-toolbar{background:var(--paper-lt);border-bottom:3px solid var(--accent);padding:11px 28px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
    .con-search{padding:7px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-size:13px;color:var(--ink);background:#fff;outline:none;width:280px;transition:border-color .15s,box-shadow .15s;}
    .con-search:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);}
    .con-search::placeholder{color:var(--ink-faint);font-style:italic;font-size:12px;}

    .btn{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:2px;font-family:var(--f-sans);font-size:11.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .14s;white-space:nowrap;}
    .btn-primary{background:var(--accent);border-color:var(--accent);color:#fff;}
    .btn-primary:hover{filter:brightness(1.1);}
    .btn-ghost{background:#fff;border-color:var(--rule-dk);color:var(--ink-muted);}
    .btn-ghost:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-lt);}

    .con-table-wrap{margin:22px 28px;background:var(--paper);border:1px solid var(--rule);border-top:3px solid var(--accent);border-radius:2px;box-shadow:var(--shadow);overflow:hidden;}
    .con-table-wrap .dataTables_filter,.con-table-wrap .dataTables_length{display:none;}
    .con-table-wrap .dataTables_info{padding:10px 18px;font-size:11px;color:var(--ink-faint);font-family:var(--f-mono);border-top:1px solid var(--rule);background:var(--paper-lt);}
    .con-table-wrap .dataTables_paginate{padding:10px 18px;border-top:1px solid var(--rule);background:var(--paper-lt);}
    .con-table-wrap .paginate_button{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:28px;padding:0 8px;border:1.5px solid var(--rule-dk)!important;border-radius:2px;font-size:11px;font-weight:600;color:var(--ink-muted)!important;background:#fff!important;cursor:pointer;margin:0 2px;transition:all .13s;}
    .con-table-wrap .paginate_button:hover{border-color:var(--accent)!important;color:var(--accent)!important;background:var(--accent-lt)!important;}
    .con-table-wrap .paginate_button.current{background:var(--accent)!important;border-color:var(--accent)!important;color:#fff!important;}
    .con-table-wrap .paginate_button.disabled{opacity:.35!important;}

    #consultTable{width:100%!important;border-collapse:collapse;}
    #consultTable thead th{padding:10px 14px;background:var(--paper-lt);text-align:left;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-muted);border-bottom:1px solid var(--rule-dk);white-space:nowrap;cursor:pointer;user-select:none;}
    #consultTable thead th:hover{color:var(--accent);}
    #consultTable thead th.sorting_asc::after{content:' ↑';color:var(--accent);}
    #consultTable thead th.sorting_desc::after{content:' ↓';color:var(--accent);}
    #consultTable tbody tr{border-bottom:1px solid #f0ede8;transition:background .1s;}
    #consultTable tbody tr:hover{background:var(--accent-lt);}
    #consultTable td{padding:10px 14px;font-size:12.5px;color:var(--ink);vertical-align:middle;}

    .td-patient{font-weight:600;font-size:13px;margin-bottom:2px;}
    .td-age{font-family:var(--f-mono);font-size:9.5px;color:var(--ink-faint);}
    .td-date{font-family:var(--f-mono);font-size:11.5px;color:var(--ink-muted);white-space:nowrap;}
    .td-trunc{max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:12px;color:var(--ink-muted);}

    /* All 6 type badge colors */
    .type-badge{display:inline-block;padding:2px 8px;border-radius:2px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;border:1px solid;}
    .tb-immunization{background:#f5f3ff;color:#4c1d95;border-color:#ddd6fe;}
    .tb-maternal{background:#fff1f2;color:#9f1239;border-color:#fecdd3;}
    .tb-family_planning{background:#eff6ff;color:#1e40af;border-color:#bfdbfe;}
    .tb-prenatal{background:#fffbeb;color:#92400e;border-color:#fde68a;}
    .tb-postnatal{background:#f0fdfa;color:#134e4a;border-color:#99f6e4;}
    .tb-child_nutrition{background:#f0fdf4;color:#14532d;border-color:#bbf7d0;}
    .tb-default{background:var(--neu-bg);color:var(--neu-fg);border-color:var(--rule);}

    .status-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:2px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;border:1px solid;}
    .status-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}
    .sb-completed{background:var(--ok-bg);color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent);}
    .sb-ongoing{background:var(--warn-bg);color:var(--warn-fg);border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent);}
    .sb-dismissed{background:var(--neu-bg);color:var(--neu-fg);border-color:var(--rule);}
    .sb-followup{background:var(--info-bg);color:var(--info-fg);border-color:color-mix(in srgb,var(--info-fg) 25%,transparent);}

    .td-actions{display:flex;gap:5px;}
    .act-btn{display:inline-flex;align-items:center;padding:4px 10px;border-radius:2px;font-size:9.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid var(--rule-dk);font-family:var(--f-sans);transition:all .13s;background:#fff;color:var(--ink-muted);white-space:nowrap;}
    .act-view:hover{border-color:var(--info-fg);color:var(--info-fg);background:var(--info-bg);}
    .act-edit:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-lt);}
    .act-gen:hover{border-color:#7c3aed;color:#7c3aed;background:#f5f3ff;}

    /* Dialog overrides */
    .ui-dialog{border:1px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 8px 48px rgba(0,0,0,.18)!important;padding:0!important;font-family:var(--f-sans)!important;}
    .ui-dialog-titlebar{background:var(--accent)!important;border:none!important;border-radius:0!important;padding:12px 16px!important;}
    .ui-dialog-title{font-family:var(--f-sans)!important;font-size:11px!important;font-weight:700!important;letter-spacing:1px!important;text-transform:uppercase!important;color:#fff!important;}
    .ui-dialog-titlebar-close{background:rgba(255,255,255,.15)!important;border:1px solid rgba(255,255,255,.25)!important;border-radius:2px!important;color:#fff!important;width:24px!important;height:24px!important;top:50%!important;transform:translateY(-50%)!important;}
    .ui-dialog-content{padding:0!important;}
    .ui-dialog-buttonpane{background:var(--paper-lt)!important;border-top:1px solid var(--rule)!important;padding:12px 16px!important;margin:0!important;}
    .ui-dialog-buttonpane .ui-button{font-family:var(--f-sans)!important;font-size:11px!important;font-weight:700!important;letter-spacing:.5px!important;text-transform:uppercase!important;padding:7px 18px!important;border-radius:2px!important;cursor:pointer!important;}
    .ui-dialog-buttonpane .ui-button:first-child{background:var(--accent)!important;border:1.5px solid var(--accent)!important;color:#fff!important;}
    .ui-dialog-buttonpane .ui-button:not(:first-child){background:#fff!important;border:1.5px solid var(--rule-dk)!important;color:var(--ink-muted)!important;}

    /* View modal */
    .view-body{padding:20px 22px;}
    .view-header{display:flex;align-items:center;gap:16px;padding:16px 22px;background:var(--accent-lt);border-bottom:1px solid var(--rule);}
    .view-header-name{font-family:var(--f-serif);font-size:17px;font-weight:600;color:var(--ink);}
    .view-header-meta{font-size:11px;color:var(--ink-faint);margin-top:3px;}
    .view-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    .view-section-title{font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--ink-faint);display:flex;align-items:center;gap:8px;margin-bottom:12px;}
    .view-section-title::after{content:'';flex:1;height:1px;background:var(--rule);}
    .view-row{margin-bottom:10px;}
    .view-lbl{font-size:8.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:2px;}
    .view-val{font-size:13px;font-weight:500;color:var(--ink);line-height:1.6;white-space:pre-line;}
    .view-val-mono{font-family:var(--f-mono);font-size:12px;color:var(--ink-muted);}

    /* Form modals */
    .modal-scroll{max-height:68vh;overflow-y:auto;}
    .form-section{padding:14px 18px 0;border-top:1px solid var(--rule);}
    .form-section:first-child{border-top:none;padding-top:18px;}
    .form-section-lbl{font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:12px;display:flex;align-items:center;gap:8px;}
    .form-section-lbl::after{content:'';flex:1;height:1px;background:var(--rule);}
    .form-section-body{padding-bottom:14px;}
    .fg{margin-bottom:12px;}
    .fg-label{display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:5px;}
    .fg-label .req{color:var(--danger-fg);}
    .fg-input,.fg-select,.fg-textarea{width:100%;padding:9px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-family:var(--f-sans);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s;}
    .fg-input:focus,.fg-select:focus,.fg-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);}
    .fg-input::placeholder{color:var(--ink-faint);font-style:italic;font-size:12px;}
    .fg-textarea{resize:vertical;min-height:72px;}
    .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}

    .ui-autocomplete{border:1.5px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 4px 20px rgba(0,0,0,.12)!important;font-family:var(--f-sans)!important;font-size:13px!important;max-height:240px;overflow-y:auto!important;background:var(--paper)!important;}
    .ui-menu-item-wrapper{padding:9px 13px!important;border-bottom:1px solid #f0ede8!important;transition:background .1s!important;}
    .ui-menu-item-wrapper:hover,.ui-state-active{background:var(--accent-lt)!important;color:var(--ink)!important;}
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto con-page">

            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Health Center</div>
                        <div class="doc-title">Consultation Records</div>
                        <div class="doc-sub">Patient consultations — entry point for all care records</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnGenerateReport">↗ Generate Report</button>
                        <button class="btn btn-primary" id="btnAddConsult">+ Add Consultation</button>
                    </div>
                </div>
                <div class="con-toolbar">
                    <input type="text" class="con-search" id="conSearch" placeholder="Search patient name, complaint, diagnosis…">
                    <span style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;">
                        <?php
                        $cnt = $conn->query("SELECT COUNT(*) c FROM consultations");
                        echo number_format($cnt ? (int)$cnt->fetch_assoc()['c'] : 0) . ' RECORDS ON FILE';
                        ?>
                    </span>
                </div>
            </div>

            <div class="con-table-wrap">
                <table id="consultTable" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Chief Complaint</th>
                            <th>Diagnosis</th>
                            <th>Health Worker</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result): while ($row = $result->fetch_assoc()):
                            $name  = fullNameRow($row);
                            $age   = AutoComputeAge($row['birthdate'] ?? '');
                            $meta  = safeNotes($row['notes'] ?? '');
                            $prog  = $meta['program'] ?? '';
                            $stat  = $meta['status']  ?? '';
                            $statCls = match(strtolower($stat)) {
                                'completed' => 'sb-completed',
                                'ongoing'   => 'sb-ongoing',
                                'dismissed' => 'sb-dismissed',
                                'follow-up' => 'sb-followup',
                                default     => 'sb-dismissed'
                            };
                            $typeCls = in_array($prog, ['immunization','maternal','family_planning','prenatal','postnatal','child_nutrition'])
                                ? 'tb-' . $prog : 'tb-default';
                        ?>
                        <tr>
                            <td>
                                <div class="td-patient"><?= htmlspecialchars($name) ?></div>
                                <div class="td-age"><?= $age ?> yrs</div>
                            </td>
                            <td class="td-date"><?= htmlspecialchars($row['consultation_date'] ?? '—') ?></td>
                            <td>
                                <?php if ($prog): ?>
                                <span class="type-badge <?= $typeCls ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $prog)) ?>
                                </span>
                                <?php else: ?>
                                <span style="color:var(--ink-faint);font-size:11px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><div class="td-trunc"><?= htmlspecialchars($row['complaint'] ?? '—') ?></div></td>
                            <td><div class="td-trunc"><?= htmlspecialchars($row['diagnosis'] ?? '—') ?></div></td>
                            <td style="font-size:12px;color:var(--ink-muted);"><?= htmlspecialchars($meta['health_worker'] ?? '—') ?></td>
                            <td>
                                <?php if ($stat): ?>
                                <span class="status-badge <?= $statCls ?>"><?= htmlspecialchars($stat) ?></span>
                                <?php else: ?>
                                <span style="color:var(--ink-faint);font-size:11px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="td-actions">
                                    <button class="act-btn act-view viewConsultBtn" data-id="<?= (int)$row['id'] ?>">View</button>
                                    <button class="act-btn act-edit editConsultBtn" data-id="<?= (int)$row['id'] ?>">Edit</button>
                                    <button class="act-btn act-gen genConsultBtn"   data-resident-id="<?= (int)$row['resident_id'] ?>">Doc</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" style="padding:32px;text-align:center;color:var(--ink-faint);font-style:italic;">No consultations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- ADD CONSULTATION MODAL -->
    <div id="addConsultModal" title="Add Consultation" class="hidden">
        <form id="addConsultForm" class="modal-scroll">
            <input type="hidden" name="action"      value="add_consultation">
            <input type="hidden" name="resident_id" id="add_resident_id">

            <div class="form-section">
                <div class="form-section-lbl">Patient</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Resident / Patient <span class="req">*</span></label>
                        <input type="text" id="add_resident_name" class="fg-input"
                               placeholder="Type to search resident…" autocomplete="off">
                        <div style="font-size:10px;color:var(--ink-faint);margin-top:4px;font-style:italic;">
                            Select from the dropdown — do not type a name manually.
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Visit Details</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Date <span class="req">*</span></label>
                            <input type="text" name="consultation_date" id="add_date" class="fg-input" placeholder="mm/dd/yyyy">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Time</label>
                            <input type="time" name="consultation_time" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select name="status" class="fg-select">
                                <option value="Ongoing" selected>Ongoing</option>
                                <option value="Dismissed">Dismissed</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <!-- FIELD NAME: consultation_type — maps to 'program' in API -->
                        <div class="fg">
                            <label class="fg-label">Consultation Type <span class="req">*</span></label>
                            <select name="consultation_type" id="add_type" class="fg-select">
                                <option value="maternal">Maternal</option>
                                <option value="family_planning">Family Planning</option>
                                <option value="prenatal">Prenatal</option>
                                <option value="postnatal">Postnatal</option>
                                <option value="child_nutrition">Child Nutrition</option>
                                <option value="immunization">Immunization</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Sub Type</label>
                            <input type="text" name="sub_type" class="fg-input"
                                   placeholder="e.g. BCG, Pills, First Visit…" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Clinical Notes</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Chief Complaint <span class="req">*</span></label>
                        <textarea name="complaint" class="fg-textarea" placeholder="Describe the patient's complaint…"></textarea>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Diagnosis</label>
                            <textarea name="diagnosis" class="fg-textarea" placeholder="Clinical diagnosis…"></textarea>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Treatment / Prescription</label>
                            <textarea name="treatment" class="fg-textarea" placeholder="Treatment provided…"></textarea>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Attending Health Worker</label>
                            <input type="text" name="health_worker" class="fg-input"
                                   value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Remarks</label>
                            <input type="text" name="remarks" class="fg-input" autocomplete="off" placeholder="Additional notes…">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- VIEW CONSULTATION MODAL -->
    <div id="viewConsultModal" title="Consultation Record" class="hidden">
        <div class="view-header">
            <div>
                <div class="view-header-name" id="vc-name">—</div>
                <div class="view-header-meta" id="vc-meta">—</div>
            </div>
            <div style="margin-left:auto;" id="vc-status-wrap"></div>
        </div>
        <div class="view-body">
            <div class="view-grid">
                <div>
                    <div class="view-section-title">Visit Information</div>
                    <div class="view-row"><div class="view-lbl">Date</div><div class="view-val view-val-mono" id="vc-date">—</div></div>
                    <div class="view-row"><div class="view-lbl">Time</div><div class="view-val view-val-mono" id="vc-time">—</div></div>
                    <div class="view-row"><div class="view-lbl">Type</div><div class="view-val" id="vc-type">—</div></div>
                    <div class="view-row"><div class="view-lbl">Health Worker</div><div class="view-val" id="vc-worker">—</div></div>
                    <div class="view-section-title" style="margin-top:16px;">Chief Complaint</div>
                    <div class="view-row"><div class="view-val" id="vc-complaint" style="white-space:pre-line;">—</div></div>
                </div>
                <div>
                    <div class="view-section-title">Clinical Notes</div>
                    <div class="view-row"><div class="view-lbl">Diagnosis</div><div class="view-val" id="vc-diagnosis" style="white-space:pre-line;">—</div></div>
                    <div class="view-row"><div class="view-lbl">Treatment</div><div class="view-val" id="vc-treatment" style="white-space:pre-line;">—</div></div>
                    <div class="view-row"><div class="view-lbl">Remarks</div><div class="view-val" id="vc-remarks" style="font-style:italic;color:var(--ink-muted);">—</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT CONSULTATION MODAL -->
    <div id="editConsultModal" title="Edit Consultation" class="hidden">
        <form id="editConsultForm" class="modal-scroll">
            <input type="hidden" name="id"          id="edit_id">
            <input type="hidden" name="resident_id" id="edit_resident_id">

            <div class="form-section">
                <div class="form-section-lbl">Patient</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Resident</label>
                        <input type="text" id="edit_resident_name" class="fg-input" disabled
                               style="background:var(--paper-lt);color:var(--ink-muted);">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Visit Details</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Date <span class="req">*</span></label>
                            <input type="text" name="consultation_date" id="edit_date" class="fg-input" placeholder="mm/dd/yyyy">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Time</label>
                            <input type="time" name="consultation_time" id="edit_time" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select name="status" id="edit_status" class="fg-select">
                                <option value="Ongoing">Ongoing</option>
                                <option value="Dismissed">Dismissed</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Clinical Notes</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Chief Complaint <span class="req">*</span></label>
                        <textarea name="complaint" id="edit_complaint" class="fg-textarea"></textarea>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Diagnosis</label>
                            <textarea name="diagnosis" id="edit_diagnosis" class="fg-textarea"></textarea>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Treatment</label>
                            <textarea name="treatment" id="edit_treatment" class="fg-textarea"></textarea>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Health Worker</label>
                            <input type="text" name="health_worker" id="edit_worker" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Remarks</label>
                            <input type="text" name="remarks" id="edit_remarks" class="fg-input" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- GENERATE MODAL -->
    <div id="generateModal" title="Generate Document" class="hidden">
        <form id="generateForm" style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
            <input type="hidden" id="gen_resident_id" name="resident_id">
            <div class="fg">
                <label class="fg-label">Document Type</label>
                <select id="gen_doc" name="doc" class="fg-select">
                    <option value="report">Full Report (All Residents)</option>
                    <option value="summary">Patient Summary</option>
                    <option value="certificate">Health Certificate</option>
                </select>
            </div>
            <div class="fg">
                <label class="fg-label">Time Period</label>
                <select id="gen_period" name="period" class="fg-select">
                    <option value="daily">Daily — Today</option>
                    <option value="weekly">Weekly — This Week</option>
                    <option value="monthly" selected>Monthly — Select Month</option>
                </select>
            </div>
            <div id="gen_month_wrap" class="fg">
                <label class="fg-label">Month</label>
                <input type="month" id="gen_month" name="month" class="fg-input" value="<?= date('Y-m') ?>">
            </div>
            <div id="gen_purpose_wrap" class="fg" style="display:none;">
                <label class="fg-label">Purpose</label>
                <input type="text" id="gen_purpose" name="purpose" class="fg-input"
                       placeholder="e.g. School requirement, Medical clearance…" autocomplete="off">
            </div>
        </form>
    </div>

    <script>
    $(function () {
        $('body').show();

        const table = $('#consultTable').DataTable({
            pageLength: 25, order: [[1, 'desc']], dom: 'tip',
            language: { info: 'Showing _START_–_END_ of _TOTAL_ consultations', paginate: { previous: '‹', next: '›' } }
        });
        $('#conSearch').on('input', function () { table.search(this.value).draw(); });

        function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        function showAlert(title, msg, type, onClose) {
            const col = type === 'success' ? 'var(--ok-fg)' : 'var(--danger-fg)';
            const id  = 'al_' + Date.now();
            $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
                <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
            </div>`);
            $(`#${id}`).dialog({
                autoOpen: true, modal: true, width: 400, resizable: false,
                close: onClose || null,
                buttons: { 'OK': function () { $(this).dialog('close').remove(); } }
            });
        }

        $('#add_date').datepicker({ dateFormat: 'mm/dd/yy', changeMonth: true, changeYear: true });
        $('#edit_date').datepicker({ dateFormat: 'mm/dd/yy', changeMonth: true, changeYear: true });

        /* ── Add modal ── */
        $('#addConsultModal').dialog({
            autoOpen: false, modal: true, width: 760, resizable: false,
            buttons: {
                'Save Consultation': function () { $('#addConsultForm').trigger('submit'); },
                'Cancel':            function () { $(this).dialog('close'); }
            },
            open: function () {
                $('#addConsultForm')[0].reset();
                $('#add_resident_id').val('');
                $('#add_resident_name').val('');
                $('input[name="health_worker"]', this).val('<?= addslashes($_SESSION['name'] ?? '') ?>');
                $('#add_date').datepicker('setDate', new Date());
            }
        });
        $('#btnAddConsult').on('click', () => $('#addConsultModal').dialog('open'));

        $('#add_resident_name').autocomplete({
            appendTo: '#addConsultModal', minLength: 1,
            source: function (req, res) { $.getJSON('api/resident.php', { term: req.term }, res); },
            select: function (e, ui) {
                $('#add_resident_id').val(ui.item.id);
                $('#add_resident_name').val(ui.item.label);
                return false;
            }
        });
        $('#add_resident_name').on('input', function () { $('#add_resident_id').val(''); });

        $('#addConsultForm').on('submit', function (e) {
            e.preventDefault();
            if (!$('#add_resident_id').val()) {
                showAlert('Validation', 'Please select a resident from the dropdown.', 'danger');
                return;
            }
            $.ajax({
                url: 'api/add.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function (res) {
                    if (!res.success) { showAlert('Error', res.message || 'Failed.', 'danger'); return; }
                    $('#addConsultModal').dialog('close');
                    showAlert('Saved', 'Consultation record added.', 'success', function () { location.reload(); });
                },
                error: () => showAlert('Error', 'Server error.', 'danger')
            });
        });

        /* ── View modal ── */
        $('#viewConsultModal').dialog({ autoOpen: false, modal: true, width: 820, resizable: true,
            buttons: { 'Close': function () { $(this).dialog('close'); } } });

        $(document).on('click', '.viewConsultBtn', function () {
            const id = $(this).data('id');
            $.getJSON('api/view.php', { id }, function (res) {
                if (!res.success) { showAlert('Error', res.message || 'Not found.', 'danger'); return; }
                const d = res.data;

                $('#vc-name').text(d.fullname || '—');
                $('#vc-meta').text('Patient · Consultation #' + String(d.id).padStart(5, '0'));
                $('#vc-date').text(d.consultation_date || '—');
                $('#vc-time').text(d.time || '—');

                // BUG FIX: view API already unpacks program — don't JSON.parse notes
                $('#vc-type').text(d.notes
                    ? (() => { try { return (JSON.parse(d.notes).program || '—').replace(/_/g,' '); } catch(e){ return '—'; } })()
                    : '—');
                $('#vc-worker').text(d.health_worker || '—');
                $('#vc-complaint').text(d.complaint || '—');
                $('#vc-diagnosis').text(d.diagnosis || '—');
                $('#vc-treatment').text(d.treatment || '—');
                $('#vc-remarks').text(d.remarks || '—');

                const s = d.status || '';
                const cls = s === 'Completed' ? 'sb-completed' : s === 'Ongoing' ? 'sb-ongoing' : s === 'Follow-up' ? 'sb-followup' : 'sb-dismissed';
                $('#vc-status-wrap').html(s ? `<span class="status-badge ${cls}">${esc(s)}</span>` : '');

                $('#viewConsultModal').dialog('option', 'title', 'Consultation — ' + d.fullname).dialog('open');
            }).fail(() => showAlert('Error', 'Failed to load record.', 'danger'));
        });

        /* ── Edit modal ── */
        $('#editConsultModal').dialog({ autoOpen: false, modal: true, width: 720, resizable: false,
            buttons: {
                'Save Changes': function () { submitEdit(); },
                'Cancel':       function () { $(this).dialog('close'); }
            }
        });

        $(document).on('click', '.editConsultBtn', function () {
            const id = $(this).data('id');
            $.getJSON('api/view.php', { id }, function (res) {
                if (!res.success) { showAlert('Error', res.message || 'Not found.', 'danger'); return; }
                const d = res.data;
                $('#edit_id').val(d.id);
                $('#edit_resident_id').val(d.resident_id);
                $('#edit_resident_name').val(d.fullname || '');
                if (d.consultation_date && d.consultation_date.includes('-')) {
                    const p = d.consultation_date.split('-');
                    $('#edit_date').val(p[1] + '/' + p[2] + '/' + p[0]);
                } else { $('#edit_date').val(d.consultation_date || ''); }
                $('#edit_time').val(d.time || '');
                $('#edit_status').val(d.status || 'Completed');
                $('#edit_complaint').val(d.complaint || '');
                $('#edit_diagnosis').val(d.diagnosis || '');
                $('#edit_treatment').val(d.treatment || '');
                $('#edit_worker').val(d.health_worker || '');
                $('#edit_remarks').val(d.remarks || '');
                $('#editConsultModal').dialog('option', 'title', 'Edit — ' + d.fullname).dialog('open');
            }).fail(() => showAlert('Error', 'Failed to load record.', 'danger'));
        });

        function submitEdit() {
            $.ajax({
                url: 'api/edit.php', type: 'POST', data: $('#editConsultForm').serialize(), dataType: 'json',
                success: function (res) {
                    if (!res.success) { showAlert('Error', res.message || 'Failed.', 'danger'); return; }
                    $('#editConsultModal').dialog('close');
                    showAlert('Saved', 'Consultation updated.', 'success', function () { location.reload(); });
                },
                error: () => showAlert('Error', 'Server error.', 'danger')
            });
        }

        /* ── Generate modal ── */
        $('#generateModal').dialog({
            autoOpen: false, modal: true, width: 520, resizable: false,
            buttons: {
                'Generate & Print': function () {
                    const params = new URLSearchParams($('#generateForm').serialize());
                    window.open('api/generate.php?' + params.toString(), '_blank');
                    $(this).dialog('close');
                },
                'Cancel': function () { $(this).dialog('close'); }
            }
        });
        $('#gen_period').on('change', function () { $('#gen_month_wrap').toggle($(this).val() === 'monthly'); });
        $('#gen_doc').on('change', function () { $('#gen_purpose_wrap').toggle($(this).val() === 'certificate'); });

        $(document).on('click', '.genConsultBtn', function () {
            $('#gen_resident_id').val($(this).data('resident-id'));
            $('#gen_doc').val('summary').trigger('change');
            $('#gen_period').val('monthly').trigger('change');
            $('#generateModal').dialog('open');
        });
        $('#btnGenerateReport').on('click', function () {
            $('#gen_resident_id').val('');
            $('#gen_doc').val('report').trigger('change');
            $('#gen_period').val('monthly').trigger('change');
            $('#generateModal').dialog('open');
        });
    });
    </script>
</body>
</html>
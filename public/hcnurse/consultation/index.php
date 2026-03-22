<?php
/**
 * Enhanced Consultation Records Page
 * Replaces: public/hcnurse/consultation/index.php
 *
 * Form sections:
 *   1. Patient information (auto-filled from resident)
 *   2. Consult details (type, date, health worker, status)
 *   3. Vital signs & body measurements (BP, temp, pulse, weight, height → BMI)
 *   4. Chief complaint & diagnosis
 *   5. Health advice & patient education
 *   6. Health potential & risk profile
 *   7. Complete medical history & assessment
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$sql = "
    SELECT c.id, c.resident_id, c.complaint, c.diagnosis,
           c.consult_type, c.consult_status, c.health_worker,
           c.consultation_date, c.bp_systolic, c.bp_diastolic,
           c.temp_celsius, c.weight_kg, c.height_cm, c.bmi,
           c.risk_level, c.is_referred, c.notes,
           r.first_name, r.middle_name, r.last_name, r.suffix, r.birthdate
    FROM consultations c
    INNER JOIN residents r ON r.id = c.resident_id AND r.deleted_at IS NULL
    ORDER BY c.consultation_date DESC, c.id DESC
    LIMIT 200
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

function fullNameRow(array $r): string {
    return trim(preg_replace('/\s+/',' ',implode(' ',array_filter([
        $r['first_name']??'',$r['middle_name']??'',$r['last_name']??'',$r['suffix']??''
    ]))));
}

$typeConfig = [
    'general'         => ['color'=>'#5a5a5a','bg'=>'#f3f1ec','label'=>'General'],
    'maternal'        => ['color'=>'#9f1239','bg'=>'#fff1f2','label'=>'Maternal'],
    'family_planning' => ['color'=>'#1e40af','bg'=>'#eff6ff','label'=>'Family Planning'],
    'prenatal'        => ['color'=>'#92400e','bg'=>'#fffbeb','label'=>'Prenatal'],
    'postnatal'       => ['color'=>'#134e4a','bg'=>'#f0fdfa','label'=>'Postnatal'],
    'child_nutrition' => ['color'=>'#14532d','bg'=>'#f0fdf4','label'=>'Child Nutrition'],
    'immunization'    => ['color'=>'#4c1d95','bg'=>'#f5f3ff','label'=>'Immunization'],
    'other'           => ['color'=>'#5a5a5a','bg'=>'#f3f1ec','label'=>'Other'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --paper:#fdfcf9;--paper-lt:#f9f7f3;--paper-dk:#f0ede6;
        --ink:#1a1a1a;--ink-muted:#5a5a5a;--ink-faint:#a0a0a0;
        --rule:#d8d4cc;--rule-dk:#b8b4ac;--bg:#edeae4;
        --accent:var(--theme-primary,#2d5a27);
        --accent-lt:color-mix(in srgb,var(--accent) 8%,white);
        --ok-bg:#edfaf3;--ok-fg:#1a5c35;
        --warn-bg:#fef9ec;--warn-fg:#7a5700;
        --danger-bg:#fdeeed;--danger-fg:#7a1f1a;
        --info-bg:#edf3fa;--info-fg:#1a3a5c;
        --f-serif:'Source Serif 4',Georgia,serif;
        --f-sans:'Source Sans 3','Segoe UI',sans-serif;
        --f-mono:'Source Code Pro','Courier New',monospace;
        --shadow:0 1px 2px rgba(0,0,0,.07),0 3px 14px rgba(0,0,0,.05);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body,input,button,select,textarea{font-family:var(--f-sans);}
    .con-page{background:var(--bg);min-height:100%;padding-bottom:56px;}

    /* doc header */
    .doc-header{background:var(--paper);border-bottom:1px solid var(--rule);}
    .doc-header-inner{padding:20px 28px 18px;display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;}
    .doc-eyebrow{font-size:8.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--ink-faint);display:flex;align-items:center;gap:8px;margin-bottom:6px;}
    .doc-eyebrow::before{content:'';width:18px;height:2px;background:var(--accent);display:inline-block;}
    .doc-title{font-family:var(--f-serif);font-size:22px;font-weight:700;color:var(--ink);letter-spacing:-.3px;margin-bottom:3px;}
    .doc-sub{font-size:12px;color:var(--ink-faint);font-style:italic;}
    .header-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}

    /* toolbar */
    .con-toolbar{background:var(--paper-lt);border-bottom:3px solid var(--accent);padding:11px 28px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
    .con-search{padding:7px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-size:13px;color:var(--ink);background:#fff;outline:none;width:280px;transition:border-color .15s,box-shadow .15s;}
    .con-search:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);}
    .con-search::placeholder{color:var(--ink-faint);font-style:italic;font-size:12px;}

    /* buttons */
    .btn{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:2px;font-family:var(--f-sans);font-size:11.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .14s;white-space:nowrap;}
    .btn-primary{background:var(--accent);border-color:var(--accent);color:#fff;}
    .btn-primary:hover{filter:brightness(1.1);}
    .btn-ghost{background:#fff;border-color:var(--rule-dk);color:var(--ink-muted);}
    .btn-ghost:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-lt);}

    /* table */
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
    .td-trunc{max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:12px;color:var(--ink-muted);}
    .td-vitals{font-family:var(--f-mono);font-size:10.5px;color:var(--ink-muted);}
    .type-badge{display:inline-block;padding:2px 8px;border-radius:2px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;border:1px solid;}
    .risk-dot{display:inline-flex;align-items:center;gap:4px;font-size:11px;}
    .risk-dot::before{content:'';width:6px;height:6px;border-radius:50%;flex-shrink:0;}
    .risk-low::before{background:var(--ok-fg);}
    .risk-mod::before{background:#f59e0b;}
    .risk-high::before{background:var(--danger-fg);}

    /* dialog */
    .ui-dialog{border:1px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 8px 48px rgba(0,0,0,.18)!important;padding:0!important;font-family:var(--f-sans)!important;}
    .ui-dialog-titlebar{background:var(--accent)!important;border:none!important;border-radius:0!important;padding:12px 16px!important;}
    .ui-dialog-title{font-family:var(--f-sans)!important;font-size:11px!important;font-weight:700!important;letter-spacing:1px!important;text-transform:uppercase!important;color:#fff!important;}
    .ui-dialog-titlebar-close{background:rgba(255,255,255,.15)!important;border:1px solid rgba(255,255,255,.25)!important;border-radius:2px!important;color:#fff!important;width:24px!important;height:24px!important;top:50%!important;transform:translateY(-50%)!important;}
    .ui-dialog-content{padding:0!important;}
    .ui-dialog-buttonpane{background:var(--paper-lt)!important;border-top:1px solid var(--rule)!important;padding:12px 16px!important;margin:0!important;}
    .ui-dialog-buttonpane .ui-button{font-family:var(--f-sans)!important;font-size:11px!important;font-weight:700!important;letter-spacing:.5px!important;text-transform:uppercase!important;padding:7px 18px!important;border-radius:2px!important;cursor:pointer!important;}
    .ui-dialog-buttonpane .ui-button:first-child{background:var(--accent)!important;border:1.5px solid var(--accent)!important;color:#fff!important;}
    .ui-dialog-buttonpane .ui-button:not(:first-child){background:#fff!important;border:1.5px solid var(--rule-dk)!important;color:var(--ink-muted)!important;}
    .ui-autocomplete {z-index: 9999 !important;max-height: 220px;overflow-y: auto;overflow-x: hidden;}
    
    /* ── ADD / EDIT MODAL FORM (scrollable on small screens) ── */
    .consult-form-dialog{max-width:95vw;}
    .consult-form-dialog .ui-dialog-content{display:flex;flex-direction:column;max-height:min(85vh, 720px);overflow:hidden;}
    .consult-form-dialog #consultModal,.consult-form-dialog #consultForm{display:flex;flex-direction:column;min-height:0;flex:1;}
    .consult-form-dialog .form-stepper{flex-shrink:0;}
    .consult-form-dialog .modal-scroll{flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;}

    /* Section stepper in modal header */
    .form-stepper{display:flex;gap:0;padding:0 18px;background:var(--paper-lt);border-bottom:1px solid var(--rule);overflow-x:auto;scrollbar-width:none;}
    .form-stepper::-webkit-scrollbar{display:none;}
    .fs-step{display:flex;align-items:center;gap:6px;padding:10px 14px;font-size:11px;font-weight:600;color:var(--ink-faint);cursor:pointer;border-bottom:2.5px solid transparent;white-space:nowrap;transition:all .12s;}
    .fs-step:hover{color:var(--ink-muted);}
    .fs-step.active{color:var(--accent);border-bottom-color:var(--accent);}
    .fs-step-num{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:var(--rule);color:var(--ink-faint);font-size:9px;font-weight:700;flex-shrink:0;transition:all .12s;}
    .fs-step.active .fs-step-num{background:var(--accent);color:#fff;}
    .fs-step.done .fs-step-num{background:var(--ok-fg);color:#fff;}
    .fs-step.done .fs-step-num::after{content:'✓';}

    /* Section panels */
    .form-panel{display:none;}
    .form-panel.active{display:block;}

    .form-section{padding:14px 18px 0;border-top:1px solid var(--rule);}
    .form-section:first-child{border-top:none;padding-top:18px;}
    .form-section-lbl{font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:12px;display:flex;align-items:center;gap:8px;}
    .form-section-lbl::after{content:'';flex:1;height:1px;background:var(--rule);}
    .form-section-body{padding-bottom:14px;}
    .fg{margin-bottom:12px;}
    .fg-label{display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:5px;}
    .fg-label .req{color:var(--danger-fg);}
    .fg-hint{font-size:10px;color:var(--ink-faint);margin-top:4px;font-style:italic;}
    .fg-input,.fg-select,.fg-textarea{width:100%;padding:9px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-family:var(--f-sans);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s;}
    .fg-input:focus,.fg-select:focus,.fg-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);}
    .fg-input::placeholder{color:var(--ink-faint);font-style:italic;font-size:12px;}
    .fg-input:read-only{background:var(--paper-lt);color:var(--ink-muted);}
    .fg-textarea{resize:vertical;min-height:68px;}
    .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
    .form-grid-4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;}

    /* Patient info panel — read-only card */
    .patient-card{margin:0 18px 14px;padding:14px 16px;background:var(--paper-lt);border:1px solid var(--rule);border-left:3px solid var(--accent);border-radius:2px;}
    .patient-card-name{font-family:var(--f-serif);font-size:16px;font-weight:600;color:var(--ink);margin-bottom:6px;}
    .patient-card-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px 16px;font-size:12px;}
    .patient-card-grid .lbl{color:var(--ink-faint);font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;margin-bottom:1px;}
    .patient-card-grid .val{color:var(--ink-muted);}

    /* Vitals grid */
    .vitals-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:14px;}
    .vital-cell{padding:10px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;background:#fff;text-align:center;}
    .vital-cell-lbl{font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:6px;}
    .vital-cell .fg-input{text-align:center;padding:6px 4px;font-family:var(--f-mono);}
    /* BMI auto-display */
    .bmi-pill{display:inline-block;padding:4px 12px;border-radius:2px;font-family:var(--f-mono);font-size:13px;font-weight:600;margin-top:4px;border:1.5px solid var(--rule);}

    /* View modal */
    .vm-header{padding:16px 22px;background:var(--accent-lt);border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;}
    .vm-name{font-family:var(--f-serif);font-size:17px;font-weight:600;color:var(--ink);}
    .vm-meta{font-size:11px;color:var(--ink-faint);margin-top:3px;}
    .vm-body{padding:18px 22px;}
    .vm-section{margin-bottom:16px;}
    .vm-section-title{font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--ink-faint);display:flex;align-items:center;gap:8px;margin-bottom:10px;}
    .vm-section-title::after{content:'';flex:1;height:1px;background:var(--rule);}
    .vm-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
    .vm-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
    .vm-vitals-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;}
    .vm-vital{padding:8px 14px;background:var(--paper-lt);border:1px solid var(--rule);border-radius:2px;text-align:center;min-width:80px;}
    .vm-vital-lbl{font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:2px;}
    .vm-vital-val{font-family:var(--f-mono);font-size:14px;font-weight:600;color:var(--ink);}
    .vm-field{margin-bottom:10px;}
    .vm-lbl{font-size:8.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:2px;}
    .vm-val{font-size:13px;color:var(--ink);line-height:1.6;white-space:pre-line;}
    .vm-val-empty{font-size:12px;color:var(--ink-faint);font-style:italic;}

    .ui-autocomplete{border:1.5px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 4px 20px rgba(0,0,0,.12)!important;font-family:var(--f-sans)!important;font-size:13px!important;background:var(--paper)!important;max-height:200px;overflow-y:auto!important;}
    .ui-menu-item-wrapper{padding:8px 13px!important;border-bottom:1px solid #f0ede8!important;}
    .ui-state-active{background:var(--accent-lt)!important;color:var(--ink)!important;}

    /* nav bar bottom of stepper panels */
    .panel-nav{padding:12px 18px;border-top:1px solid var(--rule);background:var(--paper-lt);display:flex;justify-content:space-between;align-items:center;}
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
                    <div class="doc-sub">Clinical encounters — vitals, diagnosis, health advice, medical history</div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-ghost" id="btnGenerateReport">↗ Generate Report</button>
                    <button class="btn btn-primary" id="btnAdd">+ New Consultation</button>
                </div>
            </div>
            <div class="con-toolbar">
                <input type="text" class="con-search" id="conSearch" placeholder="Search patient, complaint, diagnosis…">
                <span style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;">
                    <?php
                    $cnt = $conn->query("SELECT COUNT(*) c FROM consultations");
                    echo number_format($cnt ? (int)$cnt->fetch_assoc()['c'] : 0) . ' RECORDS';
                    ?>
                </span>
            </div>
        </div>

        <div class="con-table-wrap">
            <table id="consultTable" class="display" style="width:100%;">
                <thead><tr>
                    <th>Patient</th><th>Date</th><th>Type</th>
                    <th>Vitals</th><th>Chief Complaint</th>
                    <th>Risk</th><th>Worker</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if ($result): while ($row = $result->fetch_assoc()):
                    $name = fullNameRow($row);
                    $age  = AutoComputeAge($row['birthdate']??'');
                    $type = $row['consult_type'] ?? 'general';
                    // Fall back to notes JSON for old rows
                    if ($type === 'general' && !empty($row['notes'])) {
                        $m = meta_decode($row['notes']);
                        if (!empty($m['program'])) $type = $m['program'];
                    }
                    $tc   = $typeConfig[$type] ?? $typeConfig['general'];
                    $risk = $row['risk_level'] ?? 'Low';
                    $riskCls = $risk==='High'?'risk-high':($risk==='Moderate'?'risk-mod':'risk-low');
                    // Vitals summary
                    $vParts = [];
                    if ($row['bp_systolic'] && $row['bp_diastolic']) $vParts[] = $row['bp_systolic'].'/'.$row['bp_diastolic'];
                    if ($row['temp_celsius']) $vParts[] = $row['temp_celsius'].'°C';
                    if ($row['weight_kg']) $vParts[] = $row['weight_kg'].'kg';
                    $vitals = $vParts ? implode(' · ',$vParts) : '—';
                    // Status
                    $cStatus = $row['consult_status'] ?? '';
                    if (!$cStatus && !empty($row['notes'])) {
                        $m2 = meta_decode($row['notes']);
                        $cStatus = $m2['status'] ?? '';
                    }
                    $cWorker = $row['health_worker'] ?? '';
                    if (!$cWorker && !empty($row['notes'])) {
                        $m3 = meta_decode($row['notes']);
                        $cWorker = $m3['health_worker'] ?? '';
                    }
                ?>
                <tr>
                    <td>
                        <div class="td-patient"><?= htmlspecialchars($name) ?></div>
                        <div class="td-age"><?= $age ?> yrs</div>
                    </td>
                    <td class="td-date"><?= htmlspecialchars($row['consultation_date']??'—') ?></td>
                    <td><span class="type-badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;border-color:<?= $tc['color'] ?>33;"><?= htmlspecialchars($tc['label']) ?></span></td>
                    <td><span class="td-vitals"><?= htmlspecialchars($vitals) ?></span></td>
                    <td><div class="td-trunc"><?= htmlspecialchars($row['complaint']??'—') ?></div></td>
                    <td><span class="risk-dot <?= $riskCls ?>"><?= htmlspecialchars($risk) ?></span></td>
                    <td style="font-size:12px;color:var(--ink-muted);"><?= htmlspecialchars($cWorker ?: '—') ?></td>
                    <td><span style="font-size:11px;color:var(--ink-muted);"><?= htmlspecialchars($cStatus ?: '—') ?></span></td>
                    <td>
                        <div style="display:flex;gap:5px;">
                            <button class="btn btn-ghost" style="padding:4px 10px;font-size:9.5px;" onclick="viewConsult(<?= (int)$row['id'] ?>)">View</button>
                            <button class="btn btn-ghost" style="padding:4px 10px;font-size:9.5px;" onclick="editConsult(<?= (int)$row['id'] ?>)">Edit</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="9" style="padding:32px;text-align:center;color:var(--ink-faint);font-style:italic;">No consultations found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- ══════════════════════════════════════════════
     ADD / EDIT CONSULTATION MODAL
══════════════════════════════════════════════ -->
<div id="consultModal" title="New Consultation" class="hidden">
    <form id="consultForm" style="max-height:74vh;overflow-y:auto;">
        <input type="hidden" name="id"          id="cf_id">
        <input type="hidden" name="resident_id" id="cf_res_id">

        <!-- Section stepper (sticky at top) -->
        <div class="form-stepper" id="formStepper">
            <div class="fs-step active" data-step="1"><span class="fs-step-num">1</span> Patient</div>
            <div class="fs-step"        data-step="2"><span class="fs-step-num">2</span> Consult Details</div>
            <div class="fs-step"        data-step="3"><span class="fs-step-num">3</span> Vital Signs</div>
            <div class="fs-step"        data-step="4"><span class="fs-step-num">4</span> Complaint &amp; Diagnosis</div>
            <div class="fs-step"        data-step="5"><span class="fs-step-num">5</span> Health Advice</div>
            <div class="fs-step"        data-step="6"><span class="fs-step-num">6</span> Health Profile</div>
            <div class="fs-step"        data-step="7"><span class="fs-step-num">7</span> Medical History</div>
        </div>

        <!-- Scrollable panels area -->
        <div class="modal-scroll">
        <!-- ══ PANEL 1: Patient ══ -->
        <div class="form-panel active" id="panel1">
            <div class="form-section" style="border-top:none;padding-top:16px;">
                <div class="form-section-lbl">Search &amp; select patient</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Resident / Patient <span class="req">*</span></label>
                        <input type="text" id="cf_res_name" class="fg-input"
                               placeholder="Type name to search…" autocomplete="off">
                        <div class="fg-hint">Select from dropdown — required before proceeding</div>
                    </div>
                </div>
            </div>
            <!-- Patient card fills after selection -->
            <div id="patientCard" class="patient-card" style="display:none;">
                <div class="patient-card-name" id="pc_name">—</div>
                <div class="patient-card-grid">
                    <div><div class="lbl">Birthdate / Age</div><div class="val" id="pc_age">—</div></div>
                    <div><div class="lbl">Gender</div><div class="val" id="pc_gender">—</div></div>
                    <div><div class="lbl">Civil Status</div><div class="val" id="pc_civil">—</div></div>
                    <div><div class="lbl">Contact</div><div class="val" id="pc_contact">—</div></div>
                    <div><div class="lbl">Occupation</div><div class="val" id="pc_occ">—</div></div>
                    <div><div class="lbl">Address</div><div class="val" id="pc_addr">—</div></div>
                </div>
            </div>
            <div class="panel-nav">
                <span></span>
                <button type="button" class="btn btn-primary" onclick="goStep(2)">Next: Consult Details →</button>
            </div>
        </div>

        <!-- ══ PANEL 2: Consult Details ══ -->
        <div class="form-panel" id="panel2">
            <div class="form-section" style="border-top:none;padding-top:16px;">
                <div class="form-section-lbl">Consultation details</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Date <span class="req">*</span></label>
                            <input type="date" name="consultation_date" id="cf_date" class="fg-input" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Time</label>
                            <input type="time" name="consultation_time" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Consult Type <span class="req">*</span></label>
                            <select name="consult_type" id="cf_type" class="fg-select">
                                <option value="general">General</option>
                                <option value="maternal">Maternal</option>
                                <option value="family_planning">Family Planning</option>
                                <option value="prenatal">Prenatal</option>
                                <option value="postnatal">Postnatal</option>
                                <option value="child_nutrition">Child Nutrition</option>
                                <option value="immunization">Immunization</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Sub Type / Category</label>
                            <input type="text" name="sub_type" class="fg-input"
                                   placeholder="e.g. Prenatal Visit 1, BCG…" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Health Worker</label>
                            <input type="text" name="health_worker" id="cf_worker" class="fg-input"
                                   value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Status</label>
                            <select name="consult_status" class="fg-select">
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Dismissed">Dismissed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Risk Level</label>
                            <select name="risk_level" class="fg-select">
                                <option value="Low">Low Risk</option>
                                <option value="Moderate">Moderate Risk</option>
                                <option value="High">High Risk</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Follow-up Date</label>
                            <input type="date" name="follow_up_date" class="fg-input">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Referred To</label>
                            <input type="text" name="referred_to" class="fg-input"
                                   placeholder="Hospital / specialist" autocomplete="off">
                            <label style="display:flex;align-items:center;gap:5px;margin-top:6px;font-size:12px;color:var(--ink-muted);cursor:pointer;">
                                <input type="checkbox" name="is_referred" value="1"> Referred out
                            </label>
                        </div>
                    </div>
                    <!-- Link to care visit (optional) -->
                    <div class="fg" style="margin-top:4px;">
                        <label class="fg-label">Link to Care Visit (optional)</label>
                        <input type="number" name="care_visit_id" class="fg-input"
                               placeholder="care_visits.id — leave blank if not linked" style="max-width:280px;">
                        <div class="fg-hint">For care programme visits (prenatal, FP, etc.) — links this consult to the structured care record</div>
                    </div>
                </div>
            </div>
            <div class="panel-nav">
                <button type="button" class="btn btn-ghost" onclick="goStep(1)">← Patient</button>
                <button type="button" class="btn btn-primary" onclick="goStep(3)">Next: Vital Signs →</button>
            </div>
        </div>

        <!-- ══ PANEL 3: Vital Signs & Measurements ══ -->
        <div class="form-panel" id="panel3">
            <div class="form-section" style="border-top:none;padding-top:16px;">
                <div class="form-section-lbl">Vital signs</div>
                <div class="form-section-body">
                    <div class="vitals-grid">
                        <div class="vital-cell">
                            <div class="vital-cell-lbl">Temp (°C)</div>
                            <input type="number" name="temp_celsius" step="0.1" min="34" max="43" class="fg-input" placeholder="36.8">
                        </div>
                        <div class="vital-cell">
                            <div class="vital-cell-lbl">BP Sys (mmHg)</div>
                            <input type="number" name="bp_systolic" min="60" max="250" class="fg-input" placeholder="120">
                        </div>
                        <div class="vital-cell">
                            <div class="vital-cell-lbl">BP Dia (mmHg)</div>
                            <input type="number" name="bp_diastolic" min="40" max="160" class="fg-input" placeholder="80">
                        </div>
                        <div class="vital-cell">
                            <div class="vital-cell-lbl">Pulse (bpm)</div>
                            <input type="number" name="pulse_rate" min="30" max="250" class="fg-input" placeholder="72">
                        </div>
                        <div class="vital-cell">
                            <div class="vital-cell-lbl">RR (/min)</div>
                            <input type="number" name="respiratory_rate" min="8" max="60" class="fg-input" placeholder="16">
                        </div>
                        <div class="vital-cell">
                            <div class="vital-cell-lbl">SpO2 (%)</div>
                            <input type="number" name="o2_saturation" min="50" max="100" class="fg-input" placeholder="98">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="form-section-lbl">Body measurements</div>
                <div class="form-section-body">
                    <div class="form-grid-4">
                        <div class="fg">
                            <label class="fg-label">Weight (kg)</label>
                            <input type="number" name="weight_kg" id="cf_weight" step="0.1" class="fg-input" placeholder="60.0">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Height (cm)</label>
                            <input type="number" name="height_cm" id="cf_height" step="0.5" class="fg-input" placeholder="160.0">
                        </div>
                        <div class="fg">
                            <label class="fg-label">BMI (auto)</label>
                            <input type="text" id="cf_bmi_display" class="fg-input" readonly placeholder="—">
                            <div id="cf_bmi_class" class="bmi-pill" style="display:none;"></div>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Waist (cm)</label>
                            <input type="number" name="waist_cm" step="0.5" class="fg-input" placeholder="—">
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-nav">
                <button type="button" class="btn btn-ghost" onclick="goStep(2)">← Consult Details</button>
                <button type="button" class="btn btn-primary" onclick="goStep(4)">Next: Complaint →</button>
            </div>
        </div>

        <!-- ══ PANEL 4: Chief Complaint & Diagnosis ══ -->
        <div class="form-panel" id="panel4">
            <div class="form-section" style="border-top:none;padding-top:16px;">
                <div class="form-section-lbl">Chief complaint</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg" style="grid-column:1/-1">
                            <label class="fg-label">Chief Complaint <span class="req">*</span></label>
                            <textarea name="complaint" id="cf_complaint" class="fg-textarea"
                                      placeholder="Describe the patient's main complaint in their own words…"></textarea>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Duration</label>
                            <input type="text" name="complaint_duration" class="fg-input"
                                   placeholder="e.g. 3 days, 2 weeks" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Onset</label>
                            <select name="complaint_onset" class="fg-select">
                                <option>Sudden</option><option>Gradual</option><option>Chronic</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="form-section-lbl">Diagnosis</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Primary Diagnosis</label>
                            <textarea name="diagnosis" id="cf_diagnosis" class="fg-textarea"
                                      placeholder="Primary diagnosis / working diagnosis…"></textarea>
                            <input type="text" name="icd_code" class="fg-input"
                                   placeholder="ICD-10 code (optional)" autocomplete="off" style="margin-top:6px;">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Secondary Diagnosis</label>
                            <textarea name="secondary_diagnosis" class="fg-textarea"
                                      placeholder="Co-morbidities / secondary findings…"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="form-section-lbl">Treatment &amp; Prescription</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Treatment / Management</label>
                            <textarea name="treatment" class="fg-textarea"
                                      placeholder="Management plan, procedures performed…"></textarea>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Medicines Prescribed</label>
                            <textarea name="medicines_prescribed" class="fg-textarea"
                                      placeholder="Drug name, dose, frequency, duration…"></textarea>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Procedures Done</label>
                        <input type="text" name="procedures_done" class="fg-input"
                               placeholder="e.g. Wound dressing, Nebulization, Blood extraction…" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="panel-nav">
                <button type="button" class="btn btn-ghost" onclick="goStep(3)">← Vital Signs</button>
                <button type="button" class="btn btn-primary" onclick="goStep(5)">Next: Health Advice →</button>
            </div>
        </div>

        <!-- ══ PANEL 5: Health Advice & Education ══ -->
        <div class="form-panel" id="panel5">
            <div class="form-section" style="border-top:none;padding-top:16px;">
                <div class="form-section-lbl">Health advice given to patient</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Health Advice / Recommendations</label>
                        <textarea name="health_advice" class="fg-textarea" style="min-height:90px;"
                                  placeholder="Specific advice given: diet restrictions, activity, wound care, follow-up instructions…"></textarea>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Lifestyle Advice</label>
                        <textarea name="lifestyle_advice" class="fg-textarea"
                                  placeholder="Diet, exercise, smoking cessation, stress management, sleep hygiene…"></textarea>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Patient Education Topics Discussed</label>
                        <textarea name="patient_education" class="fg-textarea"
                                  placeholder="Topics explained to patient / family: disease management, medication adherence, when to return…"></textarea>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Assessment / Clinical Summary</label>
                        <textarea name="assessment" class="fg-textarea"
                                  placeholder="Subjective + Objective + Assessment (SOAP note format)…"></textarea>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Plan</label>
                            <textarea name="plan" class="fg-textarea"
                                      placeholder="Next steps, further tests ordered, referrals planned…"></textarea>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Prognosis</label>
                            <select name="prognosis" class="fg-select">
                                <option value="NA">Not assessed</option>
                                <option>Good</option><option>Fair</option><option>Poor</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-nav">
                <button type="button" class="btn btn-ghost" onclick="goStep(4)">← Complaint</button>
                <button type="button" class="btn btn-primary" onclick="goStep(6)">Next: Health Profile →</button>
            </div>
        </div>

        <!-- ══ PANEL 6: Health Potential & Risk Profile ══ -->
        <div class="form-panel" id="panel6">
            <div class="form-section" style="border-top:none;padding-top:16px;">
                <div class="form-section-lbl">Lifestyle &amp; risk factors</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Smoking Status</label>
                            <select name="smoking_status" class="fg-select">
                                <option value="NA">Not assessed</option>
                                <option>Never</option><option>Former</option><option>Current</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Alcohol Use</label>
                            <select name="alcohol_use" class="fg-select">
                                <option value="NA">Not assessed</option>
                                <option>None</option><option>Occasional</option><option>Regular</option><option>Heavy</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Physical Activity Level</label>
                            <select name="physical_activity" class="fg-select">
                                <option value="NA">Not assessed</option>
                                <option>Sedentary</option><option>Light</option><option>Moderate</option><option>Active</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Nutritional Status</label>
                            <select name="nutritional_status" class="fg-select">
                                <option value="NA">Not assessed</option>
                                <option>Normal</option><option>Underweight</option><option>Overweight</option><option>Obese</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Mental Health Screening</label>
                            <select name="mental_health_screen" class="fg-select">
                                <option value="Not screened">Not screened</option>
                                <option>Normal</option><option>Needs follow-up</option><option>Referred</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="form-section-lbl">Social history</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Occupation</label>
                            <input type="text" name="occupation" id="cf_occ" class="fg-input" autocomplete="off">
                        </div>
                        <div class="fg">
                            <label class="fg-label">Civil Status</label>
                            <select name="civil_status" id="cf_civil" class="fg-select">
                                <option value="">—</option>
                                <option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Educational Attainment</label>
                            <select name="educational_attainment" class="fg-select">
                                <option value="">—</option>
                                <option>No formal education</option><option>Elementary</option><option>High School</option>
                                <option>Vocational</option><option>College</option><option>Post-graduate</option>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Living Conditions</label>
                        <input type="text" name="living_conditions" class="fg-input"
                               placeholder="Housing type, no. of household members, access to water/sanitation…" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="panel-nav">
                <button type="button" class="btn btn-ghost" onclick="goStep(5)">← Health Advice</button>
                <button type="button" class="btn btn-primary" onclick="goStep(7)">Next: Medical History →</button>
            </div>
        </div>

        <!-- ══ PANEL 7: Complete Medical History ══ -->
        <div class="form-panel" id="panel7">
            <div class="form-section" style="border-top:none;padding-top:16px;">
                <div class="form-section-lbl">Past &amp; current medical history</div>
                <div class="form-section-body">
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Past Medical History</label>
                            <textarea name="past_medical_history" class="fg-textarea"
                                      placeholder="Previous illnesses, surgeries, hospitalizations, accidents…"></textarea>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Family History</label>
                            <textarea name="family_history" class="fg-textarea"
                                      placeholder="Hereditary / familial conditions (hypertension, diabetes, cancer…)"></textarea>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Current Maintenance Medications</label>
                            <textarea name="current_medications" class="fg-textarea"
                                      placeholder="List all current medications (name + dose + frequency)…"></textarea>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Known Allergies</label>
                            <textarea name="known_allergies" class="fg-textarea"
                                      placeholder="Drug allergies, food allergies, environmental allergens…"></textarea>
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Immunization History</label>
                        <textarea name="immunization_history" class="fg-textarea"
                                  placeholder="Vaccines received (outside normal NIP schedule), travel vaccines…"></textarea>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Remarks / Additional Notes</label>
                        <textarea name="remarks" class="fg-textarea"
                                  placeholder="Any other clinical notes…"></textarea>
                    </div>
                </div>
            </div>
            <div class="panel-nav" style="justify-content:space-between;">
                <button type="button" class="btn btn-ghost" onclick="goStep(6)">← Health Profile</button>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn btn-ghost" id="btnQuickSave">Save (quick)</button>
                    <button type="submit" class="btn btn-primary" id="btnFinalSave">✓ Save Consultation</button>
                </div>
            </div>
        </div>
        </div><!-- /.modal-scroll -->
    </form>
</div>

<!-- ════════════════════════════
     VIEW MODAL
════════════════════════════ -->
<div id="viewModal" title="Consultation Record" class="hidden">
    <div class="vm-header">
        <div>
            <div class="vm-name" id="vm_name">—</div>
            <div class="vm-meta" id="vm_meta">—</div>
        </div>
        <div id="vm_badges" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
    </div>
    <div class="vm-body" style="max-height:74vh;overflow-y:auto;">

        <!-- Vital signs strip -->
        <div class="vm-section">
            <div class="vm-section-title">Vital signs &amp; measurements</div>
            <div class="vm-vitals-row" id="vm_vitals">
                <div class="vm-vital"><div class="vm-vital-lbl">Temp</div><div class="vm-vital-val" id="vm_temp">—</div></div>
                <div class="vm-vital"><div class="vm-vital-lbl">BP</div><div class="vm-vital-val" id="vm_bp">—</div></div>
                <div class="vm-vital"><div class="vm-vital-lbl">Pulse</div><div class="vm-vital-val" id="vm_pulse">—</div></div>
                <div class="vm-vital"><div class="vm-vital-lbl">RR</div><div class="vm-vital-val" id="vm_rr">—</div></div>
                <div class="vm-vital"><div class="vm-vital-lbl">SpO2</div><div class="vm-vital-val" id="vm_spo2">—</div></div>
                <div class="vm-vital"><div class="vm-vital-lbl">Weight</div><div class="vm-vital-val" id="vm_weight">—</div></div>
                <div class="vm-vital"><div class="vm-vital-lbl">Height</div><div class="vm-vital-val" id="vm_height">—</div></div>
                <div class="vm-vital"><div class="vm-vital-lbl">BMI</div><div class="vm-vital-val" id="vm_bmi">—</div></div>
            </div>
        </div>

        <div class="vm-grid">
            <div>
                <div class="vm-section">
                    <div class="vm-section-title">Chief complaint</div>
                    <div class="vm-field"><div class="vm-val" id="vm_complaint"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Duration / Onset</div><div class="vm-val" id="vm_duration"></div></div>
                </div>
                <div class="vm-section">
                    <div class="vm-section-title">Diagnosis</div>
                    <div class="vm-field"><div class="vm-lbl">Primary</div><div class="vm-val" id="vm_diagnosis"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Secondary</div><div class="vm-val" id="vm_secondary"></div></div>
                    <div class="vm-field"><div class="vm-lbl">ICD Code</div><div class="vm-val" id="vm_icd"></div></div>
                </div>
                <div class="vm-section">
                    <div class="vm-section-title">Treatment</div>
                    <div class="vm-field"><div class="vm-val" id="vm_treatment"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Medicines Prescribed</div><div class="vm-val" id="vm_meds"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Procedures</div><div class="vm-val" id="vm_procedures"></div></div>
                </div>
            </div>
            <div>
                <div class="vm-section">
                    <div class="vm-section-title">Health advice</div>
                    <div class="vm-field"><div class="vm-val" id="vm_advice"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Lifestyle advice</div><div class="vm-val" id="vm_lifestyle"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Patient education</div><div class="vm-val" id="vm_education"></div></div>
                </div>
                <div class="vm-section">
                    <div class="vm-section-title">Assessment &amp; plan</div>
                    <div class="vm-field"><div class="vm-val" id="vm_assessment"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Plan</div><div class="vm-val" id="vm_plan"></div></div>
                </div>
                <div class="vm-section">
                    <div class="vm-section-title">Health profile</div>
                    <div class="vm-grid-3" id="vm_profile">—</div>
                </div>
                <div class="vm-section">
                    <div class="vm-section-title">Medical history</div>
                    <div class="vm-field"><div class="vm-lbl">Past history</div><div class="vm-val" id="vm_pmhx"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Family history</div><div class="vm-val" id="vm_fhx"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Current medications</div><div class="vm-val" id="vm_meds_curr"></div></div>
                    <div class="vm-field"><div class="vm-lbl">Allergies</div><div class="vm-val" id="vm_allergies"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GENERATE MODAL (unchanged) -->
<div id="generateModal" title="Generate Document" class="hidden">
    <form id="generateForm" style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
        <input type="hidden" id="gen_resident_id" name="resident_id" value="">
        <div class="fg">
            <label class="fg-label">Document Type</label>
            <select id="gen_doc" name="doc" class="fg-select">
                <option value="report">Full Report (All Residents)</option>
                <option value="summary">Patient Summary</option>
                <option value="certificate">Health Certificate</option>
            </select>
        </div>
        <div id="gen_resident_wrap" class="fg" style="display:none;">
            <label class="fg-label">Patient <span style="color:var(--danger-fg,#b91c1c);">*</span></label>
            <input type="text" id="gen_res_name" class="fg-input" placeholder="Search name…" autocomplete="off">
            <p class="fg-hint" style="font-size:11px;color:var(--ink-muted,#6b7280);margin-top:4px;">Required for Patient Summary and Health Certificate. Search and select the resident.</p>
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
            <input type="text" name="purpose" class="fg-input" placeholder="e.g. School requirement…" autocomplete="off">
        </div>
        <div style="display:flex;gap:8px;">
            <button type="button" class="btn btn-ghost" style="flex:1;justify-content:center;"
                    onclick="$('#generateModal').dialog('close')">Cancel</button>
            <button type="button" class="btn btn-primary" style="flex:1;justify-content:center;" onclick="doGenerate()">↗ Generate</button>
        </div>
    </form>
</div>
<script>const SESSION_WORKER = <?= json_encode($_SESSION['name'] ?? '') ?>;</script>
<script src="./js/index.js"></script>
</body>
</html>
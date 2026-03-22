<?php
/**
 * Care Visits — Main Page
 * Unified entry point for all 6 care modules.
 * Route: /hcnurse/care-visits/?type=prenatal
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type = $_GET['type'] ?? 'maternal';
$allowed = ['maternal','family_planning','prenatal','postnatal','child_nutrition','immunization'];
if (!in_array($type, $allowed, true)) $type = 'maternal';

$moduleConfig = [
    'maternal'        => ['icon'=>'🤱', 'label'=>'Maternal Health',   'color'=>'#9f1239', 'bg'=>'#fff1f2'],
    'family_planning' => ['icon'=>'💊', 'label'=>'Family Planning',   'color'=>'#1e40af', 'bg'=>'#eff6ff'],
    'prenatal'        => ['icon'=>'👶', 'label'=>'Prenatal / ANC',    'color'=>'#92400e', 'bg'=>'#fffbeb'],
    'postnatal'       => ['icon'=>'🍼', 'label'=>'Postnatal / PNC',   'color'=>'#134e4a', 'bg'=>'#f0fdfa'],
    'child_nutrition' => ['icon'=>'🥗', 'label'=>'Child Nutrition',   'color'=>'#14532d', 'bg'=>'#f0fdf4'],
    'immunization'    => ['icon'=>'💉', 'label'=>'Immunization',      'color'=>'#4c1d95', 'bg'=>'#f5f3ff'],
];
$mc = $moduleConfig[$type];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($mc['label']) ?> — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllStyles(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
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
        --mod-color:<?= $mc['color'] ?>;
        --mod-bg:<?= $mc['bg'] ?>;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body,input,button,select,textarea{font-family:var(--f-sans);}
    .cv-page{background:var(--bg);min-height:100%;padding-bottom:56px;}

    /* ── Doc header ── */
    .doc-header{background:var(--paper);border-bottom:1px solid var(--rule);}
    .doc-header-inner{padding:20px 28px 0;display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;}
    .doc-eyebrow{font-size:8.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--ink-faint);display:flex;align-items:center;gap:8px;margin-bottom:6px;}
    .doc-eyebrow::before{content:'';width:18px;height:2px;background:var(--mod-color);display:inline-block;}
    .doc-title{font-family:var(--f-serif);font-size:22px;font-weight:700;color:var(--ink);letter-spacing:-.3px;margin-bottom:3px;display:flex;align-items:center;gap:10px;}
    .doc-sub{font-size:12px;color:var(--ink-faint);font-style:italic;}
    .mod-tag{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:2px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;background:var(--mod-bg);color:var(--mod-color);border:1px solid color-mix(in srgb,var(--mod-color) 20%,transparent);}

    /* ── Module nav tabs ── */
    .mod-tabs{display:flex;gap:0;padding:0 28px;overflow-x:auto;scrollbar-width:none;}
    .mod-tabs::-webkit-scrollbar{display:none;}
    .mod-tab{display:flex;align-items:center;gap:6px;padding:10px 14px;font-size:12px;font-weight:600;color:var(--ink-muted);text-decoration:none;border-bottom:2.5px solid transparent;white-space:nowrap;transition:color .12s,border-color .12s;}
    .mod-tab:hover{color:var(--ink);}
    .mod-tab.active{color:var(--mod-color);border-bottom-color:var(--mod-color);}
    .doc-accent-bar{height:3px;background:linear-gradient(to right,var(--mod-color),transparent);}

    /* ── Layout ── */
    .cv-body{display:grid;grid-template-columns:1fr 380px;gap:18px;margin:18px 28px 0;align-items:start;}
    @media(max-width:1100px){.cv-body{grid-template-columns:1fr;}}

    /* ── Card ── */
    .cv-card{background:var(--paper);border:1px solid var(--rule);border-radius:2px;box-shadow:var(--shadow);overflow:hidden;}
    .cv-card-head{padding:12px 18px;border-bottom:1px solid var(--rule);background:var(--paper-lt);display:flex;align-items:center;justify-content:space-between;gap:12px;}
    .cv-card-title{font-size:8.5px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--ink-muted);display:flex;align-items:center;gap:8px;}
    .cv-card-title::before{content:'';display:inline-block;width:3px;height:12px;background:var(--mod-color);border-radius:1px;flex-shrink:0;}

    /* ── Form ── */
    .modal-scroll{max-height:78vh;overflow-y:auto;}
    .form-section{padding:14px 18px 0;border-top:1px solid var(--rule);}
    .form-section:first-child{border-top:none;padding-top:18px;}
    .form-section-lbl{font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:12px;display:flex;align-items:center;gap:8px;}
    .form-section-lbl::after{content:'';flex:1;height:1px;background:var(--rule);}
    .form-section-body{padding-bottom:14px;}
    .fg{margin-bottom:12px;}
    .fg-label{display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:5px;}
    .fg-label .req{color:var(--danger-fg);}
    .fg-input,.fg-select,.fg-textarea{width:100%;padding:9px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-family:var(--f-sans);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s;}
    .fg-input:focus,.fg-select:focus,.fg-textarea:focus{border-color:var(--mod-color);box-shadow:0 0 0 3px color-mix(in srgb,var(--mod-color) 10%,transparent);}
    .fg-input::placeholder{color:var(--ink-faint);font-style:italic;font-size:12px;}
    .fg-textarea{resize:vertical;min-height:68px;}
    .fg-hint{font-size:10.5px;color:var(--ink-faint);margin-top:4px;font-style:italic;}
    .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
    .form-grid-4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;}

    /* Checkbox group */
    .check-group{display:flex;flex-wrap:wrap;gap:8px;}
    .check-item{display:flex;align-items:center;gap:5px;padding:5px 10px;border:1.5px solid var(--rule-dk);border-radius:2px;cursor:pointer;font-size:12px;color:var(--ink-muted);transition:all .12s;}
    .check-item:has(input:checked){border-color:var(--mod-color);background:var(--mod-bg);color:var(--mod-color);}
    .check-item input{display:none;}

    /* ── Submit bar ── */
    .form-submit-bar{padding:12px 18px;border-top:1px solid var(--rule);background:var(--paper-lt);display:flex;justify-content:flex-end;gap:8px;}
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:2px;font-family:var(--f-sans);font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .13s;}
    .btn-primary{background:var(--mod-color);border-color:var(--mod-color);color:#fff;}
    .btn-primary:hover{filter:brightness(1.1);}
    .btn-ghost{background:#fff;border-color:var(--rule-dk);color:var(--ink-muted);}
    .btn-ghost:hover{border-color:var(--mod-color);color:var(--mod-color);}

    /* ── Records list ── */
    .rec-list{max-height:70vh;overflow-y:auto;}
    .rec-item{padding:12px 18px;border-bottom:1px solid #f0ede8;cursor:pointer;transition:background .1s;}
    .rec-item:last-child{border-bottom:none;}
    .rec-item:hover{background:var(--mod-bg);}
    .rec-item-date{font-family:var(--f-mono);font-size:10.5px;color:var(--ink-faint);margin-bottom:3px;}
    .rec-item-name{font-weight:600;font-size:13px;color:var(--ink);margin-bottom:2px;}
    .rec-item-sub{font-size:11px;color:var(--ink-muted);}
    .rec-empty{padding:28px;text-align:center;color:var(--ink-faint);font-size:12px;font-style:italic;}

    /* ── Status badges ── */
    .badge{display:inline-block;padding:2px 8px;border-radius:2px;font-size:9px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;border:1px solid;}
    .badge-risk-low{background:var(--ok-bg);color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent);}
    .badge-risk-mod{background:var(--warn-bg);color:var(--warn-fg);border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent);}
    .badge-risk-high{background:var(--danger-bg);color:var(--danger-fg);border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent);}

    /* ── Imm card ── */
    .nip-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:14px 18px;}
    .nip-slot{padding:10px 12px;border-radius:2px;border:1.5px solid var(--rule);background:var(--paper-lt);position:relative;}
    .nip-slot.given{border-color:var(--ok-fg);background:var(--ok-bg);}
    .nip-slot.overdue{border-color:var(--danger-fg);background:var(--danger-bg);}
    .nip-vaccine{font-weight:700;font-size:11px;color:var(--ink);margin-bottom:2px;}
    .nip-dose{font-size:9.5px;color:var(--ink-faint);margin-bottom:4px;}
    .nip-status{font-size:8.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;}
    .nip-slot.given .nip-status{color:var(--ok-fg);}
    .nip-slot.overdue .nip-status{color:var(--danger-fg);}
    .nip-slot:not(.given):not(.overdue) .nip-status{color:var(--ink-faint);}
    .nip-date{font-family:var(--f-mono);font-size:9px;color:var(--ink-faint);margin-top:2px;}

    /* ── Resident selector ── */
    .res-selector{padding:10px 18px;border-bottom:1px solid var(--rule);background:var(--paper);}
    .res-selector-label{font-size:8px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:6px;}
    .res-selector-row{display:flex;gap:8px;align-items:center;}
    .res-selector-input{flex:1;padding:7px 11px;border:1.5px solid var(--rule-dk);border-radius:2px;font-size:12.5px;color:var(--ink);background:#fff;outline:none;transition:border-color .14s;}
    .res-selector-input:focus{border-color:var(--mod-color);}
    .res-selector-name{font-size:13px;font-weight:600;color:var(--ink);}

    /* ui-autocomplete */
    .ui-autocomplete{border:1.5px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 4px 20px rgba(0,0,0,.12)!important;font-family:var(--f-sans)!important;font-size:13px!important;background:var(--paper)!important;max-height:200px;overflow-y:auto!important;}
    .ui-menu-item-wrapper{padding:8px 13px!important;border-bottom:1px solid #f0ede8!important;}
    .ui-state-active{background:var(--mod-bg)!important;color:var(--ink)!important;}

    /* Dialog overrides */
    .ui-dialog{border:1px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 8px 48px rgba(0,0,0,.18)!important;padding:0!important;font-family:var(--f-sans)!important;}
    .ui-dialog-titlebar{background:var(--mod-color)!important;border:none!important;padding:12px 16px!important;}
    .ui-dialog-title{font-family:var(--f-sans)!important;font-size:11px!important;font-weight:700!important;letter-spacing:1px!important;text-transform:uppercase!important;color:#fff!important;}
    .ui-dialog-titlebar-close{background:rgba(255,255,255,.15)!important;border:1px solid rgba(255,255,255,.25)!important;border-radius:2px!important;color:#fff!important;width:24px!important;height:24px!important;top:50%!important;transform:translateY(-50%)!important;}
    .ui-dialog-content{padding:0!important;}
    .ui-dialog-buttonpane{background:var(--paper-lt)!important;border-top:1px solid var(--rule)!important;padding:12px 16px!important;margin:0!important;}
    .ui-dialog-buttonpane .ui-button{font-family:var(--f-sans)!important;font-size:11px!important;font-weight:700!important;letter-spacing:.5px!important;text-transform:uppercase!important;padding:7px 18px!important;border-radius:2px!important;cursor:pointer!important;}
    .ui-dialog-buttonpane .ui-button:first-child{background:var(--mod-color)!important;border:1.5px solid var(--mod-color)!important;color:#fff!important;}
    .ui-dialog-buttonpane .ui-button:not(:first-child){background:#fff!important;border:1.5px solid var(--rule-dk)!important;color:var(--ink-muted)!important;}
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
<?php include_once '../layout/navbar.php'; ?>
<div class="flex h-full" style="background:var(--bg);">
    <?php include_once '../layout/sidebar.php'; ?>

    <main class="flex-1 h-screen overflow-y-auto cv-page">

        <!-- ── Header ── -->
        <div class="doc-header">
            <div class="doc-header-inner">
                <div>
                    <div class="doc-eyebrow">Barangay Bombongan · Health Center · Care Records</div>
                    <div class="doc-title">
                        <span><?= $mc['icon'] ?></span>
                        <?= htmlspecialchars($mc['label']) ?>
                        <span class="mod-tag"><?= htmlspecialchars(strtoupper(str_replace('_',' ',$type))) ?></span>
                    </div>
                    <div class="doc-sub" id="pageSubtitle">Select a resident to begin</div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;padding-bottom:4px;">
                    <button class="btn btn-ghost" id="btnPrint">↗ Print</button>
                    <button class="btn btn-primary" id="btnAdd">+ New Visit</button>
                </div>
            </div>
            <!-- Module nav tabs -->
            <div class="mod-tabs">
                <?php foreach ($moduleConfig as $t => $cfg): ?>
                <a href="?type=<?= $t ?>"
                   class="mod-tab <?= $t === $type ? 'active' : '' ?>"
                   style="<?= $t === $type ? '--mod-color:' . $cfg['color'] . ';' : '' ?>">
                    <?= $cfg['icon'] ?> <?= htmlspecialchars($cfg['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="doc-accent-bar"></div>

        <!-- ── Body ── -->
        <div class="cv-body">

            <!-- Left: Form + Records -->
            <div style="display:flex;flex-direction:column;gap:18px;">

                <!-- Resident selector -->
                <div class="cv-card">
                    <div class="res-selector">
                        <div class="res-selector-label">Patient</div>
                        <div class="res-selector-row">
                            <input type="text" id="resSearch" class="res-selector-input"
                                   placeholder="Search resident name…" autocomplete="off">
                            <input type="hidden" id="resId">
                            <span class="res-selector-name" id="resName" style="color:var(--ink-faint);font-style:italic;">No patient selected</span>
                        </div>
                    </div>
                </div>

                <!-- Records list -->
                <div class="cv-card">
                    <div class="cv-card-head">
                        <div class="cv-card-title">Visit History</div>
                        <span id="recCount" style="font-family:var(--f-mono);font-size:9.5px;color:var(--ink-faint);">— RECORDS</span>
                    </div>
                    <div class="rec-list" id="recList">
                        <div class="rec-empty">Select a patient to view visit history.</div>
                    </div>
                </div>
            </div>

            <!-- Right: Module-specific panel -->
            <div style="display:flex;flex-direction:column;gap:18px;">

                <?php if ($type === 'immunization'): ?>
                <!-- IMMUNIZATION CARD -->
                <div class="cv-card" id="immCard">
                    <div class="cv-card-head">
                        <div class="cv-card-title">NIP Immunization Card</div>
                        <div id="immStats" style="font-family:var(--f-mono);font-size:9.5px;color:var(--ink-faint);">—</div>
                    </div>
                    <div class="nip-grid" id="nipGrid">
                        <div class="rec-empty" style="grid-column:1/-1">Select a patient to load their immunization card.</div>
                    </div>
                </div>

                <?php elseif ($type === 'maternal'): ?>
                <!-- MATERNAL PROFILE (GTPAL) -->
                <div class="cv-card">
                    <div class="cv-card-head">
                        <div class="cv-card-title">Obstetric Profile (GTPAL)</div>
                        <button class="btn btn-ghost" id="btnSaveProfile" style="font-size:10px;padding:4px 12px;" disabled>Save Profile</button>
                    </div>
                    <form id="maternalProfileForm">
                        <input type="hidden" name="resident_id" id="mpResId">
                        <div class="form-section">
                            <div class="form-section-lbl">GTPAL — Obstetric History</div>
                            <div class="form-section-body">
                                <div class="form-grid-4">
                                    <div class="fg">
                                        <label class="fg-label">G (Gravida)</label>
                                        <input type="number" name="gravida" min="0" class="fg-input" placeholder="0">
                                    </div>
                                    <div class="fg">
                                        <label class="fg-label">T (Term)</label>
                                        <input type="number" name="term" min="0" class="fg-input" placeholder="0">
                                    </div>
                                    <div class="fg">
                                        <label class="fg-label">P (Preterm)</label>
                                        <input type="number" name="preterm" min="0" class="fg-input" placeholder="0">
                                    </div>
                                    <div class="fg">
                                        <label class="fg-label">A (Abortions)</label>
                                        <input type="number" name="abortions" min="0" class="fg-input" placeholder="0">
                                    </div>
                                </div>
                                <div class="fg">
                                    <label class="fg-label">L (Living Children)</label>
                                    <input type="number" name="living_children" min="0" class="fg-input" placeholder="0" style="width:100px;">
                                </div>
                            </div>
                        </div>
                        <div class="form-section">
                            <div class="form-section-lbl">Complication History</div>
                            <div class="form-section-body">
                                <div class="check-group">
                                    <label class="check-item"><input type="checkbox" name="hx_pre_eclampsia" value="1"> Pre-eclampsia</label>
                                    <label class="check-item"><input type="checkbox" name="hx_pph" value="1"> PPH</label>
                                    <label class="check-item"><input type="checkbox" name="hx_cesarean" value="1"> C-Section</label>
                                    <label class="check-item"><input type="checkbox" name="hx_ectopic" value="1"> Ectopic</label>
                                    <label class="check-item"><input type="checkbox" name="hx_stillbirth" value="1"> Stillbirth</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-section">
                            <div class="form-section-lbl">Chronic Conditions</div>
                            <div class="form-section-body">
                                <div class="check-group">
                                    <label class="check-item"><input type="checkbox" name="has_diabetes" value="1"> Diabetes</label>
                                    <label class="check-item"><input type="checkbox" name="has_hypertension" value="1"> Hypertension</label>
                                    <label class="check-item"><input type="checkbox" name="has_hiv" value="1"> HIV</label>
                                    <label class="check-item"><input type="checkbox" name="has_anemia" value="1"> Anemia</label>
                                </div>
                                <div class="fg" style="margin-top:10px;">
                                    <label class="fg-label">Other Conditions</label>
                                    <input type="text" name="other_conditions" class="fg-input" placeholder="e.g. Thyroid disease, Heart condition…" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="form-section">
                            <div class="form-section-lbl">Blood Type</div>
                            <div class="form-section-body">
                                <select name="blood_type" class="fg-select" style="max-width:160px;">
                                    <?php foreach (['Unknown','A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                                    <option value="<?= $bt ?>"><?= $bt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-submit-bar">
                            <button type="submit" class="btn btn-primary" disabled id="btnSaveProfile2">Save Obstetric Profile</button>
                        </div>
                    </form>
                </div>

                <?php endif; ?>

            </div><!-- /right -->
        </div><!-- /cv-body -->
    </main>
</div>

<!-- ════════════════════════════
     ADD VISIT MODAL
════════════════════════════ -->
<div id="addVisitModal" title="New <?= htmlspecialchars($mc['label']) ?> Visit" class="hidden">
    <form id="addVisitForm" class="modal-scroll">
        <input type="hidden" name="type"          value="<?= htmlspecialchars($type) ?>">
        <input type="hidden" name="resident_id"   id="av_res_id">
        <input type="hidden" name="care_visit_id" id="av_visit_id" value="">

        <!-- Common: Resident + Date -->
        <div class="form-section">
            <div class="form-section-lbl">Visit Information</div>
            <div class="form-section-body">
                <div class="form-grid-2">
                    <div class="fg">
                        <label class="fg-label">Patient <span class="req">*</span></label>
                        <input type="text" id="av_res_name" class="fg-input" placeholder="Type to search…" autocomplete="off">
                        <div class="fg-hint">Select from dropdown — required</div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Visit Date <span class="req">*</span></label>
                        <input type="date" name="visit_date" id="av_date" class="fg-input" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
        </div>

        <?php if ($type === 'family_planning'): ?>
        <!-- FAMILY PLANNING FIELDS -->
        <div class="form-section">
            <div class="form-section-lbl">Contraceptive Method</div>
            <div class="form-section-body">
                <div class="form-grid-2">
                    <div class="fg">
                        <label class="fg-label">Method <span class="req">*</span></label>
                        <select name="method" id="fpMethod" class="fg-select">
                            <?php foreach (['Pills','Injectable','IUD','Implant','Condom','LAM','BTL','Vasectomy','NFP','SDM','Abstinence','Other'] as $m): ?>
                            <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg" id="fpMethodOtherWrap" style="display:none;">
                        <label class="fg-label">Specify Method</label>
                        <input type="text" name="method_other" class="fg-input" placeholder="Specify…">
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Method Start Date</label>
                        <input type="date" name="method_start_date" class="fg-input">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Next Supply Date</label>
                        <input type="date" name="next_supply_date" class="fg-input">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Next Checkup</label>
                        <input type="date" name="next_checkup_date" class="fg-input">
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Acceptor Status &amp; Supplies</div>
            <div class="form-section-body">
                <div class="check-group" style="margin-bottom:12px;">
                    <label class="check-item"><input type="checkbox" name="is_new_acceptor" value="1"> New Acceptor</label>
                    <label class="check-item"><input type="checkbox" name="is_method_switch" value="1"> Method Switch</label>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Previous Method</label>
                        <input type="text" name="prev_method" class="fg-input" placeholder="If switching…" autocomplete="off">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Pills Given (packs)</label>
                        <input type="number" name="pills_given" min="0" class="fg-input" placeholder="0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Injectables Given</label>
                        <input type="number" name="injectables_given" min="0" class="fg-input" placeholder="0">
                    </div>
                </div>
                <div class="fg">
                    <label class="fg-label">Side Effects Reported</label>
                    <textarea name="side_effects" class="fg-textarea" placeholder="Describe any side effects…"></textarea>
                </div>
                <div class="fg">
                    <label class="fg-label">Counseling Notes</label>
                    <textarea name="counseling_notes" class="fg-textarea" placeholder="Counseling provided…"></textarea>
                </div>
            </div>
        </div>

        <?php elseif ($type === 'prenatal'): ?>
        <!-- PRENATAL FIELDS -->
        <div class="form-section">
            <div class="form-section-lbl">Pregnancy Dating</div>
            <div class="form-section-body">
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">LMP Date</label>
                        <input type="date" name="lmp_date" id="lmpDate" class="fg-input">
                        <div class="fg-hint">Auto-computes EDD</div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">EDD (auto)</label>
                        <input type="text" id="eddDisplay" class="fg-input" readonly placeholder="Fill LMP to compute"
                               style="background:var(--paper-lt);color:var(--ink-muted);">
                    </div>
                    <div class="fg">
                        <label class="fg-label">AOG (weeks)</label>
                        <input type="number" name="aog_weeks" min="0" max="45" class="fg-input" placeholder="0">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="fg">
                        <label class="fg-label">Visit Number</label>
                        <select name="visit_number" class="fg-select">
                            <?php for($i=1;$i<=10;$i++): ?><option value="<?=$i?>">Visit <?=$i?></option><?php endfor; ?>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Risk Level</label>
                        <select name="risk_level" class="fg-select">
                            <option value="Low">Low Risk</option>
                            <option value="Moderate">Moderate Risk</option>
                            <option value="High">High Risk</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Physical Assessment</div>
            <div class="form-section-body">
                <div class="form-grid-4">
                    <div class="fg">
                        <label class="fg-label">Weight (kg)</label>
                        <input type="number" name="weight_kg" step="0.1" class="fg-input" placeholder="0.0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">BP Systolic</label>
                        <input type="number" name="bp_systolic" class="fg-input" placeholder="120">
                    </div>
                    <div class="fg">
                        <label class="fg-label">BP Diastolic</label>
                        <input type="number" name="bp_diastolic" class="fg-input" placeholder="80">
                    </div>
                    <div class="fg">
                        <label class="fg-label">FHR (bpm)</label>
                        <input type="number" name="fetal_heart_rate" class="fg-input" placeholder="140">
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Fundal Height (cm)</label>
                        <input type="number" name="fundal_height_cm" step="0.5" class="fg-input" placeholder="0.0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Hgb (g/dL)</label>
                        <input type="number" name="hgb_result" step="0.1" class="fg-input" placeholder="12.0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Fetal Presentation</label>
                        <select name="fetal_presentation" class="fg-select">
                            <option>Unknown</option><option>Cephalic</option><option>Breech</option><option>Transverse</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Supplementation &amp; Vaccines</div>
            <div class="form-section-body">
                <div class="check-group">
                    <label class="check-item"><input type="checkbox" name="folic_acid_given" value="1"> Folic Acid</label>
                    <label class="check-item"><input type="checkbox" name="iron_given" value="1"> Iron</label>
                    <label class="check-item"><input type="checkbox" name="calcium_given" value="1"> Calcium</label>
                    <label class="check-item"><input type="checkbox" name="iodine_given" value="1"> Iodine</label>
                </div>
                <div class="form-grid-2" style="margin-top:10px;">
                    <div class="fg">
                        <label class="fg-label">Iron Tablets Qty</label>
                        <input type="number" name="iron_tablets_qty" min="0" class="fg-input" placeholder="0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">TT Dose</label>
                        <select name="tt_dose" class="fg-select">
                            <option value="None">None</option>
                            <option>TT1</option><option>TT2</option><option>TT3</option><option>TT4</option><option>TT5</option><option>TD</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Lab Tests</div>
            <div class="form-section-body">
                <div class="check-group">
                    <label class="check-item"><input type="checkbox" name="urinalysis_done" value="1"> Urinalysis</label>
                    <label class="check-item"><input type="checkbox" name="blood_type_done" value="1"> Blood Type</label>
                    <label class="check-item"><input type="checkbox" name="hiv_test_done" value="1"> HIV Test</label>
                    <label class="check-item"><input type="checkbox" name="syphilis_done" value="1"> Syphilis</label>
                </div>
                <div class="form-grid-2" style="margin-top:10px;">
                    <div class="fg">
                        <label class="fg-label">HIV Result</label>
                        <select name="hiv_result" class="fg-select">
                            <option>Not done</option><option>Negative</option><option>Positive</option><option>Referred</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Syphilis Result</label>
                        <select name="syphilis_result" class="fg-select">
                            <option>Not done</option><option>Non-reactive</option><option>Reactive</option><option>Referred</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Clinical Notes</div>
            <div class="form-section-body">
                <div class="fg">
                    <label class="fg-label">Chief Complaint</label>
                    <textarea name="chief_complaint" class="fg-textarea" placeholder="What brought her in today?"></textarea>
                </div>
                <div class="form-grid-2">
                    <div class="fg">
                        <label class="fg-label">Assessment</label>
                        <textarea name="assessment" class="fg-textarea"></textarea>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Plan</label>
                        <textarea name="plan" class="fg-textarea"></textarea>
                    </div>
                </div>
                <div class="fg">
                    <label class="fg-label">Risk Notes</label>
                    <input type="text" name="risk_notes" class="fg-input" placeholder="Any identified risks…" autocomplete="off">
                </div>
            </div>
        </div>

        <?php elseif ($type === 'postnatal'): ?>
        <!-- POSTNATAL FIELDS -->
        <div class="form-section">
            <div class="form-section-lbl">Delivery Information</div>
            <div class="form-section-body">
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Delivery Date</label>
                        <input type="date" name="delivery_date" class="fg-input">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Delivery Type</label>
                        <select name="delivery_type" class="fg-select">
                            <option value="Unknown">Unknown</option>
                            <option>NSD</option><option>CS</option><option>Assisted</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Visit Number</label>
                        <select name="visit_number" class="fg-select">
                            <?php for($i=1;$i<=6;$i++): ?><option value="<?=$i?>">PNC Visit <?=$i?></option><?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="fg">
                        <label class="fg-label">Delivery Facility</label>
                        <input type="text" name="delivery_facility" class="fg-input" placeholder="Hospital / BHS / Home" autocomplete="off">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Birth Attendant</label>
                        <input type="text" name="birth_attendant" class="fg-input" placeholder="OB, midwife, hilot…" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Maternal Recovery</div>
            <div class="form-section-body">
                <div class="form-grid-4">
                    <div class="fg">
                        <label class="fg-label">Weight (kg)</label>
                        <input type="number" name="weight_kg" step="0.1" class="fg-input" placeholder="0.0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">BP Systolic</label>
                        <input type="number" name="bp_systolic" class="fg-input" placeholder="120">
                    </div>
                    <div class="fg">
                        <label class="fg-label">BP Diastolic</label>
                        <input type="number" name="bp_diastolic" class="fg-input" placeholder="80">
                    </div>
                    <div class="fg">
                        <label class="fg-label">PPD Score (EPDS)</label>
                        <input type="number" name="ppd_score" min="0" max="30" class="fg-input" placeholder="0–30">
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Lochia</label>
                        <select name="lochia_type" class="fg-select">
                            <option value="Not checked">Not checked</option>
                            <option>Rubra</option><option>Serosa</option><option>Alba</option><option>Abnormal</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Fundal Involution</label>
                        <select name="fundal_involution" class="fg-select">
                            <option value="Not checked">Not checked</option>
                            <option>Normal</option><option>Subinvolution</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Breastfeeding</label>
                        <select name="breastfeeding_status" class="fg-select">
                            <option>NA</option><option>Exclusive</option><option>Mixed</option><option>Not breastfeeding</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Newborn Check</div>
            <div class="form-section-body">
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Birth Weight (g)</label>
                        <input type="number" name="newborn_weight_g" class="fg-input" placeholder="3200">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Length (cm)</label>
                        <input type="number" name="newborn_length_cm" step="0.5" class="fg-input" placeholder="50.0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">APGAR 1 min</label>
                        <input type="number" name="apgar_1min" min="0" max="10" class="fg-input" placeholder="7">
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">APGAR 5 min</label>
                        <input type="number" name="apgar_5min" min="0" max="10" class="fg-input" placeholder="9">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Cord Status</label>
                        <select name="cord_status" class="fg-select">
                            <option>NA</option><option>Normal</option><option>Healing</option><option>Infected</option>
                        </select>
                    </div>
                    <div></div>
                </div>
                <div class="check-group">
                    <label class="check-item"><input type="checkbox" name="jaundice" value="1"> Jaundice</label>
                    <label class="check-item"><input type="checkbox" name="newborn_screening_done" value="1"> NBS Done</label>
                    <label class="check-item"><input type="checkbox" name="bcg_given" value="1"> BCG Given</label>
                    <label class="check-item"><input type="checkbox" name="hb_vaccine_given" value="1"> HepB Birth Dose</label>
                    <label class="check-item"><input type="checkbox" name="ppd_referred" value="1"> PPD Referred</label>
                </div>
            </div>
        </div>

        <?php elseif ($type === 'child_nutrition'): ?>
        <!-- CHILD NUTRITION FIELDS -->
        <div class="form-section">
            <div class="form-section-lbl">Anthropometrics</div>
            <div class="form-section-body">
                <div class="form-grid-4">
                    <div class="fg">
                        <label class="fg-label">Age (months)</label>
                        <input type="number" name="age_months" min="0" max="60" class="fg-input" placeholder="0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Weight (kg)</label>
                        <input type="number" name="weight_kg" step="0.001" class="fg-input" placeholder="0.000">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Height (cm)</label>
                        <input type="number" name="height_cm" step="0.5" class="fg-input" placeholder="0.0">
                    </div>
                    <div class="fg">
                        <label class="fg-label">MUAC (cm)</label>
                        <input type="number" name="muac_cm" step="0.1" class="fg-input" placeholder="0.0">
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">WAZ (weight-for-age)</label>
                        <input type="number" name="waz" step="0.01" class="fg-input" placeholder="-0.00">
                        <div class="fg-hint">&lt;-2 = underweight</div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">HAZ (stunting)</label>
                        <input type="number" name="haz" step="0.01" class="fg-input" placeholder="-0.00">
                        <div class="fg-hint">&lt;-2 = stunted</div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">WHZ (wasting)</label>
                        <input type="number" name="whz" step="0.01" class="fg-input" placeholder="-0.00">
                        <div class="fg-hint">&lt;-2 = wasted</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Classification (WHO)</div>
            <div class="form-section-body">
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Stunting</label>
                        <select name="stunting_status" class="fg-select">
                            <option>Not assessed</option><option>Normal</option><option>Mild</option><option>Moderate</option><option>Severe</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Wasting</label>
                        <select name="wasting_status" class="fg-select">
                            <option>Not assessed</option><option>Normal</option><option>Mild</option><option>Moderate</option><option>Severe</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Underweight</label>
                        <select name="underweight_status" class="fg-select">
                            <option>Not assessed</option><option>Normal</option><option>Mild</option><option>Moderate</option><option>Severe</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Micronutrients &amp; Deworming</div>
            <div class="form-section-body">
                <div class="check-group" style="margin-bottom:10px;">
                    <label class="check-item"><input type="checkbox" name="vita_supplemented" value="1"> Vitamin A</label>
                    <label class="check-item"><input type="checkbox" name="iron_supplemented" value="1"> Iron Supplement</label>
                    <label class="check-item"><input type="checkbox" name="zinc_given" value="1"> Zinc</label>
                    <label class="check-item"><input type="checkbox" name="deworming_done" value="1"> Deworming</label>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Vitamin A Dose</label>
                        <select name="vita_dose" class="fg-select">
                            <option value="NA">NA</option>
                            <option value="100000 IU">100,000 IU</option>
                            <option value="200000 IU">200,000 IU</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Vitamin A Date</label>
                        <input type="date" name="vita_date" class="fg-input">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Deworming Date</label>
                        <input type="date" name="deworming_date" class="fg-input">
                    </div>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Feeding &amp; Counseling</div>
            <div class="form-section-body">
                <div class="form-grid-2">
                    <div class="fg">
                        <label class="fg-label">Breastfeeding Status</label>
                        <select name="breastfeeding" class="fg-select">
                            <option>NA</option><option>Exclusive</option><option>Mixed</option><option>Complementary</option><option>Weaned</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Complementary Foods Intro Date</label>
                        <input type="date" name="complementary_intro" class="fg-input">
                    </div>
                </div>
                <div class="fg">
                    <label class="fg-label">Feeding Problems</label>
                    <textarea name="feeding_problems" class="fg-textarea" placeholder="Any feeding difficulties…"></textarea>
                </div>
                <div class="check-group">
                    <label class="check-item"><input type="checkbox" name="counseling_given" value="1"> Counseling Given</label>
                    <label class="check-item"><input type="checkbox" name="referred" value="1"> Referred</label>
                </div>
                <div class="fg" style="margin-top:10px;">
                    <label class="fg-label">Counseling Notes</label>
                    <textarea name="counseling_notes" class="fg-textarea"></textarea>
                </div>
            </div>
        </div>

        <?php elseif ($type === 'immunization'): ?>
        <!-- IMMUNIZATION FIELDS -->
        <div class="form-section">
            <div class="form-section-lbl">Vaccine Details</div>
            <div class="form-section-body">
                <div class="form-grid-2">
                    <div class="fg">
                        <label class="fg-label">Vaccine Name <span class="req">*</span></label>
                        <input type="text" name="vaccine_name" id="vaccineName" class="fg-input"
                               placeholder="e.g. BCG, Pentavalent, MR…" autocomplete="off">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Dose</label>
                        <input type="text" name="dose" id="vaccineDose" class="fg-input"
                               placeholder="e.g. Dose 1, Booster…" autocomplete="off">
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Date Given <span class="req">*</span></label>
                        <input type="date" name="date_given" class="fg-input" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Next Schedule</label>
                        <input type="date" name="next_schedule" class="fg-input">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Route</label>
                        <select name="route" class="fg-select">
                            <option>IM</option><option>SC</option><option>ID</option><option>Oral</option><option>Nasal</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="fg">
                        <label class="fg-label">Batch Number</label>
                        <input type="text" name="batch_number" class="fg-input" placeholder="LOT-XXX" autocomplete="off">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="fg-input">
                    </div>
                    <div class="fg">
                        <label class="fg-label">Site Given</label>
                        <input type="text" name="site_given" class="fg-input" placeholder="e.g. Left deltoid" autocomplete="off">
                    </div>
                </div>
                <div class="fg">
                    <label class="fg-label">Administered By</label>
                    <input type="text" name="administered_by" class="fg-input"
                           value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                </div>
                <div class="check-group">
                    <label class="check-item"><input type="checkbox" name="is_defaulter" value="1"> Defaulter (overdue)</label>
                    <label class="check-item"><input type="checkbox" name="catch_up" value="1"> Catch-up dose</label>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-lbl">Post-vaccination</div>
            <div class="form-section-body">
                <div class="fg">
                    <label class="fg-label">Adverse Reaction</label>
                    <textarea name="adverse_reaction" class="fg-textarea" placeholder="Any immediate or delayed reactions observed…"></textarea>
                </div>
                <div class="fg">
                    <label class="fg-label">Remarks</label>
                    <input type="text" name="remarks" class="fg-input" placeholder="Additional notes…" autocomplete="off">
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- MATERNAL VISIT (general) -->
        <div class="form-section">
            <div class="form-section-lbl">Visit Notes</div>
            <div class="form-section-body">
                <div class="fg">
                    <label class="fg-label">Health Worker</label>
                    <input type="text" name="health_worker" class="fg-input"
                           value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                </div>
                <div class="fg">
                    <label class="fg-label">Notes</label>
                    <textarea name="notes" class="fg-textarea" placeholder="Visit notes, observations…"></textarea>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Health worker (common to most modules) -->
        <?php if (!in_array($type, ['immunization','maternal'])): ?>
        <div class="form-section">
            <div class="form-section-lbl">Health Worker</div>
            <div class="form-section-body">
                <div class="form-grid-2">
                    <div class="fg">
                        <label class="fg-label">Attending Health Worker</label>
                        <input type="text" name="health_worker" class="fg-input"
                               value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                    </div>
                    <div class="fg">
                        <label class="fg-label">General Notes</label>
                        <input type="text" name="notes" class="fg-input" placeholder="Additional remarks…" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-submit-bar">
            <button type="button" class="btn btn-ghost" onclick="$('#addVisitModal').dialog('close')">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveBtn">Save Visit</button>
        </div>
    </form>
</div>

<?php loadAllScripts(); ?>
<script>
const CV_TYPE = <?= json_encode($type) ?>;
const API     = 'api/care_visits_api.php';

$(function(){
    $('body').show();

    function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

    function showAlert(title,msg,type,cb){
        const col=type==='success'?'var(--ok-fg)':'var(--danger-fg)';
        const id='al_'+Date.now();
        $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
        </div>`);
        $(`#${id}`).dialog({autoOpen:true,modal:true,width:400,resizable:false,close:cb||null,
            buttons:{'OK':function(){$(this).dialog('close').remove();}}});
    }

    /* ── Resident autocomplete ── */
    function bindResSearch(inputId, hiddenId, labelId, onSelect){
        $('#'+inputId).autocomplete({
            minLength:1,
            source:(req,res)=>$.getJSON('../consultation/api/resident.php',{term:req.term},res),
            select(e,ui){
                $('#'+hiddenId).val(ui.item.id);
                if(labelId) $('#'+labelId).text(ui.item.label).css({color:'var(--ink)',fontStyle:'normal'});
                if(onSelect) onSelect(ui.item.id, ui.item.label);
                return false;
            }
        });
        $('#'+inputId).on('input',()=>{ $('#'+hiddenId).val(''); if(labelId) $('#'+labelId).text('No patient selected').css({color:'var(--ink-faint)',fontStyle:'italic'}); });
    }

    /* ── Page resident selector ── */
    bindResSearch('resSearch','resId',null,(rid,name)=>{
        $('#resName').text(name).css({color:'var(--ink)',fontStyle:'normal'});
        $('#pageSubtitle').text(name);
        loadRecords(rid);
        if(CV_TYPE==='immunization') loadImmCard(rid);
        if(CV_TYPE==='maternal') loadMaternalProfile(rid);
        $('#btnSaveProfile,#btnSaveProfile2').prop('disabled',false);
        $('#mpResId').val(rid);
        // Pre-fill add modal resident
        $('#av_res_id').val(rid);
        $('#av_res_name').val(name);
    });

    /* ── Load records ── */
    function loadRecords(rid){
        $('#recList').html('<div class="rec-empty">Loading…</div>');
        $.getJSON(API,{action:'list',type:CV_TYPE,resident_id:rid},function(res){
            if(res.status!=='ok'){ $('#recList').html('<div class="rec-empty">Error loading records.</div>'); return; }
            const rows=res.data?.data||[];
            $('#recCount').text(rows.length+(rows.length===1?' RECORD':' RECORDS'));
            if(!rows.length){ $('#recList').html('<div class="rec-empty">No visits recorded yet.</div>'); return; }
            let html='';
            rows.forEach(r=>{
                const m=r.module||{};
                let sub='—';
                if(CV_TYPE==='prenatal'&&m.aog_weeks) sub='AOG: '+m.aog_weeks+' wks · '+( m.risk_level||'');
                else if(CV_TYPE==='postnatal'&&m.delivery_type) sub='Delivery: '+m.delivery_type+(m.breastfeeding_status?' · BF: '+m.breastfeeding_status:'');
                else if(CV_TYPE==='family_planning'&&m.method) sub='Method: '+m.method+(m.next_supply_date?' · Next: '+m.next_supply_date:'');
                else if(CV_TYPE==='child_nutrition'&&m.weight_kg) sub='Wt: '+m.weight_kg+'kg · '+( m.stunting_status||'Not assessed');
                else if(r.notes) sub=r.notes.substring(0,60);
                html+=`<div class="rec-item" data-id="${r.id}">
                    <div class="rec-item-date">${esc(r.visit_date)}</div>
                    <div class="rec-item-name">${esc(r.resident_name||'')}</div>
                    <div class="rec-item-sub">${esc(sub)}</div>
                </div>`;
            });
            $('#recList').html(html);
        });
    }

    /* ── Load immunization card ── */
    function loadImmCard(rid){
        $('#nipGrid').html('<div class="rec-empty" style="grid-column:1/-1">Loading…</div>');
        $.getJSON(API,{action:'immunization_card',resident_id:rid},function(res){
            if(res.status!=='ok'){ $('#nipGrid').html('<div class="rec-empty" style="grid-column:1/-1">Error.</div>'); return; }
            const d=res.data;
            $('#immStats').text(`${d.given_count}/${d.total} given · ${d.overdue} overdue`);
            let html='';
            (d.schedule||[]).forEach(s=>{
                const cls=s.given?'given':s.is_overdue?'overdue':'';
                const status=s.given?'✓ Given':(s.is_overdue?'Overdue':'Pending');
                const dateStr=s.given?s.given.date_given:(s.due_date||'');
                html+=`<div class="nip-slot ${cls}">
                    <div class="nip-vaccine">${esc(s.vaccine_name)}</div>
                    <div class="nip-dose">${esc(s.dose_label)}</div>
                    <div class="nip-status">${status}</div>
                    ${dateStr?`<div class="nip-date">${esc(dateStr)}</div>`:''}
                    ${s.given&&s.given.batch_number?`<div class="nip-date">Lot: ${esc(s.given.batch_number)}</div>`:''}
                </div>`;
            });
            $('#nipGrid').html(html||'<div class="rec-empty" style="grid-column:1/-1">No schedule found.</div>');
        });
    }

    /* ── Load maternal profile ── */
    function loadMaternalProfile(rid){
        $.getJSON(API,{action:'maternal_profile',resident_id:rid},function(res){
            if(res.status!=='ok'||!res.data?.profile) return;
            const p=res.data.profile;
            const form=$('#maternalProfileForm');
            Object.entries(p).forEach(([k,v])=>{
                const el=form.find(`[name="${k}"]`);
                if(!el.length) return;
                if(el.attr('type')==='checkbox') el.prop('checked', v==1||v===true||v==='1');
                else el.val(v||'');
            });
        });
    }

    /* ── Add modal ── */
    $('#addVisitModal').dialog({
        autoOpen:false, modal:true, width:780, resizable:false,
        buttons:{
            'Save Visit':function(){$('#addVisitForm').trigger('submit');},
            'Cancel':function(){$(this).dialog('close');}
        }
    });
    $('#btnAdd').on('click',()=>{
        const rid=$('#resId').val(), name=$('#resName').text();
        if(!rid){ showAlert('No Patient','Please select a patient first.','danger'); return; }
        $('#av_res_id').val(rid);
        $('#av_res_name').val(name);
        $('#av_date').val(new Date().toISOString().slice(0,10));
        $('#addVisitModal').dialog('open');
    });

    /* Modal resident search */
    bindResSearch('av_res_name','av_res_id',null,(rid,name)=>{
        $('#resId').val(rid);
        $('#resName').text(name).css({color:'var(--ink)',fontStyle:'normal'});
        loadRecords(rid);
        if(CV_TYPE==='immunization') loadImmCard(rid);
        if(CV_TYPE==='maternal') loadMaternalProfile(rid);
        $('#mpResId').val(rid);
    });

    /* LMP → EDD computation */
    $('#lmpDate').on('change',function(){
        const lmp=this.value;
        if(!lmp){ $('#eddDisplay').val(''); return; }
        const edd=new Date(new Date(lmp).getTime()+280*86400000);
        $('#eddDisplay').val(edd.toISOString().slice(0,10));
    });

    /* FP method other */
    $('#fpMethod').on('change',function(){
        $('#fpMethodOtherWrap').toggle(this.value==='Other');
    });

    /* NIP schedule → auto-fill vaccine fields */
    if(CV_TYPE==='immunization'){
        $.getJSON(API,{action:'nip_schedule'},function(res){
            const sch=res.data?.schedule||[];
            const names=[...new Set(sch.map(s=>s.vaccine_name))];
            $('#vaccineName').autocomplete({
                source:names,
                select(e,ui){
                    $('#vaccineName').val(ui.item.value);
                    // Find first matching dose for this vaccine
                    const s=sch.find(x=>x.vaccine_name===ui.item.value);
                    if(s){
                        $('#vaccineDose').val(s.dose_label);
                        $('[name="route"]').val(s.route||'IM');
                        $('[name="site_given"]').val(s.site||'');
                    }
                    return false;
                }
            });
        });
    }

    /* ── Form submit ── */
    $('#addVisitForm').on('submit',function(e){
        e.preventDefault();
        const rid=$('#av_res_id').val();
        if(!rid){ showAlert('Error','Please select a patient.','danger'); return; }
        const $btn=$('#saveBtn');
        $btn.prop('disabled',true).text('Saving…');
        $.ajax({
            url:API+'?action=save',type:'POST',data:$(this).serialize(),dataType:'json',
            success(res){
                if(res.status!=='ok'){ showAlert('Error',res.message||'Save failed.','danger'); return; }
                $('#addVisitModal').dialog('close');
                showAlert('Saved','Visit recorded successfully.','success',()=>{
                    loadRecords(rid);
                    if(CV_TYPE==='immunization') loadImmCard(rid);
                });
            },
            error(xhr){ showAlert('Error','Server error ('+xhr.status+').','danger'); },
            complete(){ $btn.prop('disabled',false).text('Save Visit'); }
        });
    });

    /* ── Maternal profile submit ── */
    $('#maternalProfileForm').on('submit',function(e){
        e.preventDefault();
        const rid=$('#mpResId').val();
        if(!rid){ showAlert('Error','Select a patient first.','danger'); return; }
        $.post(API+'?action=save_maternal_profile',$(this).serialize(),function(res){
            const r=typeof res==='string'?JSON.parse(res):res;
            if(r.status!=='ok'){ showAlert('Error',r.message||'Save failed.','danger'); return; }
            showAlert('Saved','Obstetric profile updated.','success');
        }).fail(()=>showAlert('Error','Server error.','danger'));
    });

    /* ── Print ── */
    $('#btnPrint').on('click',()=>{
        const rid=$('#resId').val();
        const p=new URLSearchParams({type:CV_TYPE,resident_id:rid||''});
        window.open('print.php?'+p.toString(),'_blank');
    });
});
</script>
</body>
</html>
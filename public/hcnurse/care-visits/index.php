<?php
/**
 * Care Visits — v4
 * Changes:
 *  - "Print" button replaced with "Generate Report" which opens a modal
 *  - Modal lets user pick date range, then opens print.php
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type    = $_GET['type'] ?? 'general';
$allowed = ['general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','other'];
if (!in_array($type, $allowed, true)) $type = 'general';

$mod = [
    'general'         => ['icon'=>'❤️‍🩹','label'=>'General',         'color'=>'#2d5a27','bg'=>'#f0fdf4','light'=>'#e8faea'],
    'maternal'        => ['icon'=>'🤱','label'=>'Maternal Health',  'color'=>'#9f1239','bg'=>'#fff1f2','light'=>'#fdf0f2'],
    'family_planning' => ['icon'=>'💊','label'=>'Family Planning',  'color'=>'#1e40af','bg'=>'#eff6ff','light'=>'#eaf3ff'],
    'prenatal'        => ['icon'=>'👶','label'=>'Prenatal / ANC',   'color'=>'#92400e','bg'=>'#fffbeb','light'=>'#fef8e0'],
    'postnatal'       => ['icon'=>'🍼','label'=>'Postnatal / PNC',  'color'=>'#134e4a','bg'=>'#f0fdfa','light'=>'#e8fbf5'],
    'child_nutrition' => ['icon'=>'🥗','label'=>'Child Nutrition',  'color'=>'#14532d','bg'=>'#f0fdf4','light'=>'#e8faea'],
    'immunization'    => ['icon'=>'💉','label'=>'Immunization',     'color'=>'#4c1d95','bg'=>'#f5f3ff','light'=>'#eeeaff'],
    'other'           => ['icon'=>'📋','label'=>'Other',            'color'=>'#0c444e','bg'=>'#e0f5f8','light'=>'#d4f0f5'],
];
$mc = $mod[$type];

/* ── Patients with records (consultations) ── */
$pStmt = $conn->prepare("
    SELECT r.id,
           CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) full_name,
           r.birthdate, r.gender,
           MAX(c.consultation_date) last_visit,
           COUNT(c.id) cnt,
           MAX(c.consult_status) last_status
    FROM consultations c
    INNER JOIN residents r ON r.id=c.resident_id AND r.deleted_at IS NULL
    WHERE c.consult_type=?
    GROUP BY r.id,r.first_name,r.middle_name,r.last_name,r.birthdate,r.gender
    ORDER BY last_visit DESC LIMIT 300
");
$pStmt->bind_param('s',$type); $pStmt->execute();
$patients = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── Patients with records (care_visits) ── */
$cvStmt = $conn->prepare("
    SELECT r.id,
           CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) full_name,
           r.birthdate, r.gender,
           MAX(cv.visit_date) last_visit, COUNT(cv.id) cnt, NULL last_status
    FROM care_visits cv
    INNER JOIN residents r ON r.id=cv.resident_id AND r.deleted_at IS NULL
    WHERE cv.care_type=?
    GROUP BY r.id,r.first_name,r.middle_name,r.last_name,r.birthdate,r.gender
    ORDER BY last_visit DESC LIMIT 300
");
$cvStmt->bind_param('s',$type); $cvStmt->execute();
$cvPts = $cvStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$ptMap = [];
foreach ($patients as $p) $ptMap[$p['id']] = $p;
foreach ($cvPts as $p) {
    if (!isset($ptMap[$p['id']])) $ptMap[$p['id']] = $p;
    else $ptMap[$p['id']]['cnt'] += $p['cnt'];
}
usort($ptMap, fn($a,$b) => strcmp($b['last_visit']??'',$a['last_visit']??''));
$total = count($ptMap);

function age(string $bd=''): string {
    if (!$bd) return '';
    $b = new DateTime($bd);
    return $b->diff(new DateTime())->y.' yrs';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($mc['label']) ?> — MIS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php loadAllAssets(); ?>
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
    --p:var(--paper,#fdfcf9);--plt:var(--paper-lt,#f9f7f3);
    --ink:#1a1a1a;--muted:#5a5a5a;--faint:#a0a0a0;
    --rule:#d8d4cc;--rule-dk:#b8b4ac;--bg:#edeae4;
    --acc:var(--theme-primary,#2d5a27);
    --mod:<?= $mc['color'] ?>;--mod-bg:<?= $mc['bg'] ?>;--mod-lt:<?= $mc['light'] ?>;
    --ok-bg:#edfaf3;--ok-fg:#1a5c35;
    --warn-bg:#fef9ec;--warn-fg:#7a5700;
    --danger-bg:#fdeeed;--danger-fg:#7a1f1a;
    --info-bg:#edf3fa;--info-fg:#1a3a5c;
    --f-s:'Source Serif 4',Georgia,serif;
    --f-n:'Source Sans 3','Segoe UI',sans-serif;
    --f-m:'Source Code Pro','Courier New',monospace;
    --sh:0 1px 2px rgba(0,0,0,.07),0 3px 14px rgba(0,0,0,.05);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:var(--f-n);}
.page{background:var(--bg);min-height:100%;padding-bottom:56px;}

/* header */
.hdr{background:var(--p);border-bottom:1px solid var(--rule);}
.hdr-inner{padding:18px 28px 0;display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.eyebrow{font-size:8.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:5px;}
.eyebrow::before{content:'';width:18px;height:2px;background:var(--mod);display:inline-block;}
.page-title{font-family:var(--f-s);font-size:21px;font-weight:700;color:var(--ink);margin-bottom:3px;display:flex;align-items:center;gap:9px;}
.page-sub{font-size:12px;color:var(--faint);font-style:italic;}
.accent-bar{height:3px;margin-top:12px;background:linear-gradient(to right,var(--mod),transparent);}

/* tabs */
.tabs{display:flex;gap:0;padding:0 28px;overflow-x:auto;scrollbar-width:none;}
.tabs::-webkit-scrollbar{display:none;}
.tab{display:flex;align-items:center;gap:6px;padding:9px 13px;font-size:12px;font-weight:600;color:var(--muted);text-decoration:none;border-bottom:2.5px solid transparent;white-space:nowrap;transition:all .12s;}
.tab:hover{color:var(--ink);}
.tab.on{color:var(--mod);border-bottom-color:var(--mod);}

/* layout */
.body{display:grid;grid-template-columns:320px 1fr;gap:16px;margin:16px 28px 0;}
@media(max-width:1060px){.body{grid-template-columns:1fr;}}

/* card */
.card{background:var(--p);border:1px solid var(--rule);border-radius:2px;box-shadow:var(--sh);overflow:hidden;}
.card-head{padding:10px 15px;border-bottom:1px solid var(--rule);background:var(--plt);display:flex;align-items:center;justify-content:space-between;gap:8px;}
.card-title{font-size:8.5px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:7px;}
.card-title::before{content:'';width:3px;height:11px;background:var(--mod);border-radius:1px;flex-shrink:0;}

/* search */
.search-box{padding:10px 14px;border-bottom:1px solid var(--rule);}
.search-input{width:100%;padding:7px 11px;border:1.5px solid var(--rule-dk);border-radius:2px;font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .13s;}
.search-input:focus{border-color:var(--mod);}
.search-input::placeholder{color:var(--faint);font-style:italic;font-size:12px;}

/* patient list */
.pt-list{max-height:calc(100vh - 340px);overflow-y:auto;}
.pt-row{display:flex;align-items:center;gap:12px;padding:10px 14px;cursor:pointer;border-bottom:1px solid #f0ede8;transition:background .1s;}
.pt-row:last-child{border-bottom:none;}
.pt-row:hover{background:var(--mod-bg);}
.pt-row.on{background:var(--mod-bg);border-left:3px solid var(--mod);}
.pt-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--f-m);font-size:12px;font-weight:700;flex-shrink:0;background:var(--mod-lt);color:var(--mod);border:1.5px solid color-mix(in srgb,var(--mod) 20%,transparent);}
.pt-info{flex:1;min-width:0;}
.pt-name-txt{font-weight:600;font-size:13px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pt-meta{font-size:10.5px;color:var(--faint);margin-top:1px;}
.pt-right{text-align:right;flex-shrink:0;}
.pt-date{font-family:var(--f-m);font-size:9.5px;color:var(--faint);}
.pt-cnt{font-family:var(--f-m);font-size:10px;font-weight:700;color:var(--mod);margin-top:2px;}
.pt-empty{padding:28px 16px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;line-height:1.7;}

/* right pane */
.right{display:flex;flex-direction:column;gap:14px;}

/* selected patient header */
.sel-hdr{padding:14px 18px;background:var(--mod-lt);border-bottom:1px solid color-mix(in srgb,var(--mod) 12%,transparent);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
.sel-name{font-family:var(--f-s);font-size:16px;font-weight:600;color:var(--ink);}
.sel-meta{font-size:11px;color:var(--muted);margin-top:2px;}

/* visit history */
.v-list{max-height:400px;overflow-y:auto;}
.v-row{display:flex;align-items:center;gap:10px;padding:10px 15px;border-bottom:1px solid #f0ede8;cursor:pointer;transition:background .1s;}
.v-row:last-child{border-bottom:none;}
.v-row:hover{background:var(--plt);}
.v-row.on{background:var(--mod-bg);}
.v-date{font-family:var(--f-m);font-size:10px;color:var(--faint);white-space:nowrap;min-width:76px;}
.v-summary{flex:1;font-size:12.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.v-badges{display:flex;gap:5px;flex-shrink:0;}
.badge{display:inline-block;padding:2px 7px;border-radius:2px;font-size:8.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;border:1px solid;}
.b-completed{background:var(--ok-bg);color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent);}
.b-ongoing{background:var(--warn-bg);color:var(--warn-fg);border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent);}
.b-followup{background:var(--info-bg);color:var(--info-fg);border-color:color-mix(in srgb,var(--info-fg) 25%,transparent);}
.b-dismissed{background:#f3f1ec;color:#5a5a5a;border-color:#d8d4cc;}
.b-consult{background:#edf3fa;color:#1a3a5c;border-color:#bfdbfe;}
.b-care{background:var(--ok-bg);color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent);}

/* visit detail panel */
.vd-body{padding:16px 18px;max-height:520px;overflow-y:auto;}
.vd-section{margin-bottom:16px;}
.vd-section-title{font-size:8px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:10px;}
.vd-section-title::after{content:'';flex:1;height:1px;background:var(--rule);}
.vitals-row{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:12px;}
.vital{padding:7px 11px;border:1px solid var(--rule);border-radius:2px;background:var(--plt);text-align:center;min-width:74px;}
.vital-lbl{font-size:7.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--faint);margin-bottom:2px;}
.vital-val{font-family:var(--f-m);font-size:13px;font-weight:600;color:var(--ink);}
.vd-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.vf{margin-bottom:10px;}
.vf-lbl{font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--faint);margin-bottom:2px;}
.vf-val{font-size:13px;color:var(--ink);line-height:1.6;white-space:pre-line;}
.vf-empty{font-size:12px;color:var(--faint);font-style:italic;}
.check-tag{display:inline-block;padding:1px 7px;border-radius:2px;font-size:9px;font-weight:700;margin-right:3px;margin-bottom:3px;background:var(--ok-bg);color:var(--ok-fg);border:1px solid color-mix(in srgb,var(--ok-fg) 20%,transparent);}

/* nip card */
.nip-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;padding:12px 15px;}
.nip-slot{padding:8px 10px;border-radius:2px;border:1.5px solid var(--rule);background:var(--plt);}
.nip-slot.given{border-color:var(--ok-fg);background:var(--ok-bg);}
.nip-slot.overdue{border-color:var(--danger-fg);background:var(--danger-bg);}
.ns-vaccine{font-weight:700;font-size:11px;margin-bottom:1px;}
.ns-dose{font-size:9px;color:var(--faint);margin-bottom:3px;}
.ns-status{font-size:8px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;}
.nip-slot.given .ns-status{color:var(--ok-fg);}
.nip-slot.overdue .ns-status{color:var(--danger-fg);}
.nip-slot:not(.given):not(.overdue) .ns-status{color:var(--faint);}
.ns-date{font-family:var(--f-m);font-size:8.5px;color:var(--faint);margin-top:2px;}

/* maternal profile */
.mp-display{padding:12px 16px;}
.mp-gtpal{font-family:var(--f-m);font-size:18px;font-weight:600;letter-spacing:2px;color:var(--ink);margin-bottom:6px;}
.flag-row{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px;}
.flag{padding:2px 8px;border-radius:2px;font-size:9px;font-weight:700;text-transform:uppercase;background:var(--danger-bg);color:var(--danger-fg);border:1px solid color-mix(in srgb,var(--danger-fg) 20%,transparent);}

/* empty state */
.empty-state{padding:44px 20px;text-align:center;color:var(--faint);}
.empty-icon{font-size:32px;margin-bottom:12px;opacity:.4;}
.empty-text{font-size:13px;font-style:italic;line-height:1.8;}

/* form */
.fg{margin-bottom:12px;}
.fg-lbl{display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:5px;}
.fg-lbl .req{color:var(--danger-fg);}
.fg-in,.fg-sel,.fg-ta{width:100%;padding:9px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-family:var(--f-n);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .14px,box-shadow .14s;}
.fg-in:focus,.fg-sel:focus,.fg-ta:focus{border-color:var(--mod);box-shadow:0 0 0 3px color-mix(in srgb,var(--mod) 10%,transparent);}
.fg-in::placeholder{color:var(--faint);font-style:italic;font-size:12px;}
.fg-ta{resize:vertical;min-height:66px;}
.fg-hint{font-size:10px;color:var(--faint);margin-top:3px;font-style:italic;}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.g4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:9px;}
.check-row{display:flex;flex-wrap:wrap;gap:7px;}
.chk{display:flex;align-items:center;gap:5px;padding:5px 10px;border:1.5px solid var(--rule-dk);border-radius:2px;cursor:pointer;font-size:12px;color:var(--muted);transition:all .12s;}
.chk:has(input:checked){border-color:var(--mod);background:var(--mod-bg);color:var(--mod);}
.chk input{display:none;}
.form-section-title{font-size:8px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:12px;margin-top:4px;}
.form-section-title::after{content:'';flex:1;height:1px;background:var(--rule);}

/* btn */
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:2px;font-family:var(--f-n);font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .13s;white-space:nowrap;}
.btn-mod{background:var(--mod);border-color:var(--mod);color:#fff;}
.btn-mod:hover{filter:brightness(1.1);}
.btn-ghost{background:#fff;border-color:var(--rule-dk);color:var(--muted);}
.btn-ghost:hover{border-color:var(--mod);color:var(--mod);}
.btn-danger{background:var(--danger-bg);border-color:color-mix(in srgb,var(--danger-fg) 30%,transparent);color:var(--danger-fg);}
.btn-danger:hover{background:var(--danger-fg);color:#fff;}
.btn:disabled{opacity:.4;cursor:not-allowed;filter:none!important;}
.submit-bar{padding:11px 16px;border-top:1px solid var(--rule);background:var(--plt);display:flex;justify-content:flex-end;gap:8px;}

/* modal */
.ui-dialog{border:1px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 8px 48px rgba(0,0,0,.18)!important;padding:0!important;font-family:var(--f-n)!important;}
.ui-dialog-titlebar{background:var(--mod)!important;border:none!important;padding:11px 15px!important;}
.ui-dialog-title{font-family:var(--f-n)!important;font-size:11px!important;font-weight:700!important;letter-spacing:1px!important;text-transform:uppercase!important;color:#fff!important;}
.ui-dialog-titlebar-close{background:rgba(255,255,255,.15)!important;border:1px solid rgba(255,255,255,.25)!important;border-radius:2px!important;color:#fff!important;width:24px!important;height:24px!important;top:50%!important;transform:translateY(-50%)!important;}
.ui-dialog-content{padding:0!important;}
.ui-dialog-buttonpane{display:none!important;}
.ui-autocomplete{z-index:9999!important;max-height:200px;overflow-y:auto;}

/* generate modal specific */
.gen-scope-toggle{display:flex;gap:0;margin-bottom:14px;border:1.5px solid var(--rule-dk);border-radius:2px;overflow:hidden;}
.gen-scope-btn{flex:1;padding:8px 10px;text-align:center;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;color:var(--muted);background:#fff;border:none;transition:all .13s;}
.gen-scope-btn.active{background:var(--mod);color:#fff;}
.gen-preview{margin-top:10px;padding:10px 12px;background:var(--mod-lt);border:1px solid color-mix(in srgb,var(--mod) 20%,transparent);border-radius:2px;font-size:11px;color:var(--muted);line-height:1.6;}
.gen-preview strong{color:var(--mod);}
</style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
<?php include_once '../layout/navbar.php'; ?>
<div class="flex h-full" style="background:var(--bg);">
    <?php include_once '../layout/sidebar.php'; ?>
    <main class="flex-1 h-screen overflow-y-auto page">

        <!-- header -->
        <div class="hdr">
            <div class="hdr-inner">
                <div>
                    <div class="eyebrow">Barangay Bombongan · Health Center · Care Records</div>
                    <div class="page-title"><?= $mc['icon'] ?> <?= htmlspecialchars($mc['label']) ?></div>
                    <div class="page-sub"><?= $total ?> patient<?= $total!==1?'s':'' ?> on record — select to view</div>
                </div>
                <div style="display:flex;gap:7px;padding-bottom:4px;">
                    <button class="btn btn-ghost" id="btnGenerate">↗ Generate Report</button>
                    <button class="btn btn-mod" id="btnNewVisit" disabled>+ New Visit</button>
                </div>
            </div>
            <div class="tabs">
                <?php foreach ($mod as $t=>$cfg): ?>
                <a href="?type=<?= $t ?>" class="tab <?= $t===$type?'on':'' ?>"
                   style="<?= $t===$type?'--mod:'.$cfg['color'].';':'' ?>">
                    <?= $cfg['icon'] ?> <?= htmlspecialchars($cfg['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="accent-bar"></div>
        </div>

        <div class="body">

            <!-- LEFT: patient list -->
            <div style="display:flex;flex-direction:column;gap:12px;">

                <!-- search any resident -->
                <div class="card">
                    <div class="card-head">
                        <div class="card-title">Search any resident</div>
                    </div>
                    <div class="search-box">
                        <input type="text" id="anySearch" class="search-input"
                               placeholder="Type name to search all residents…" autocomplete="off">
                        <input type="hidden" id="anyId">
                    </div>
                </div>

                <!-- patients with records -->
                <div class="card" style="flex:1;">
                    <div class="card-head">
                        <div class="card-title">
                            Patients with records
                            <span style="font-family:var(--f-m);font-size:9px;color:var(--faint);letter-spacing:.3px;font-weight:400;">(<?= $total ?>)</span>
                        </div>
                        <input type="text" id="ptFilter" class="search-input"
                               style="width:140px;padding:4px 9px;font-size:12px;" placeholder="Filter…">
                    </div>
                    <div class="pt-list" id="ptList">
                        <?php if (empty($ptMap)): ?>
                        <div class="pt-empty">
                            No <?= htmlspecialchars(strtolower($mc['label'])) ?> records yet.<br>
                            <small>Use "+ New Visit" to add one.</small>
                        </div>
                        <?php else: foreach ($ptMap as $p):
                            $a     = age($p['birthdate']??'');
                            $g     = $p['gender']??'';
                            $lv    = $p['last_visit']??'';
                            $parts = explode(' ', trim($p['full_name']));
                            $init  = strtoupper(substr($parts[0]??'?',0,1).(count($parts)>1?substr($parts[count($parts)-1],0,1):''));
                        ?>
                        <div class="pt-row"
                             data-id="<?= (int)$p['id'] ?>"
                             data-name="<?= htmlspecialchars($p['full_name']) ?>"
                             data-age="<?= htmlspecialchars($a) ?>"
                             data-gender="<?= htmlspecialchars($g) ?>">
                            <div class="pt-avatar"><?= htmlspecialchars($init) ?></div>
                            <div class="pt-info">
                                <div class="pt-name-txt"><?= htmlspecialchars($p['full_name']) ?></div>
                                <div class="pt-meta"><?= htmlspecialchars(implode(' · ',array_filter([$a,$g]))) ?></div>
                            </div>
                            <div class="pt-right">
                                <div class="pt-date"><?= htmlspecialchars($lv) ?></div>
                                <div class="pt-cnt"><?= (int)$p['cnt'] ?> visit<?= $p['cnt']!=1?'s':'' ?></div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: detail pane -->
            <div class="right">

                <!-- empty state -->
                <div class="card" id="emptyCard">
                    <div class="empty-state">
                        <div class="empty-icon"><?= $mc['icon'] ?></div>
                        <div class="empty-text">
                            Select a patient from the list<br>
                            or search above to load their<br>
                            <?= htmlspecialchars(strtolower($mc['label'])) ?> records.
                        </div>
                    </div>
                </div>

                <!-- selected patient card -->
                <div class="card" id="detailCard" style="display:none;">

                    <!-- patient bar -->
                    <div class="sel-hdr">
                        <div>
                            <div class="sel-name" id="selName">—</div>
                            <div class="sel-meta" id="selMeta">—</div>
                        </div>
                    </div>

                    <?php if ($type==='immunization'): ?>
                    <div>
                        <div class="card-head" style="border-top:1px solid var(--rule);">
                            <div class="card-title">NIP Immunization Card</div>
                            <span id="immStats" style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);">—</span>
                        </div>
                        <div class="nip-grid" id="nipGrid">
                            <div style="grid-column:1/-1;padding:14px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">—</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($type==='maternal'): ?>
                    <div>
                        <div class="card-head" style="border-top:1px solid var(--rule);">
                            <div class="card-title">Obstetric Profile (GTPAL)</div>
                            <button class="btn btn-ghost" id="btnEditProfile" style="padding:4px 10px;font-size:10px;">Edit</button>
                        </div>
                        <div class="mp-display" id="mpDisplay">
                            <span style="color:var(--faint);font-size:12px;font-style:italic;">No profile recorded yet.</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- visit history -->
                    <div>
                        <div class="card-head" style="border-top:1px solid var(--rule);">
                            <div class="card-title">Visit History</div>
                            <span id="vCount" style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);">—</span>
                        </div>
                        <div class="v-list" id="vList">
                            <div style="padding:16px;text-align:center;color:var(--faint);font-size:12px;">—</div>
                        </div>
                    </div>

                </div>

                <!-- visit detail card -->
                <div class="card" id="vDetailCard" style="display:none;">
                    <div class="card-head">
                        <div class="card-title" id="vdTitle">Visit Detail</div>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-ghost" style="padding:4px 10px;font-size:10px;" id="btnEditVisit">✏ Edit</button>
                            <button class="btn btn-ghost" style="padding:4px 10px;font-size:10px;" onclick="$('#vDetailCard').slideUp(120)">✕</button>
                        </div>
                    </div>
                    <div class="vd-body" id="vdBody"></div>
                </div>

            </div>
        </div>

    </main>
</div>

<!-- ══════════════════════════════
     GENERATE REPORT MODAL
══════════════════════════════ -->
<div id="generateModal" title="Generate Report" class="hidden">
    <form id="generateForm" style="padding:18px 20px 0;display:flex;flex-direction:column;gap:0;">

        <!-- Scope toggle: specific patient vs all patients -->
        <div style="margin-bottom:14px;">
            <label class="fg-lbl" style="margin-bottom:8px;">Report Scope</label>
            <div class="gen-scope-toggle">
                <button type="button" class="gen-scope-btn active" id="scopePatient">Selected Patient</button>
                <button type="button" class="gen-scope-btn" id="scopeAll">All Patients</button>
            </div>
        </div>

        <!-- Date range -->
        <div class="g2" style="margin-bottom:14px;">
            <div class="fg" style="margin-bottom:0;">
                <label class="fg-lbl">From Date</label>
                <input type="date" id="gen_from" class="fg-in" value="<?= date('Y-01-01') ?>">
            </div>
            <div class="fg" style="margin-bottom:0;">
                <label class="fg-lbl">To Date</label>
                <input type="date" id="gen_to" class="fg-in" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Quick range shortcuts -->
        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:16px;">
            <button type="button" class="btn btn-ghost" style="padding:4px 10px;font-size:9.5px;" data-range="today">Today</button>
            <button type="button" class="btn btn-ghost" style="padding:4px 10px;font-size:9.5px;" data-range="week">This Week</button>
            <button type="button" class="btn btn-ghost" style="padding:4px 10px;font-size:9.5px;" data-range="month">This Month</button>
            <button type="button" class="btn btn-ghost" style="padding:4px 10px;font-size:9.5px;" data-range="quarter">This Quarter</button>
            <button type="button" class="btn btn-ghost" style="padding:4px 10px;font-size:9.5px;" data-range="year">This Year</button>
        </div>

        <!-- Preview strip -->
        <div class="gen-preview" id="genPreview">
            Will generate a <strong><?= htmlspecialchars($mc['label']) ?></strong> report
            for <strong id="previewScope">the selected patient</strong>
            from <strong id="previewFrom"><?= date('Y-01-01') ?></strong>
            to <strong id="previewTo"><?= date('Y-m-d') ?></strong>.
        </div>

        <div class="submit-bar" style="margin:14px -20px 0;padding:11px 20px;">
            <button type="button" class="btn btn-ghost" onclick="$('#generateModal').dialog('close')">Cancel</button>
            <button type="button" class="btn btn-mod" id="btnDoGenerate">↗ Generate &amp; Print</button>
        </div>
    </form>
</div>

<!-- ══════════════════════════════
     MATERNAL PROFILE MODAL
══════════════════════════════ -->
<?php if ($type==='maternal'): ?>
<div id="mpModal" title="Edit Obstetric Profile" class="hidden">
<form id="mpForm" style="max-height:68vh;overflow-y:auto;">
    <input type="hidden" name="resident_id" id="mp_rid">
    <div style="padding:16px 18px 0;">
        <div class="form-section-title">GTPAL</div>
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;padding-bottom:14px;">
            <?php foreach(['gravida'=>'G','term'=>'T','preterm'=>'P','abortions'=>'A','living_children'=>'L'] as $n=>$l): ?>
            <div class="fg">
                <label class="fg-lbl"><?= $l ?></label>
                <input type="number" name="<?= $n ?>" min="0" class="fg-in" placeholder="0" style="text-align:center;">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="form-section-title">Complications</div>
        <div class="check-row" style="padding-bottom:14px;">
            <?php foreach(['hx_pre_eclampsia'=>'Pre-eclampsia','hx_pph'=>'PPH','hx_cesarean'=>'C-Section','hx_ectopic'=>'Ectopic','hx_stillbirth'=>'Stillbirth'] as $n=>$l): ?>
            <label class="chk"><input type="checkbox" name="<?= $n ?>" value="1"> <?= $l ?></label>
            <?php endforeach; ?>
        </div>
        <div class="form-section-title">Chronic Conditions</div>
        <div class="check-row" style="padding-bottom:12px;">
            <?php foreach(['has_diabetes'=>'Diabetes','has_hypertension'=>'Hypertension','has_hiv'=>'HIV','has_anemia'=>'Anemia'] as $n=>$l): ?>
            <label class="chk"><input type="checkbox" name="<?= $n ?>" value="1"> <?= $l ?></label>
            <?php endforeach; ?>
        </div>
        <div class="g2" style="padding-bottom:14px;">
            <div class="fg">
                <label class="fg-lbl">Blood Type</label>
                <select name="blood_type" class="fg-sel">
                    <?php foreach(['Unknown','A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                    <option value="<?= $bt ?>"><?= $bt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label class="fg-lbl">Other Conditions</label>
                <input type="text" name="other_conditions" class="fg-in" autocomplete="off">
            </div>
        </div>
    </div>
    <div class="submit-bar">
        <button type="button" class="btn btn-ghost" onclick="$('#mpModal').dialog('close')">Cancel</button>
        <button type="submit" class="btn btn-mod">Save Profile</button>
    </div>
</form>
</div>
<?php endif; ?>

<!-- ══════════════════════════════
     EDIT CARE VISIT MODAL
══════════════════════════════ -->
<div id="editModal" title="Edit Care Visit" class="hidden">
<form id="editForm" style="max-height:76vh;overflow-y:auto;">
    <input type="hidden" name="care_visit_id" id="ef_vid">
    <input type="hidden" name="type"          id="ef_type">
    <div style="padding:16px 18px 0;">

        <!-- Always present: date + notes -->
        <div class="g2">
            <div class="fg">
                <label class="fg-lbl">Visit Date <span class="req">*</span></label>
                <input type="date" name="visit_date" id="ef_date" class="fg-in">
            </div>
            <div class="fg">
                <label class="fg-lbl">Health Worker</label>
                <input type="text" name="health_worker" id="ef_worker" class="fg-in" autocomplete="off">
            </div>
        </div>
        <div class="fg">
            <label class="fg-lbl">Notes / General Remarks</label>
            <textarea name="notes" id="ef_notes" class="fg-ta"></textarea>
        </div>

        <!-- Dynamic fields rendered per type -->
        <div id="ef_fields"></div>

    </div>
    <div class="submit-bar">
        <button type="button" class="btn btn-ghost" onclick="$('#editModal').dialog('close')">Cancel</button>
        <button type="submit" class="btn btn-mod" id="efSubmit">Save Changes</button>
    </div>
</form>
</div>

<script>
const CV_TYPE = <?= json_encode($type) ?>;
const CV_API  = 'api/care_visits_api.php';
let selId = null, selName = '';
let currentVisitData = null;
let genScope = 'patient'; // 'patient' | 'all'

$(function(){
    $('body').show();

    function esc(s){ const d=document.createElement('div'); d.textContent=String(s||''); return d.innerHTML; }
    function or(v,fb=''){ return (v===null||v===undefined||v==='')?fb:v; }
    function yn(v){ return v==1||v==='1'?'<span class="check-tag">Yes</span>':'No'; }

    function statusBadge(s){
        if(!s) return '';
        const map={Completed:'b-completed',Ongoing:'b-ongoing','Follow-up':'b-followup',Dismissed:'b-dismissed'};
        return `<span class="badge ${map[s]||'b-dismissed'}">${esc(s)}</span>`;
    }

    /* ════════════════════════════
       GENERATE REPORT MODAL
    ════════════════════════════ */
    $('#generateModal').dialog({
        autoOpen: false, modal: true, width: 460, resizable: false,
    });

    $('#btnGenerate').on('click', function(){
        // Pre-fill scope based on whether a patient is selected
        if(selId){
            setScope('patient');
        } else {
            setScope('all');
        }
        updatePreview();
        $('#generateModal').dialog('open');
    });

    // Scope toggle
    function setScope(s){
        genScope = s;
        if(s === 'patient'){
            $('#scopePatient').addClass('active');
            $('#scopeAll').removeClass('active');
        } else {
            $('#scopeAll').addClass('active');
            $('#scopePatient').removeClass('active');
        }
        updatePreview();
    }

    $('#scopePatient').on('click', function(){
        if(!selId){ alert('Please select a patient first.'); return; }
        setScope('patient');
    });
    $('#scopeAll').on('click', function(){ setScope('all'); });

    // Quick range shortcuts
    $('[data-range]').on('click', function(){
        const r = $(this).data('range');
        const today = new Date();
        const fmt = d => d.toISOString().slice(0,10);
        let from, to = fmt(today);
        if(r==='today'){
            from = to;
        } else if(r==='week'){
            const d = new Date(today);
            d.setDate(today.getDate() - today.getDay() + 1); // Monday
            from = fmt(d);
        } else if(r==='month'){
            from = today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-01';
        } else if(r==='quarter'){
            const q = Math.floor(today.getMonth()/3);
            from = today.getFullYear()+'-'+String(q*3+1).padStart(2,'0')+'-01';
        } else if(r==='year'){
            from = today.getFullYear()+'-01-01';
        }
        $('#gen_from').val(from);
        $('#gen_to').val(to);
        updatePreview();
    });

    $('#gen_from, #gen_to').on('change', updatePreview);

    function updatePreview(){
        const from = $('#gen_from').val();
        const to   = $('#gen_to').val();
        const scopeText = genScope==='patient' && selId
            ? `<strong>${esc(selName)}</strong>`
            : '<strong>all patients</strong>';
        $('#previewScope').parent().html(
            `Will generate a <strong><?= htmlspecialchars($mc['label']) ?></strong> report
             for ${scopeText}
             from <strong>${esc(from)}</strong>
             to <strong>${esc(to)}</strong>.`
        );
    }

    $('#btnDoGenerate').on('click', function(){
        const from = $('#gen_from').val();
        const to   = $('#gen_to').val();
        if(!from || !to){ alert('Please set a date range.'); return; }
        const rid = (genScope==='patient' && selId) ? selId : '';
        const url = `print.php?type=${encodeURIComponent(CV_TYPE)}&resident_id=${encodeURIComponent(rid)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
        window.open(url, '_blank');
        $('#generateModal').dialog('close');
    });

    /* ── filter patient list ── */
    $('#ptFilter').on('input',function(){
        const q=$(this).val().toLowerCase();
        $('.pt-row').each(function(){
            const n=$(this).data('name')||'';
            $(this).toggle(!q||n.toLowerCase().includes(q));
        });
    });

    /* ── click row ── */
    $(document).on('click','.pt-row',function(){
        $('.pt-row').removeClass('on');
        $(this).addClass('on');
        selectPatient($(this).data('id'),$(this).data('name'),$(this).data('age'),$(this).data('gender'));
    });

    /* ── search any resident ── */
    $('#anySearch').autocomplete({
        minLength:1, appendTo:'body',
        position:{my:'left top',at:'left bottom',collision:'none'},
        source:(req,res)=>$.getJSON('../consultation/api/resident.php',{term:req.term},res),
        select(e,ui){
            e.preventDefault();
            $('#anyId').val(ui.item.id);
            $('#anySearch').val(ui.item.label);
            const row=$(`.pt-row[data-id="${ui.item.id}"]`);
            $('.pt-row').removeClass('on');
            if(row.length){ row.addClass('on'); row[0].scrollIntoView({behavior:'smooth',block:'nearest'}); }
            selectPatient(ui.item.id,ui.item.label,'','');
            return false;
        }
    });
    $('#anySearch').on('autocompleteopen',()=>$('.ui-autocomplete').css('z-index',9999));

    /* ── core select ── */
    function selectPatient(id,name,age,gender){
        selId=id; selName=name;
        $('#emptyCard').hide();
        $('#detailCard').show();
        $('#vDetailCard').hide();
        $('#btnNewVisit').prop('disabled',false);
        $('#selName').text(name);
        $('#selMeta').text([age,gender].filter(Boolean).join(' · ')||'Resident');
        loadVisits(id);
        if(CV_TYPE==='immunization') loadNIP(id);
        if(CV_TYPE==='maternal')     loadMP(id);
    }

    /* ── visit history ── */
    function loadVisits(rid){
        $('#vList').html('<div style="padding:14px;text-align:center;color:var(--faint);font-size:12px;">Loading…</div>');
        $('#vCount').text('…');

        const today=new Date().toISOString().slice(0,10);
        const p1=$.getJSON('../consultation/api/list_by_type.php',{type:CV_TYPE,resident_id:rid});
        const p2=$.getJSON(CV_API,{action:'list',type:CV_TYPE,resident_id:rid,from:'2000-01-01',to:today});

        $.when(p1.then(r=>r,()=>({status:'err'})),p2.then(r=>r,()=>({status:'err'}))).done(function(r1,r2){
            const c=(r1.status==='ok'?r1.data:[]).map(r=>({...r,_src:'consult'}));
            const v=(r2.status==='ok'?(r2.data?.data||[]):[]).map(r=>({...r,_src:'care'}));
            const all=[...c,...v].sort((a,b)=>{
                const da=a.consultation_date||a.visit_date||'';
                const db=b.consultation_date||b.visit_date||'';
                return db.localeCompare(da);
            });
            $('#vCount').text(all.length+(all.length===1?' RECORD':' RECORDS'));
            if(!all.length){
                $('#vList').html('<div style="padding:20px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">No visits recorded yet.<br><small>Click "+ New Visit" to add one.</small></div>');
                return;
            }
            let html='';
            all.forEach(r=>{
                const date=r.consultation_date||r.visit_date||'—';
                const status=r.consult_status||'';
                const srcBadge=r._src==='consult'
                    ?'<span class="badge b-consult">Consult</span>'
                    :'<span class="badge b-care">Care</span>';
                let summary='';
                if(r._src==='consult'){
                    summary=[r.complaint,r.diagnosis].filter(Boolean).join(' · ').substring(0,65);
                } else {
                    const m=r.module||{};
                    const vals=Object.entries(m).filter(([k,v])=>v&&!['id','resident_id','care_visit_id','created_at','updated_at','visit_date'].includes(k)).map(([,v])=>String(v));
                    summary=vals.slice(0,3).join(' · ').substring(0,65)||r.notes||'';
                }
                html+=`<div class="v-row" data-id="${r.id}" data-src="${r._src}">
                    <div class="v-date">${esc(date)}</div>
                    <div class="v-summary">${esc(summary||'—')}</div>
                    <div class="v-badges">${statusBadge(status)}${srcBadge}</div>
                </div>`;
            });
            $('#vList').html(html);
        });
    }

    /* ── click visit row → detail ── */
    $(document).on('click','.v-row',function(){
        $('.v-row').removeClass('on');
        $(this).addClass('on');
        const id=$(this).data('id'), src=$(this).data('src');
        if(src==='consult') showConsultDetail(id);
        else                showCareDetail(id);
    });

    /* ════════════════════════════
       CONSULT DETAIL (read-only)
    ════════════════════════════ */
    function showConsultDetail(id){
        $.getJSON('../consultation/api/view.php',{id},function(res){
            if(!res.success) return;
            const d=res.data;
            $('#vdTitle').text('Consult — '+d.consultation_date);
            $('#btnEditVisit').data({id,src:'consult'}).show();
            currentVisitData=null;

            let html='';
            const statusCls={Completed:'b-completed',Ongoing:'b-ongoing','Follow-up':'b-followup',Dismissed:'b-dismissed'};
            const sc=statusCls[d.consult_status]||'b-dismissed';
            html+=`<div style="padding:10px 16px;border-bottom:1px solid var(--rule);display:flex;flex-wrap:wrap;gap:6px;align-items:center;">`;
            if(d.consult_status) html+=`<span class="badge ${sc}">${esc(d.consult_status)}</span>`;
            if(d.follow_up_date) html+=`<span style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);">Follow-up: ${esc(d.follow_up_date)}</span>`;
            html+=`<span style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);margin-left:auto;">${esc(d.health_worker||'')}</span>`;
            html+='</div>';

            const vitals=[];
            if(d.temp_celsius)     vitals.push({l:'Temp',v:d.temp_celsius+'°C'});
            if(d.bp_systolic)      vitals.push({l:'BP',v:d.bp_systolic+'/'+d.bp_diastolic});
            if(d.pulse_rate)       vitals.push({l:'Pulse',v:d.pulse_rate+' bpm'});
            if(d.weight_kg)        vitals.push({l:'Weight',v:d.weight_kg+' kg'});
            if(d.height_cm)        vitals.push({l:'Height',v:d.height_cm+' cm'});
            if(d.bmi)              vitals.push({l:'BMI',v:d.bmi+(d.bmi_class?' · '+d.bmi_class:'')});
            if(vitals.length){
                html+='<div style="padding:12px 16px 0;"><div class="vd-section-title">Vitals</div><div class="vitals-row">';
                vitals.forEach(v=>{html+=`<div class="vital"><div class="vital-lbl">${esc(v.l)}</div><div class="vital-val">${esc(v.v)}</div></div>`;});
                html+='</div></div>';
            }

            function rf(lbl,val){ return val?`<div class="vf"><div class="vf-lbl">${esc(lbl)}</div><div class="vf-val">${esc(val)}</div></div>`:''; }
            html+='<div style="padding:12px 16px 0;"><div class="vd-section-title">Clinical</div><div class="vd-grid">';
            html+=`<div>${rf('Chief complaint',d.chief_complaint||d.complaint)}${rf('Diagnosis',d.primary_diagnosis||d.diagnosis)}${rf('Treatment',d.treatment)}${rf('Medicines',d.medicines_prescribed)}</div>`;
            html+=`<div>${rf('Health advice',d.health_advice)}${rf('Assessment',d.assessment)}${rf('Plan',d.plan)}</div>`;
            html+='</div></div>';

            $('#vdBody').html(html);
            $('#vDetailCard').slideDown(120);
        });
    }

    /* ════════════════════════════
       CARE VISIT DETAIL (type-aware)
    ════════════════════════════ */
    function showCareDetail(id){
        $.getJSON(CV_API,{action:'get',type:CV_TYPE,id},function(res){
            if(res.status!=='ok') return;
            const d=res.data;
            currentVisitData=d;
            $('#vdTitle').text('Care Visit — '+(d.visit_date||''));
            $('#btnEditVisit').data({id,src:'care'}).show();

            let html=renderCareView(d);
            $('#vdBody').html(html);
            $('#vDetailCard').slideDown(120);
        });
    }

    function renderCareView(d){
        function rf(lbl,val,full=false){
            if(!val&&val!==0) return '';
            return `<div class="vf"${full?' style="grid-column:1/-1"':''}><div class="vf-lbl">${esc(lbl)}</div><div class="vf-val">${esc(String(val))}</div></div>`;
        }
        function rb(lbl,val){ return `<div class="vf"><div class="vf-lbl">${esc(lbl)}</div><div class="vf-val">${yn(val)}</div></div>`; }
        function section(title,content){
            if(!content.trim()) return '';
            return `<div class="vd-section" style="padding:12px 16px 0;"><div class="vd-section-title">${esc(title)}</div>${content}</div>`;
        }

        let html=`<div style="padding:10px 16px;border-bottom:1px solid var(--rule);display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <span style="font-family:var(--f-m);font-size:10px;color:var(--mod);font-weight:700;">${esc(d.visit_date||'')}</span>
            ${d.health_worker?`<span style="font-size:11px;color:var(--muted);">by ${esc(d.health_worker)}</span>`:''}
            <span style="margin-left:auto;font-size:10px;color:var(--faint);">Care Visit #${esc(String(d.id||''))}</span>
        </div>`;

        if(d.notes){
            html+=`<div style="padding:10px 16px;border-bottom:1px solid var(--rule);font-size:12.5px;color:var(--muted);font-style:italic;">${esc(d.notes)}</div>`;
        }

        if(CV_TYPE==='general'||CV_TYPE==='maternal'||CV_TYPE==='other'){
            if(!d.notes) html+=`<div style="padding:20px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">No additional details recorded.</div>`;
        } else if(CV_TYPE==='family_planning'){
            const inner=`<div class="vd-grid">
                ${rf('Method',d.method)}${rf('Previous Method',d.prev_method)}
                ${rb('New Acceptor',d.is_new_acceptor)}${rb('Method Switch',d.is_method_switch)}
                ${rf('Method Start Date',d.method_start_date)}${rf('Next Supply Date',d.next_supply_date)}
                ${rf('Next Checkup',d.next_checkup_date)}${rf('Pills Given',d.pills_given+' packs')}
                ${rf('Injectables',d.injectables_given)}${rf('Health Worker',d.health_worker)}
            </div>
            ${d.side_effects?`<div class="vf" style="padding-top:8px;">${rf('Side Effects',d.side_effects)}</div>`:''}
            ${d.counseling_notes?rf('Counseling Notes',d.counseling_notes):''}`;
            html+=section('Family Planning Details',inner);
        } else if(CV_TYPE==='prenatal'){
            const vitals=`<div class="vitals-row">
                ${d.aog_weeks?`<div class="vital"><div class="vital-lbl">AOG</div><div class="vital-val">${esc(d.aog_weeks)} wks</div></div>`:''}
                ${d.weight_kg?`<div class="vital"><div class="vital-lbl">Weight</div><div class="vital-val">${esc(d.weight_kg)} kg</div></div>`:''}
                ${(d.bp_systolic&&d.bp_diastolic)?`<div class="vital"><div class="vital-lbl">BP</div><div class="vital-val">${esc(d.bp_systolic)}/${esc(d.bp_diastolic)}</div></div>`:''}
                ${d.fetal_heart_rate?`<div class="vital"><div class="vital-lbl">FHR</div><div class="vital-val">${esc(d.fetal_heart_rate)} bpm</div></div>`:''}
                ${d.fundal_height_cm?`<div class="vital"><div class="vital-lbl">FH</div><div class="vital-val">${esc(d.fundal_height_cm)} cm</div></div>`:''}
            </div>`;
            const info=`<div class="vd-grid">
                ${rf('Visit No.',d.visit_number)}${rf('LMP Date',d.lmp_date)}
                ${rf('EDD',d.edd_date)}${rf('Presentation',d.fetal_presentation)}
                ${rf('Risk Level',d.risk_level)}${rf('TT Dose',d.tt_dose)}
                ${d.hgb_result?rf('HGB',d.hgb_result):''}
                ${rb('Folic Acid',d.folic_acid_given)}${rb('Iron',d.iron_given)}${rb('Calcium',d.calcium_given)}
                ${rf('Chief Complaint',d.chief_complaint,true)}
                ${rf('Assessment',d.assessment,true)}${rf('Plan',d.plan,true)}
            </div>`;
            html+=`<div style="padding:12px 16px 0;"><div class="vd-section-title">Vitals & Measurements</div>${vitals}</div>`;
            html+=section('Prenatal Details',info);
        } else if(CV_TYPE==='postnatal'){
            const info=`<div class="vd-grid">
                ${rf('Delivery Type',d.delivery_type)}${rf('Delivery Date',d.delivery_date)}
                ${rf('Facility',d.delivery_facility)}${rf('Birth Attendant',d.birth_attendant)}
                ${(d.bp_systolic&&d.bp_diastolic)?rf('BP',d.bp_systolic+'/'+d.bp_diastolic):''}
                ${rf('Lochia',d.lochia_type)}${rf('Fundal Involution',d.fundal_involution)}
                ${rf('Breastfeeding',d.breastfeeding_status)}
                ${d.ppd_score!==null&&d.ppd_score!==''?rf('PPD Score',d.ppd_score):''}
                ${d.newborn_weight_g?rf('Newborn Weight',Number(d.newborn_weight_g).toLocaleString()+'g'):''}
                ${d.apgar_1min!==null?rf('APGAR 1min / 5min',d.apgar_1min+' / '+d.apgar_5min):''}
                ${rb('BCG Given',d.bcg_given)}${rb('HB Vaccine',d.hb_vaccine_given)}
                ${rf('Assessment',d.assessment,true)}${rf('Plan',d.plan,true)}
            </div>`;
            html+=section('Postnatal Details',info);
        } else if(CV_TYPE==='child_nutrition'){
            const vitals=`<div class="vitals-row">
                ${d.age_months!==null?`<div class="vital"><div class="vital-lbl">Age</div><div class="vital-val">${esc(d.age_months)} mo</div></div>`:''}
                ${d.weight_kg?`<div class="vital"><div class="vital-lbl">Weight</div><div class="vital-val">${esc(d.weight_kg)} kg</div></div>`:''}
                ${d.height_cm?`<div class="vital"><div class="vital-lbl">Height</div><div class="vital-val">${esc(d.height_cm)} cm</div></div>`:''}
                ${d.muac_cm?`<div class="vital"><div class="vital-lbl">MUAC</div><div class="vital-val">${esc(d.muac_cm)} cm</div></div>`:''}
                ${d.waz?`<div class="vital"><div class="vital-lbl">WAZ</div><div class="vital-val">${esc(d.waz)}</div></div>`:''}
                ${d.haz?`<div class="vital"><div class="vital-lbl">HAZ</div><div class="vital-val">${esc(d.haz)}</div></div>`:''}
            </div>`;
            const status=`<div class="vd-grid">
                ${rf('Stunting',d.stunting_status)}${rf('Wasting',d.wasting_status)}
                ${rf('Underweight',d.underweight_status)}${rf('Breastfeeding',d.breastfeeding)}
                ${rb('Vit A',d.vita_supplemented)}${rb('Iron',d.iron_supplemented)}
                ${rb('Zinc',d.zinc_given)}${rb('Deworming',d.deworming_done)}
                ${rf('Counseling Notes',d.counseling_notes,true)}
                ${d.referred==1?rf('Referral Reason',d.referral_reason,true):''}
            </div>`;
            html+=`<div style="padding:12px 16px 0;"><div class="vd-section-title">Growth Measurements</div>${vitals}</div>`;
            html+=section('Nutrition Status',status);
        } else if(CV_TYPE==='immunization'){
            const info=`<div class="vd-grid">
                ${rf('Vaccine',d.vaccine_name)}${rf('Dose',d.dose)}
                ${rf('Date Given',d.date_given)}${rf('Route',d.route)}
                ${rf('Site',d.site_given)}${rf('Batch No.',d.batch_number)}
                ${rf('Expiry Date',d.expiry_date)}${rf('Next Schedule',d.next_schedule)}
                ${rf('Administered By',d.administered_by)}
                ${d.adverse_reaction?rf('Adverse Reaction',d.adverse_reaction,true):''}
                ${rb('Is Defaulter',d.is_defaulter)}${rb('Catch-up',d.catch_up)}
            </div>`;
            html+=section('Immunization Record',info);
        }

        return html;
    }

    /* ════════════════════════════
       EDIT MODAL
    ════════════════════════════ */
    $('#editModal').dialog({autoOpen:false,modal:true,width:680,resizable:false});

    $('#btnEditVisit').on('click',function(){
        const {id, src}=$(this).data();
        if(src==='consult'){
            window.location.href='../consultation/?edit='+id;
            return;
        }
        openEditVisit(id);
    });

    function openEditVisit(id){
        $.getJSON(CV_API,{action:'get',type:CV_TYPE,id},function(res){
            if(res.status!=='ok'){ alert('Could not load record.'); return; }
            const d=res.data;
            $('#ef_vid').val(d.id);
            $('#ef_type').val(CV_TYPE);
            $('#ef_date').val(d.visit_date||'');
            $('#ef_worker').val(d.health_worker||d.administered_by||'');
            $('#ef_notes').val(d.notes||'');
            $('#ef_fields').html(buildEditFields(d));
            $('#editModal').dialog('option','title','Edit Visit — '+d.visit_date).dialog('open');
        });
    }

    function buildEditFields(d){
        const inp=(n,lbl,v,type='text',extra='')=>`
            <div class="fg">
                <label class="fg-lbl">${esc(lbl)}</label>
                <input type="${type}" name="${n}" class="fg-in" value="${esc(v||'')}" ${extra}>
            </div>`;
        const sel=(n,lbl,v,opts)=>`
            <div class="fg">
                <label class="fg-lbl">${esc(lbl)}</label>
                <select name="${n}" class="fg-sel">${opts.map(o=>`<option value="${esc(o)}" ${o==v?'selected':''}>${esc(o)}</option>`).join('')}</select>
            </div>`;
        const ta=(n,lbl,v)=>`
            <div class="fg">
                <label class="fg-lbl">${esc(lbl)}</label>
                <textarea name="${n}" class="fg-ta">${esc(v||'')}</textarea>
            </div>`;
        const chk=(n,lbl,v)=>`
            <label class="chk" style="margin-bottom:8px;"><input type="checkbox" name="${n}" value="1" ${v==1||v==='1'?'checked':''}> ${esc(lbl)}</label>`;

        if(CV_TYPE==='general'||CV_TYPE==='maternal'||CV_TYPE==='other') return '';

        if(CV_TYPE==='family_planning'){
            return `<div class="form-section-title">Family Planning Details</div>
            <div class="g2">
                ${sel('method','Method',d.method,['Pills','Condom','IUD','Injectable','Implant','LAM','NFP','Permanent','Other'])}
                ${inp('method_other','Method (if Other)',d.method_other)}
            </div>
            <div class="g2">
                ${inp('method_start_date','Method Start Date',d.method_start_date,'date')}
                ${inp('next_supply_date','Next Supply Date',d.next_supply_date,'date')}
            </div>
            <div class="g2">
                ${inp('next_checkup_date','Next Checkup Date',d.next_checkup_date,'date')}
                ${inp('pills_given','Pills Given (packs)',d.pills_given,'number','min="0"')}
            </div>
            ${ta('side_effects','Side Effects',d.side_effects)}
            ${ta('counseling_notes','Counseling Notes',d.counseling_notes)}
            <div class="check-row">
                ${chk('is_new_acceptor','New Acceptor',d.is_new_acceptor)}
                ${chk('is_method_switch','Method Switch',d.is_method_switch)}
            </div>`;
        }

        if(CV_TYPE==='prenatal'){
            return `<div class="form-section-title">Prenatal Details</div>
            <div class="g3">
                ${inp('visit_number','Visit No.',d.visit_number,'number','min="1"')}
                ${inp('aog_weeks','AOG (weeks)',d.aog_weeks,'number')}
                ${inp('lmp_date','LMP Date',d.lmp_date,'date')}
            </div>
            <div class="g3">
                ${inp('weight_kg','Weight (kg)',d.weight_kg,'number','step="0.1"')}
                ${inp('bp_systolic','BP Systolic',d.bp_systolic,'number')}
                ${inp('bp_diastolic','BP Diastolic',d.bp_diastolic,'number')}
            </div>
            <div class="g3">
                ${inp('fetal_heart_rate','FHR (bpm)',d.fetal_heart_rate,'number')}
                ${inp('fundal_height_cm','Fundal Height (cm)',d.fundal_height_cm,'number','step="0.5"')}
                ${sel('risk_level','Risk Level',d.risk_level,['Low','Moderate','High'])}
            </div>
            ${sel('fetal_presentation','Presentation',d.fetal_presentation,['Cephalic','Breech','Transverse','Unknown'])}
            ${sel('tt_dose','TT Dose',d.tt_dose,['None','TT1','TT2','TT3','TT4','TT5'])}
            ${ta('chief_complaint','Chief Complaint',d.chief_complaint)}
            ${ta('assessment','Assessment',d.assessment)}
            ${ta('plan','Plan',d.plan)}
            <div class="check-row">
                ${chk('folic_acid_given','Folic Acid',d.folic_acid_given)}
                ${chk('iron_given','Iron',d.iron_given)}
                ${chk('calcium_given','Calcium',d.calcium_given)}
                ${chk('iodine_given','Iodine',d.iodine_given)}
            </div>`;
        }

        if(CV_TYPE==='postnatal'){
            return `<div class="form-section-title">Postnatal Details</div>
            <div class="g2">
                ${inp('visit_number','Visit No.',d.visit_number,'number','min="1"')}
                ${inp('delivery_date','Delivery Date',d.delivery_date,'date')}
            </div>
            <div class="g2">
                ${sel('delivery_type','Delivery Type',d.delivery_type,['Normal','Cesarean','Assisted','Unknown'])}
                ${inp('delivery_facility','Facility',d.delivery_facility)}
            </div>
            <div class="g2">
                ${inp('bp_systolic','BP Systolic',d.bp_systolic,'number')}
                ${inp('bp_diastolic','BP Diastolic',d.bp_diastolic,'number')}
            </div>
            ${sel('lochia_type','Lochia Type',d.lochia_type,['Rubra','Serosa','Alba','Not checked'])}
            ${sel('breastfeeding_status','Breastfeeding Status',d.breastfeeding_status,['Exclusive','Partial','None','NA'])}
            <div class="g2">
                ${inp('newborn_weight_g','Newborn Weight (g)',d.newborn_weight_g,'number')}
                ${inp('ppd_score','PPD Score',d.ppd_score,'number','min="0" max="30"')}
            </div>
            ${ta('assessment','Assessment',d.assessment)}
            ${ta('plan','Plan',d.plan)}
            <div class="check-row">
                ${chk('bcg_given','BCG Given',d.bcg_given)}
                ${chk('hb_vaccine_given','Hep B Given',d.hb_vaccine_given)}
                ${chk('fp_counseled','FP Counseled',d.fp_counseled)}
            </div>`;
        }

        if(CV_TYPE==='child_nutrition'){
            return `<div class="form-section-title">Nutrition Details</div>
            <div class="g3">
                ${inp('age_months','Age (months)',d.age_months,'number','min="0"')}
                ${inp('weight_kg','Weight (kg)',d.weight_kg,'number','step="0.01"')}
                ${inp('height_cm','Height (cm)',d.height_cm,'number','step="0.1"')}
            </div>
            <div class="g3">
                ${inp('muac_cm','MUAC (cm)',d.muac_cm,'number','step="0.1"')}
                ${inp('waz','WAZ',d.waz,'number','step="0.01"')}
                ${inp('haz','HAZ',d.haz,'number','step="0.01"')}
            </div>
            <div class="g3">
                ${sel('stunting_status','Stunting',d.stunting_status,['Not assessed','Normal','Mild','Moderate','Severe'])}
                ${sel('wasting_status','Wasting',d.wasting_status,['Not assessed','Normal','Mild','Moderate','Severe'])}
                ${sel('underweight_status','Underweight',d.underweight_status,['Not assessed','Normal','Mild','Moderate','Severe'])}
            </div>
            ${ta('counseling_notes','Counseling Notes',d.counseling_notes)}
            <div class="check-row">
                ${chk('vita_supplemented','Vit A',d.vita_supplemented)}
                ${chk('iron_supplemented','Iron',d.iron_supplemented)}
                ${chk('zinc_given','Zinc',d.zinc_given)}
                ${chk('deworming_done','Deworming',d.deworming_done)}
            </div>`;
        }

        if(CV_TYPE==='immunization'){
            return `<div class="form-section-title">Immunization Details</div>
            <div class="g2">
                ${inp('vaccine_name','Vaccine Name',d.vaccine_name)}
                ${inp('dose','Dose',d.dose)}
            </div>
            <div class="g2">
                ${inp('date_given','Date Given',d.date_given,'date')}
                ${inp('next_schedule','Next Schedule',d.next_schedule,'date')}
            </div>
            <div class="g2">
                ${sel('route','Route',d.route,['IM','SC','ID','Oral'])}
                ${inp('site_given','Site Given',d.site_given)}
            </div>
            <div class="g2">
                ${inp('batch_number','Batch No.',d.batch_number)}
                ${inp('expiry_date','Expiry Date',d.expiry_date,'date')}
            </div>
            ${inp('administered_by','Administered By',d.administered_by)}
            ${ta('adverse_reaction','Adverse Reaction',d.adverse_reaction)}`;
        }

        return '';
    }

    $('#editForm').on('submit',function(e){
        e.preventDefault();
        const $btn=$('#efSubmit');
        $btn.prop('disabled',true).text('Saving…');
        $.ajax({
            url:CV_API+'?action=update',
            type:'POST',
            data:$(this).serialize(),
            dataType:'json',
            success(res){
                if(res.status!=='ok'){ alert(res.message||'Save failed.'); return; }
                $('#editModal').dialog('close');
                const vid=$('#ef_vid').val();
                loadVisits(selId);
                showCareDetail(parseInt(vid));
            },
            error(xhr){ alert('Server error ('+xhr.status+').'); },
            complete(){ $btn.prop('disabled',false).text('Save Changes'); }
        });
    });

    /* ════════════════════════════
       NIP CARD
    ════════════════════════════ */
    function loadNIP(rid){
        $('#nipGrid').html('<div style="grid-column:1/-1;padding:12px;text-align:center;color:var(--faint);font-size:12px;">Loading…</div>');
        $.getJSON(CV_API,{action:'immunization_card',resident_id:rid},function(res){
            if(res.status!=='ok') return;
            const d=res.data;
            $('#immStats').text(d.given_count+'/'+d.total+' given · '+d.overdue+' overdue');
            let html='';
            (d.schedule||[]).forEach(s=>{
                const cls=s.given?'given':s.is_overdue?'overdue':'';
                const status=s.given?'✓ Given':s.is_overdue?'Overdue':'Pending';
                const dt=s.given?s.given.date_given:s.due_date||'';
                html+=`<div class="nip-slot ${cls}">
                    <div class="ns-vaccine">${esc(s.vaccine_name)}</div>
                    <div class="ns-dose">${esc(s.dose_label)}</div>
                    <div class="ns-status">${status}</div>
                    ${dt?`<div class="ns-date">${esc(dt)}</div>`:''}
                </div>`;
            });
            $('#nipGrid').html(html||'<div style="grid-column:1/-1;padding:12px;text-align:center;color:var(--faint);">No NIP schedule.</div>');
        });
    }

    /* ════════════════════════════
       MATERNAL PROFILE
    ════════════════════════════ */
    function loadMP(rid){
        $.getJSON(CV_API,{action:'maternal_profile',resident_id:rid},function(res){
            const p=res.data?.profile;
            $('#mp_rid').val(rid);
            if(!p){ $('#mpDisplay').html('<span style="color:var(--faint);font-size:12px;font-style:italic;">No profile recorded yet.</span>'); return; }
            const keys=['gravida','term','preterm','abortions','living_children'];
            const prefixes=['G','T','P','A','L'];
            const gtpal=keys.map((k,i)=>p[k]?prefixes[i]+p[k]:null).filter(Boolean).join('  ');
            const flagKeys=['hx_pre_eclampsia','hx_pph','hx_cesarean','hx_ectopic','has_diabetes','has_hypertension','has_hiv','has_anemia'];
            const flagLabels=['Pre-eclampsia','PPH','C-Section','Ectopic','Diabetes','Hypertension','HIV','Anemia'];
            const flags=flagKeys.map((k,i)=>p[k]==1||p[k]==='1'?flagLabels[i]:null).filter(Boolean);
            let html='';
            if(gtpal) html+=`<div class="mp-gtpal">${esc(gtpal)}</div>`;
            if(p.blood_type&&p.blood_type!=='Unknown') html+=`<div style="font-size:12px;color:var(--muted);margin-bottom:5px;">Blood type: <strong>${esc(p.blood_type)}</strong></div>`;
            if(flags.length) html+=`<div class="flag-row">${flags.map(f=>`<span class="flag">${esc(f)}</span>`).join('')}</div>`;
            if(!html) html='<span style="color:var(--faint);font-size:12px;font-style:italic;">Profile incomplete.</span>';
            $('#mpDisplay').html(html);
            Object.entries(p).forEach(([k,v])=>{
                const el=$('#mpForm [name="'+k+'"]');
                if(!el.length) return;
                if(el.attr('type')==='checkbox') el.prop('checked',v==1||v==='1');
                else el.val(v||'');
            });
        });
    }

    <?php if ($type==='maternal'): ?>
    $('#mpModal').dialog({autoOpen:false,modal:true,width:640,resizable:false});
    $('#btnEditProfile').on('click',()=>$('#mpModal').dialog('open'));
    $('#mpForm').on('submit',function(e){
        e.preventDefault();
        $.post(CV_API+'?action=save_maternal_profile',$(this).serialize(),function(res){
            const r=typeof res==='string'?JSON.parse(res):res;
            if(r.status!=='ok'){alert(r.message||'Save failed.');return;}
            $('#mpModal').dialog('close');
            loadMP(selId);
        });
    });
    <?php endif; ?>

    /* ── New Visit ── */
    $('#btnNewVisit').on('click',function(){
        if(!selId) return;
        window.location.href='../consultation/?new=1&type='+CV_TYPE+'&resident_id='+selId;
    });
});
</script>
</body>
</html>
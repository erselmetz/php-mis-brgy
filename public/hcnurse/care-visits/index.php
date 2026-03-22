<?php
/**
 * Care Visits — v3
 * Changes:
 * - Professional patient list (no DataTable chrome, just clean rows)
 * - Visit history shows status badge (Ongoing / Completed / Dismissed / Follow-up)
 * - Informative record detail panel with all fields
 * - Single "+ New Visit" button (was duplicated)
 * - Immunization: one NIP card per patient (no duplicates)
 * - Print shows status + all clinical fields
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type    = $_GET['type'] ?? 'maternal';
$allowed = ['maternal','family_planning','prenatal','postnatal','child_nutrition','immunization'];
if (!in_array($type, $allowed, true)) $type = 'maternal';

$mod = [
    'maternal'        => ['icon'=>'🤱','label'=>'Maternal Health',  'color'=>'#9f1239','bg'=>'#fff1f2','light'=>'#fdf0f2'],
    'family_planning' => ['icon'=>'💊','label'=>'Family Planning',  'color'=>'#1e40af','bg'=>'#eff6ff','light'=>'#eaf3ff'],
    'prenatal'        => ['icon'=>'👶','label'=>'Prenatal / ANC',   'color'=>'#92400e','bg'=>'#fffbeb','light'=>'#fef8e0'],
    'postnatal'       => ['icon'=>'🍼','label'=>'Postnatal / PNC',  'color'=>'#134e4a','bg'=>'#f0fdfa','light'=>'#e8fbf5'],
    'child_nutrition' => ['icon'=>'🥗','label'=>'Child Nutrition',  'color'=>'#14532d','bg'=>'#f0fdf4','light'=>'#e8faea'],
    'immunization'    => ['icon'=>'💉','label'=>'Immunization',     'color'=>'#4c1d95','bg'=>'#f5f3ff','light'=>'#eeeaff'],
];
$mc = $mod[$type];

/* ── Patients with records (consultations table) ── */
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

/* Also from care_visits */
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

/* module tabs */
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
.pt-row.on .pt-name-txt{padding-left:0;}
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
.fg-in,.fg-sel,.fg-ta{width:100%;padding:9px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-family:var(--f-n);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .14s,box-shadow .14s;}
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

/* btn */
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:2px;font-family:var(--f-n);font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .13s;white-space:nowrap;}
.btn-mod{background:var(--mod);border-color:var(--mod);color:#fff;}
.btn-mod:hover{filter:brightness(1.1);}
.btn-ghost{background:#fff;border-color:var(--rule-dk);color:var(--muted);}
.btn-ghost:hover{border-color:var(--mod);color:var(--mod);}
.btn:disabled{opacity:.4;cursor:not-allowed;filter:none!important;}
.submit-bar{padding:11px 16px;border-top:1px solid var(--rule);background:var(--plt);display:flex;justify-content:flex-end;gap:8px;}

/* modal */
.ui-dialog{border:1px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 8px 48px rgba(0,0,0,.18)!important;padding:0!important;font-family:var(--f-n)!important;}
.ui-dialog-titlebar{background:var(--mod)!important;border:none!important;padding:11px 15px!important;}
.ui-dialog-title{font-family:var(--f-n)!important;font-size:11px!important;font-weight:700!important;letter-spacing:1px!important;text-transform:uppercase!important;color:#fff!important;}
.ui-dialog-titlebar-close{background:rgba(255,255,255,.15)!important;border:1px solid rgba(255,255,255,.25)!important;border-radius:2px!important;color:#fff!important;width:24px!important;height:24px!important;top:50%!important;transform:translateY(-50%)!important;}
.ui-dialog-content{padding:0!important;}
.ui-dialog-buttonpane{background:var(--plt)!important;border-top:1px solid var(--rule)!important;padding:11px 15px!important;margin:0!important;}
.ui-dialog-buttonpane .ui-button{font-family:var(--f-n)!important;font-size:11px!important;font-weight:700!important;letter-spacing:.5px!important;text-transform:uppercase!important;padding:7px 16px!important;border-radius:2px!important;cursor:pointer!important;}
.ui-dialog-buttonpane .ui-button:first-child{background:var(--mod)!important;border:1.5px solid var(--mod)!important;color:#fff!important;}
.ui-dialog-buttonpane .ui-button:not(:first-child){background:#fff!important;border:1.5px solid var(--rule-dk)!important;color:var(--muted)!important;}
.ui-autocomplete{z-index:9999!important;max-height:200px;overflow-y:auto;overflow-x:hidden;}
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
                    <button class="btn btn-ghost" id="btnPrint">↗ Print</button>
                    <button class="btn btn-mod"   id="btnNewVisit" disabled>+ New Visit</button>
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
                               style="width:150px;padding:4px 9px;font-size:12px;"
                               placeholder="Filter…">
                    </div>
                    <div class="pt-list" id="ptList">
                        <?php if (empty($ptMap)): ?>
                        <div class="pt-empty">
                            No <?= htmlspecialchars(strtolower($mc['label'])) ?> records yet.<br>
                            <small>Add a consultation with type<br>"<?= htmlspecialchars(str_replace('_',' ',$type)) ?>".</small>
                        </div>
                        <?php else: foreach ($ptMap as $p):
                            $a  = age($p['birthdate']??'');
                            $g  = $p['gender']??'';
                            $lv = $p['last_visit']??'';
                            // initials
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

                <!-- selected patient card (hidden until selection) -->
                <div class="card" id="detailCard" style="display:none;">

                    <!-- patient bar -->
                    <div class="sel-hdr">
                        <div>
                            <div class="sel-name" id="selName">—</div>
                            <div class="sel-meta" id="selMeta">—</div>
                        </div>
                    </div>

                    <!-- immunization NIP card (only once, only for immunization type) -->
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

                    <!-- maternal profile (only for maternal type) -->
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

                <!-- visit detail (shown when a row clicked) -->
                <div class="card" id="vDetailCard" style="display:none;">
                    <div class="card-head">
                        <div class="card-title" id="vdTitle">Visit Detail</div>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-ghost" style="padding:4px 10px;font-size:10px;" id="btnEditVisit">Edit</button>
                            <button class="btn btn-ghost" style="padding:4px 10px;font-size:10px;" onclick="$('#vDetailCard').slideUp(120)">✕</button>
                        </div>
                    </div>
                    <div class="vd-body" id="vdBody"></div>
                </div>

            </div>
        </div>

    </main>
</div>

<!-- maternal profile modal -->
<?php if ($type==='maternal'): ?>
<div id="mpModal" title="Edit Obstetric Profile" class="hidden">
<form id="mpForm" style="max-height:68vh;overflow-y:auto;">
    <input type="hidden" name="resident_id" id="mp_rid">
    <div style="padding:16px 18px 0;">
        <div style="font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:12px;">GTPAL<div style="flex:1;height:1px;background:var(--rule);"></div></div>
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;padding-bottom:14px;">
            <?php foreach(['gravida'=>'G','term'=>'T','preterm'=>'P','abortions'=>'A','living_children'=>'L'] as $n=>$l): ?>
            <div class="fg">
                <label class="fg-lbl"><?= $l ?></label>
                <input type="number" name="<?= $n ?>" min="0" class="fg-in" placeholder="0" style="text-align:center;">
            </div>
            <?php endforeach; ?>
        </div>
        <div style="font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:12px;">Complications<div style="flex:1;height:1px;background:var(--rule);"></div></div>
        <div class="check-row" style="padding-bottom:14px;">
            <?php foreach(['hx_pre_eclampsia'=>'Pre-eclampsia','hx_pph'=>'PPH','hx_cesarean'=>'C-Section','hx_ectopic'=>'Ectopic','hx_stillbirth'=>'Stillbirth'] as $n=>$l): ?>
            <label class="chk"><input type="checkbox" name="<?= $n ?>" value="1"> <?= $l ?></label>
            <?php endforeach; ?>
        </div>
        <div style="font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:12px;">Chronic Conditions<div style="flex:1;height:1px;background:var(--rule);"></div></div>
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

<script>
const CV_TYPE = <?= json_encode($type) ?>;
const CV_API  = 'api/care_visits_api.php';
let selId = null, selName = '';

$(function(){
    $('body').show();

    function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
    function or(v,fb=''){ return (v===null||v===undefined||v==='')?fb:v; }

    function statusBadge(s){
        if(!s) return '';
        const map={Completed:'b-completed',Ongoing:'b-ongoing','Follow-up':'b-followup',Dismissed:'b-dismissed'};
        return `<span class="badge ${map[s]||'b-dismissed'}">${esc(s)}</span>`;
    }

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
        selectPatient($(this).data('id'), $(this).data('name'), $(this).data('age'), $(this).data('gender'));
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

    /* ── visit history (consultations + care_visits) ── */
    function loadVisits(rid){
        $('#vList').html('<div style="padding:14px;text-align:center;color:var(--faint);font-size:12px;">Loading…</div>');
        $('#vCount').text('…');

        const today=new Date().toISOString().slice(0,10);
        const p1=$.getJSON('../consultation/api/list_by_type.php',{type:CV_TYPE,resident_id:rid});
        const p2=$.getJSON(CV_API,{action:'list',type:CV_TYPE,resident_id:rid,from:'2000-01-01',to:today});

        $.when(p1.then(r=>r,()=>({status:'err'})), p2.then(r=>r,()=>({status:'err'}))).done(function(r1,r2){
            const c=(r1.status==='ok'?r1.data:[]).map(r=>({...r,_src:'consult'}));
            const v=(r2.status==='ok'?(r2.data?.data||[]):[]).map(r=>({...r,_src:'care'}));
            const all=[...c,...v].sort((a,b)=>{
                const da=a.consultation_date||a.visit_date||'';
                const db=b.consultation_date||b.visit_date||'';
                return db.localeCompare(da);
            });
            $('#vCount').text(all.length+(all.length===1?' RECORD':' RECORDS'));
            if(!all.length){
                $('#vList').html('<div style="padding:20px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">No visits recorded yet.<br><small>Add a consultation of type "'+CV_TYPE.replace(/_/g,' ')+'".</small></div>');
                return;
            }
            let html='';
            all.forEach(r=>{
                const date=r.consultation_date||r.visit_date||'—';
                const status=r.consult_status||r._status||'';
                const srcBadge=r._src==='consult'?'<span class="badge b-consult">Consult</span>':'<span class="badge b-care">Care</span>';
                let summary='';
                if(r._src==='consult'){
                    summary=[r.complaint,r.diagnosis].filter(Boolean).join(' · ').substring(0,65);
                } else {
                    const m=r.module||{};
                    summary=Object.entries(m).filter(([k,v])=>v&&!['id','resident_id','care_visit_id','created_at','updated_at'].includes(k)).map(([,v])=>String(v)).slice(0,3).join(' · ').substring(0,65);
                    if(!summary) summary=r.notes||'';
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

    function showConsultDetail(id){
        $.getJSON('../consultation/api/view.php',{id},function(res){
            if(!res.success) return;
            const d=res.data;
            $('#vdTitle').text('Consult — '+d.consultation_date);
            $('#btnEditVisit').data({id,src:'consult'});

            let html='';

            /* status strip */
            const statusCls={Completed:'b-completed',Ongoing:'b-ongoing','Follow-up':'b-followup',Dismissed:'b-dismissed'};
            const sc=statusCls[d.consult_status]||'b-dismissed';
            const riskClr={Low:'ok-fg',Moderate:'warn-fg',High:'danger-fg'}[d.risk_level||'Low'];
            html+=`<div style="padding:10px 16px;border-bottom:1px solid var(--rule);display:flex;flex-wrap:wrap;gap:6px;align-items:center;">`;
            if(d.consult_status) html+=`<span class="badge ${sc}">${esc(d.consult_status)}</span>`;
            html+=`<span class="badge" style="background:var(--${riskClr==='ok-fg'?'ok-bg':riskClr==='warn-fg'?'warn-bg':'danger-bg'});color:var(--${riskClr});border-color:color-mix(in srgb,var(--${riskClr}) 25%,transparent);">Risk: ${esc(d.risk_level||'Low')}</span>`;
            if(d.follow_up_date) html+=`<span style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);">Follow-up: ${esc(d.follow_up_date)}</span>`;
            if(d.is_referred&&d.referred_to) html+=`<span class="badge b-followup">Referred → ${esc(d.referred_to)}</span>`;
            html+=`<span style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);margin-left:auto;">${esc(d.health_worker||'')}</span>`;
            html+='</div>';

            /* vitals */
            const vitals=[];
            if(d.temp_celsius)     vitals.push({l:'Temp',v:d.temp_celsius+'°C'});
            if(d.bp_systolic)      vitals.push({l:'BP',v:d.bp_systolic+'/'+d.bp_diastolic+' mmHg'});
            if(d.pulse_rate)       vitals.push({l:'Pulse',v:d.pulse_rate+' bpm'});
            if(d.respiratory_rate) vitals.push({l:'RR',v:d.respiratory_rate+'/min'});
            if(d.o2_saturation)    vitals.push({l:'SpO2',v:d.o2_saturation+'%'});
            if(d.weight_kg)        vitals.push({l:'Weight',v:d.weight_kg+' kg'});
            if(d.height_cm)        vitals.push({l:'Height',v:d.height_cm+' cm'});
            if(d.bmi)              vitals.push({l:'BMI',v:d.bmi+(d.bmi_class?' — '+d.bmi_class:'')});
            if(d.waist_cm)         vitals.push({l:'Waist',v:d.waist_cm+' cm'});
            if(vitals.length){
                html+='<div class="vd-section" style="padding:12px 16px 0;"><div class="vd-section-title">Vital signs &amp; measurements</div><div class="vitals-row">';
                vitals.forEach(v=>{ html+=`<div class="vital"><div class="vital-lbl">${esc(v.l)}</div><div class="vital-val">${esc(v.v)}</div></div>`; });
                html+='</div></div>';
            }

            /* clinical fields */
            const left=[
                ['Chief complaint', d.chief_complaint||d.complaint, true],
                ['Duration / onset', [d.complaint_duration,d.complaint_onset].filter(Boolean).join(' · '), false],
                ['Primary diagnosis', d.primary_diagnosis||d.diagnosis, false],
                ['Secondary diagnosis', d.secondary_diagnosis, false],
                ['ICD-10 code', d.icd_code, false],
                ['Treatment', d.treatment, false],
                ['Medicines prescribed', d.medicines_prescribed, false],
                ['Procedures done', d.procedures_done, false],
            ];
            const right=[
                ['Health advice', d.health_advice, false],
                ['Lifestyle advice', d.lifestyle_advice, false],
                ['Patient education', d.patient_education, false],
                ['Assessment', d.assessment, false],
                ['Plan', d.plan, false],
                ['Prognosis', d.prognosis==='NA'?'':d.prognosis, false],
            ];
            const history=[
                ['Past medical history', d.past_medical_history],
                ['Family history', d.family_history],
                ['Current medications', d.current_medications],
                ['Known allergies', d.known_allergies],
            ];
            const social=[
                ['Smoking', d.smoking_status],['Alcohol', d.alcohol_use],
                ['Physical activity', d.physical_activity],['Nutrition', d.nutritional_status],
                ['Mental health screen', d.mental_health_screen],
            ];

            function renderFields(arr){
                let h='';
                arr.forEach(([l,v,req])=>{
                    if(!v&&!req) return;
                    h+=`<div class="vf"><div class="vf-lbl">${esc(l)}</div><div class="${v?'vf-val':'vf-empty'}">${esc(v||'—')}</div></div>`;
                });
                return h;
            }

            const lh=renderFields(left), rh=renderFields(right);
            if(lh||rh){
                html+='<div class="vd-section" style="padding:12px 16px 0;"><div class="vd-section-title">Clinical notes</div><div class="vd-grid" style="padding-bottom:12px;">';
                html+=`<div>${lh}</div><div>${rh}</div>`;
                html+='</div></div>';
            }

            const hh=renderFields(history);
            if(hh){
                html+='<div class="vd-section" style="padding:12px 16px 0;"><div class="vd-section-title">Medical history</div>';
                html+=`<div style="padding-bottom:12px;">${hh}</div></div>`;
            }

            const sh=renderFields(social.filter(([,v])=>v&&v!=='NA'&&v!=='Not screened'));
            if(sh){
                html+='<div class="vd-section" style="padding:12px 16px 0;"><div class="vd-section-title">Health profile</div>';
                html+=`<div class="vd-grid" style="padding-bottom:12px;">${sh}</div></div>`;
            }

            $('#vdBody').html(html);
            $('#vDetailCard').slideDown(120);
        });
    }

    function showCareDetail(id){
        $.getJSON(CV_API,{action:'get',type:CV_TYPE,id},function(res){
            if(res.status!=='ok') return;
            const raw=res.data?.data||res.data||{};
            $('#vdTitle').text('Care Visit — '+(raw.visit_date||''));
            $('#btnEditVisit').data({id,src:'care'});
            let html='<div class="vd-section" style="padding:14px 16px;">';
            let any=false;
            Object.entries(raw).forEach(([k,v])=>{
                if(!v||['id','resident_id','care_visit_id','resident_name','birthdate','created_at','updated_at'].includes(k)) return;
                any=true;
                html+=`<div class="vf"><div class="vf-lbl">${esc(k.replace(/_/g,' '))}</div><div class="vf-val">${esc(String(v))}</div></div>`;
            });
            if(!any) html+='<div class="vf-empty">No detail available.</div>';
            html+='</div>';
            $('#vdBody').html(html);
            $('#vDetailCard').slideDown(120);
        });
    }

    /* ── NIP card (single instance) ── */
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
                html+=`<div class="nip-slot ${cls}"><div class="ns-vaccine">${esc(s.vaccine_name)}</div><div class="ns-dose">${esc(s.dose_label)}</div><div class="ns-status">${status}</div>${dt?`<div class="ns-date">${esc(dt)}</div>`:''}</div>`;
            });
            $('#nipGrid').html(html||'<div style="grid-column:1/-1;padding:12px;text-align:center;color:var(--faint);">No NIP schedule.</div>');
        });
    }

    /* ── Maternal profile ── */
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
            /* Pre-fill modal */
            Object.entries(p).forEach(([k,v])=>{
                const el=$('#mpForm [name="'+k+'"]');
                if(!el.length) return;
                if(el.attr('type')==='checkbox') el.prop('checked',v==1||v==='1');
                else el.val(v||'');
            });
        });
    }

    <?php if ($type==='maternal'): ?>
    $('#mpModal').dialog({autoOpen:false,modal:true,width:640,resizable:false,
        buttons:{'Save':function(){$('#mpForm').trigger('submit');},'Cancel':function(){$(this).dialog('close');}}});
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

    /* ── Print ── */
    $('#btnPrint').on('click',()=>{
        window.open('print.php?type='+CV_TYPE+'&resident_id='+(selId||''),'_blank');
    });
});
</script>
</body>
</html>
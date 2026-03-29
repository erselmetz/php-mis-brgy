<?php
/**
 * Care Visits — v5 (Immunization NIP Card enhanced)
 * - NIP slots: show Given/Pending/Overdue, Edit button on given slots, Add button on pending
 * - Custom immunizations: separate section for non-NIP records with Add/Edit/Delete
 * - Full add/edit dialog for every immunization record
 */
require_once __DIR__ . '/../../includes/app.php';
requireHCNurse();

$type    = $_GET['type'] ?? 'general';
$allowed = ['general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','other'];
if (!in_array($type, $allowed, true)) $type = 'general';

$mod = [
    'general'         => ['icon'=>'❤️‍🩹','label'=>'General',         'color'=>'#2d5a27','bg'=>'#f0fdf4','light'=>'#e8faea'],
    'immunization'    => ['icon'=>'💉','label'=>'Immunization',     'color'=>'#4c1d95','bg'=>'#f5f3ff','light'=>'#eeeaff'],
];
$mc = $mod[$type] ?? $mod['general'];

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

/* ── Patients from care_visits ── */
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

/* ── Merge patient lists ── */
if ($type === 'immunization') {
    /* Also include residents with direct immunization records */
    $immPt = $conn->query("
        SELECT r.id, CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) full_name,
               r.birthdate, r.gender,
               MAX(i.date_given) last_visit, COUNT(i.id) cnt, NULL last_status
        FROM immunizations i
        INNER JOIN residents r ON r.id=i.resident_id AND r.deleted_at IS NULL
        GROUP BY r.id,r.first_name,r.middle_name,r.last_name,r.birthdate,r.gender
        ORDER BY last_visit DESC LIMIT 300
    ");
    if ($immPt) while ($p = $immPt->fetch_assoc()) $cvPts[] = $p;
}

$ptMap = [];
foreach ($patients as $p) $ptMap[$p['id']] = $p;
foreach ($cvPts    as $p) {
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
    --acc-lt:color-mix(in srgb,var(--acc) 8%,white);
    --ok-bg:#edfaf3;--ok-fg:#1a5c35;
    --warn-bg:#fef9ec;--warn-fg:#7a5700;
    --danger-bg:#fdeeed;--danger-fg:#7a1f1a;
    --info-bg:#edf3fa;--info-fg:#1a3a5c;
    --mod:<?= $mc['color'] ?>;--mod-bg:<?= $mc['bg'] ?>;--mod-lt:<?= $mc['light'] ?>;
    --f-s:'Source Serif 4',Georgia,serif;
    --f-n:'Source Sans 3','Segoe UI',sans-serif;
    --f-m:'Source Code Pro','Courier New',monospace;
    --sh:0 1px 2px rgba(0,0,0,.07),0 3px 14px rgba(0,0,0,.05);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:var(--f-n);}
.page{background:var(--bg);min-height:100%;padding-bottom:56px;}
.hdr{background:var(--p);border-bottom:1px solid var(--rule);}
.hdr-inner{padding:18px 28px 0;display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.eyebrow{font-size:8.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:5px;}
.eyebrow::before{content:'';width:18px;height:2px;background:var(--mod);display:inline-block;}
.page-title{font-family:var(--f-s);font-size:21px;font-weight:700;color:var(--ink);margin-bottom:3px;display:flex;align-items:center;gap:9px;}
.page-sub{font-size:12px;color:var(--faint);font-style:italic;}
.accent-bar{height:3px;margin-top:12px;background:linear-gradient(to right,var(--mod),transparent);}
.tabs{display:flex;gap:0;padding:0 28px;overflow-x:auto;scrollbar-width:none;}
.tabs::-webkit-scrollbar{display:none;}
.tab{display:flex;align-items:center;gap:6px;padding:9px 13px;font-size:12px;font-weight:600;color:var(--muted);text-decoration:none;border-bottom:2.5px solid transparent;white-space:nowrap;transition:all .12s;}
.tab:hover{color:var(--ink);}
.tab.on{color:var(--mod);border-bottom-color:var(--mod);}

/* layout */
.body{display:grid;grid-template-columns:300px 1fr;gap:16px;margin:16px 28px 0;}
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
.pt-list{max-height:calc(100vh - 380px);overflow-y:auto;}
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

/* patient header */
.sel-hdr{padding:14px 18px;background:var(--mod-lt);border-bottom:1px solid color-mix(in srgb,var(--mod) 12%,transparent);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
.sel-name{font-family:var(--f-s);font-size:16px;font-weight:600;color:var(--ink);}
.sel-meta{font-size:11px;color:var(--muted);margin-top:2px;}

/* empty state */
.empty-state{padding:44px 20px;text-align:center;color:var(--faint);}
.empty-icon{font-size:32px;margin-bottom:12px;opacity:.4;}
.empty-text{font-size:13px;font-style:italic;line-height:1.8;}

/* ══════════════════════════
   NIP CARD  (enhanced)
══════════════════════════ */
.nip-stats-bar{display:flex;gap:0;border-bottom:1px solid var(--rule);background:var(--plt);}
.nsb-cell{flex:1;padding:10px 14px;border-right:1px solid var(--rule);text-align:center;}
.nsb-cell:last-child{border-right:none;}
.nsb-val{font-family:var(--f-m);font-size:18px;font-weight:700;line-height:1;margin-bottom:2px;}
.nsb-lbl{font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--faint);}

/* NIP grid */
.nip-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:12px 14px;}

/* NIP slot — the core interactive unit */
.nip-slot{
    border:1.5px solid var(--rule);
    border-radius:3px;
    background:var(--p);
    padding:9px 11px 8px;
    position:relative;
    transition:box-shadow .12s, border-color .12s;
    overflow:hidden;
}
.nip-slot::before{
    content:'';position:absolute;top:0;left:0;right:0;height:3px;
}
.nip-slot.ns-given  { border-color:color-mix(in srgb,var(--ok-fg) 35%,transparent); background:var(--ok-bg); }
.nip-slot.ns-given::before  { background:var(--ok-fg); }
.nip-slot.ns-overdue{ border-color:color-mix(in srgb,var(--danger-fg) 30%,transparent); background:var(--danger-bg); }
.nip-slot.ns-overdue::before{ background:var(--danger-fg); }
.nip-slot.ns-pending::before{ background:var(--faint); }
.nip-slot:hover{ box-shadow:0 2px 8px rgba(0,0,0,.1); border-color:var(--rule-dk); }

.ns-vaccine{font-weight:700;font-size:11.5px;color:var(--ink);margin-bottom:1px;line-height:1.3;}
.ns-dose   {font-size:9px;color:var(--faint);margin-bottom:4px;letter-spacing:.2px;}
.ns-status {font-size:8.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;}
.nip-slot.ns-given   .ns-status{color:var(--ok-fg);}
.nip-slot.ns-overdue .ns-status{color:var(--danger-fg);}
.nip-slot.ns-pending .ns-status{color:var(--faint);}
.ns-date   {font-family:var(--f-m);font-size:8.5px;color:var(--faint);margin-top:2px;}

/* slot action buttons */
.ns-actions{display:flex;gap:4px;margin-top:7px;}
.ns-btn{
    flex:1;padding:4px 6px;border-radius:2px;font-family:var(--f-n);
    font-size:9px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;
    cursor:pointer;border:1.5px solid;transition:all .12s;text-align:center;
    white-space:nowrap;
}
.ns-btn-add  {background:#fff;border-color:var(--mod);color:var(--mod);}
.ns-btn-add:hover{background:var(--mod);color:#fff;}
.ns-btn-edit {background:#fff;border-color:var(--rule-dk);color:var(--muted);}
.ns-btn-edit:hover{border-color:var(--ok-fg);color:var(--ok-fg);background:var(--ok-bg);}
.ns-btn-del  {background:#fff;border-color:var(--rule-dk);color:var(--faint);width:26px;flex:none;}
.ns-btn-del:hover{border-color:var(--danger-fg);color:var(--danger-fg);background:var(--danger-bg);}

/* ══════════════════════════
   CUSTOM RECORDS TABLE
══════════════════════════ */
.custom-table{width:100%;border-collapse:collapse;font-size:12.5px;}
.custom-table thead th{
    padding:8px 12px;background:var(--plt);font-size:7.5px;font-weight:700;
    letter-spacing:1px;text-transform:uppercase;color:var(--muted);
    border-bottom:1px solid var(--rule-dk);text-align:left;
}
.custom-table tbody tr{border-bottom:1px solid #f0ede8;transition:background .1s;}
.custom-table tbody tr:hover{background:var(--plt);}
.custom-table td{padding:9px 12px;vertical-align:middle;}
.td-vaccine{font-weight:600;color:var(--ink);}
.td-mono{font-family:var(--f-m);font-size:11px;color:var(--muted);}
.td-actions-sm{display:flex;gap:4px;}

/* badges */
.badge{display:inline-block;padding:2px 7px;border-radius:2px;font-size:8.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;border:1px solid;}
.b-given   {background:var(--ok-bg);color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent);}
.b-overdue {background:var(--danger-bg);color:var(--danger-fg);border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent);}
.b-custom  {background:#edf3fa;color:#1a3a5c;border-color:#bfdbfe80;}
.b-catch   {background:var(--warn-bg);color:var(--warn-fg);border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent);}

/* buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:2px;font-family:var(--f-n);font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .13s;white-space:nowrap;}
.btn-mod{background:var(--mod);border-color:var(--mod);color:#fff;}
.btn-mod:hover{filter:brightness(1.1);}
.btn-ghost{background:#fff;border-color:var(--rule-dk);color:var(--muted);}
.btn-ghost:hover{border-color:var(--mod);color:var(--mod);}
.btn-sm{padding:4px 10px;font-size:9.5px;}

/* form */
.fg{margin-bottom:12px;}
.fg-lbl{display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:5px;}
.req{color:var(--danger-fg);}
.fg-in,.fg-sel,.fg-ta{width:100%;padding:9px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-family:var(--f-n);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .14s,box-shadow .14s;}
.fg-in:focus,.fg-sel:focus,.fg-ta:focus{border-color:var(--mod);box-shadow:0 0 0 3px color-mix(in srgb,var(--mod) 10%,transparent);}
.fg-in::placeholder{color:var(--faint);font-style:italic;font-size:12px;}
.fg-ta{resize:vertical;min-height:58px;}
.fg-hint{font-size:10px;color:var(--faint);margin-top:3px;font-style:italic;}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:11px;}
.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.form-section{padding:14px 18px 0;border-top:1px solid var(--rule);}
.form-section:first-child{border-top:none;padding-top:18px;}
.fs-lbl{font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:12px;}
.fs-lbl::after{content:'';flex:1;height:1px;background:var(--rule);}
.fs-body{padding-bottom:14px;}
.submit-bar{padding:11px 16px;border-top:1px solid var(--rule);background:var(--plt);display:flex;justify-content:flex-end;gap:8px;}
.chk-row{display:flex;gap:10px;flex-wrap:wrap;}
.chk{display:flex;align-items:center;gap:5px;padding:5px 10px;border:1.5px solid var(--rule-dk);border-radius:2px;cursor:pointer;font-size:12px;color:var(--muted);transition:all .12s;}
.chk:has(input:checked){border-color:var(--mod);background:var(--mod-lt);color:var(--mod);}
.chk input{display:none;}

/* modal */
.ui-dialog{border:1px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 8px 48px rgba(0,0,0,.18)!important;padding:0!important;font-family:var(--f-n)!important;}
.ui-dialog-titlebar{background:var(--mod)!important;border:none!important;padding:11px 15px!important;}
.ui-dialog-title{font-family:var(--f-n)!important;font-size:11px!important;font-weight:700!important;letter-spacing:1px!important;text-transform:uppercase!important;color:#fff!important;}
.ui-dialog-titlebar-close{background:rgba(255,255,255,.15)!important;border:1px solid rgba(255,255,255,.25)!important;border-radius:2px!important;color:#fff!important;width:24px!important;height:24px!important;top:50%!important;transform:translateY(-50%)!important;}
.ui-dialog-content{padding:0!important;}
.ui-autocomplete{z-index:9999!important;max-height:200px;overflow-y:auto;}

/* source-type indicator */
.src-indicator{
    display:inline-flex;align-items:center;gap:4px;
    font-size:8px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;
    padding:2px 6px;border-radius:2px;
}
.src-nip   {background:#f0fdf4;color:#166534;border:1px solid #a7f3d080;}
.src-custom{background:#edf3fa;color:#1e40af;border:1px solid #bfdbfe80;}
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
                    <div class="page-sub"><?= $total ?> patient<?= $total!==1?'s':'' ?> on record — select to view & manage</div>
                </div>
                <div style="display:flex;gap:7px;padding-bottom:4px;">
                    <button class="btn btn-ghost" id="btnGenerate">↗ Generate Report</button>
                    <?php if ($type === 'immunization'): ?>
                    <button class="btn btn-mod" id="btnAddCustom" disabled>+ Custom Vaccine</button>
                    <?php else: ?>
                    <button class="btn btn-mod" id="btnNewVisit" disabled>+ New Visit</button>
                    <?php endif; ?>
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
                    <div class="card-head"><div class="card-title">Search any resident</div></div>
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
                            <span style="font-family:var(--f-m);font-size:9px;color:var(--faint);font-weight:400;">(<?= $total ?>)</span>
                        </div>
                        <input type="text" id="ptFilter" class="search-input"
                               style="width:120px;padding:4px 9px;font-size:12px;" placeholder="Filter…">
                    </div>
                    <div class="pt-list" id="ptList">
                        <?php if (empty($ptMap)): ?>
                        <div class="pt-empty">
                            No <?= htmlspecialchars(strtolower($mc['label'])) ?> records yet.<br>
                            <small>Search a resident above to begin.</small>
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
                    <div class="sel-hdr">
                        <div>
                            <div class="sel-name" id="selName">—</div>
                            <div class="sel-meta" id="selMeta">—</div>
                        </div>
                    </div>

                    <?php if ($type === 'immunization'): ?>

                    <!-- ══ NIP IMMUNIZATION CARD ══ -->
                    <div>
                        <div class="card-head" style="border-top:1px solid var(--rule);">
                            <div class="card-title">National Immunization Program (NIP) Card</div>
                            <div id="nipStatsLabel" style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);">—</div>
                        </div>

                        <!-- Stats bar -->
                        <div class="nip-stats-bar">
                            <div class="nsb-cell">
                                <div class="nsb-val" id="nsGiven" style="color:var(--ok-fg);">—</div>
                                <div class="nsb-lbl">Given</div>
                            </div>
                            <div class="nsb-cell">
                                <div class="nsb-val" id="nsOverdue" style="color:var(--danger-fg);">—</div>
                                <div class="nsb-lbl">Overdue</div>
                            </div>
                            <div class="nsb-cell">
                                <div class="nsb-val" id="nsPending" style="color:var(--faint);">—</div>
                                <div class="nsb-lbl">Pending</div>
                            </div>
                            <div class="nsb-cell">
                                <div class="nsb-val" id="nsTotal">—</div>
                                <div class="nsb-lbl">Total NIP</div>
                            </div>
                        </div>

                        <!-- NIP slots grid -->
                        <div class="nip-grid" id="nipGrid">
                            <div style="grid-column:1/-1;padding:16px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">Loading immunization card…</div>
                        </div>
                    </div>

                    <!-- ══ CUSTOM / NON-NIP RECORDS ══ -->
                    <div>
                        <div class="card-head" style="border-top:1px solid var(--rule);">
                            <div class="card-title">Other / Custom Vaccine Records</div>
                            <button class="btn btn-ghost btn-sm" id="btnAddCustomInline">+ Add Record</button>
                        </div>
                        <div id="customRecordsWrap">
                            <div style="padding:18px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">No custom records yet.</div>
                        </div>
                    </div>

                    <?php else: ?>

                    <!-- ══ VISIT HISTORY (non-immunization types) ══ -->
                    <div>
                        <div class="card-head" style="border-top:1px solid var(--rule);">
                            <div class="card-title">Visit History</div>
                            <span id="vCount" style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);">—</span>
                        </div>
                        <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:12.5px;" id="vTable">
                            <thead><tr>
                                <th style="padding:9px 12px;background:var(--plt);font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--rule-dk);text-align:left;">Date</th>
                                <th style="padding:9px 12px;background:var(--plt);font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--rule-dk);text-align:left;">Summary</th>
                                <th style="padding:9px 12px;background:var(--plt);font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--rule-dk);text-align:left;">Source</th>
                                <th style="padding:9px 12px;background:var(--plt);font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--rule-dk);">Actions</th>
                            </tr></thead>
                            <tbody id="vTableBody">
                                <tr><td colspan="4" style="padding:20px;text-align:center;color:var(--faint);font-size:12px;">—</td></tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <?php endif; ?>

                </div><!-- /detailCard -->
            </div><!-- /right -->
        </div><!-- /body -->

    </main>
</div>

<!-- ══════════════════════════════════════════════
     IMMUNIZATION ADD / EDIT DIALOG
══════════════════════════════════════════════ -->
<div id="immModal" title="Add Immunization" class="hidden">
<form id="immForm" style="max-height:74vh;overflow-y:auto;">
    <input type="hidden" name="id"          id="imm_id">
    <input type="hidden" name="resident_id" id="imm_rid">
    <input type="hidden" name="schedule_id" id="imm_sched_id">

    <div class="form-section" style="border-top:none;padding-top:18px;">
        <div class="fs-lbl">Vaccine Information</div>
        <div class="fs-body">
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Vaccine Name <span class="req">*</span></label>
                    <input type="text" name="vaccine_name" id="imm_vaccine" class="fg-in"
                           placeholder="e.g. BCG, Pentavalent, MMR…" autocomplete="off" required>
                    <div class="fg-hint" id="imm_nip_hint" style="display:none;color:var(--ok-fg);">
                        ✓ Linked to NIP schedule slot
                    </div>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Dose / Label</label>
                    <input type="text" name="dose" id="imm_dose" class="fg-in"
                           placeholder="e.g. Dose 1, Booster…" autocomplete="off">
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="fs-lbl">Administration</div>
        <div class="fs-body">
            <div class="g3">
                <div class="fg">
                    <label class="fg-lbl">Date Given <span class="req">*</span></label>
                    <input type="date" name="date_given" id="imm_date" class="fg-in" required>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Next Schedule</label>
                    <input type="date" name="next_schedule" id="imm_next" class="fg-in">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Administered By</label>
                    <input type="text" name="administered_by" id="imm_worker" class="fg-in"
                           value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                </div>
            </div>
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Route</label>
                    <select name="route" id="imm_route" class="fg-sel">
                        <option value="IM">IM — Intramuscular</option>
                        <option value="SC">SC — Subcutaneous</option>
                        <option value="ID">ID — Intradermal</option>
                        <option value="Oral">Oral</option>
                        <option value="Nasal">Nasal</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Site Given</label>
                    <input type="text" name="site_given" class="fg-in"
                           placeholder="e.g. Right deltoid, Left thigh…" autocomplete="off">
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="fs-lbl">Batch &amp; Expiry</div>
        <div class="fs-body">
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Batch / Lot Number</label>
                    <input type="text" name="batch_number" class="fg-in"
                           placeholder="Batch no. from vial…" autocomplete="off">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Vial Expiry Date</label>
                    <input type="date" name="expiry_date" class="fg-in">
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="fs-lbl">Adverse Reaction &amp; Flags</div>
        <div class="fs-body">
            <div class="fg">
                <label class="fg-lbl">Adverse Reaction / Notes</label>
                <textarea name="adverse_reaction" class="fg-ta"
                          placeholder="Any adverse event after vaccination, or leave blank if none…"></textarea>
            </div>
            <div class="fg">
                <label class="fg-lbl">Additional Remarks</label>
                <textarea name="remarks" class="fg-ta"
                          placeholder="Optional notes…"></textarea>
            </div>
            <div class="chk-row">
                <label class="chk">
                    <input type="checkbox" name="is_defaulter" value="1" id="imm_defaulter">
                    Defaulter (missed schedule)
                </label>
                <label class="chk">
                    <input type="checkbox" name="catch_up" value="1" id="imm_catchup">
                    Catch-up dose
                </label>
            </div>
        </div>
    </div>

    <div class="submit-bar">
        <button type="button" class="btn btn-ghost" onclick="$('#immModal').dialog('close')">Cancel</button>
        <button type="submit" class="btn btn-mod" id="immSubmit">Save Record</button>
    </div>
</form>
</div>

<!-- ══════════════════════════════
     GENERATE REPORT MODAL
══════════════════════════════ -->
<div id="generateModal" title="Generate Report" class="hidden">
    <form id="generateForm" style="padding:18px 20px 0;display:flex;flex-direction:column;gap:0;">
        <div style="margin-bottom:14px;">
            <label class="fg-lbl" style="margin-bottom:8px;">Report Scope</label>
            <div style="display:flex;gap:0;border:1.5px solid var(--rule-dk);border-radius:2px;overflow:hidden;">
                <button type="button" class="gen-scope-btn active" id="scopePatient"
                        style="flex:1;padding:8px 10px;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;color:#fff;background:var(--mod);border:none;">
                    Selected Patient
                </button>
                <button type="button" class="gen-scope-btn" id="scopeAll"
                        style="flex:1;padding:8px 10px;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;color:var(--muted);background:#fff;border:none;">
                    All Patients
                </button>
            </div>
        </div>
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
        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:16px;">
            <button type="button" class="btn btn-ghost btn-sm" data-range="today">Today</button>
            <button type="button" class="btn btn-ghost btn-sm" data-range="month">This Month</button>
            <button type="button" class="btn btn-ghost btn-sm" data-range="year">This Year</button>
        </div>
        <div class="submit-bar" style="margin:0 -20px;padding:11px 20px;">
            <button type="button" class="btn btn-ghost" onclick="$('#generateModal').dialog('close')">Cancel</button>
            <button type="button" class="btn btn-mod" id="btnDoGenerate">↗ Print</button>
        </div>
    </form>
</div>

<script>
const CV_TYPE = <?= json_encode($type) ?>;
const CV_API  = 'api/care_visits_api.php';
let selId = null, selName = '';
let genScope = 'patient';

$(function(){
    $('body').show();

    function esc(s){ const d=document.createElement('div'); d.textContent=String(s||''); return d.innerHTML; }
    function or(v,fb){ return (v===null||v===undefined||v===''||v==='0')?fb:v; }

    /* ════════════════════════
       PATIENT LIST FILTER
    ════════════════════════ */
    $('#ptFilter').on('input',function(){
        const q=$(this).val().toLowerCase();
        $('.pt-row').each(function(){
            $(this).toggle(!q||($(this).data('name')||'').toLowerCase().includes(q));
        });
    });

    $(document).on('click','.pt-row',function(){
        $('.pt-row').removeClass('on');
        $(this).addClass('on');
        selectPatient($(this).data('id'),$(this).data('name'),$(this).data('age'),$(this).data('gender'));
    });

    /* search any resident */
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

    /* ════════════════════════
       SELECT PATIENT
    ════════════════════════ */
    function selectPatient(id, name, age, gender){
        selId=id; selName=name;
        $('#emptyCard').hide();
        $('#detailCard').show();
        $('#btnNewVisit, #btnAddCustom').prop('disabled',false);
        $('#selName').text(name);
        $('#selMeta').text([age,gender].filter(Boolean).join(' · ')||'Resident');

        if(CV_TYPE==='immunization'){
            loadNIPCard(id);
        } else {
            loadVisits(id);
        }
    }

    /* ════════════════════════════════════════════
       NIP CARD  — enhanced rendering
    ════════════════════════════════════════════ */
    function loadNIPCard(rid){
        $('#nipGrid').html('<div style="grid-column:1/-1;padding:16px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">Loading…</div>');
        $.getJSON(CV_API,{action:'immunization_card',resident_id:rid},function(res){
            if(res.status!=='ok'){ $('#nipGrid').html('<div style="grid-column:1/-1;padding:14px;color:var(--danger-fg);">Failed to load card.</div>'); return; }
            const d=res.data;

            /* Stats */
            const given=d.given_count||0, over=d.overdue||0, tot=d.total||0, pend=tot-given;
            $('#nsGiven').text(given); $('#nsOverdue').text(over);
            $('#nsPending').text(pend); $('#nsTotal').text(tot);
            $('#nipStatsLabel').text(`${given}/${tot} vaccinated`);

            /* NIP Slots */
            let html='';
            (d.schedule||[]).forEach(s=>{
                const g=s.given;
                let cls='ns-pending', statusTxt='Pending';
                if(g) { cls='ns-given'; statusTxt='Given ✓'; }
                else if(s.is_overdue){ cls='ns-overdue'; statusTxt='Overdue!'; }

                const dateStr = g ? esc(g.date_given||'') : (s.due_date ? 'Due: '+esc(s.due_date) : '');

                let actionHtml='';
                if(g){
                    actionHtml=`<div class="ns-actions">
                        <button class="ns-btn ns-btn-edit nipEditBtn" data-id="${g.id}" data-sched="${s.id}">✏ Edit</button>
                        <button class="ns-btn ns-btn-del nipDelBtn" data-id="${g.id}" title="Delete record">✕</button>
                    </div>`;
                } else {
                    actionHtml=`<div class="ns-actions">
                        <button class="ns-btn ns-btn-add nipAddBtn" data-sched="${s.id}" data-vaccine="${esc(s.vaccine_name)}" data-dose="${esc(s.dose_label)}">
                            + Record
                        </button>
                    </div>`;
                }

                html+=`<div class="nip-slot ${cls}">
                    <div class="ns-vaccine">${esc(s.vaccine_name)}</div>
                    <div class="ns-dose">${esc(s.dose_label)}</div>
                    <div class="ns-status">${statusTxt}</div>
                    <div class="ns-date">${dateStr}</div>
                    ${actionHtml}
                </div>`;
            });

            if(!html) html='<div style="grid-column:1/-1;padding:14px;text-align:center;color:var(--faint);">No NIP schedule found.</div>';
            $('#nipGrid').html(html);

            /* Custom records */
            renderCustomRecords(d.custom||[]);
        });
    }

    /* Custom (non-NIP) records table */
    function renderCustomRecords(rows){
        if(!rows.length){
            $('#customRecordsWrap').html('<div style="padding:16px 18px;color:var(--faint);font-size:12px;font-style:italic;">No custom records yet. Use "+ Add Record" to log a catch-up or non-NIP vaccine.</div>');
            return;
        }
        let html=`<div style="overflow-x:auto;"><table class="custom-table">
            <thead><tr>
                <th>Vaccine</th><th>Dose</th><th>Date Given</th>
                <th>Next</th><th>Given By</th><th>Flags</th><th>Actions</th>
            </tr></thead><tbody>`;
        rows.forEach(r=>{
            const flags=[];
            if(r.is_defaulter==1) flags.push('<span class="badge b-overdue">Defaulter</span>');
            if(r.catch_up==1)     flags.push('<span class="badge b-catch">Catch-up</span>');
            if(r.adverse_reaction) flags.push('<span class="badge" style="background:#fdeeed;color:#7a1f1a;border-color:#fca5a580;">⚠ ADR</span>');
            html+=`<tr>
                <td class="td-vaccine">${esc(r.vaccine_name||'—')}</td>
                <td style="font-size:11.5px;color:var(--muted);">${esc(r.dose||'—')}</td>
                <td class="td-mono">${esc(r.date_given||'—')}</td>
                <td class="td-mono">${esc(r.next_schedule||'—')}</td>
                <td style="font-size:11.5px;color:var(--muted);">${esc(r.administered_by||'—')}</td>
                <td>${flags.join(' ')||'<span style="color:var(--faint);font-size:11px;">—</span>'}</td>
                <td>
                    <div class="td-actions-sm">
                        <button class="btn btn-ghost btn-sm custEditBtn" data-id="${r.id}">Edit</button>
                        <button class="btn btn-sm" style="background:var(--danger-bg);border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent);color:var(--danger-fg);" class="custDelBtn" data-id="${r.id}">✕</button>
                    </div>
                </td>
            </tr>`;
        });
        html+='</tbody></table></div>';
        $('#customRecordsWrap').html(html);
    }

    /* ════════════════════════════════════════
       IMM DIALOG  — shared for NIP + custom
    ════════════════════════════════════════ */
    $('#immModal').dialog({
        autoOpen:false, modal:true, width:600, resizable:false,
        open:function(){ $(this).find(':input:first').blur(); }
    });

    function openImmAdd(schedId, vaccineName, doseLabel){
        resetImmForm();
        $('#imm_rid').val(selId);
        $('#imm_sched_id').val(schedId||'');
        $('#imm_vaccine').val(vaccineName||'').prop('readonly', !!schedId);
        $('#imm_dose').val(doseLabel||'').prop('readonly', !!schedId);
        $('#imm_date').val(new Date().toISOString().slice(0,10));
        $('#imm_worker').val(<?= json_encode($_SESSION['name'] ?? '') ?>);
        $('#imm_nip_hint').toggle(!!schedId);
        $('#immSubmit').text('Save Record');
        $('#immModal').dialog('option','title', schedId ? 'Record NIP Vaccine — '+vaccineName : 'Add Custom Vaccine Record').dialog('open');
    }

    function openImmEdit(immId){
        $.getJSON(CV_API,{action:'get_immunization',id:immId},function(res){
            if(res.status!=='ok'){ alert('Could not load record.'); return; }
            const d=res.data.data||res.data;
            resetImmForm();
            $('#imm_id').val(d.id);
            $('#imm_rid').val(d.resident_id);
            $('#imm_sched_id').val(d.schedule_id||'');
            const isNip=!!(d.schedule_id);
            $('#imm_vaccine').val(d.vaccine_name||'').prop('readonly', isNip);
            $('#imm_dose').val(d.dose||'').prop('readonly', isNip);
            $('#imm_date').val(d.date_given||'');
            $('#imm_next').val(d.next_schedule||'');
            $('#imm_worker').val(d.administered_by||'');
            $('[name="route"]').val(d.route||'IM');
            $('[name="site_given"]').val(d.site_given||'');
            $('[name="batch_number"]').val(d.batch_number||'');
            $('[name="expiry_date"]').val(d.expiry_date||'');
            $('[name="adverse_reaction"]').val(d.adverse_reaction||'');
            $('[name="remarks"]').val(d.remarks||'');
            $('#imm_defaulter').prop('checked', d.is_defaulter==1);
            $('#imm_catchup').prop('checked',   d.catch_up==1);
            $('#imm_nip_hint').toggle(isNip);
            $('#immSubmit').text('Update Record');
            $('#immModal').dialog('option','title','Edit — '+esc(d.vaccine_name||'')).dialog('open');
        }).fail(()=>alert('Request failed.'));
    }

    function resetImmForm(){
        $('#immForm')[0].reset();
        $('#imm_id').val('');
        $('#imm_sched_id').val('');
        $('#imm_vaccine').prop('readonly',false);
        $('#imm_dose').prop('readonly',false);
        $('#imm_nip_hint').hide();
    }

    /* NIP slot — Add button */
    $(document).on('click','.nipAddBtn',function(){
        openImmAdd($(this).data('sched'), $(this).data('vaccine'), $(this).data('dose'));
    });

    /* NIP slot — Edit button */
    $(document).on('click','.nipEditBtn',function(){
        openImmEdit($(this).data('id'));
    });

    /* NIP slot — Delete button */
    $(document).on('click','.nipDelBtn',function(){
        const id=$(this).data('id');
        if(!confirm('Delete this immunization record? This cannot be undone.')) return;
        $.post(CV_API+'?action=delete_immunization',{id},function(res){
            const r=typeof res==='string'?JSON.parse(res):res;
            if(r.status!=='ok'){ alert(r.message||'Delete failed.'); return; }
            loadNIPCard(selId);
        });
    });

    /* Custom — Edit (delegated from dynamic content) */
    $(document).on('click','.custEditBtn',function(){
        openImmEdit($(this).data('id'));
    });

    /* Custom — Delete (delegated) */
    $(document).on('click','.custDelBtn',function(){
        const id=$(this).data('id');
        if(!confirm('Delete this immunization record?')) return;
        $.post(CV_API+'?action=delete_immunization',{id},function(res){
            const r=typeof res==='string'?JSON.parse(res):res;
            if(r.status!=='ok'){ alert(r.message||'Delete failed.'); return; }
            loadNIPCard(selId);
        });
    });

    /* Top-bar "Custom Vaccine" button */
    $('#btnAddCustom, #btnAddCustomInline').on('click',function(){
        if(!selId){ alert('Please select a patient first.'); return; }
        openImmAdd(null,'','');
    });

    /* Form submit */
    $('#immForm').on('submit',function(e){
        e.preventDefault();
        if(!$('#imm_rid').val()){ alert('No patient selected.'); return; }
        const id   = $('#imm_id').val();
        const action = id ? 'update_immunization' : 'save_immunization';
        const $btn = $('#immSubmit');
        $btn.prop('disabled',true).text('Saving…');
        $.ajax({
            url: CV_API+'?action='+action,
            type:'POST', data:$(this).serialize(), dataType:'json',
            success(res){
                if(res.status!=='ok'){ alert(res.message||'Save failed.'); return; }
                $('#immModal').dialog('close');
                loadNIPCard(selId);
            },
            error(xhr){ alert('Server error ('+xhr.status+').'); },
            complete(){ $btn.prop('disabled',false).text(id?'Update Record':'Save Record'); }
        });
    });

    /* ════════════════════════════
       VISIT HISTORY (non-imm)
    ════════════════════════════ */
    function loadVisits(rid){
        $('#vTableBody').html('<tr><td colspan="4" style="padding:16px;text-align:center;color:var(--faint);">Loading…</td></tr>');
        const today=new Date().toISOString().slice(0,10);
        const p1=$.getJSON('../consultation/api/list_by_type.php',{type:CV_TYPE,resident_id:rid});
        const p2=$.getJSON(CV_API,{action:'list',type:CV_TYPE,resident_id:rid,from:'2000-01-01',to:today});
        $.when(p1.then(r=>r,()=>({status:'err'})),p2.then(r=>r,()=>({status:'err'}))).done(function(r1,r2){
            const c=(r1.status==='ok'?r1.data:[]).map(r=>({...r,_src:'consult'}));
            const v=(r2.status==='ok'?(r2.data?.data||[]):[]).map(r=>({...r,_src:'care'}));
            const all=[...c,...v].sort((a,b)=>{
                const da=a.consultation_date||a.visit_date||'', db=b.consultation_date||b.visit_date||'';
                return db.localeCompare(da);
            });
            $('#vCount').text(all.length+(all.length===1?' RECORD':' RECORDS'));
            if(!all.length){
                $('#vTableBody').html('<tr><td colspan="4" style="padding:24px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">No visits recorded yet.</td></tr>');
                return;
            }
            let html='';
            all.forEach(r=>{
                const date=r.consultation_date||r.visit_date||'—';
                const summary=(r._src==='consult')?([r.complaint,r.diagnosis].filter(Boolean).join(' · ').substring(0,60)):((r.notes||'').substring(0,60));
                const srcTag=r._src==='consult'
                    ? '<span class="badge b-custom">Consult</span>'
                    : '<span class="badge b-given">Care</span>';
                html+=`<tr>
                    <td style="font-family:var(--f-m);font-size:11.5px;color:var(--muted);">${esc(date)}</td>
                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:12px;color:var(--muted);">${esc(summary||'—')}</td>
                    <td>${srcTag}</td>
                    <td><div style="display:flex;gap:4px;">
                        <button class="btn btn-ghost btn-sm">View</button>
                    </div></td>
                </tr>`;
            });
            $('#vTableBody').html(html);
        });
    }

    /* ════════════════════════
       GENERATE MODAL
    ════════════════════════ */
    $('#generateModal').dialog({autoOpen:false,modal:true,width:440,resizable:false});
    $('#btnGenerate').on('click',function(){ genScope=selId?'patient':'all'; updateScopeUI(); $('#generateModal').dialog('open'); });

    function updateScopeUI(){
        const pat=genScope==='patient';
        $('#scopePatient').css({background:pat?'var(--mod)':'#fff',color:pat?'#fff':'var(--muted)'});
        $('#scopeAll').css({background:!pat?'var(--mod)':'#fff',color:!pat?'#fff':'var(--muted)'});
    }
    $('#scopePatient').on('click',function(){ if(!selId){alert('Select a patient first.');return;} genScope='patient'; updateScopeUI(); });
    $('#scopeAll').on('click',function(){ genScope='all'; updateScopeUI(); });

    $('[data-range]').on('click',function(){
        const r=$(this).data('range'), t=new Date(), fmt=d=>d.toISOString().slice(0,10);
        let from; const to=fmt(t);
        if(r==='today') from=to;
        else if(r==='month') from=t.getFullYear()+'-'+String(t.getMonth()+1).padStart(2,'0')+'-01';
        else from=t.getFullYear()+'-01-01';
        $('#gen_from').val(from); $('#gen_to').val(to);
    });

    $('#btnDoGenerate').on('click',function(){
        const from=$('#gen_from').val(), to=$('#gen_to').val();
        if(!from||!to){ alert('Set a date range.'); return; }
        const rid=(genScope==='patient'&&selId)?selId:'';
        window.open(`print.php?type=${encodeURIComponent(CV_TYPE)}&resident_id=${encodeURIComponent(rid)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`,'_blank');
        $('#generateModal').dialog('close');
    });
});
</script>
</body>
</html>
<?php
/**
 * Care Visits Print Page
 * Generates a government-document style print for any care module
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type = $_GET['type'] ?? 'prenatal';
$rid  = (int)($_GET['resident_id'] ?? 0);
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$allowed = ['maternal','family_planning','prenatal','postnatal','child_nutrition','immunization'];
if (!in_array($type, $allowed, true)) $type = 'prenatal';

$labels = [
    'maternal'        => 'Maternal Health Record',
    'family_planning' => 'Family Planning Record',
    'prenatal'        => 'Prenatal / Antenatal Care Record',
    'postnatal'       => 'Postnatal / Postnatal Care Record',
    'child_nutrition' => 'Child Nutrition Monitoring Record',
    'immunization'    => 'Immunization Record',
];
$pageTitle = $labels[$type] ?? 'Care Record';

// Fetch resident info
$residentName = '';
$birthdate = '';
$address = '';
if ($rid > 0) {
    $rStmt = $conn->prepare("SELECT CONCAT_WS(' ',first_name,middle_name,last_name) name, birthdate, address FROM residents WHERE id=? LIMIT 1");
    $rStmt->bind_param('i', $rid);
    $rStmt->execute();
    $rRow = $rStmt->get_result()->fetch_assoc();
    if ($rRow) {
        $residentName = $rRow['name'];
        $birthdate    = $rRow['birthdate'];
        $address      = $rRow['address'];
    }
}
$age = '';
if ($birthdate) {
    $bd = new DateTime($birthdate);
    $age = $bd->diff(new DateTime())->y . ' yrs old';
}

// Determine table joining based on type
$joinMap = [
    'family_planning' => ['family_planning_record', 'fpr'],
    'prenatal'        => ['prenatal_visit', 'pv'],
    'postnatal'       => ['postnatal_visit', 'pnv'],
    'child_nutrition' => ['child_nutrition_visit', 'cnv'],
];

$rows = [];
if ($type === 'immunization') {
    $sql = "SELECT i.*, CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) resident_name
            FROM immunizations i LEFT JOIN residents r ON r.id=i.resident_id
            WHERE 1=1";
    $params = []; $types = '';
    if ($rid > 0) { $sql .= " AND i.resident_id=?"; $params[]=$rid; $types.='i'; }
    $sql .= " AND i.date_given BETWEEN ? AND ? ORDER BY i.date_given DESC";
    $params[] = $from; $params[] = $to; $types .= 'ss';
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
} elseif (isset($joinMap[$type])) {
    [$tbl, $alias] = $joinMap[$type];
    $sql = "SELECT cv.*, {$alias}.*,
                   CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) resident_name
            FROM care_visits cv
            INNER JOIN {$tbl} {$alias} ON {$alias}.care_visit_id = cv.id
            INNER JOIN residents r ON r.id = cv.resident_id
            WHERE cv.care_type = ?";
    $params = [$type]; $types = 's';
    if ($rid > 0) { $sql .= " AND cv.resident_id=?"; $params[]=$rid; $types.='i'; }
    $sql .= " AND cv.visit_date BETWEEN ? AND ? ORDER BY cv.visit_date DESC";
    $params[]=$from; $params[]=$to; $types.='ss';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
} else {
    // Maternal — care_visits only
    $sql = "SELECT cv.*, CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) resident_name
            FROM care_visits cv INNER JOIN residents r ON r.id=cv.resident_id
            WHERE cv.care_type='maternal'";
    $params=[]; $types='';
    if ($rid > 0) { $sql .= " AND cv.resident_id=?"; $params[]=$rid; $types.='i'; }
    $sql .= " AND cv.visit_date BETWEEN ? AND ? ORDER BY cv.visit_date DESC";
    $params[]=$from; $params[]=$to; $types.='ss';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
}

function h($s){ return htmlspecialchars((string)($s??''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= h($pageTitle) ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Source+Sans+3:wght@400;600;700&family=Source+Code+Pro:wght@400;600&display=swap');
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Source Sans 3',Arial,sans-serif;font-size:11px;color:#1a1a1a;background:#fff;padding:24px 32px;}
.no-print{margin-bottom:12px;display:flex;gap:8px;}
@media print{.no-print{display:none;} body{padding:12px 18px;}}
.lh{border-bottom:3px solid #2d5a27;padding-bottom:10px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:flex-end;}
.lh-brgy{font-family:'Source Serif 4';font-size:16px;font-weight:700;color:#2d5a27;}
.lh-addr{font-size:9px;color:#888;margin-top:2px;}
.lh-right{text-align:right;}
.lh-doc-title{font-family:'Source Serif 4';font-size:13px;font-weight:700;}
.lh-doc-sub{font-size:9px;color:#888;margin-top:2px;}
.res-block{margin-bottom:12px;padding:10px 14px;background:#f9f7f3;border:1px solid #d8d4cc;border-left:3px solid #2d5a27;display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px 16px;}
.rf-lbl{font-size:7.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#a0a0a0;margin-bottom:2px;}
.rf-val{font-size:12px;font-weight:600;color:#1a1a1a;}
table{width:100%;border-collapse:collapse;font-size:10.5px;}
thead th{padding:7px 9px;background:#f0ede6;font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#5a5a5a;border-bottom:2px solid #b8b4ac;text-align:left;}
tbody tr{border-bottom:1px solid #f0ede8;}
tbody tr:hover{background:#fafaf8;}
td{padding:7px 9px;vertical-align:top;}
.mono{font-family:'Source Code Pro';font-size:9.5px;color:#5a5a5a;}
.badge{display:inline-block;padding:2px 6px;border-radius:2px;font-size:8px;font-weight:700;border:1px solid;}
.ok{background:#edfaf3;color:#1a5c35;border-color:#a7f3d0;}
.warn{background:#fef9ec;color:#7a5700;border-color:#fde68a;}
.danger{background:#fdeeed;color:#7a1f1a;border-color:#fca5a5;}
.sigs{margin-top:36px;display:grid;grid-template-columns:1fr 1fr;gap:40px;}
.sig-line{border-top:1px solid #1a1a1a;margin-top:40px;margin-bottom:4px;}
.sig-name{font-weight:700;font-size:11px;}
.sig-role{font-size:9px;color:#888;margin-top:2px;}
.footer{margin-top:18px;padding-top:8px;border-top:1px solid #d8d4cc;display:flex;justify-content:space-between;font-size:8px;color:#a0a0a0;}
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="padding:6px 18px;background:#2d5a27;color:#fff;border:none;border-radius:2px;font-weight:700;cursor:pointer;">↗ Print</button>
    <button onclick="window.close()" style="padding:6px 18px;background:#fff;border:1.5px solid #b8b4ac;border-radius:2px;cursor:pointer;">Close</button>
</div>

<div class="lh">
    <div><div class="lh-brgy">Barangay Bombongan</div><div class="lh-addr">Morong, Rizal · Barangay Health Center</div></div>
    <div class="lh-right"><div class="lh-doc-title"><?= h($pageTitle) ?></div><div class="lh-doc-sub">Printed: <?= date('d F Y, h:i A') ?></div></div>
</div>

<?php if ($residentName): ?>
<div class="res-block">
    <div><div class="rf-lbl">Patient</div><div class="rf-val"><?= h($residentName) ?></div></div>
    <div><div class="rf-lbl">Age / Birthdate</div><div class="rf-val"><?= h($age) ?> · <?= h($birthdate) ?></div></div>
    <div><div class="rf-lbl">Address</div><div class="rf-val"><?= h($address ?: '—') ?></div></div>
</div>
<?php endif; ?>

<!-- IMMUNIZATION -->
<?php if ($type === 'immunization'): ?>
<table>
    <thead><tr><th>Date Given</th><th>Vaccine</th><th>Dose</th><th>Route</th><th>Batch No.</th><th>Next Schedule</th><th>Administered By</th><th>Adverse Reaction</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="8" style="padding:20px;text-align:center;color:#a0a0a0;font-style:italic;">No records found.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="mono"><?= h($r['date_given']) ?></td>
            <td style="font-weight:600;"><?= h($r['vaccine_name']) ?></td>
            <td><?= h($r['dose'] ?? '—') ?></td>
            <td><?= h($r['route'] ?? 'IM') ?></td>
            <td class="mono"><?= h($r['batch_number'] ?? '—') ?></td>
            <td class="mono"><?= h($r['next_schedule'] ?? '—') ?></td>
            <td><?= h($r['administered_by'] ?? '—') ?></td>
            <td style="color:#d14520;"><?= h($r['adverse_reaction'] ?? '—') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- PRENATAL -->
<?php elseif ($type === 'prenatal'): ?>
<table>
    <thead><tr><th>Visit Date</th><th>Visit #</th><th>AOG</th><th>Weight</th><th>BP</th><th>FHR</th><th>FH</th><th>Risk</th><th>TT</th><th>Worker</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="10" style="padding:20px;text-align:center;color:#a0a0a0;font-style:italic;">No records found.</td></tr>
    <?php else: foreach ($rows as $r):
        $riskCls = $r['risk_level']==='High'?'danger':($r['risk_level']==='Moderate'?'warn':'ok');
    ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <td>Visit <?= h($r['visit_number']) ?></td>
            <td><?= $r['aog_weeks'] ? h($r['aog_weeks']).' wks' : '—' ?></td>
            <td><?= $r['weight_kg'] ? h($r['weight_kg']).' kg' : '—' ?></td>
            <td class="mono"><?= ($r['bp_systolic']&&$r['bp_diastolic']) ? h($r['bp_systolic']).'/'.h($r['bp_diastolic']) : '—' ?></td>
            <td><?= $r['fetal_heart_rate'] ? h($r['fetal_heart_rate']).' bpm' : '—' ?></td>
            <td><?= $r['fundal_height_cm'] ? h($r['fundal_height_cm']).' cm' : '—' ?></td>
            <td><span class="badge <?= $riskCls ?>"><?= h($r['risk_level'] ?? 'Low') ?></span></td>
            <td><?= h($r['tt_dose'] ?? 'None') ?></td>
            <td><?= h($r['health_worker'] ?? '—') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- POSTNATAL -->
<?php elseif ($type === 'postnatal'): ?>
<table>
    <thead><tr><th>Visit Date</th><th>Visit #</th><th>Delivery Type</th><th>BP</th><th>Lochia</th><th>BF Status</th><th>PPD Score</th><th>NB Weight</th><th>APGAR</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="9" style="padding:20px;text-align:center;color:#a0a0a0;font-style:italic;">No records found.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <td>PNC <?= h($r['visit_number']) ?></td>
            <td><?= h($r['delivery_type'] ?? '—') ?></td>
            <td class="mono"><?= ($r['bp_systolic']&&$r['bp_diastolic']) ? h($r['bp_systolic']).'/'.h($r['bp_diastolic']) : '—' ?></td>
            <td><?= h($r['lochia_type'] ?? '—') ?></td>
            <td><?= h($r['breastfeeding_status'] ?? '—') ?></td>
            <td><?= isset($r['ppd_score'])&&$r['ppd_score']!==null ? h($r['ppd_score']) : '—' ?></td>
            <td><?= $r['newborn_weight_g'] ? number_format($r['newborn_weight_g']).'g' : '—' ?></td>
            <td><?= ($r['apgar_1min']!==null&&$r['apgar_5min']!==null) ? h($r['apgar_1min']).' / '.h($r['apgar_5min']) : '—' ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- FAMILY PLANNING -->
<?php elseif ($type === 'family_planning'): ?>
<table>
    <thead><tr><th>Visit Date</th><th>Method</th><th>New Acceptor</th><th>Switch</th><th>Next Supply</th><th>Next Checkup</th><th>Pills Given</th><th>Side Effects</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="8" style="padding:20px;text-align:center;color:#a0a0a0;font-style:italic;">No records found.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <td style="font-weight:600;"><?= h($r['method']) ?><?= $r['method_other'] ? ' ('.h($r['method_other']).')' : '' ?></td>
            <td><?= $r['is_new_acceptor'] ? '<span class="badge ok">Yes</span>' : 'No' ?></td>
            <td><?= $r['is_method_switch'] ? '<span class="badge warn">Yes</span>' : 'No' ?></td>
            <td class="mono"><?= h($r['next_supply_date'] ?? '—') ?></td>
            <td class="mono"><?= h($r['next_checkup_date'] ?? '—') ?></td>
            <td><?= (int)($r['pills_given']??0) ?> pks</td>
            <td style="color:#d14520;font-size:10px;"><?= h($r['side_effects'] ? substr($r['side_effects'],0,60).'…' : '—') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- CHILD NUTRITION -->
<?php elseif ($type === 'child_nutrition'): ?>
<table>
    <thead><tr><th>Visit Date</th><th>Age</th><th>Weight</th><th>Height</th><th>MUAC</th><th>WAZ</th><th>HAZ</th><th>Stunting</th><th>Wasting</th><th>Vit A</th><th>Deworming</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="11" style="padding:20px;text-align:center;color:#a0a0a0;font-style:italic;">No records found.</td></tr>
    <?php else: foreach ($rows as $r):
        $stunCls = match($r['stunting_status']??'Not assessed'){ 'Normal'=>'ok','Mild','Moderate'=>'warn','Severe'=>'danger', default=>'' };
        $wastCls = match($r['wasting_status']??'Not assessed'){ 'Normal'=>'ok','Mild','Moderate'=>'warn','Severe'=>'danger', default=>'' };
    ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <td><?= isset($r['age_months'])&&$r['age_months']!==null ? h($r['age_months']).' mo' : '—' ?></td>
            <td><?= $r['weight_kg'] ? h($r['weight_kg']).' kg' : '—' ?></td>
            <td><?= $r['height_cm'] ? h($r['height_cm']).' cm' : '—' ?></td>
            <td><?= $r['muac_cm'] ? h($r['muac_cm']).' cm' : '—' ?></td>
            <td class="mono"><?= $r['waz']!==null ? h($r['waz']) : '—' ?></td>
            <td class="mono"><?= $r['haz']!==null ? h($r['haz']) : '—' ?></td>
            <td><?= $r['stunting_status'] ? '<span class="badge '.$stunCls.'">'.h($r['stunting_status']).'</span>' : '—' ?></td>
            <td><?= $r['wasting_status'] ? '<span class="badge '.$wastCls.'">'.h($r['wasting_status']).'</span>' : '—' ?></td>
            <td><?= $r['vita_supplemented'] ? '<span class="badge ok">✓</span>' : '—' ?></td>
            <td><?= $r['deworming_done'] ? '<span class="badge ok">✓</span>' : '—' ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<?php else: ?>
<!-- MATERNAL (general visits) -->
<table>
    <thead><tr><th>Visit Date</th><th>Patient</th><th>Notes</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="3" style="padding:20px;text-align:center;color:#a0a0a0;font-style:italic;">No records found.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <td style="font-weight:600;"><?= h($r['resident_name'] ?? '—') ?></td>
            <td><?= h($r['notes'] ?? '—') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
<?php endif; ?>

<div class="sigs">
    <div><div class="sig-line"></div><div class="sig-name"><?= h($_SESSION['name'] ?? 'HC Nurse') ?></div><div class="sig-role">Health Center Nurse — Prepared by</div></div>
    <div><div class="sig-line"></div><div class="sig-name">Barangay Official</div><div class="sig-role">Noted by — Barangay Bombongan</div></div>
</div>

<div class="footer">
    <span>Barangay Bombongan Health Center · Official Record</span>
    <span>Generated: <?= date('d F Y h:i A') ?></span>
</div>
</body>
</html>
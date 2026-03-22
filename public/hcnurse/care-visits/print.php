<?php
/**
 * Care Visits Print Page — v4
 * Fixed: explicit columns, proper error reporting, debug mode, fallback queries
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type  = $_GET['type'] ?? 'general';
$rid   = (int)($_GET['resident_id'] ?? 0);
$from  = $_GET['from'] ?? date('Y-01-01');
$to    = $_GET['to']   ?? date('Y-m-d');
$debug = isset($_GET['debug']);

$allowed = ['general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','other'];
if (!in_array($type, $allowed, true)) $type = 'general';

$labels = [
    'general'         => 'General Care Record',
    'maternal'        => 'Maternal Health Record',
    'family_planning' => 'Family Planning Record',
    'prenatal'        => 'Prenatal / Antenatal Care Record',
    'postnatal'       => 'Postnatal / Postnatal Care Record',
    'child_nutrition' => 'Child Nutrition Monitoring Record',
    'immunization'    => 'Immunization Record',
    'other'           => 'Other Care Record',
];
$pageTitle = $labels[$type] ?? 'Care Record';

function h($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

/* ── Resident info ── */
$residentName = $birthdate = $address = $gender = '';
if ($rid > 0) {
    $rStmt = $conn->prepare("
        SELECT CONCAT_WS(' ', first_name, middle_name, last_name) AS name,
               birthdate, address, gender
        FROM residents WHERE id = ? LIMIT 1
    ");
    $rStmt->bind_param('i', $rid);
    $rStmt->execute();
    $rRow = $rStmt->get_result()->fetch_assoc();
    if ($rRow) {
        $residentName = $rRow['name'];
        $birthdate    = $rRow['birthdate'];
        $address      = $rRow['address'];
        $gender       = $rRow['gender'];
    }
}

$age = '';
if ($birthdate) {
    $age = (new DateTime($birthdate))->diff(new DateTime())->y . ' yrs old';
}

/* ── Safe query runner ── */
$queryErrors = [];
function runQuery(mysqli $conn, string $sql, string $types, array $params): array {
    global $debug, $queryErrors;
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $queryErrors[] = 'Prepare failed: ' . $conn->error . "\n\nSQL:\n" . $sql;
            return [];
        }
        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        return $rows;
    } catch (\Throwable $e) {
        $queryErrors[] = $e->getMessage() . "\n\nSQL:\n" . $sql;
        return [];
    }
}

/* ── Build rows ── */
$rows = [];

if ($type === 'immunization') {

    $sql    = "SELECT i.id, i.resident_id, i.vaccine_name, i.dose,
                      i.date_given, i.next_schedule, i.administered_by,
                      i.batch_number, i.expiry_date, i.site_given, i.route,
                      i.adverse_reaction, i.is_defaulter, i.catch_up,
                      i.remarks, i.care_visit_id,
                      CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
               FROM immunizations i
               LEFT JOIN residents r ON r.id = i.resident_id
               WHERE i.date_given BETWEEN ? AND ?";
    $params = [$from, $to];
    $types  = 'ss';
    if ($rid > 0) { $sql .= " AND i.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY i.date_given DESC";
    $rows = runQuery($conn, $sql, $types, $params);

} elseif ($type === 'prenatal') {

    /* With module table */
    $sql    = "SELECT cv.id AS cv_id, cv.visit_date, cv.notes,
                      CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                      pv.visit_number, pv.lmp_date, pv.edd_date,
                      pv.aog_weeks, pv.weight_kg, pv.bp_systolic, pv.bp_diastolic,
                      pv.fundal_height_cm, pv.fetal_heart_rate, pv.fetal_presentation,
                      pv.tt_dose, pv.risk_level, pv.health_worker,
                      pv.chief_complaint, pv.assessment, pv.plan
               FROM care_visits cv
               INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
               LEFT JOIN prenatal_visit pv ON pv.care_visit_id = cv.id
               WHERE cv.care_type = 'prenatal' AND cv.visit_date BETWEEN ? AND ?";
    $params = [$from, $to]; $types = 'ss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    /* Fallback: care_visits only (if module table missing/broken) */
    if (empty($rows) && empty($queryErrors)) {
        $sql2   = "SELECT cv.id AS cv_id, cv.visit_date, cv.notes,
                          CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                          NULL AS visit_number, NULL AS aog_weeks, NULL AS weight_kg,
                          NULL AS bp_systolic, NULL AS bp_diastolic,
                          NULL AS fetal_heart_rate, NULL AS fundal_height_cm,
                          NULL AS fetal_presentation, NULL AS risk_level,
                          NULL AS tt_dose, NULL AS health_worker
                   FROM care_visits cv
                   INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
                   WHERE cv.care_type = 'prenatal' AND cv.visit_date BETWEEN ? AND ?";
        $p2 = [$from, $to]; $t2 = 'ss';
        if ($rid > 0) { $sql2 .= " AND cv.resident_id = ?"; $p2[] = $rid; $t2 .= 'i'; }
        $sql2 .= " ORDER BY cv.visit_date DESC";
        $rows = runQuery($conn, $sql2, $t2, $p2);
    }

} elseif ($type === 'postnatal') {

    $sql    = "SELECT cv.id AS cv_id, cv.visit_date, cv.notes,
                      CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                      pnv.visit_number, pnv.delivery_date, pnv.delivery_type,
                      pnv.bp_systolic, pnv.bp_diastolic,
                      pnv.lochia_type, pnv.breastfeeding_status,
                      pnv.ppd_score, pnv.newborn_weight_g,
                      pnv.apgar_1min, pnv.apgar_5min, pnv.health_worker
               FROM care_visits cv
               INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
               LEFT JOIN postnatal_visit pnv ON pnv.care_visit_id = cv.id
               WHERE cv.care_type = 'postnatal' AND cv.visit_date BETWEEN ? AND ?";
    $params = [$from, $to]; $types = 'ss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    if (empty($rows) && empty($queryErrors)) {
        $sql2   = "SELECT cv.id AS cv_id, cv.visit_date, cv.notes,
                          CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                          NULL AS visit_number, NULL AS delivery_type,
                          NULL AS bp_systolic, NULL AS bp_diastolic,
                          NULL AS lochia_type, NULL AS breastfeeding_status,
                          NULL AS ppd_score, NULL AS newborn_weight_g,
                          NULL AS apgar_1min, NULL AS apgar_5min, NULL AS health_worker
                   FROM care_visits cv
                   INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
                   WHERE cv.care_type = 'postnatal' AND cv.visit_date BETWEEN ? AND ?";
        $p2 = [$from, $to]; $t2 = 'ss';
        if ($rid > 0) { $sql2 .= " AND cv.resident_id = ?"; $p2[] = $rid; $t2 .= 'i'; }
        $sql2 .= " ORDER BY cv.visit_date DESC";
        $rows = runQuery($conn, $sql2, $t2, $p2);
    }

} elseif ($type === 'family_planning') {

    $sql    = "SELECT cv.id AS cv_id, cv.visit_date, cv.notes,
                      CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                      fpr.method, fpr.method_other,
                      fpr.next_supply_date, fpr.next_checkup_date,
                      fpr.is_new_acceptor, fpr.is_method_switch,
                      fpr.side_effects, fpr.pills_given, fpr.health_worker
               FROM care_visits cv
               INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
               LEFT JOIN family_planning_record fpr ON fpr.care_visit_id = cv.id
               WHERE cv.care_type = 'family_planning' AND cv.visit_date BETWEEN ? AND ?";
    $params = [$from, $to]; $types = 'ss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    if (empty($rows) && empty($queryErrors)) {
        $sql2   = "SELECT cv.id AS cv_id, cv.visit_date, cv.notes,
                          CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                          NULL AS method, NULL AS method_other,
                          NULL AS next_supply_date, NULL AS next_checkup_date,
                          NULL AS is_new_acceptor, NULL AS is_method_switch,
                          NULL AS side_effects, NULL AS pills_given, NULL AS health_worker
                   FROM care_visits cv
                   INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
                   WHERE cv.care_type = 'family_planning' AND cv.visit_date BETWEEN ? AND ?";
        $p2 = [$from, $to]; $t2 = 'ss';
        if ($rid > 0) { $sql2 .= " AND cv.resident_id = ?"; $p2[] = $rid; $t2 .= 'i'; }
        $sql2 .= " ORDER BY cv.visit_date DESC";
        $rows = runQuery($conn, $sql2, $t2, $p2);
    }

} elseif ($type === 'child_nutrition') {

    $sql    = "SELECT cv.id AS cv_id, cv.visit_date, cv.notes,
                      CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                      cnv.age_months, cnv.weight_kg, cnv.height_cm, cnv.muac_cm,
                      cnv.waz, cnv.haz,
                      cnv.stunting_status, cnv.wasting_status,
                      cnv.vita_supplemented, cnv.deworming_done, cnv.health_worker
               FROM care_visits cv
               INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
               LEFT JOIN child_nutrition_visit cnv ON cnv.care_visit_id = cv.id
               WHERE cv.care_type = 'child_nutrition' AND cv.visit_date BETWEEN ? AND ?";
    $params = [$from, $to]; $types = 'ss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    if (empty($rows) && empty($queryErrors)) {
        $sql2   = "SELECT cv.id AS cv_id, cv.visit_date, cv.notes,
                          CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                          NULL AS age_months, NULL AS weight_kg, NULL AS height_cm, NULL AS muac_cm,
                          NULL AS waz, NULL AS haz,
                          NULL AS stunting_status, NULL AS wasting_status,
                          NULL AS vita_supplemented, NULL AS deworming_done, NULL AS health_worker
                   FROM care_visits cv
                   INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
                   WHERE cv.care_type = 'child_nutrition' AND cv.visit_date BETWEEN ? AND ?";
        $p2 = [$from, $to]; $t2 = 'ss';
        if ($rid > 0) { $sql2 .= " AND cv.resident_id = ?"; $p2[] = $rid; $t2 .= 'i'; }
        $sql2 .= " ORDER BY cv.visit_date DESC";
        $rows = runQuery($conn, $sql2, $t2, $p2);
    }

} else {
    /* general, maternal, other */
    $sql    = "SELECT cv.id, cv.visit_date, cv.notes, cv.care_type,
                      CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
               FROM care_visits cv
               INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
               WHERE cv.care_type = ? AND cv.visit_date BETWEEN ? AND ?";
    $params = [$type, $from, $to]; $types = 'sss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC";
    $rows = runQuery($conn, $sql, $types, $params);
}
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
.no-print{margin-bottom:12px;display:flex;gap:8px;align-items:center;}
@media print{.no-print{display:none;}body{padding:12px 18px;}}
.lh{border-bottom:3px solid #2d5a27;padding-bottom:10px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:flex-end;}
.lh-brgy{font-family:'Source Serif 4';font-size:16px;font-weight:700;color:#2d5a27;}
.lh-addr{font-size:9px;color:#888;margin-top:2px;}
.lh-right{text-align:right;}
.lh-doc-title{font-family:'Source Serif 4';font-size:13px;font-weight:700;}
.lh-doc-sub{font-size:9px;color:#888;margin-top:2px;}
.res-block{margin-bottom:14px;padding:10px 14px;background:#f9f7f3;border:1px solid #d8d4cc;border-left:3px solid #2d5a27;display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px 16px;}
.rf-lbl{font-size:7.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#a0a0a0;margin-bottom:2px;}
.rf-val{font-size:12px;font-weight:600;color:#1a1a1a;}
.summary{display:flex;gap:0;margin-bottom:14px;border:1px solid #e0e0e0;overflow:hidden;border-radius:2px;}
.sum-cell{flex:1;padding:8px 14px;text-align:center;border-right:1px solid #e0e0e0;}
.sum-cell:last-child{border-right:none;}
.sum-val{font-family:'Source Code Pro';font-size:18px;font-weight:700;color:#1a1a1a;line-height:1;margin-bottom:2px;}
.sum-lbl{font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#a0a0a0;}
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
.no-data{padding:28px;text-align:center;color:#a0a0a0;font-style:italic;border:1px dashed #d8d4cc;border-radius:2px;margin-top:4px;}
.db-error{padding:14px 16px;background:#fdeeed;border:1px solid #fca5a5;border-radius:2px;color:#7a1f1a;font-size:11px;margin-bottom:12px;}
.db-error strong{display:block;font-size:12px;margin-bottom:4px;}
.db-error pre{font-size:9.5px;margin-top:6px;white-space:pre-wrap;word-break:break-all;background:#fff5f5;padding:8px;border-radius:2px;}
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:6px 18px;background:#2d5a27;color:#fff;border:none;border-radius:2px;font-weight:700;cursor:pointer;">↗ Print / Save PDF</button>
    <button onclick="window.close()" style="padding:6px 18px;background:#fff;border:1.5px solid #b8b4ac;border-radius:2px;cursor:pointer;">Close</button>
    <?php if (!$debug): ?>
    <a href="?<?= h(http_build_query(array_merge($_GET, ['debug' => '1']))) ?>"
       style="padding:6px 14px;background:#fff3cd;border:1.5px solid #e0a800;border-radius:2px;font-size:10px;font-weight:700;color:#7a5700;text-decoration:none;margin-left:8px;">
       🔍 Debug (click if no data shows)
    </a>
    <?php else: ?>
    <span style="font-size:10px;color:#7a5700;font-weight:700;margin-left:8px;">⚠ DEBUG MODE ON</span>
    <?php endif; ?>
</div>

<?php if (!empty($queryErrors)): ?>
    <?php foreach ($queryErrors as $err): ?>
    <div class="db-error">
        <strong>⚠ Database Error</strong>
        <pre><?= h($err) ?></pre>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Letterhead -->
<div class="lh">
    <div>
        <div class="lh-brgy">Barangay Bombongan</div>
        <div class="lh-addr">Morong, Rizal · Barangay Health Center</div>
    </div>
    <div class="lh-right">
        <div class="lh-doc-title"><?= h($pageTitle) ?></div>
        <div class="lh-doc-sub">
            Period: <?= h($from) ?> to <?= h($to) ?> &nbsp;·&nbsp;
            Printed: <?= date('d F Y, h:i A') ?>
        </div>
    </div>
</div>

<?php if ($residentName): ?>
<div class="res-block">
    <div><div class="rf-lbl">Patient</div><div class="rf-val"><?= h($residentName) ?></div></div>
    <div><div class="rf-lbl">Age / Birthdate</div><div class="rf-val"><?= h($age) ?><?= $birthdate ? ' · '.h($birthdate) : '' ?></div></div>
    <div><div class="rf-lbl">Address</div><div class="rf-val"><?= h($address ?: '—') ?></div></div>
</div>
<?php endif; ?>

<div class="summary">
    <div class="sum-cell"><div class="sum-val"><?= count($rows) ?></div><div class="sum-lbl">Total Records</div></div>
    <div class="sum-cell"><div class="sum-val"><?= h($from) ?></div><div class="sum-lbl">From</div></div>
    <div class="sum-cell"><div class="sum-val"><?= h($to) ?></div><div class="sum-lbl">To</div></div>
</div>

<!-- ════ IMMUNIZATION ════ -->
<?php if ($type === 'immunization'): ?>
<table>
    <thead><tr>
        <th>Date Given</th>
        <?php if (!$residentName): ?><th>Patient</th><?php endif; ?>
        <th>Vaccine</th><th>Dose</th><th>Route</th>
        <th>Batch No.</th><th>Next Schedule</th>
        <th>Administered By</th><th>Adverse Reaction</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="9"><div class="no-data">No immunization records found for this period.</div></td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="mono"><?= h($r['date_given']) ?></td>
            <?php if (!$residentName): ?><td style="font-weight:600;"><?= h($r['resident_name']) ?></td><?php endif; ?>
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

<!-- ════ PRENATAL ════ -->
<?php elseif ($type === 'prenatal'): ?>
<table>
    <thead><tr>
        <th>Visit Date</th>
        <?php if (!$residentName): ?><th>Patient</th><?php endif; ?>
        <th>Visit #</th><th>AOG</th><th>Weight</th><th>BP</th>
        <th>FHR</th><th>FH</th><th>Risk</th><th>TT</th><th>Worker</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="11"><div class="no-data">No prenatal records found for this period.</div></td></tr>
    <?php else: foreach ($rows as $r):
        $riskCls = ($r['risk_level'] ?? '') === 'High' ? 'danger' : (($r['risk_level'] ?? '') === 'Moderate' ? 'warn' : 'ok');
    ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <?php if (!$residentName): ?><td style="font-weight:600;"><?= h($r['resident_name']) ?></td><?php endif; ?>
            <td><?= ($r['visit_number'] ?? '') ? 'Visit '.h($r['visit_number']) : '—' ?></td>
            <td><?= ($r['aog_weeks'] ?? '') ? h($r['aog_weeks']).' wks' : '—' ?></td>
            <td><?= ($r['weight_kg'] ?? '') ? h($r['weight_kg']).' kg' : '—' ?></td>
            <td class="mono"><?= (($r['bp_systolic'] ?? '') && ($r['bp_diastolic'] ?? '')) ? h($r['bp_systolic']).'/'.h($r['bp_diastolic']) : '—' ?></td>
            <td><?= ($r['fetal_heart_rate'] ?? '') ? h($r['fetal_heart_rate']).' bpm' : '—' ?></td>
            <td><?= ($r['fundal_height_cm'] ?? '') ? h($r['fundal_height_cm']).' cm' : '—' ?></td>
            <td><?php if ($r['risk_level'] ?? ''): ?><span class="badge <?= $riskCls ?>"><?= h($r['risk_level']) ?></span><?php else: ?>—<?php endif; ?></td>
            <td><?= h($r['tt_dose'] ?? '—') ?></td>
            <td><?= h($r['health_worker'] ?? '—') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- ════ POSTNATAL ════ -->
<?php elseif ($type === 'postnatal'): ?>
<table>
    <thead><tr>
        <th>Visit Date</th>
        <?php if (!$residentName): ?><th>Patient</th><?php endif; ?>
        <th>Visit #</th><th>Delivery Type</th><th>BP</th>
        <th>Lochia</th><th>BF Status</th><th>PPD Score</th>
        <th>NB Weight</th><th>APGAR</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="10"><div class="no-data">No postnatal records found for this period.</div></td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <?php if (!$residentName): ?><td style="font-weight:600;"><?= h($r['resident_name']) ?></td><?php endif; ?>
            <td><?= ($r['visit_number'] ?? '') ? 'PNC '.h($r['visit_number']) : '—' ?></td>
            <td><?= h($r['delivery_type'] ?? '—') ?></td>
            <td class="mono"><?= (($r['bp_systolic'] ?? '') && ($r['bp_diastolic'] ?? '')) ? h($r['bp_systolic']).'/'.h($r['bp_diastolic']) : '—' ?></td>
            <td><?= h($r['lochia_type'] ?? '—') ?></td>
            <td><?= h($r['breastfeeding_status'] ?? '—') ?></td>
            <td><?= isset($r['ppd_score']) && $r['ppd_score'] !== null ? h($r['ppd_score']) : '—' ?></td>
            <td><?= ($r['newborn_weight_g'] ?? '') ? number_format((int)$r['newborn_weight_g']).'g' : '—' ?></td>
            <td><?= (isset($r['apgar_1min']) && $r['apgar_1min'] !== null) ? h($r['apgar_1min']).' / '.h($r['apgar_5min']) : '—' ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- ════ FAMILY PLANNING ════ -->
<?php elseif ($type === 'family_planning'): ?>
<table>
    <thead><tr>
        <th>Visit Date</th>
        <?php if (!$residentName): ?><th>Patient</th><?php endif; ?>
        <th>Method</th><th>New Acceptor</th><th>Method Switch</th>
        <th>Next Supply</th><th>Next Checkup</th>
        <th>Pills Given</th><th>Side Effects</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="9"><div class="no-data">No family planning records found for this period.</div></td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <?php if (!$residentName): ?><td style="font-weight:600;"><?= h($r['resident_name']) ?></td><?php endif; ?>
            <td style="font-weight:600;"><?= h($r['method'] ?? '—') ?><?= ($r['method_other'] ?? '') ? ' ('.h($r['method_other']).')' : '' ?></td>
            <td><?= ($r['is_new_acceptor'] ?? 0) ? '<span class="badge ok">Yes</span>' : 'No' ?></td>
            <td><?= ($r['is_method_switch'] ?? 0) ? '<span class="badge warn">Yes</span>' : 'No' ?></td>
            <td class="mono"><?= h($r['next_supply_date'] ?? '—') ?></td>
            <td class="mono"><?= h($r['next_checkup_date'] ?? '—') ?></td>
            <td><?= (int)($r['pills_given'] ?? 0) ?> pks</td>
            <td style="color:#d14520;font-size:10px;"><?= h(($r['side_effects'] ?? '') ? mb_substr($r['side_effects'], 0, 60).'…' : '—') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- ════ CHILD NUTRITION ════ -->
<?php elseif ($type === 'child_nutrition'): ?>
<table>
    <thead><tr>
        <th>Visit Date</th>
        <?php if (!$residentName): ?><th>Patient</th><?php endif; ?>
        <th>Age</th><th>Weight</th><th>Height</th><th>MUAC</th>
        <th>WAZ</th><th>HAZ</th><th>Stunting</th><th>Wasting</th>
        <th>Vit A</th><th>Deworming</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="12"><div class="no-data">No child nutrition records found for this period.</div></td></tr>
    <?php else: foreach ($rows as $r):
        $stunCls = match($r['stunting_status'] ?? '') { 'Normal'=>'ok','Mild'=>'warn','Moderate'=>'warn','Severe'=>'danger', default=>'' };
        $wastCls = match($r['wasting_status']  ?? '') { 'Normal'=>'ok','Mild'=>'warn','Moderate'=>'warn','Severe'=>'danger', default=>'' };
    ?>
        <tr>
            <td class="mono"><?= h($r['visit_date']) ?></td>
            <?php if (!$residentName): ?><td style="font-weight:600;"><?= h($r['resident_name']) ?></td><?php endif; ?>
            <td><?= isset($r['age_months']) && $r['age_months'] !== null ? h($r['age_months']).' mo' : '—' ?></td>
            <td><?= ($r['weight_kg'] ?? '') ? h($r['weight_kg']).' kg' : '—' ?></td>
            <td><?= ($r['height_cm'] ?? '') ? h($r['height_cm']).' cm' : '—' ?></td>
            <td><?= ($r['muac_cm']   ?? '') ? h($r['muac_cm']).' cm'   : '—' ?></td>
            <td class="mono"><?= (($r['waz'] ?? '') !== '') ? h($r['waz']) : '—' ?></td>
            <td class="mono"><?= (($r['haz'] ?? '') !== '') ? h($r['haz']) : '—' ?></td>
            <td><?= ($r['stunting_status'] ?? '') ? '<span class="badge '.$stunCls.'">'.h($r['stunting_status']).'</span>' : '—' ?></td>
            <td><?= ($r['wasting_status']  ?? '') ? '<span class="badge '.$wastCls.'">'.h($r['wasting_status']).'</span>'  : '—' ?></td>
            <td><?= ($r['vita_supplemented'] ?? 0) ? '<span class="badge ok">✓</span>' : '—' ?></td>
            <td><?= ($r['deworming_done']    ?? 0) ? '<span class="badge ok">✓</span>' : '—' ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- ════ GENERAL / MATERNAL / OTHER ════ -->
<?php else: ?>
<table>
    <thead><tr>
        <th>Visit Date</th>
        <?php if (!$residentName): ?><th>Patient</th><?php endif; ?>
        <th>Notes / Remarks</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="3"><div class="no-data">No <?= h(str_replace('_', ' ', $type)) ?> records found for this period.</div></td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="mono" style="white-space:nowrap;"><?= h($r['visit_date']) ?></td>
            <?php if (!$residentName): ?><td style="font-weight:600;"><?= h($r['resident_name']) ?></td><?php endif; ?>
            <td style="color:#5a5a5a;"><?= h($r['notes'] ?? '—') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
<?php endif; ?>

<div class="sigs">
    <div>
        <div class="sig-line"></div>
        <div class="sig-name"><?= h($_SESSION['name'] ?? 'HC Nurse') ?></div>
        <div class="sig-role">Health Center Nurse — Prepared by</div>
    </div>
    <div>
        <div class="sig-line"></div>
        <div class="sig-name">Barangay Official</div>
        <div class="sig-role">Noted by — Barangay Bombongan</div>
    </div>
</div>

<div class="footer">
    <span>Barangay Bombongan Health Center · Official Record</span>
    <span>Generated: <?= date('d F Y h:i A') ?></span>
</div>
</body>
</html>
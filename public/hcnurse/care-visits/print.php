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

/** Notes/remarks for official print: preserve line breaks; empty → blank cell. */
function printNoteText(?string $s): string {
    $s = trim((string)($s ?? ''));
    if ($s === '') {
        return '';
    }
    return nl2br(h($s));
}

/** Optional table cell: empty or whitespace-only → blank (no placeholder dash). */
function cellStr(mixed $v): string {
    if ($v === null) {
        return '';
    }
    if (is_string($v)) {
        $t = trim($v);
        return $t === '' ? '' : h($t);
    }
    if (is_bool($v)) {
        return $v ? '1' : '';
    }
    if (is_int($v) || is_float($v)) {
        return h((string)$v);
    }
    return h(trim((string)$v)) === '' ? '' : h((string)$v);
}

require_once __DIR__ . '/print_clinical_render.php';

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

/**
 * Standalone consultations (+ consultation_detail) for this programme type.
 * Excludes consults already merged via care_visit_id onto a care_visits row in this report.
 */
function fetchConsultationsRichForPrint(
    mysqli $conn,
    string $type,
    string $from,
    string $to,
    int $rid,
    array $excludeCareVisitIds
): array {
    $cp = care_print_consult_projection('c');
    $dp = care_print_detail_projection();
    $sql = "SELECT {$cp}, {$dp},
                   c.consultation_date AS visit_date,
                   c.consult_type,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                   'consultation' AS _source
            FROM consultations c
            INNER JOIN residents r ON r.id = c.resident_id AND r.deleted_at IS NULL
            LEFT JOIN consultation_detail cd ON cd.consultation_id = c.id
            WHERE c.consult_type = ? AND c.consultation_date BETWEEN ? AND ?";
    $params = [$type, $from, $to];
    $types  = 'sss';
    if ($rid > 0) {
        $sql .= " AND c.resident_id = ?";
        $params[] = $rid;
        $types   .= 'i';
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $excludeCareVisitIds))));
    if ($ids !== []) {
        $in = implode(',', $ids);
        $sql .= " AND (c.care_visit_id IS NULL OR c.care_visit_id NOT IN ($in))";
    }
    $sql .= " ORDER BY c.consultation_date DESC, c.id DESC";
    return runQuery($conn, $sql, $types, $params);
}

function careVisitIdsFromRows(array $rows, string $idKey = 'cv_id'): array {
    $out = [];
    foreach ($rows as $r) {
        $v = (int)($r[$idKey] ?? $r['id'] ?? 0);
        if ($v > 0) {
            $out[] = $v;
        }
    }
    return $out;
}

function sortRowsByVisitDateDesc(array &$rows): void {
    usort($rows, static function ($a, $b) {
        $da = $a['visit_date'] ?? '';
        $db = $b['visit_date'] ?? '';
        return strcmp((string)$db, (string)$da);
    });
}

/* ── Build rows ── */
$rows = [];

$joinConsCd = "
    LEFT JOIN consultations cons ON cons.id = (
        SELECT MAX(c2.id) FROM consultations c2 WHERE c2.care_visit_id = cv.id
    )
    LEFT JOIN consultation_detail cd ON cd.consultation_id = cons.id
";

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

    $cp = care_print_consult_projection('cons');
    $dp = care_print_detail_projection();
    $sql = "SELECT cv.id AS cv_id,
                   cv.visit_date AS visit_date,
                   cv.notes AS cv_notes,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                   pv.visit_number, pv.lmp_date, pv.edd_date,
                   pv.aog_weeks, pv.weight_kg, pv.bp_systolic, pv.bp_diastolic,
                   pv.fundal_height_cm, pv.fetal_heart_rate, pv.fetal_presentation,
                   pv.tt_dose, pv.risk_level, pv.health_worker,
                   pv.chief_complaint, pv.assessment, pv.plan,
                   {$cp}, {$dp},
                   'care_visit' AS _source
            FROM care_visits cv
            INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
            LEFT JOIN prenatal_visit pv ON pv.care_visit_id = cv.id
            {$joinConsCd}
            WHERE cv.care_type = 'prenatal' AND cv.visit_date BETWEEN ? AND ?";
    $params = [$from, $to]; $types = 'ss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC, cv.id DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    foreach (fetchConsultationsRichForPrint($conn, 'prenatal', $from, $to, $rid, careVisitIdsFromRows($rows, 'cv_id')) as $c) {
        $rows[] = $c;
    }
    sortRowsByVisitDateDesc($rows);

} elseif ($type === 'postnatal') {

    $cp = care_print_consult_projection('cons');
    $dp = care_print_detail_projection();
    $sql = "SELECT cv.id AS cv_id,
                   cv.visit_date AS visit_date,
                   cv.notes AS cv_notes,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                   pnv.visit_number, pnv.delivery_date, pnv.delivery_type,
                   pnv.bp_systolic, pnv.bp_diastolic,
                   pnv.lochia_type, pnv.breastfeeding_status,
                   pnv.ppd_score, pnv.newborn_weight_g,
                   pnv.apgar_1min, pnv.apgar_5min, pnv.health_worker,
                   {$cp}, {$dp},
                   'care_visit' AS _source
            FROM care_visits cv
            INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
            LEFT JOIN postnatal_visit pnv ON pnv.care_visit_id = cv.id
            {$joinConsCd}
            WHERE cv.care_type = 'postnatal' AND cv.visit_date BETWEEN ? AND ?";
    $params = [$from, $to]; $types = 'ss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC, cv.id DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    foreach (fetchConsultationsRichForPrint($conn, 'postnatal', $from, $to, $rid, careVisitIdsFromRows($rows, 'cv_id')) as $c) {
        $rows[] = $c;
    }
    sortRowsByVisitDateDesc($rows);

} elseif ($type === 'family_planning') {

    $cp = care_print_consult_projection('cons');
    $dp = care_print_detail_projection();
    $sql = "SELECT cv.id AS cv_id,
                   cv.visit_date AS visit_date,
                   cv.notes AS cv_notes,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                   fpr.method, fpr.method_other,
                   fpr.next_supply_date, fpr.next_checkup_date,
                   fpr.is_new_acceptor, fpr.is_method_switch,
                   fpr.side_effects, fpr.pills_given, fpr.health_worker,
                   {$cp}, {$dp},
                   'care_visit' AS _source
            FROM care_visits cv
            INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
            LEFT JOIN family_planning_record fpr ON fpr.care_visit_id = cv.id
            {$joinConsCd}
            WHERE cv.care_type = 'family_planning' AND cv.visit_date BETWEEN ? AND ?";
    $params = [$from, $to]; $types = 'ss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC, cv.id DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    foreach (fetchConsultationsRichForPrint($conn, 'family_planning', $from, $to, $rid, careVisitIdsFromRows($rows, 'cv_id')) as $c) {
        $rows[] = $c;
    }
    sortRowsByVisitDateDesc($rows);

} elseif ($type === 'child_nutrition') {

    $cp = care_print_consult_projection('cons');
    $dp = care_print_detail_projection();
    $sql = "SELECT cv.id AS cv_id,
                   cv.visit_date AS visit_date,
                   cv.notes AS cv_notes,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                   cnv.age_months, cnv.weight_kg, cnv.height_cm, cnv.muac_cm,
                   cnv.waz, cnv.haz,
                   cnv.stunting_status, cnv.wasting_status,
                   cnv.vita_supplemented, cnv.deworming_done, cnv.health_worker,
                   {$cp}, {$dp},
                   'care_visit' AS _source
            FROM care_visits cv
            INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
            LEFT JOIN child_nutrition_visit cnv ON cnv.care_visit_id = cv.id
            {$joinConsCd}
            WHERE cv.care_type = 'child_nutrition' AND cv.visit_date BETWEEN ? AND ?";
    $params = [$from, $to]; $types = 'ss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC, cv.id DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    foreach (fetchConsultationsRichForPrint($conn, 'child_nutrition', $from, $to, $rid, careVisitIdsFromRows($rows, 'cv_id')) as $c) {
        $rows[] = $c;
    }
    sortRowsByVisitDateDesc($rows);

} else {
    /* general, maternal, other */
    $cp = care_print_consult_projection('cons');
    $dp = care_print_detail_projection();
    $sql = "SELECT cv.id AS cv_id,
                   cv.visit_date AS visit_date,
                   cv.notes AS cv_notes,
                   cv.care_type,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                   {$cp}, {$dp},
                   'care_visit' AS _source
            FROM care_visits cv
            INNER JOIN residents r ON r.id = cv.resident_id AND r.deleted_at IS NULL
            {$joinConsCd}
            WHERE cv.care_type = ? AND cv.visit_date BETWEEN ? AND ?";
    $params = [$type, $from, $to]; $types = 'sss';
    if ($rid > 0) { $sql .= " AND cv.resident_id = ?"; $params[] = $rid; $types .= 'i'; }
    $sql .= " ORDER BY cv.visit_date DESC, cv.id DESC";
    $rows = runQuery($conn, $sql, $types, $params);

    foreach (fetchConsultationsRichForPrint($conn, $type, $from, $to, $rid, careVisitIdsFromRows($rows, 'cv_id')) as $c) {
        $rows[] = $c;
    }
    sortRowsByVisitDateDesc($rows);
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
.note-cell{line-height:1.4;vertical-align:top;}
.visit-stack{display:flex;flex-direction:column;gap:14px;}
.visit-card{border:1px solid #d0ccc4;border-radius:4px;background:#fff;break-inside:avoid;page-break-inside:avoid;}
.vc-head{display:flex;flex-wrap:wrap;gap:8px 16px;align-items:center;padding:10px 14px;background:linear-gradient(180deg,#f7f5f0 0%,#f0ede6 100%);border-bottom:1px solid #d8d4cc;}
.vc-date{font-weight:700;font-size:11px;color:#1a1a1a;}
.vc-patient{font-weight:600;font-size:11px;}
.vc-prog{font-size:9px;color:#5a5a5a;font-weight:600;}
.vc-src{margin-left:auto;font-size:7.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#5a6b52;max-width:55%;text-align:right;line-height:1.3;}
.vc-body{padding:12px 14px 14px;}
.vc-sec{margin-bottom:14px;}
.vc-sec:last-child{margin-bottom:0;}
.vc-sec-title{font-size:7.5px;font-weight:700;letter-spacing:.95px;text-transform:uppercase;color:#2d5a27;margin-bottom:7px;padding-bottom:4px;border-bottom:1px solid #e5e1d8;}
.vc-dl{display:grid;grid-template-columns:minmax(120px,34%) 1fr;gap:5px 14px;font-size:10px;line-height:1.35;}
.vc-dl dt{color:#6b6560;font-weight:600;margin:0;}
.vc-dl dd{margin:0;color:#1a1a1a;}
.vc-narr{font-size:10px;line-height:1.45;color:#333;}
.vc-empty{font-size:10px;color:#9a9590;font-style:italic;padding:8px 0;}
@media print{.visit-card{box-shadow:none;}.vc-head{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}
.db-error{padding:14px 16px;background:#fdeeed;border:1px solid #fca5a5;border-radius:2px;color:#7a1f1a;font-size:11px;margin-bottom:12px;}
.db-error strong{display:block;font-size:12px;margin-bottom:4px;}
.db-error pre{font-size:9.5px;margin-top:6px;white-space:pre-wrap;word-break:break-all;background:#fff5f5;padding:8px;border-radius:2px;}
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:6px 18px;background:#2d5a27;color:#fff;border:none;border-radius:2px;font-weight:700;cursor:pointer;">↗ Print / Save PDF</button>
    <button onclick="window.close()" style="padding:6px 18px;background:#fff;border:1.5px solid #b8b4ac;border-radius:2px;cursor:pointer;">Close</button>
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
    <div><div class="rf-lbl">Address</div><div class="rf-val"><?= cellStr($address) ?></div></div>
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
            <td><?= cellStr($r['dose'] ?? null) ?></td>
            <td><?= cellStr($r['route'] ?? null) ?></td>
            <td class="mono"><?= cellStr($r['batch_number'] ?? null) ?></td>
            <td class="mono"><?= cellStr($r['next_schedule'] ?? null) ?></td>
            <td><?= cellStr($r['administered_by'] ?? null) ?></td>
            <td style="color:#d14520;"><?= cellStr($r['adverse_reaction'] ?? null) ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- ════ PROGRAMME VISITS (care_visits modules + linked / standalone consultations) ════ -->
<?php else: ?>
<div class="visit-stack">
    <?php if (!$rows): ?>
        <div class="no-data">No <?= h(str_replace('_', ' ', $type)) ?> records found for this period.</div>
    <?php else: foreach ($rows as $r): ?>
        <?= care_print_render_visit_card($r, $type, (bool)$residentName) ?>
    <?php endforeach; endif; ?>
</div>
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
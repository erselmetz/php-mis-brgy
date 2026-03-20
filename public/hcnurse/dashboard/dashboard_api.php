<?php
/**
 * HC Nurse Dashboard API
 * GET: date_from (Y-m-d), date_to (Y-m-d)
 * Replaces/creates: public/hcnurse/dashboard/dashboard_api.php
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();
header('Content-Type: application/json');

/* ── Input sanitize ── */
$from = $_GET['date_from'] ?? date('Y-m-01');
$to   = $_GET['date_to']   ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
if ($from > $to) [$from,$to] = [$to,$from];

/* ── Helper ── */
function hcGet(mysqli $c, string $sql, string $types='', array $params=[]): ?array {
    if (!$types) { $r=$c->query($sql); return $r ? $r->fetch_assoc() : null; }
    $s=$c->prepare($sql); if(!$s) return null;
    $s->bind_param($types,...$params);
    $s->execute();
    return $s->get_result()->fetch_assoc() ?: null;
}
function hcAll(mysqli $c, string $sql, string $types='', array $params=[]): array {
    if (!$types) { $r=$c->query($sql); $rows=[]; if($r) while($row=$r->fetch_assoc()) $rows[]=$row; return $rows; }
    $s=$c->prepare($sql); if(!$s) return [];
    $s->bind_param($types,...$params);
    $s->execute();
    $r=$s->get_result(); $rows=[];
    while($row=$r->fetch_assoc()) $rows[]=$row;
    $s->close(); return $rows;
}
function tableOk(mysqli $c, string $t): bool {
    $t=$c->real_escape_string($t); $r=$c->query("SHOW TABLES LIKE '{$t}'");
    return $r && $r->num_rows>0;
}

/* ── 1. Consultations ── */
$consultRow = tableOk($conn,'consultations')
    ? hcGet($conn,"SELECT COUNT(*) cnt, SUM(consultation_date=CURDATE()) today_cnt FROM consultations WHERE consultation_date BETWEEN ? AND ?",'ss',[$from,$to])
    : null;
$consultations = (int)($consultRow['cnt']       ?? 0);
$todayConsult  = (int)($consultRow['today_cnt'] ?? 0);

/* Daily trend */
$consultByDay = tableOk($conn,'consultations')
    ? hcAll($conn,"SELECT DATE(consultation_date) day, COUNT(*) cnt FROM consultations WHERE consultation_date BETWEEN ? AND ? GROUP BY DATE(consultation_date) ORDER BY day ASC",'ss',[$from,$to])
    : [];

/* ── 2. Immunizations ── */
$immuneRow = tableOk($conn,'immunizations')
    ? hcGet($conn,"SELECT COUNT(*) cnt, SUM(next_schedule IS NOT NULL AND next_schedule>=CURDATE()) upcoming FROM immunizations WHERE date_given BETWEEN ? AND ?",'ss',[$from,$to])
    : null;
$immunizations  = (int)($immuneRow['cnt']      ?? 0);
$upcomingImmune = (int)($immuneRow['upcoming'] ?? 0);

/* ── 3. Care visits ── */
$visitTotal = 0; $careByType = ['maternal'=>0,'family_planning'=>0,'prenatal'=>0,'postnatal'=>0,'child_nutrition'=>0];
$activePrograms = 0;
if (tableOk($conn,'care_visits')) {
    $vr = hcAll($conn,"SELECT care_type, COUNT(*) cnt FROM care_visits WHERE visit_date BETWEEN ? AND ? GROUP BY care_type",'ss',[$from,$to]);
    foreach ($vr as $v) {
        $visitTotal += (int)$v['cnt'];
        if (isset($careByType[$v['care_type']])) {
            $careByType[$v['care_type']] = (int)$v['cnt'];
            if ((int)$v['cnt'] > 0) $activePrograms++;
        }
    }
}
/* Fallback: count from consultations meta if care_visits table empty */
if ($visitTotal === 0 && tableOk($conn,'consultations')) {
    $metaRows = hcAll($conn,"SELECT notes FROM consultations WHERE consultation_date BETWEEN ? AND ?",'ss',[$from,$to]);
    foreach ($metaRows as $mr) {
        $meta = [];
        $raw  = trim($mr['notes'] ?? '');
        if ($raw && $raw[0]==='{') $meta = json_decode($raw, true) ?: [];
        $prog = $meta['program'] ?? '';
        if ($prog && isset($careByType[$prog])) {
            $careByType[$prog]++;
            $visitTotal++;
        }
    }
    $activePrograms = count(array_filter($careByType));
}

/* ── 4. Medicine dispense ── */
$dispRow = tableOk($conn,'medicine_dispense')
    ? hcGet($conn,"SELECT COUNT(*) txn, COALESCE(SUM(quantity),0) qty FROM medicine_dispense WHERE dispense_date BETWEEN ? AND ?",'ss',[$from,$to])
    : null;
$dispensedQty = (int)($dispRow['qty'] ?? 0);
$dispensedTxn = (int)($dispRow['txn'] ?? 0);

/* ── 5. Low stock (always current) ── */
$lowStockCount = 0;
if (tableOk($conn,'medicines')) {
    $ls = hcGet($conn,"SELECT SUM(stock_qty <= reorder_level) low FROM medicines");
    $lowStockCount = (int)($ls['low'] ?? 0);
}

/* ── Response ── */
echo json_encode([
    'date_from'       => $from,
    'date_to'         => $to,
    'consultations'   => $consultations,
    'today_consult'   => $todayConsult,
    'consult_by_day'  => $consultByDay,
    'immunizations'   => $immunizations,
    'upcoming_immune' => $upcomingImmune,
    'care_visits'     => $visitTotal,
    'care_by_type'    => $careByType,
    'active_programs' => $activePrograms,
    'dispensed_qty'   => $dispensedQty,
    'dispensed_txn'   => $dispensedTxn,
    'low_stock_count' => $lowStockCount,
]);
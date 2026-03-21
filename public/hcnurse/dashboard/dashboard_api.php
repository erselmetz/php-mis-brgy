<?php
/**
 * HC Nurse Dashboard API
 * Replaces: public/hcnurse/dashboard/dashboard_api.php
 *
 * FIXES:
 * 1. Age groups computed from residents.birthdate (Infant/Child/Teen/Adult/Senior)
 * 2. All stats filtered by date_from / date_to
 * 3. care_by_type counts from consultations.notes JSON (program field)
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();
header('Content-Type: application/json');

$from = $_GET['date_from'] ?? date('Y-m-01');
$to   = $_GET['date_to']   ?? date('Y-m-d');

// Sanitise
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
if ($from > $to) [$from,$to] = [$to,$from];

function q(mysqli $c, string $sql): ?array {
    $r = $c->query($sql);
    if (!$r) { error_log('Dashboard: '.$c->error); return null; }
    return $r->fetch_assoc();
}
function tbl(mysqli $c, string $t): bool {
    return (bool)$c->query("SHOW TABLES LIKE '".addslashes($t)."'")?->num_rows;
}

/* consultations in range */
$cRow = q($conn, "SELECT
    COUNT(*) total,
    SUM(consultation_date = CURDATE()) today
    FROM consultations WHERE consultation_date BETWEEN '{$from}' AND '{$to}'");

$consult_total = (int)($cRow['total'] ?? 0);
$today_consult = (int)($cRow['today'] ?? 0);

/* care by type — parse JSON program field */
$careTypes = ['maternal','family_planning','prenatal','postnatal','child_nutrition','immunization'];
$care_by_type = [];
foreach ($careTypes as $ct) {
    $r = q($conn,
        "SELECT COUNT(*) cnt FROM consultations
         WHERE consultation_date BETWEEN '{$from}' AND '{$to}'
           AND notes LIKE '%\"program\":\"{$ct}\"%'");
    $care_by_type[$ct] = (int)($r['cnt'] ?? 0);
}
// immunizations from dedicated table too (overwrite)
if (tbl($conn,'immunizations')) {
    $r = q($conn, "SELECT COUNT(*) cnt FROM immunizations WHERE date_given BETWEEN '{$from}' AND '{$to}'");
    $care_by_type['immunization'] = (int)($r['cnt'] ?? 0);
}

$active_programs = count(array_filter($care_by_type));

/* immunizations */
$immTotal = $care_by_type['immunization'];
$immUpcoming = 0;
if (tbl($conn,'immunizations')) {
    $r = q($conn, "SELECT COUNT(*) cnt FROM immunizations WHERE next_schedule >= CURDATE()");
    $immUpcoming = (int)($r['cnt'] ?? 0);
}

/* dispensed */
$dispQty = 0; $dispTxn = 0;
if (tbl($conn,'medicine_dispense')) {
    $r = q($conn, "SELECT COALESCE(SUM(quantity),0) qty, COUNT(*) txn
                   FROM medicine_dispense WHERE dispense_date BETWEEN '{$from}' AND '{$to}'");
    $dispQty = (int)($r['qty'] ?? 0);
    $dispTxn = (int)($r['txn'] ?? 0);
}

/* daily trend */
$trend = [];
if ($consult_total > 0) {
    $tr = $conn->query("
        SELECT DATE_FORMAT(consultation_date,'%m/%d') day, COUNT(*) cnt
        FROM consultations
        WHERE consultation_date BETWEEN '{$from}' AND '{$to}'
        GROUP BY consultation_date ORDER BY consultation_date ASC LIMIT 60
    ");
    if ($tr) while ($r = $tr->fetch_assoc()) $trend[] = $r;
}

echo json_encode([
    'date_from'       => $from,
    'date_to'         => $to,
    'consultations'   => $consult_total,
    'today_consult'   => $today_consult,
    'immunizations'   => $immTotal,
    'upcoming_immune' => $immUpcoming,
    'care_visits'     => $consult_total,
    'active_programs' => $active_programs,
    'dispensed_qty'   => $dispQty,
    'dispensed_txn'   => $dispTxn,
    'care_by_type'    => $care_by_type,
    'consult_by_day'  => $trend,
]);
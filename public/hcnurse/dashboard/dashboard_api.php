<?php
/**
 * HC Nurse Dashboard API
 *
 * Uses: consultations (consult_type + legacy JSON program), care_visits + module tables,
 *       consultation_detail, health_metrics, immunizations, immunization_schedule.
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();
header('Content-Type: application/json');

$from = $_GET['date_from'] ?? date('Y-m-01');
$to   = $_GET['date_to']   ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = date('Y-m-d');
}
if ($from > $to) {
    [$from, $to] = [$to, $from];
}

function q(mysqli $c, string $sql): ?array {
    $r = $c->query($sql);
    if (!$r) {
        error_log('Dashboard: '.$c->error); return null;
    }
    return $r->fetch_assoc();
}
function tbl(mysqli $c, string $t): bool {
    return (bool)$c->query("SHOW TABLES LIKE '".addslashes($t)."'")?->num_rows;
}

function has_consult_type_column(mysqli $c): bool {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $r = $c->query("SHOW COLUMNS FROM consultations LIKE 'consult_type'");
    $cache = $r && $r->num_rows > 0;
    return $cache;
}

/** Consultations tagged to a programme (consult_type column OR legacy notes JSON). */
function count_consult_program(mysqli $c, string $from, string $to, string $ct): int {
    $jsonNeedle = '%"program":"'.$ct.'"%';
    $likeEsc = "'".$c->real_escape_string($jsonNeedle)."'";
    if (has_consult_type_column($c)) {
        $ctEsc = $c->real_escape_string($ct);
        $r = q($c, "
            SELECT COUNT(*) cnt FROM consultations
            WHERE consultation_date BETWEEN '{$from}' AND '{$to}'
              AND (
                consult_type = '{$ctEsc}'
                OR (
                  (consult_type IS NULL OR consult_type = '' OR consult_type = 'general')
                  AND notes LIKE {$likeEsc}
                )
              )
        ");
    } else {
        $r = q($c, "
            SELECT COUNT(*) cnt FROM consultations
            WHERE consultation_date BETWEEN '{$from}' AND '{$to}'
              AND notes LIKE {$likeEsc}
        ");
    }
    return (int)($r['cnt'] ?? 0);
}

$cRow = q($conn, "SELECT
    COUNT(*) total,
    SUM(consultation_date = CURDATE()) today
    FROM consultations WHERE consultation_date BETWEEN '{$from}' AND '{$to}'");

$consult_total = (int)($cRow['total'] ?? 0);
$today_consult = (int)($cRow['today'] ?? 0);

$careTypes = ['general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization'];
$care_by_type = [];
foreach ($careTypes as $ct) {
    $care_by_type[$ct] = count_consult_program($conn, $from, $to, $ct);
}

if (tbl($conn, 'immunizations')) {
    $r = q($conn, "SELECT COUNT(*) cnt FROM immunizations WHERE date_given BETWEEN '{$from}' AND '{$to}'");
    $care_by_type['immunization'] = (int)($r['cnt'] ?? 0);
} else {
    $care_by_type['immunization'] = (int)($care_by_type['immunization'] ?? 0);
}

$active_programs = count(array_filter($care_by_type));

$immTotal = $care_by_type['immunization'];
$immUpcoming = 0;
if (tbl($conn, 'immunizations')) {
    $r = q($conn, "SELECT COUNT(*) cnt FROM immunizations WHERE next_schedule >= CURDATE()");
    $immUpcoming = (int)($r['cnt'] ?? 0);
}

/* care_visits table (actual programme visits) */
$care_visits_records = 0;
if (tbl($conn, 'care_visits')) {
    $r = q($conn, "SELECT COUNT(*) cnt FROM care_visits WHERE visit_date BETWEEN '{$from}' AND '{$to}'");
    $care_visits_records = (int)($r['cnt'] ?? 0);
}

/* Module rows linked to care_visits in range */
$module_rows = [
    'prenatal_visit'       => 0,
    'postnatal_visit'      => 0,
    'family_planning'      => 0,
    'child_nutrition_visit'=> 0,
];
if (tbl($conn, 'care_visits')) {
    if (tbl($conn, 'prenatal_visit')) {
        $r = q($conn, "
            SELECT COUNT(*) cnt FROM prenatal_visit pv
            INNER JOIN care_visits cv ON cv.id = pv.care_visit_id
            WHERE cv.visit_date BETWEEN '{$from}' AND '{$to}'");
        $module_rows['prenatal_visit'] = (int)($r['cnt'] ?? 0);
    }
    if (tbl($conn, 'postnatal_visit')) {
        $r = q($conn, "
            SELECT COUNT(*) cnt FROM postnatal_visit pnv
            INNER JOIN care_visits cv ON cv.id = pnv.care_visit_id
            WHERE cv.visit_date BETWEEN '{$from}' AND '{$to}'");
        $module_rows['postnatal_visit'] = (int)($r['cnt'] ?? 0);
    }
    if (tbl($conn, 'family_planning_record')) {
        $r = q($conn, "
            SELECT COUNT(*) cnt FROM family_planning_record fpr
            INNER JOIN care_visits cv ON cv.id = fpr.care_visit_id
            WHERE cv.visit_date BETWEEN '{$from}' AND '{$to}'");
        $module_rows['family_planning'] = (int)($r['cnt'] ?? 0);
    }
    if (tbl($conn, 'child_nutrition_visit')) {
        $r = q($conn, "
            SELECT COUNT(*) cnt FROM child_nutrition_visit cnv
            INNER JOIN care_visits cv ON cv.id = cnv.care_visit_id
            WHERE cv.visit_date BETWEEN '{$from}' AND '{$to}'");
        $module_rows['child_nutrition_visit'] = (int)($r['cnt'] ?? 0);
    }
    // if (tbl($conn, 'general')) {
    //     $r = q($conn, "
    //         SELECT COUNT(*) cnt FROM general_record gr
    //         INNER JOIN care_visits cv ON cv.id = gr.care_visit_id
    //         WHERE cv.visit_date BETWEEN '{$from}' AND '{$to}'");
    //     $module_rows['general_record'] = (int)($r['cnt'] ?? 0);
    // }
}

/* consultation_detail (extended narrative) */
$consultation_detail_count = 0;
if (tbl($conn, 'consultation_detail')) {
    $r = q($conn, "
        SELECT COUNT(*) cnt FROM consultation_detail cd
        INNER JOIN consultations c ON c.id = cd.consultation_id
        WHERE c.consultation_date BETWEEN '{$from}' AND '{$to}'");
    $consultation_detail_count = (int)($r['cnt'] ?? 0);
}

/* health_metrics (vitals log) */
$health_metrics_count = 0;
if (tbl($conn, 'health_metrics')) {
    $r = q($conn, "SELECT COUNT(*) cnt FROM health_metrics WHERE recorded_at BETWEEN '{$from}' AND '{$to}'");
    $health_metrics_count = (int)($r['cnt'] ?? 0);
}

/* maternal_profile (longitudinal) */
$maternal_profile_count = 0;
if (tbl($conn, 'maternal_profile')) {
    $r = q($conn, 'SELECT COUNT(*) cnt FROM maternal_profile');
    $maternal_profile_count = (int)($r['cnt'] ?? 0);
}

/* NIP schedule (reference catalog — not date-bound) */
$nip_schedule_entries = 0;
if (tbl($conn, 'immunization_schedule')) {
    $r = q($conn, 'SELECT COUNT(*) cnt FROM immunization_schedule WHERE is_nip = 1');
    $nip_schedule_entries = (int)($r['cnt'] ?? 0);
}

$dispQty = 0;
$dispTxn = 0;
if (tbl($conn, 'medicine_dispense')) {
    $r = q($conn, "SELECT COALESCE(SUM(quantity),0) qty, COUNT(*) txn
                   FROM medicine_dispense WHERE dispense_date BETWEEN '{$from}' AND '{$to}'");
    $dispQty = (int)($r['qty'] ?? 0);
    $dispTxn = (int)($r['txn'] ?? 0);
}

$trend = [];
if ($consult_total > 0) {
    $tr = $conn->query("
        SELECT DATE_FORMAT(consultation_date,'%m/%d') day, COUNT(*) cnt
        FROM consultations
        WHERE consultation_date BETWEEN '{$from}' AND '{$to}'
        GROUP BY consultation_date ORDER BY consultation_date ASC LIMIT 60
    ");
    if ($tr) {
        while ($r = $tr->fetch_assoc()) {
            $trend[] = $r;
        }
    }
}

echo json_encode([
    'date_from'                  => $from,
    'date_to'                    => $to,
    'consultations'              => $consult_total,
    'today_consult'              => $today_consult,
    'immunizations'              => $immTotal,
    'upcoming_immune'            => $immUpcoming,
    'care_visits'                => $care_visits_records,
    'care_visits_records'        => $care_visits_records,
    'active_programs'            => $active_programs,
    'dispensed_qty'              => $dispQty,
    'dispensed_txn'              => $dispTxn,
    'care_by_type'               => $care_by_type,
    'consult_by_day'             => $trend,
    'module_table_rows'          => $module_rows,
    'consultation_detail_count'  => $consultation_detail_count,
    'health_metrics_count'       => $health_metrics_count,
    'maternal_profile_records'   => $maternal_profile_count,
    'nip_schedule_entries'       => $nip_schedule_entries,
]);
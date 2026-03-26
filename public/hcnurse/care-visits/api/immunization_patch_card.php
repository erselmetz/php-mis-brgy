<?php
/**
 * Care Visits API — immunization_card action patch
 * Only the immunization_card action is modified to return all_given
 * (non-NIP vaccines included). Everything else unchanged.
 *
 * Drop this file as a PATCH — it only adds `all_given` to the
 * immunization_card response so the NIP UI can show custom vaccines.
 *
 * File: public/hcnurse/care-visits/api/immunization_card_patch.php
 *
 * In care_visits_api.php, find the immunization_card action and replace
 * the final json_ok_data call with:
 *
 *   json_ok_data([
 *       'schedule'    => $schedule,
 *       'all_given'   => $allGiven,          // ← ADD THIS
 *       'given_count' => count(array_filter($schedule, fn($s)=>$s['given']!==null)),
 *       'overdue'     => count(array_filter($schedule, fn($s)=>$s['is_overdue'])),
 *       'total'       => count($schedule),
 *   ]);
 *
 * And after the existing `$givenIdx` build loop, add:
 *
 *   $allGivenStmt = $conn->prepare("SELECT * FROM immunizations WHERE resident_id=? ORDER BY date_given DESC");
 *   $allGivenStmt->bind_param('i',$rid); $allGivenStmt->execute();
 *   $allGiven = $allGivenStmt->get_result()->fetch_all(MYSQLI_ASSOC);
 *
 *
 * ──────────────────────────────────────────────
 * This file also provides a standalone endpoint
 * for the enhanced immunization_card action.
 * ──────────────────────────────────────────────
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();
header('Content-Type: application/json; charset=utf-8');

$rid = (int)($_GET['resident_id'] ?? 0);
if ($rid <= 0) json_err('Invalid resident_id');

// All NIP schedule slots
$schedule = [];
$r = $conn->query("SELECT * FROM immunization_schedule WHERE is_nip=1 ORDER BY sort_order");
if ($r) while ($row = $r->fetch_assoc()) $schedule[] = $row;

// All given immunizations (NIP and non-NIP)
$allGivenStmt = $conn->prepare("SELECT * FROM immunizations WHERE resident_id=? ORDER BY date_given DESC");
$allGivenStmt->bind_param('i', $rid);
$allGivenStmt->execute();
$allGiven = $allGivenStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build given index: schedule_id first, then fallback to vaccine_name|dose
$givenBySchedId   = [];
$givenByNameDose  = [];
foreach ($allGiven as $g) {
    $sid = (int)($g['schedule_id'] ?? 0);
    if ($sid > 0 && !isset($givenBySchedId[$sid])) {
        $givenBySchedId[$sid] = $g;
    }
    $key = strtolower($g['vaccine_name']).'|'.strtolower($g['dose'] ?? '');
    if (!isset($givenByNameDose[$key])) {
        $givenByNameDose[$key] = $g;
    }
}

// Resident birthdate for due-date computation
$bdStmt = $conn->prepare("SELECT birthdate FROM residents WHERE id=? LIMIT 1");
$bdStmt->bind_param('i', $rid);
$bdStmt->execute();
$bd = $bdStmt->get_result()->fetch_assoc();
$birthdate = $bd['birthdate'] ?? null;

$today = date('Y-m-d');

foreach ($schedule as &$slot) {
    $sid = (int)($slot['id'] ?? 0);
    $key = strtolower($slot['vaccine_name']).'|'.strtolower($slot['dose_label'] ?? '');

    // Match: prefer schedule_id, fallback to name|dose
    $given = $givenBySchedId[$sid] ?? $givenByNameDose[$key] ?? null;
    $slot['given']      = $given;
    $slot['is_overdue'] = false;
    $slot['due_date']   = null;

    if (!$given && $slot['target_age_days'] !== null && $birthdate) {
        $due = date('Y-m-d', strtotime($birthdate . ' +' . (int)$slot['target_age_days'] . ' days'));
        $slot['due_date']   = $due;
        $slot['is_overdue'] = $due < $today;
    }
}
unset($slot);

json_ok_data([
    'schedule'    => $schedule,
    'all_given'   => $allGiven,
    'given_count' => count(array_filter($schedule, fn($s) => $s['given'] !== null)),
    'overdue'     => count(array_filter($schedule, fn($s) => $s['is_overdue'])),
    'total'       => count($schedule),
]);
<?php
/**
 * Care Visits Unified API
 * Handles CRUD for all 6 care modules:
 *   maternal, family_planning, prenatal, postnatal, child_nutrition, immunization
 *
 * Routes:
 *   GET  ?action=list&type=prenatal&resident_id=X
 *   GET  ?action=get&type=prenatal&id=X
 *   GET  ?action=maternal_profile&resident_id=X
 *   GET  ?action=nip_schedule
 *   GET  ?action=immunization_card&resident_id=X
 *   POST ?action=save       (create/update care_visit + module record)
 *   POST ?action=save_maternal_profile
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$type   = $_GET['type']   ?? ($_POST['type']   ?? '');

$ALLOWED_TYPES = ['maternal','family_planning','prenatal','postnatal','child_nutrition','immunization'];

/* ════════════════════════════════════════════════════════════════
   HELPER: get a module record joined with care_visit
════════════════════════════════════════════════════════════════ */
function getModuleRecord(mysqli $conn, string $type, int $id): ?array {
    $tbl = moduleTable($type);
    if (!$tbl) return null;

    // care_visits row + module row
    $sql = "SELECT cv.*, m.*,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                   r.birthdate
            FROM care_visits cv
            INNER JOIN {$tbl} m ON m.care_visit_id = cv.id
            INNER JOIN residents r ON r.id = cv.resident_id
            WHERE cv.id = ? AND cv.care_type = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $id, $type);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function moduleTable(string $type): ?string {
    return match($type) {
        'family_planning'  => 'family_planning_record',
        'prenatal'         => 'prenatal_visit',
        'postnatal'        => 'postnatal_visit',
        'child_nutrition'  => 'child_nutrition_visit',
        default            => null,
    };
}

/* ════════════════════════════════════════════════════════════════
   GET: nip_schedule — return full NIP schedule for card
════════════════════════════════════════════════════════════════ */
if ($action === 'nip_schedule') {
    $rows = [];
    $res  = $conn->query("SELECT * FROM immunization_schedule WHERE is_nip=1 ORDER BY sort_order ASC");
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    json_ok_data(['schedule' => $rows]);
}

/* ════════════════════════════════════════════════════════════════
   GET: immunization_card — NIP schedule with given doses overlaid
════════════════════════════════════════════════════════════════ */
if ($action === 'immunization_card') {
    $rid = (int)($_GET['resident_id'] ?? 0);
    if ($rid <= 0) json_err('Invalid resident_id');

    // Get NIP schedule
    $schedule = [];
    $res = $conn->query("SELECT * FROM immunization_schedule WHERE is_nip=1 ORDER BY sort_order ASC");
    if ($res) while ($r = $res->fetch_assoc()) $schedule[] = $r;

    // Get all immunizations for this resident
    $given = [];
    $stmt = $conn->prepare("SELECT * FROM immunizations WHERE resident_id=? ORDER BY date_given ASC");
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    $gr = $stmt->get_result();
    while ($r = $gr->fetch_assoc()) $given[] = $r;

    // Match given doses to schedule slots
    $givenIndex = [];
    foreach ($given as $g) {
        $key = strtolower(trim($g['vaccine_name'])) . '|' . strtolower(trim($g['dose'] ?? ''));
        $givenIndex[$key] = $g;
    }

    foreach ($schedule as &$slot) {
        $key = strtolower($slot['vaccine_name']) . '|' . strtolower($slot['dose_label']);
        $slot['given'] = $givenIndex[$key] ?? null;
        $slot['is_overdue'] = false;
        if (!$slot['given'] && $slot['target_age_days'] !== null) {
            // Check if overdue based on resident birthdate
            $bdRes = $conn->prepare("SELECT birthdate FROM residents WHERE id=? LIMIT 1");
            $bdRes->bind_param('i', $rid);
            $bdRes->execute();
            $bd = $bdRes->get_result()->fetch_assoc();
            if ($bd && $bd['birthdate']) {
                $dueDate = date('Y-m-d', strtotime($bd['birthdate'] . ' +' . $slot['target_age_days'] . ' days'));
                $slot['due_date']  = $dueDate;
                $slot['is_overdue'] = $dueDate < date('Y-m-d');
            }
        }
    }
    unset($slot);

    $overdue = count(array_filter($schedule, fn($s) => $s['is_overdue']));
    $given_count = count(array_filter($schedule, fn($s) => $s['given'] !== null));

    json_ok_data([
        'schedule'    => $schedule,
        'given_count' => $given_count,
        'overdue'     => $overdue,
        'total'       => count($schedule),
    ]);
}

/* ════════════════════════════════════════════════════════════════
   GET: maternal_profile
════════════════════════════════════════════════════════════════ */
if ($action === 'maternal_profile') {
    $rid = (int)($_GET['resident_id'] ?? 0);
    if ($rid <= 0) json_err('Invalid resident_id');

    $stmt = $conn->prepare("SELECT * FROM maternal_profile WHERE resident_id=? LIMIT 1");
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    json_ok_data(['profile' => $row]);
}

/* ════════════════════════════════════════════════════════════════
   GET: list — all care_visits of a type for a resident
════════════════════════════════════════════════════════════════ */
if ($action === 'list') {
    $rid  = (int)($_GET['resident_id'] ?? 0);
    $from = $_GET['from'] ?? '2000-01-01';
    $to   = $_GET['to']   ?? date('Y-m-d');

    if (!in_array($type, $ALLOWED_TYPES, true)) json_err('Invalid type');

    $sql = "SELECT cv.id, cv.visit_date, cv.notes, cv.created_at,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
            FROM care_visits cv
            INNER JOIN residents r ON r.id = cv.resident_id
            WHERE cv.care_type = ? AND cv.visit_date BETWEEN ? AND ?";
    $params = [$type, $from, $to];
    $types  = 'sss';

    if ($rid > 0) {
        $sql .= " AND cv.resident_id = ?";
        $params[] = $rid;
        $types .= 'i';
    }
    $sql .= " ORDER BY cv.visit_date DESC, cv.id DESC LIMIT 200";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = [];
    $res  = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        // Attach module-specific summary
        $tbl = moduleTable($type);
        if ($tbl) {
            $ms = $conn->prepare("SELECT * FROM {$tbl} WHERE care_visit_id=? LIMIT 1");
            $ms->bind_param('i', (int)$r['id']);
            $ms->execute();
            $r['module'] = $ms->get_result()->fetch_assoc() ?? [];
        }
        $rows[] = $r;
    }

    json_ok_data(['data' => $rows, 'total' => count($rows)]);
}

/* ════════════════════════════════════════════════════════════════
   GET: get single record
════════════════════════════════════════════════════════════════ */
if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!in_array($type, $ALLOWED_TYPES, true)) json_err('Invalid type');
    if ($id <= 0) json_err('Invalid id');

    $tbl = moduleTable($type);
    if (!$tbl) {
        // Immunization or maternal use their own tables
        if ($type === 'immunization') {
            $stmt = $conn->prepare("SELECT i.*, CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) AS resident_name
                                    FROM immunizations i LEFT JOIN residents r ON r.id=i.resident_id WHERE i.id=? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) json_err('Record not found', 404);
            json_ok_data(['data' => $row]);
        }
        json_err('Module not found');
    }

    $sql  = "SELECT cv.*, m.*,
                    CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                    r.birthdate
             FROM care_visits cv
             INNER JOIN {$tbl} m ON m.care_visit_id = cv.id
             INNER JOIN residents r ON r.id = cv.resident_id
             WHERE cv.id = ? AND cv.care_type = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $id, $type);
    $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc();
    if (!$row) json_err('Record not found', 404);
    json_ok_data(['data' => $row]);
}

/* ════════════════════════════════════════════════════════════════
   POST: save_maternal_profile
════════════════════════════════════════════════════════════════ */
if ($action === 'save_maternal_profile') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Invalid method', 405);

    $rid = (int)($_POST['resident_id'] ?? 0);
    if ($rid <= 0) json_err('Resident is required');

    $fields = ['gravida','term','preterm','abortions','living_children',
                'hx_pre_eclampsia','hx_pph','hx_cesarean','hx_ectopic','hx_stillbirth',
                'has_diabetes','has_hypertension','has_hiv','has_anemia',
                'blood_type','other_conditions','notes'];

    $setClauses = [];
    $params = [$rid]; // first param for ON DUPLICATE KEY
    $types  = 'i';
    $assignments = [];

    foreach ($fields as $f) {
        $v = $_POST[$f] ?? null;
        if ($v === '') $v = null;
        $assignments[$f] = $v;
        $setClauses[] = "`{$f}` = ?";
        $params[] = $v;
        $types .= 's';
    }

    // updated_by
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $params[] = $userId;
    $types   .= 'i';

    // Build INSERT ... ON DUPLICATE KEY UPDATE
    $cols    = implode(', ', array_keys($assignments));
    $placeholders = implode(', ', array_fill(0, count($assignments), '?'));
    $updates = implode(', ', $setClauses);

    $sql = "INSERT INTO maternal_profile (resident_id, {$cols}, updated_by)
            VALUES (?, {$placeholders}, ?)
            ON DUPLICATE KEY UPDATE {$updates}, updated_by = VALUES(updated_by)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) json_err('SQL error: ' . $conn->error, 500);
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) json_err('Save failed: ' . $stmt->error, 500);

    json_ok_data(['resident_id' => $rid], 'Maternal profile saved.');
}

/* ════════════════════════════════════════════════════════════════
   POST: save — create/update a care visit + module record
════════════════════════════════════════════════════════════════ */
if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Invalid method', 405);
    if (!in_array($type, $ALLOWED_TYPES, true)) json_err('Invalid type');

    $rid        = (int)($_POST['resident_id'] ?? 0);
    $visitDate  = trim($_POST['visit_date'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');
    $visitId    = (int)($_POST['care_visit_id'] ?? 0);
    $userId     = (int)($_SESSION['user_id'] ?? 0);

    if ($rid <= 0)     json_err('Resident is required');
    if (!$visitDate)   json_err('Visit date is required');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $visitDate)) json_err('Invalid date format');

    $conn->begin_transaction();
    try {
        // Upsert care_visits
        if ($visitId > 0) {
            $stmt = $conn->prepare("UPDATE care_visits SET visit_date=?, notes=?, updated_at=NOW() WHERE id=? LIMIT 1");
            $stmt->bind_param('ssi', $visitDate, $notes, $visitId);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO care_visits (resident_id, care_type, visit_date, notes, created_by) VALUES (?,?,?,?,?)");
            $stmt->bind_param('isssi', $rid, $type, $visitDate, $notes, $userId);
            $stmt->execute();
            $visitId = (int)$conn->insert_id;
        }

        // Save module-specific record
        $moduleId = 0;
        switch ($type) {
            case 'family_planning':
                $moduleId = saveFamilyPlanning($conn, $visitId, $rid);
                break;
            case 'prenatal':
                $moduleId = savePrenatal($conn, $visitId, $rid);
                break;
            case 'postnatal':
                $moduleId = savePostnatal($conn, $visitId, $rid);
                break;
            case 'child_nutrition':
                $moduleId = saveChildNutrition($conn, $visitId, $rid);
                break;
            case 'immunization':
                $moduleId = saveImmunization($conn, $visitId, $rid);
                break;
            case 'maternal':
                // Maternal visit just records in care_visits; profile updated via save_maternal_profile
                $moduleId = $visitId;
                break;
        }

        $conn->commit();
        json_ok_data(['care_visit_id' => $visitId, 'module_id' => $moduleId], 'Visit saved successfully.');

    } catch (Throwable $e) {
        $conn->rollback();
        json_err('Save failed: ' . $e->getMessage(), 500);
    }
}

/* ════════════════════════════════════════════════════════════════
   MODULE SAVE FUNCTIONS
════════════════════════════════════════════════════════════════ */

function saveFamilyPlanning(mysqli $conn, int $visitId, int $rid): int {
    $p = $_POST;
    $stmt = $conn->prepare("
        INSERT INTO family_planning_record
            (resident_id, care_visit_id, method, method_other, method_start_date,
             next_supply_date, next_checkup_date, is_new_acceptor, is_method_switch,
             prev_method, side_effects, counseling_notes, pills_given, injectables_given,
             health_worker)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            method=VALUES(method), method_other=VALUES(method_other),
            method_start_date=VALUES(method_start_date),
            next_supply_date=VALUES(next_supply_date),
            next_checkup_date=VALUES(next_checkup_date),
            is_new_acceptor=VALUES(is_new_acceptor),
            is_method_switch=VALUES(is_method_switch),
            prev_method=VALUES(prev_method),
            side_effects=VALUES(side_effects),
            counseling_notes=VALUES(counseling_notes),
            pills_given=VALUES(pills_given),
            injectables_given=VALUES(injectables_given),
            health_worker=VALUES(health_worker)
    ");

    $method          = $p['method']            ?? 'Pills';
    $methodOther     = $p['method_other']       ?? null;
    $startDate       = $p['method_start_date']  ?: null;
    $nextSupply      = $p['next_supply_date']    ?: null;
    $nextCheckup     = $p['next_checkup_date']   ?: null;
    $isNew           = (int)($p['is_new_acceptor']  ?? 0);
    $isSwitch        = (int)($p['is_method_switch']  ?? 0);
    $prevMethod      = $p['prev_method']         ?? null;
    $sideEffects     = $p['side_effects']        ?? null;
    $counselingNotes = $p['counseling_notes']    ?? null;
    $pillsGiven      = (int)($p['pills_given']       ?? 0);
    $injectGiven     = (int)($p['injectables_given'] ?? 0);
    $worker          = $p['health_worker']       ?? null;

    $stmt->bind_param('iisssssiisssiis',
        $rid, $visitId, $method, $methodOther, $startDate,
        $nextSupply, $nextCheckup, $isNew, $isSwitch,
        $prevMethod, $sideEffects, $counselingNotes,
        $pillsGiven, $injectGiven, $worker
    );
    $stmt->execute();
    return (int)$conn->insert_id ?: $visitId;
}

function savePrenatal(mysqli $conn, int $visitId, int $rid): int {
    $p = $_POST;

    // Auto-compute EDD from LMP
    $lmp = $p['lmp_date'] ?: null;
    $edd = null;
    if ($lmp) {
        $edd = date('Y-m-d', strtotime($lmp . ' +280 days'));
    }

    $stmt = $conn->prepare("
        INSERT INTO prenatal_visit
            (resident_id, care_visit_id, lmp_date, edd_date, aog_weeks, visit_number,
             weight_kg, bp_systolic, bp_diastolic, fundal_height_cm, fetal_heart_rate,
             fetal_presentation, folic_acid_given, iron_given, iron_tablets_qty,
             calcium_given, iodine_given, tt_dose, tt_date,
             hgb_result, urinalysis_done, blood_type_done, hiv_test_done, hiv_result,
             syphilis_done, syphilis_result, risk_level, risk_notes,
             chief_complaint, assessment, plan, health_worker)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $vn     = (int)($p['visit_number']       ?? 1);
    $wt     = $p['weight_kg']           ?: null;
    $bps    = $p['bp_systolic']          ?: null;
    $bpd    = $p['bp_diastolic']         ?: null;
    $fh     = $p['fundal_height_cm']     ?: null;
    $fhr    = $p['fetal_heart_rate']     ?: null;
    $fp     = $p['fetal_presentation']   ?? 'Unknown';
    $fa     = (int)($p['folic_acid_given']   ?? 0);
    $iron   = (int)($p['iron_given']         ?? 0);
    $ironQ  = (int)($p['iron_tablets_qty']   ?? 0);
    $ca     = (int)($p['calcium_given']      ?? 0);
    $io     = (int)($p['iodine_given']       ?? 0);
    $ttD    = $p['tt_dose']              ?? 'None';
    $ttDt   = $p['tt_date']              ?: null;
    $hgb    = $p['hgb_result']           ?: null;
    $ua     = (int)($p['urinalysis_done']    ?? 0);
    $bt     = (int)($p['blood_type_done']    ?? 0);
    $hivT   = (int)($p['hiv_test_done']      ?? 0);
    $hivR   = $p['hiv_result']           ?? 'Not done';
    $syphT  = (int)($p['syphilis_done']      ?? 0);
    $syphR  = $p['syphilis_result']      ?? 'Not done';
    $risk   = $p['risk_level']           ?? 'Low';
    $riskN  = $p['risk_notes']           ?? null;
    $cc     = $p['chief_complaint']      ?? null;
    $assess = $p['assessment']           ?? null;
    $plan   = $p['plan']                 ?? null;
    $worker = $p['health_worker']        ?? null;
    $aog    = $p['aog_weeks']            ?: null;

    $stmt->bind_param('iisssiidddiiiiiiiissiiisssisssss',
        $rid, $visitId, $lmp, $edd, $aog, $vn,
        $wt, $bps, $bpd, $fh, $fhr, $fp,
        $fa, $iron, $ironQ, $ca, $io, $ttD, $ttDt,
        $hgb, $ua, $bt, $hivT, $hivR, $syphT, $syphR,
        $risk, $riskN, $cc, $assess, $plan, $worker
    );
    $stmt->execute();
    return (int)$conn->insert_id;
}

function savePostnatal(mysqli $conn, int $visitId, int $rid): int {
    $p = $_POST;
    $stmt = $conn->prepare("
        INSERT INTO postnatal_visit
            (resident_id, care_visit_id, delivery_date, delivery_type, delivery_facility,
             birth_attendant, visit_number, weight_kg, bp_systolic, bp_diastolic,
             lochia_type, fundal_involution, episiotomy_healing, cs_wound_healing,
             breastfeeding_status, ppd_score, ppd_referred,
             newborn_weight_g, newborn_length_cm, apgar_1min, apgar_5min,
             cord_status, jaundice, newborn_screening_done, bcg_given, hb_vaccine_given,
             fp_counseled, fp_method_chosen, chief_complaint, assessment, plan, health_worker)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $dd   = $p['delivery_date']          ?: null;
    $dt   = $p['delivery_type']          ?? 'Unknown';
    $df   = $p['delivery_facility']      ?? null;
    $ba   = $p['birth_attendant']        ?? null;
    $vn   = (int)($p['visit_number']         ?? 1);
    $wt   = $p['weight_kg']             ?: null;
    $bps  = $p['bp_systolic']            ?: null;
    $bpd  = $p['bp_diastolic']           ?: null;
    $lo   = $p['lochia_type']            ?? 'Not checked';
    $fi   = $p['fundal_involution']      ?? 'Not checked';
    $ep   = $p['episiotomy_healing']     ?? 'NA';
    $cs   = $p['cs_wound_healing']       ?? 'NA';
    $bf   = $p['breastfeeding_status']   ?? 'NA';
    $ppd  = $p['ppd_score']              ?: null;
    $ppdR = (int)($p['ppd_referred']         ?? 0);
    $nbw  = $p['newborn_weight_g']       ?: null;
    $nbl  = $p['newborn_length_cm']      ?: null;
    $a1   = $p['apgar_1min']             ?: null;
    $a5   = $p['apgar_5min']             ?: null;
    $cord = $p['cord_status']            ?? 'NA';
    $jaun = (int)($p['jaundice']             ?? 0);
    $nbs  = (int)($p['newborn_screening_done'] ?? 0);
    $bcg  = (int)($p['bcg_given']            ?? 0);
    $hbv  = (int)($p['hb_vaccine_given']     ?? 0);
    $fpc  = (int)($p['fp_counseled']         ?? 0);
    $fpm  = $p['fp_method_chosen']       ?? null;
    $cc   = $p['chief_complaint']        ?? null;
    $asx  = $p['assessment']             ?? null;
    $pl   = $p['plan']                   ?? null;
    $wk   = $p['health_worker']          ?? null;

    $stmt->bind_param('iissssidddsssssisissiiiiiissssss',
        $rid, $visitId, $dd, $dt, $df, $ba, $vn, $wt, $bps, $bpd,
        $lo, $fi, $ep, $cs, $bf, $ppd, $ppdR,
        $nbw, $nbl, $a1, $a5, $cord, $jaun, $nbs, $bcg, $hbv,
        $fpc, $fpm, $cc, $asx, $pl, $wk
    );
    $stmt->execute();
    return (int)$conn->insert_id;
}

function saveChildNutrition(mysqli $conn, int $visitId, int $rid): int {
    $p = $_POST;
    $stmt = $conn->prepare("
        INSERT INTO child_nutrition_visit
            (resident_id, care_visit_id, visit_date, age_months,
             weight_kg, height_cm, muac_cm, waz, haz, whz,
             stunting_status, wasting_status, underweight_status,
             breastfeeding, complementary_intro, feeding_problems,
             vita_supplemented, vita_dose, vita_date,
             iron_supplemented, zinc_given, deworming_done, deworming_date,
             counseling_given, counseling_notes, referred, referral_reason, health_worker)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $vDate = $_POST['visit_date'] ?? date('Y-m-d');
    $am    = $p['age_months']          ?: null;
    $wt    = $p['weight_kg']           ?: null;
    $ht    = $p['height_cm']           ?: null;
    $mu    = $p['muac_cm']             ?: null;
    $waz   = $p['waz']                 ?: null;
    $haz   = $p['haz']                 ?: null;
    $whz   = $p['whz']                 ?: null;
    $stun  = $p['stunting_status']     ?? 'Not assessed';
    $wast  = $p['wasting_status']      ?? 'Not assessed';
    $und   = $p['underweight_status']  ?? 'Not assessed';
    $bf    = $p['breastfeeding']       ?? 'NA';
    $ci    = $p['complementary_intro'] ?: null;
    $fp    = $p['feeding_problems']    ?? null;
    $vaA   = (int)($p['vita_supplemented'] ?? 0);
    $vaD   = $p['vita_dose']           ?? 'NA';
    $vaDt  = $p['vita_date']           ?: null;
    $iron  = (int)($p['iron_supplemented'] ?? 0);
    $zinc  = (int)($p['zinc_given']        ?? 0);
    $dw    = (int)($p['deworming_done']    ?? 0);
    $dwDt  = $p['deworming_date']       ?: null;
    $cg    = (int)($p['counseling_given']  ?? 0);
    $cn    = $p['counseling_notes']    ?? null;
    $ref   = (int)($p['referred']          ?? 0);
    $rr    = $p['referral_reason']     ?? null;
    $wk    = $p['health_worker']       ?? null;

    $stmt->bind_param('iiisddddddsssssisssiiisisiss',
        $rid, $visitId, $vDate, $am, $wt, $ht, $mu, $waz, $haz, $whz,
        $stun, $wast, $und, $bf, $ci, $fp,
        $vaA, $vaD, $vaDt, $iron, $zinc, $dw, $dwDt,
        $cg, $cn, $ref, $rr, $wk
    );
    $stmt->execute();
    return (int)$conn->insert_id;
}

function saveImmunization(mysqli $conn, int $visitId, int $rid): int {
    $p = $_POST;

    $vaccine  = trim($p['vaccine_name'] ?? '');
    $dose     = trim($p['dose']         ?? '');
    $dateGiven = $p['date_given']       ?: date('Y-m-d');
    $nextSched = $p['next_schedule']    ?: null;
    $remarks   = $p['remarks']          ?? null;
    $batch     = $p['batch_number']     ?? null;
    $expiry    = $p['expiry_date']       ?: null;
    $site      = $p['site_given']        ?? null;
    $route     = $p['route']             ?? 'IM';
    $adverse   = $p['adverse_reaction'] ?? null;
    $isDefault = (int)($p['is_defaulter'] ?? 0);
    $catchUp   = (int)($p['catch_up']     ?? 0);
    $schedId   = $p['schedule_id']       ?: null;
    $worker    = $p['administered_by']   ?? null;

    if (!$vaccine) throw new \InvalidArgumentException('Vaccine name is required');

    $stmt = $conn->prepare("
        INSERT INTO immunizations
            (resident_id, vaccine_name, dose, date_given, next_schedule,
             administered_by, remarks, batch_number, expiry_date,
             site_given, route, adverse_reaction, is_defaulter, catch_up,
             schedule_id, care_visit_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param('isssssssssssiiis',
        $rid, $vaccine, $dose, $dateGiven, $nextSched,
        $worker, $remarks, $batch, $expiry,
        $site, $route, $adverse, $isDefault, $catchUp,
        $schedId, $visitId
    );
    $stmt->execute();
    return (int)$conn->insert_id;
}

json_err('Unknown action', 404);
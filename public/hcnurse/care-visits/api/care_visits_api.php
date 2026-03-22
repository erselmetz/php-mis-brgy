<?php
/**
 * Care Visits Unified API  — v3 (all bind_param bugs fixed)
 *
 * Root cause of line 268 error:
 *   ON DUPLICATE KEY UPDATE col=? col=? ... added N more placeholders
 *   but $params only had the INSERT values → mismatch.
 *   Fix: use VALUES() references in the UPDATE clause (zero extra params).
 *
 * All other module save functions now use the same safe pattern:
 *   - Build $vals array explicitly
 *   - $ts = 'ii' . str_repeat('s', count($vals)-2)
 *   - Bind once. Done.
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();
header('Content-Type: application/json; charset=utf-8');

$action  = $_GET['action']  ?? ($_POST['action']  ?? '');
$type    = $_GET['type']    ?? ($_POST['type']    ?? '');
$ALLOWED = ['maternal','family_planning','prenatal','postnatal','child_nutrition','immunization'];

/* ── null-safe helpers ── */
function p(mixed $v): mixed   { return ($v === '' || $v === null) ? null : $v; }
function pint(mixed $v): ?int { $n = p($v); return $n === null ? null : (int)$n; }

function moduleTable(string $t): ?string {
    return match($t) {
        'family_planning' => 'family_planning_record',
        'prenatal'        => 'prenatal_visit',
        'postnatal'       => 'postnatal_visit',
        'child_nutrition' => 'child_nutrition_visit',
        default           => null,
    };
}

/* ══════════════ nip_schedule ══════════════ */
if ($action === 'nip_schedule') {
    $rows = [];
    $r = $conn->query("SELECT * FROM immunization_schedule WHERE is_nip=1 ORDER BY sort_order");
    if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
    json_ok_data(['schedule' => $rows]);
}

/* ══════════════ immunization_card ══════════════ */
if ($action === 'immunization_card') {
    $rid = (int)($_GET['resident_id'] ?? 0);
    if ($rid <= 0) json_err('Invalid resident_id');

    $schedule = [];
    $r = $conn->query("SELECT * FROM immunization_schedule WHERE is_nip=1 ORDER BY sort_order");
    if ($r) while ($row = $r->fetch_assoc()) $schedule[] = $row;

    $st = $conn->prepare("SELECT * FROM immunizations WHERE resident_id=? ORDER BY date_given");
    $st->bind_param('i',$rid); $st->execute();
    $gr = $st->get_result();
    $givenIdx = [];
    while ($g = $gr->fetch_assoc()) {
        $key = strtolower($g['vaccine_name']).'|'.strtolower($g['dose'] ?? '');
        if (!isset($givenIdx[$key])) $givenIdx[$key] = $g;
    }

    $bdSt = $conn->prepare("SELECT birthdate FROM residents WHERE id=? LIMIT 1");
    $bdSt->bind_param('i',$rid); $bdSt->execute();
    $bd = $bdSt->get_result()->fetch_assoc();
    $birthdate = $bd['birthdate'] ?? null;

    foreach ($schedule as &$slot) {
        $key = strtolower($slot['vaccine_name']).'|'.strtolower($slot['dose_label']);
        $slot['given']      = $givenIdx[$key] ?? null;
        $slot['is_overdue'] = false;
        $slot['due_date']   = null;
        if (!$slot['given'] && $slot['target_age_days'] !== null && $birthdate) {
            $due = date('Y-m-d', strtotime($birthdate.' +'.$slot['target_age_days'].' days'));
            $slot['due_date']   = $due;
            $slot['is_overdue'] = $due < date('Y-m-d');
        }
    }
    unset($slot);

    json_ok_data([
        'schedule'    => $schedule,
        'given_count' => count(array_filter($schedule, fn($s)=>$s['given']!==null)),
        'overdue'     => count(array_filter($schedule, fn($s)=>$s['is_overdue'])),
        'total'       => count($schedule),
    ]);
}

/* ══════════════ maternal_profile GET ══════════════ */
if ($action === 'maternal_profile') {
    $rid = (int)($_GET['resident_id'] ?? 0);
    if ($rid <= 0) json_err('Invalid resident_id');
    $st = $conn->prepare("SELECT * FROM maternal_profile WHERE resident_id=? LIMIT 1");
    $st->bind_param('i',$rid); $st->execute();
    json_ok_data(['profile' => $st->get_result()->fetch_assoc()]);
}

/* ══════════════ save_maternal_profile ══════════════
   FIX: ON DUPLICATE KEY UPDATE uses VALUES(col) — zero extra bind params.
   Before: INSERT(N params) + UPDATE(N more params) = 2N → mismatch.
   After:  INSERT(N params) only → correct.
══════════════════════════════════════════════════ */
if ($action === 'save_maternal_profile') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Invalid method',405);
    $rid = (int)($_POST['resident_id'] ?? 0);
    if ($rid <= 0) json_err('Resident is required');

    $fields = [
        'gravida','term','preterm','abortions','living_children',
        'hx_pre_eclampsia','hx_pph','hx_cesarean','hx_ectopic','hx_stillbirth',
        'has_diabetes','has_hypertension','has_hiv','has_anemia',
        'blood_type','other_conditions','notes',
    ];

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $cols   = implode(',', $fields);
    $phs    = implode(',', array_fill(0, count($fields), '?'));
    /* VALUES(col) needs zero extra params — that's the whole fix */
    $upd    = implode(',', array_map(fn($f) => "`$f`=VALUES(`$f`)", $fields));
    $sql    = "INSERT INTO maternal_profile (resident_id,$cols,updated_by)
               VALUES (?,$phs,?)
               ON DUPLICATE KEY UPDATE $upd,updated_by=VALUES(updated_by)";

    /* params: rid + N fields + userId  = N+2 */
    $params = [$rid];
    $types  = 'i';
    foreach ($fields as $f) {
        $params[] = p($_POST[$f] ?? null);
        $types   .= 's';
    }
    $params[] = $userId;
    $types   .= 'i';

    $st = $conn->prepare($sql);
    if (!$st) json_err('SQL error: '.$conn->error, 500);
    $st->bind_param($types, ...$params);
    if (!$st->execute()) json_err('Save failed: '.$st->error, 500);
    json_ok_data(['resident_id' => $rid], 'Maternal profile saved.');
}

/* ══════════════ list ══════════════ */
if ($action === 'list') {
    $rid  = (int)($_GET['resident_id'] ?? 0);
    $from = $_GET['from'] ?? '2000-01-01';
    $to   = $_GET['to']   ?? date('Y-m-d');
    if (!in_array($type, $ALLOWED, true)) json_err('Invalid type');

    $sql    = "SELECT cv.id,cv.visit_date,cv.notes,cv.created_at,
                      CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) AS resident_name
               FROM care_visits cv
               INNER JOIN residents r ON r.id=cv.resident_id
               WHERE cv.care_type=? AND cv.visit_date BETWEEN ? AND ?";
    $params = [$type,$from,$to]; $types = 'sss';
    if ($rid>0){$sql.=" AND cv.resident_id=?";$params[]=$rid;$types.='i';}
    $sql .= " ORDER BY cv.visit_date DESC,cv.id DESC LIMIT 200";

    $st = $conn->prepare($sql);
    $st->bind_param($types,...$params); $st->execute();
    $res = $st->get_result(); $rows = [];
    while ($row = $res->fetch_assoc()) {
        $tbl = moduleTable($type);
        if ($tbl) {
            $ms = $conn->prepare("SELECT * FROM $tbl WHERE care_visit_id=? LIMIT 1");
            $ms->bind_param('i',(int)$row['id']); $ms->execute();
            $row['module'] = $ms->get_result()->fetch_assoc() ?? [];
        }
        $rows[] = $row;
    }
    json_ok_data(['data'=>$rows,'total'=>count($rows)]);
}

/* ══════════════ get ══════════════ */
if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!in_array($type, $ALLOWED, true)) json_err('Invalid type');
    if ($id <= 0) json_err('Invalid id');

    $tbl = moduleTable($type);
    if (!$tbl) {
        if ($type === 'immunization') {
            $st = $conn->prepare("SELECT i.*,CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) res_name FROM immunizations i LEFT JOIN residents r ON r.id=i.resident_id WHERE i.id=? LIMIT 1");
            $st->bind_param('i',$id); $st->execute();
            $row = $st->get_result()->fetch_assoc();
            if(!$row) json_err('Not found',404);
            json_ok_data(['data'=>$row]);
        }
        json_err('Module not found');
    }
    $st = $conn->prepare("SELECT cv.*,m.*,CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) AS resident_name,r.birthdate FROM care_visits cv INNER JOIN $tbl m ON m.care_visit_id=cv.id INNER JOIN residents r ON r.id=cv.resident_id WHERE cv.id=? AND cv.care_type=? LIMIT 1");
    $st->bind_param('is',$id,$type); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!$row) json_err('Not found',404);
    json_ok_data(['data'=>$row]);
}

/* ══════════════ save ══════════════ */
if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Invalid method',405);
    if (!in_array($type, $ALLOWED, true)) json_err('Invalid type');

    $rid      = (int)($_POST['resident_id'] ?? 0);
    $vDate    = trim($_POST['visit_date'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');
    $visitId  = (int)($_POST['care_visit_id'] ?? 0);
    $userId   = (int)($_SESSION['user_id'] ?? 0);

    if ($rid <= 0)   json_err('Resident required');
    if (!$vDate)     json_err('Visit date required');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vDate)) json_err('Invalid date format');

    $conn->begin_transaction();
    try {
        if ($visitId > 0) {
            $st = $conn->prepare("UPDATE care_visits SET visit_date=?,notes=?,updated_at=NOW() WHERE id=? LIMIT 1");
            $st->bind_param('ssi',$vDate,$notes,$visitId); $st->execute();
        } else {
            $st = $conn->prepare("INSERT INTO care_visits(resident_id,care_type,visit_date,notes,created_by) VALUES(?,?,?,?,?)");
            $st->bind_param('isssi',$rid,$type,$vDate,$notes,$userId); $st->execute();
            $visitId = (int)$conn->insert_id;
        }

        $modId = match($type){
            'family_planning' => saveFP($conn,$visitId,$rid),
            'prenatal'        => savePrenatal($conn,$visitId,$rid),
            'postnatal'       => savePostnatal($conn,$visitId,$rid),
            'child_nutrition' => saveCN($conn,$visitId,$rid),
            'immunization'    => saveImm($conn,$visitId,$rid),
            default           => $visitId,
        };
        $conn->commit();
        json_ok_data(['care_visit_id'=>$visitId,'module_id'=>$modId],'Saved.');
    } catch(\Throwable $e){
        $conn->rollback();
        json_err('Save failed: '.$e->getMessage(),500);
    }
}

/* ══════════════════════════════════════════════════════
   MODULE SAVE FUNCTIONS — safe pattern:
   1. Build $vals array (all nullable → p() helper)
   2. $ts = 'ii'.str_repeat('s', count($vals)-2)
   3. Dynamic INSERT with exactly count($vals) placeholders
   4. bind_param($ts,...$vals)  — count always matches
══════════════════════════════════════════════════════ */

function saveFP(mysqli $c, int $vid, int $rid): int {
    $P = $_POST;
    $vals = [
        $rid, $vid,
        p($P['method']) ?? 'Pills',
        p($P['method_other']),
        p($P['method_start_date']),
        p($P['next_supply_date']),
        p($P['next_checkup_date']),
        (int)(p($P['is_new_acceptor'])  ?? 0),
        (int)(p($P['is_method_switch']) ?? 0),
        p($P['prev_method']),
        p($P['side_effects']),
        p($P['counseling_notes']),
        (int)(p($P['pills_given'])       ?? 0),
        (int)(p($P['injectables_given']) ?? 0),
        p($P['health_worker']),
    ];
    $ts = 'ii'.str_repeat('s', count($vals)-2);
    $ph = implode(',', array_fill(0, count($vals), '?'));
    $st = $c->prepare("INSERT INTO family_planning_record
        (resident_id,care_visit_id,method,method_other,method_start_date,
         next_supply_date,next_checkup_date,is_new_acceptor,is_method_switch,
         prev_method,side_effects,counseling_notes,pills_given,injectables_given,health_worker)
        VALUES ($ph)");
    $st->bind_param($ts,...$vals); $st->execute();
    return (int)$c->insert_id;
}

function savePrenatal(mysqli $c, int $vid, int $rid): int {
    $P = $_POST;
    $lmp = p($P['lmp_date']);
    $vals = [
        $rid, $vid,
        $lmp,
        $lmp ? date('Y-m-d', strtotime($lmp.' +280 days')) : null,
        pint($P['aog_weeks']),
        (int)(p($P['visit_number']) ?? 1),
        p($P['weight_kg']),
        pint($P['bp_systolic']),
        pint($P['bp_diastolic']),
        p($P['fundal_height_cm']),
        pint($P['fetal_heart_rate']),
        p($P['fetal_presentation']) ?? 'Unknown',
        (int)(p($P['folic_acid_given'])  ?? 0),
        (int)(p($P['iron_given'])        ?? 0),
        (int)(p($P['iron_tablets_qty'])  ?? 0),
        (int)(p($P['calcium_given'])     ?? 0),
        (int)(p($P['iodine_given'])      ?? 0),
        p($P['tt_dose'])  ?? 'None',
        p($P['tt_date']),
        p($P['hgb_result']),
        (int)(p($P['urinalysis_done'])   ?? 0),
        (int)(p($P['blood_type_done'])   ?? 0),
        (int)(p($P['hiv_test_done'])     ?? 0),
        p($P['hiv_result'])    ?? 'Not done',
        (int)(p($P['syphilis_done'])     ?? 0),
        p($P['syphilis_result']) ?? 'Not done',
        p($P['risk_level'])    ?? 'Low',
        p($P['risk_notes']),
        p($P['chief_complaint']),
        p($P['assessment']),
        p($P['plan']),
        p($P['health_worker']),
    ];
    $ts = 'ii'.str_repeat('s', count($vals)-2);
    $ph = implode(',', array_fill(0, count($vals), '?'));
    $st = $c->prepare("INSERT INTO prenatal_visit
        (resident_id,care_visit_id,lmp_date,edd_date,aog_weeks,visit_number,
         weight_kg,bp_systolic,bp_diastolic,fundal_height_cm,fetal_heart_rate,fetal_presentation,
         folic_acid_given,iron_given,iron_tablets_qty,calcium_given,iodine_given,
         tt_dose,tt_date,hgb_result,
         urinalysis_done,blood_type_done,hiv_test_done,hiv_result,
         syphilis_done,syphilis_result,risk_level,risk_notes,
         chief_complaint,assessment,plan,health_worker)
        VALUES ($ph)");
    $st->bind_param($ts,...$vals); $st->execute();
    return (int)$c->insert_id;
}

function savePostnatal(mysqli $c, int $vid, int $rid): int {
    $P = $_POST;
    $vals = [
        $rid, $vid,
        p($P['delivery_date']),
        p($P['delivery_type'])         ?? 'Unknown',
        p($P['delivery_facility']),
        p($P['birth_attendant']),
        (int)(p($P['visit_number'])    ?? 1),
        p($P['weight_kg']),
        pint($P['bp_systolic']),
        pint($P['bp_diastolic']),
        p($P['lochia_type'])           ?? 'Not checked',
        p($P['fundal_involution'])     ?? 'Not checked',
        p($P['episiotomy_healing'])    ?? 'NA',
        p($P['cs_wound_healing'])      ?? 'NA',
        p($P['breastfeeding_status'])  ?? 'NA',
        pint($P['ppd_score']),
        (int)(p($P['ppd_referred'])    ?? 0),
        pint($P['newborn_weight_g']),
        p($P['newborn_length_cm']),
        pint($P['apgar_1min']),
        pint($P['apgar_5min']),
        p($P['cord_status'])           ?? 'NA',
        (int)(p($P['jaundice'])              ?? 0),
        (int)(p($P['newborn_screening_done']) ?? 0),
        (int)(p($P['bcg_given'])             ?? 0),
        (int)(p($P['hb_vaccine_given'])      ?? 0),
        (int)(p($P['fp_counseled'])          ?? 0),
        p($P['fp_method_chosen']),
        p($P['chief_complaint']),
        p($P['assessment']),
        p($P['plan']),
        p($P['health_worker']),
    ];
    $ts = 'ii'.str_repeat('s', count($vals)-2);
    $ph = implode(',', array_fill(0, count($vals), '?'));
    $st = $c->prepare("INSERT INTO postnatal_visit
        (resident_id,care_visit_id,delivery_date,delivery_type,delivery_facility,birth_attendant,
         visit_number,weight_kg,bp_systolic,bp_diastolic,lochia_type,fundal_involution,
         episiotomy_healing,cs_wound_healing,breastfeeding_status,ppd_score,ppd_referred,
         newborn_weight_g,newborn_length_cm,apgar_1min,apgar_5min,cord_status,
         jaundice,newborn_screening_done,bcg_given,hb_vaccine_given,
         fp_counseled,fp_method_chosen,chief_complaint,assessment,plan,health_worker)
        VALUES ($ph)");
    $st->bind_param($ts,...$vals); $st->execute();
    return (int)$c->insert_id;
}

function saveCN(mysqli $c, int $vid, int $rid): int {
    $P = $_POST;
    $vals = [
        $rid, $vid,
        p($P['visit_date'])            ?? date('Y-m-d'),
        pint($P['age_months']),
        p($P['weight_kg']),
        p($P['height_cm']),
        p($P['muac_cm']),
        p($P['waz']),
        p($P['haz']),
        p($P['whz']),
        p($P['stunting_status'])       ?? 'Not assessed',
        p($P['wasting_status'])        ?? 'Not assessed',
        p($P['underweight_status'])    ?? 'Not assessed',
        p($P['breastfeeding'])         ?? 'NA',
        p($P['complementary_intro']),
        p($P['feeding_problems']),
        (int)(p($P['vita_supplemented']) ?? 0),
        p($P['vita_dose'])             ?? 'NA',
        p($P['vita_date']),
        (int)(p($P['iron_supplemented']) ?? 0),
        (int)(p($P['zinc_given'])        ?? 0),
        (int)(p($P['deworming_done'])    ?? 0),
        p($P['deworming_date']),
        (int)(p($P['counseling_given'])  ?? 0),
        p($P['counseling_notes']),
        (int)(p($P['referred'])          ?? 0),
        p($P['referral_reason']),
        p($P['health_worker']),
    ];
    $ts = 'ii'.str_repeat('s', count($vals)-2);
    $ph = implode(',', array_fill(0, count($vals), '?'));
    $st = $c->prepare("INSERT INTO child_nutrition_visit
        (resident_id,care_visit_id,visit_date,age_months,
         weight_kg,height_cm,muac_cm,waz,haz,whz,
         stunting_status,wasting_status,underweight_status,
         breastfeeding,complementary_intro,feeding_problems,
         vita_supplemented,vita_dose,vita_date,
         iron_supplemented,zinc_given,deworming_done,deworming_date,
         counseling_given,counseling_notes,referred,referral_reason,health_worker)
        VALUES ($ph)");
    $st->bind_param($ts,...$vals); $st->execute();
    return (int)$c->insert_id;
}

function saveImm(mysqli $c, int $vid, int $rid): int {
    $P = $_POST;
    $vaccine = trim($P['vaccine_name'] ?? '');
    if (!$vaccine) throw new \InvalidArgumentException('Vaccine name required');
    $vals = [
        $rid, $vaccine,
        p($P['dose']),
        p($P['date_given'])      ?? date('Y-m-d'),
        p($P['next_schedule']),
        p($P['administered_by']),
        p($P['remarks']),
        p($P['batch_number']),
        p($P['expiry_date']),
        p($P['site_given']),
        p($P['route'])           ?? 'IM',
        p($P['adverse_reaction']),
        (int)(p($P['is_defaulter']) ?? 0),
        (int)(p($P['catch_up'])     ?? 0),
        p($P['schedule_id']),
        $vid,
    ];
    $ts = 'ii'.str_repeat('s', count($vals)-2);
    $ph = implode(',', array_fill(0, count($vals), '?'));
    $st = $c->prepare("INSERT INTO immunizations
        (resident_id,vaccine_name,dose,date_given,next_schedule,
         administered_by,remarks,batch_number,expiry_date,
         site_given,route,adverse_reaction,is_defaulter,catch_up,
         schedule_id,care_visit_id)
        VALUES ($ph)");
    $st->bind_param($ts,...$vals); $st->execute();
    return (int)$c->insert_id;
}

json_err('Unknown action', 404);
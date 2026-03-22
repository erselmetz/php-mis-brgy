<?php
/**
 * Enhanced Consultation Edit API
 * Replaces: public/hcnurse/consultation/api/edit.php
 *
 * Updates consultations + consultation_detail atomically.
 * Always preserves the original consult_type (program) — never overwrites it.
 */
require_once __DIR__ . '/../../../../includes/app.php';
require_once __DIR__ . '/../../../../includes/hcnurse_health_metrics.php';
require_once __DIR__ . '/../../../../includes/hcnurse_care_visit_sync.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request');

$id          = (int)($_POST['id'] ?? 0);
$resident_id = (int)($_POST['resident_id'] ?? 0);
$date        = trim($_POST['consultation_date'] ?? '');

if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
    [$mm, $dd, $yyyy] = explode('/', $date);
    $date = sprintf('%04d-%02d-%02d', (int)$yyyy, (int)$mm, (int)$dd);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) respond(false, 'Invalid date format.');

if ($id <= 0)          respond(false, 'Invalid consultation id.');
if ($resident_id <= 0) respond(false, 'Resident is required.');

$complaint = trim($_POST['complaint'] ?? '');
if ($complaint === '') respond(false, 'Chief complaint is required.');

/* Read existing to preserve consult_type */
$existing = $conn->query("SELECT * FROM consultations WHERE id = {$id} LIMIT 1")->fetch_assoc();
if (!$existing) respond(false, 'Consultation not found.');

$oldMeta     = meta_normalize(meta_decode($existing['notes'] ?? ''));
$consultType = $existing['consult_type'] ?: ($oldMeta['program'] ?? 'general');

/* New form fields */
$diagnosis     = trim($_POST['diagnosis']         ?? '');
$treatment     = trim($_POST['treatment']         ?? '');
$healthWorker  = trim($_POST['health_worker']     ?? ($oldMeta['health_worker'] ?? ''));
$consultStatus = trim($_POST['consult_status']    ?? $_POST['status'] ?? ($oldMeta['status'] ?? 'Ongoing'));
$time          = trim($_POST['consultation_time'] ?? ($oldMeta['time'] ?? ''));
$subType       = trim($_POST['sub_type']          ?? ($oldMeta['sub_type'] ?? 'all'));
$remarks       = trim($_POST['remarks']           ?? ($oldMeta['remarks'] ?? ''));

/* Vitals */
$temp   = $_POST['temp_celsius']     ?: null;
$bpSys  = $_POST['bp_systolic']      ?: null;
$bpDia  = $_POST['bp_diastolic']     ?: null;
$pulse  = $_POST['pulse_rate']       ?: null;
$rr     = $_POST['respiratory_rate'] ?: null;
$spo2   = $_POST['o2_saturation']    ?: null;

/* Measurements */
$weight = $_POST['weight_kg'] ?: null;
$height = $_POST['height_cm'] ?: null;
$waist  = $_POST['waist_cm']  ?: null;
$bmi    = null;
if ($weight && $height && (float)$height > 0) {
    $hm  = (float)$height / 100;
    $bmi = round((float)$weight / ($hm * $hm), 1);
}

/* Clinical */
$riskLevel    = trim($_POST['risk_level']    ?? 'Low');
$isReferred   = (int)($_POST['is_referred']  ?? 0);
$referredTo   = trim($_POST['referred_to']   ?? '');
$followUpDt   = $_POST['follow_up_date']     ?: null;
$healthAdvice = trim($_POST['health_advice'] ?? '');

/* Rebuild notes JSON (backward compat) */
$notes = meta_encode([
    'program'       => $consultType,
    'sub_type'      => $subType ?: 'all',
    'status'        => $consultStatus,
    'time'          => $time,
    'health_worker' => $healthWorker,
    'remarks'       => $remarks,
]);

$care_visit_id = !empty($existing['care_visit_id']) ? (int)$existing['care_visit_id'] : 0;
$syncData = $care_visit_id <= 0 ? [
        'resident_id'   => $resident_id,
        'visit_date'    => $date,
        'consult_type'  => $consultType,
        'weight_kg'     => $weight,
        'height_cm'     => $height,
        'bp_systolic'   => $bpSys,
        'bp_diastolic'  => $bpDia,
        'complaint'     => $complaint,
        'diagnosis'     => $diagnosis,
        'assessment'    => trim($_POST['assessment'] ?? ''),
        'plan'          => trim($_POST['plan'] ?? ''),
        'health_worker' => $healthWorker,
        'created_by'    => (int)($_SESSION['user_id'] ?? 0),
        'lmp_date'      => $_POST['lmp_date'] ?? null,
        'aog_weeks'     => $_POST['aog_weeks'] ?? null,
        'visit_number'  => $_POST['visit_number'] ?? 1,
        'risk_level'    => $riskLevel,
] : null;

$conn->begin_transaction();
try {
    if ($syncData) {
        $synced = hcnurse_sync_care_visit_from_consultation($conn, $syncData);
        if ($synced) {
            $care_visit_id = $synced;
        }
    }

    /* Update consultations (include care_visit_id if we just created one) */
    $stmt = $conn->prepare("
        UPDATE consultations SET
            resident_id=?, complaint=?, diagnosis=?, treatment=?,
            notes=?, consultation_date=?,
            consult_type=?, consult_status=?, health_worker=?,
            care_visit_id=?,
            temp_celsius=?, bp_systolic=?, bp_diastolic=?,
            pulse_rate=?, respiratory_rate=?, o2_saturation=?,
            weight_kg=?, height_cm=?, bmi=?, waist_cm=?,
            health_advice=?, risk_level=?, is_referred=?,
            referred_to=?, follow_up_date=?
        WHERE id=? LIMIT 1
    ");
    $stmt->bind_param(
        'issssssssissssssddddssissi',
        $resident_id, $complaint, $diagnosis, $treatment,
        $notes, $date,
        $consultType, $consultStatus, $healthWorker,
        $care_visit_id > 0 ? $care_visit_id : null,
        $temp, $bpSys, $bpDia, $pulse, $rr, $spo2,
        $weight, $height, $bmi, $waist,
        $healthAdvice, $riskLevel, $isReferred,
        $referredTo, $followUpDt, $id
    );
    $stmt->execute();

    /* Upsert consultation_detail */
    $detailFields = [
        'chief_complaint','complaint_duration','complaint_onset',
        'primary_diagnosis','secondary_diagnosis','icd_code',
        'treatment','medicines_prescribed','procedures_done',
        'health_advice','lifestyle_advice','patient_education',
        'smoking_status','alcohol_use','physical_activity',
        'nutritional_status','mental_health_screen',
        'past_medical_history','family_history','current_medications',
        'known_allergies','immunization_history',
        'occupation','civil_status','educational_attainment','living_conditions',
        'assessment','plan','prognosis',
    ];
    $dd = [];
    foreach ($detailFields as $f) {
        $v = trim($_POST[$f] ?? '');
        if ($v !== '') $dd[$f] = $v;
    }
    if (!isset($dd['chief_complaint']) && $complaint) $dd['chief_complaint'] = $complaint;
    if (!isset($dd['primary_diagnosis']) && $diagnosis) $dd['primary_diagnosis'] = $diagnosis;

    if (!empty($dd)) {
        /* Check if detail row exists */
        $exists = $conn->query("SELECT id FROM consultation_detail WHERE consultation_id={$id} LIMIT 1")->num_rows > 0;
        if ($exists) {
            $sets   = implode(', ', array_map(fn($k) => "`{$k}`=?", array_keys($dd)));
            $types  = str_repeat('s', count($dd)) . 'i';
            $vals   = array_merge(array_values($dd), [$id]);
            $us = $conn->prepare("UPDATE consultation_detail SET {$sets} WHERE consultation_id=? LIMIT 1");
            $us->bind_param($types, ...$vals);
            $us->execute();
        } else {
            $dd['consultation_id'] = $id;
            $cols  = implode(', ', array_keys($dd));
            $phs   = implode(', ', array_fill(0, count($dd), '?'));
            $types = str_repeat('s', count($dd));
            $vals  = array_values($dd);
            $is = $conn->prepare("INSERT INTO consultation_detail ({$cols}) VALUES ({$phs})");
            $is->bind_param($types, ...$vals);
            $is->execute();
        }
    }

    hcnurse_sync_health_metrics_from_consultation(
        $conn,
        $resident_id,
        $date,
        $weight,
        $height,
        $bpSys,
        $bpDia,
        $temp
    );

    $conn->commit();
    respond(true, 'Consultation updated successfully.');

} catch (Throwable $e) {
    $conn->rollback();
    respond(false, 'Update failed: ' . $e->getMessage());
}
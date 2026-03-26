<?php
/**
 * Enhanced Consultation Add API
 * Replaces: public/hcnurse/consultation/api/add.php
 *
 * Saves to BOTH:
 *   consultations  — core record + vitals + type + status
 *   consultation_detail — full clinical narrative, history, health advice
 *
 * Backward compatible: old fields (complaint, diagnosis, treatment, notes) still work.
 * New fields are all optional → old forms won't break.
 */
require_once __DIR__ . '/../../../../includes/app.php';
require_once __DIR__ . '/../../../../includes/hcnurse_health_metrics.php';
require_once __DIR__ . '/../../../../includes/hcnurse_care_visit_sync.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request');

/* ══════════════════════════════════
   CORE FIELDS (consultations table)
══════════════════════════════════ */
$resident_id    = (int)($_POST['resident_id'] ?? 0);
$date           = trim($_POST['consultation_date'] ?? '');
$care_visit_id  = !empty($_POST['care_visit_id']) ? (int)$_POST['care_visit_id'] : null;
$health_worker  = trim($_POST['health_worker'] ?? '');

/* Convert mm/dd/yyyy → yyyy-mm-dd */
if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
    [$mm, $dd, $yyyy] = explode('/', $date);
    $date = sprintf('%04d-%02d-%02d', (int)$yyyy, (int)$mm, (int)$dd);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) respond(false, 'Invalid date format.');

$complaint  = trim($_POST['complaint']  ?? '');
$diagnosis  = trim($_POST['diagnosis']  ?? '');
$treatment  = trim($_POST['treatment']  ?? '');

/* consult_type: from new form field, or fall back to old consultation_type/program */
$typeAllowed = ['general','immunization'];
$consultType = trim($_POST['consult_type'] ?? $_POST['consultation_type'] ?? 'general');
if (!in_array($consultType, $typeAllowed, true)) $consultType = 'general';

$consultStatus = trim($_POST['consult_status'] ?? $_POST['status'] ?? 'Ongoing');
$statusAllowed = ['Ongoing','Completed','Follow-up','Dismissed'];
if (!in_array($consultStatus, $statusAllowed, true)) $consultStatus = 'Ongoing';

/* Vital signs */
$temp   = $_POST['temp_celsius']     ?: null;
$bpSys  = $_POST['bp_systolic']      ?: null;
$bpDia  = $_POST['bp_diastolic']     ?: null;
$pulse  = $_POST['pulse_rate']       ?: null;
$rr     = $_POST['respiratory_rate'] ?: null;
$spo2   = $_POST['o2_saturation']    ?: null;

/* Body measurements */
$weight = $_POST['weight_kg']  ?: null;
$height = $_POST['height_cm']  ?: null;
$waist  = $_POST['waist_cm']   ?: null;

/* Auto-compute BMI if weight + height given */
$bmi = null;
if ($weight && $height && (float)$height > 0) {
    $hm  = (float)$height / 100;
    $bmi = round((float)$weight / ($hm * $hm), 1);
}

/* Clinical */
$riskLevel   = trim($_POST['risk_level']  ?? 'Low');
$isReferred  = (int)($_POST['is_referred']  ?? 0);
$referredTo  = trim($_POST['referred_to']   ?? '');
$followUpDt  = $_POST['follow_up_date'] ?: null;
$healthAdvice = trim($_POST['health_advice'] ?? '');

/* Build backward-compat notes JSON (preserves old query compatibility) */
$subType  = trim($_POST['sub_type']      ?? 'all');
$remarks  = trim($_POST['remarks']       ?? '');
$time     = trim($_POST['consultation_time'] ?? '');

$notes = meta_encode([
    'program'       => $consultType,
    'sub_type'      => $subType ?: 'all',
    'status'        => $consultStatus,
    'time'          => $time,
    'health_worker' => $health_worker,
    'remarks'       => $remarks,
]);

/* Validate */
if ($resident_id <= 0) respond(false, 'Please select a resident.');
if ($date === '')       respond(false, 'Date is required.');
if ($complaint === '')  respond(false, 'Chief complaint is required.');

/* ════════════════════════
   TRANSACTION
════════════════════════ */
$conn->begin_transaction();
try {
    /* 0. Auto-create care_visit + module when program type and no existing link */
    if ($care_visit_id <= 0) {
        $synced = hcnurse_sync_care_visit_from_consultation($conn, [
            'resident_id'    => $resident_id,
            'visit_date'     => $date,
            'consult_type'   => $consultType,
            'weight_kg'      => $weight,
            'height_cm'      => $height,
            'bp_systolic'    => $bpSys,
            'bp_diastolic'   => $bpDia,
            'complaint'      => $complaint,
            'diagnosis'      => $diagnosis,
            'assessment'     => trim($_POST['assessment'] ?? ''),
            'plan'           => trim($_POST['plan'] ?? ''),
            'health_worker'  => $health_worker,
            'created_by'     => (int)($_SESSION['user_id'] ?? 0),
            'lmp_date'       => $_POST['lmp_date'] ?? null,
            'aog_weeks'      => $_POST['aog_weeks'] ?? null,
            'visit_number'   => $_POST['visit_number'] ?? 1,
            'risk_level'     => $riskLevel,
        ]);
        if ($synced) {
            $care_visit_id = $synced;
        }
    }

    /* 1. Insert consultations */
    $stmt = $conn->prepare("
        INSERT INTO consultations
            (resident_id, complaint, diagnosis, treatment, notes,
             consultation_date, consult_type, consult_status, care_visit_id,
             health_worker, temp_celsius, bp_systolic, bp_diastolic,
             pulse_rate, respiratory_rate, o2_saturation,
             weight_kg, height_cm, bmi, waist_cm,
             health_advice, risk_level, is_referred, referred_to, follow_up_date)
        VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        'issssssssissssssddddssiss',
        $resident_id, $complaint, $diagnosis, $treatment, $notes,
        $date, $consultType, $consultStatus, $care_visit_id,
        $health_worker, $temp, $bpSys, $bpDia,
        $pulse, $rr, $spo2,
        $weight, $height, $bmi, $waist,
        $healthAdvice, $riskLevel, $isReferred, $referredTo, $followUpDt
    );
    $stmt->execute();
    $consultId = (int)$conn->insert_id;
    if (!$consultId) throw new RuntimeException('Insert failed');

    /* 2. Insert consultation_detail (all optional — won't break if empty) */
    $haDetail = false;
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
    $detailData = [];
    foreach ($detailFields as $f) {
        $v = trim($_POST[$f] ?? '');
        if ($v !== '') { $detailData[$f] = $v; $haDetail = true; }
    }
    // Fill chief_complaint from main complaint if not set in detail form
    if (!isset($detailData['chief_complaint']) && $complaint !== '') {
        $detailData['chief_complaint'] = $complaint;
        $haDetail = true;
    }
    // Fill primary_diagnosis from diagnosis if not set
    if (!isset($detailData['primary_diagnosis']) && $diagnosis !== '') {
        $detailData['primary_diagnosis'] = $diagnosis;
        $haDetail = true;
    }
    if ($haDetail) {
        $detailData['consultation_id'] = $consultId;
        $cols   = implode(', ', array_keys($detailData));
        $phs    = implode(', ', array_fill(0, count($detailData), '?'));
        $types  = str_repeat('s', count($detailData));
        $values = array_values($detailData);
        $ds = $conn->prepare("INSERT INTO consultation_detail ({$cols}) VALUES ({$phs})");
        $ds->bind_param($types, ...$values);
        $ds->execute();
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
    respond(true, 'Consultation added successfully.', ['id' => $consultId]);

} catch (Throwable $e) {
    $conn->rollback();
    respond(false, 'Database error: ' . $e->getMessage());
}
<?php
/**
 * Enhanced Consultation View API
 * Replaces: public/hcnurse/consultation/api/view.php
 *
 * Returns the full clinical record: core + detail + computed fields.
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) respond(false, 'Invalid id');

$sql = "
    SELECT
        c.*,
        r.first_name, r.middle_name, r.last_name, r.suffix,
        r.birthdate, r.gender, r.address, r.contact_no,
        r.civil_status AS r_civil_status,
        r.occupation   AS r_occupation,
        cd.chief_complaint       AS cd_chief_complaint,
        cd.complaint_duration,
        cd.complaint_onset,
        cd.primary_diagnosis,
        cd.secondary_diagnosis,
        cd.icd_code,
        cd.medicines_prescribed,
        cd.procedures_done,
        cd.health_advice         AS cd_health_advice,
        cd.lifestyle_advice,
        cd.patient_education,
        cd.smoking_status,
        cd.alcohol_use,
        cd.physical_activity,
        cd.nutritional_status,
        cd.mental_health_screen,
        cd.past_medical_history,
        cd.family_history,
        cd.current_medications,
        cd.known_allergies,
        cd.immunization_history,
        cd.occupation            AS cd_occupation,
        cd.civil_status          AS cd_civil_status,
        cd.educational_attainment,
        cd.living_conditions,
        cd.assessment            AS cd_assessment,
        cd.plan,
        cd.prognosis
    FROM consultations c
    INNER JOIN residents r ON r.id = c.resident_id AND r.deleted_at IS NULL
    LEFT JOIN consultation_detail cd ON cd.consultation_id = c.id
    WHERE c.id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) respond(false, 'Not found');

/* Decode legacy notes JSON for backward compat */
$legacyMeta = meta_decode($row['notes'] ?? '');
$legacyMeta = meta_normalize($legacyMeta);

/* Build unified fullname */
$parts = array_filter([$row['first_name']??'', $row['middle_name']??'', $row['last_name']??'', $row['suffix']??'']);
$fullname = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));

/* Age */
$age = '';
if (!empty($row['birthdate'])) {
    $bd  = new DateTime($row['birthdate']);
    $age = $bd->diff(new DateTime())->y . ' years old';
}

/* BMI classification */
$bmiClass = null;
if (!empty($row['bmi'])) {
    $b = (float)$row['bmi'];
    $bmiClass = $b < 18.5 ? 'Underweight' : ($b < 25 ? 'Normal' : ($b < 30 ? 'Overweight' : 'Obese'));
}

respond(true, 'OK', ['data' => [
    /* Patient */
    'id'             => (int)$row['id'],
    'resident_id'    => (int)$row['resident_id'],
    'fullname'       => $fullname,
    'birthdate'      => $row['birthdate'] ?? '',
    'age'            => $age,
    'gender'         => $row['gender'] ?? '',
    'address'        => $row['address'] ?? '',
    'contact_no'     => $row['contact_no'] ?? '',

    /* Consult core */
    'consultation_date' => $row['consultation_date'] ?? '',
    'consult_type'      => $row['consult_type'] ?? ($legacyMeta['program'] ?? 'general'),
    'consult_status'    => $row['consult_status'] ?? ($legacyMeta['status'] ?? ''),
    'health_worker'     => $row['health_worker'] ?? ($legacyMeta['health_worker'] ?? ''),
    'sub_type'          => $legacyMeta['sub_type'] ?? '',
    'time'              => $legacyMeta['time'] ?? '',
    'remarks'           => $legacyMeta['remarks'] ?? '',

    /* Vital signs */
    'temp_celsius'      => $row['temp_celsius'] ?? '',
    'bp_systolic'       => $row['bp_systolic'] ?? '',
    'bp_diastolic'      => $row['bp_diastolic'] ?? '',
    'pulse_rate'        => $row['pulse_rate'] ?? '',
    'respiratory_rate'  => $row['respiratory_rate'] ?? '',
    'o2_saturation'     => $row['o2_saturation'] ?? '',

    /* Measurements */
    'weight_kg'   => $row['weight_kg'] ?? '',
    'height_cm'   => $row['height_cm'] ?? '',
    'bmi'         => $row['bmi'] ?? '',
    'bmi_class'   => $bmiClass,
    'waist_cm'    => $row['waist_cm'] ?? '',

    /* Clinical */
    'complaint'        => $row['complaint'] ?? '',
    'diagnosis'        => $row['diagnosis'] ?? '',
    'treatment'        => $row['treatment'] ?? '',
    'health_advice'    => $row['health_advice'] ?? ($row['cd_health_advice'] ?? ''),
    'risk_level'       => $row['risk_level'] ?? 'Low',
    'is_referred'      => (int)($row['is_referred'] ?? 0),
    'referred_to'      => $row['referred_to'] ?? '',
    'follow_up_date'   => $row['follow_up_date'] ?? '',

    /* Detail — complaint */
    'chief_complaint'      => $row['cd_chief_complaint'] ?? $row['complaint'] ?? '',
    'complaint_duration'   => $row['complaint_duration'] ?? '',
    'complaint_onset'      => $row['complaint_onset'] ?? '',

    /* Detail — diagnosis */
    'primary_diagnosis'    => $row['primary_diagnosis'] ?? $row['diagnosis'] ?? '',
    'secondary_diagnosis'  => $row['secondary_diagnosis'] ?? '',
    'icd_code'             => $row['icd_code'] ?? '',

    /* Detail — treatment */
    'medicines_prescribed' => $row['medicines_prescribed'] ?? '',
    'procedures_done'      => $row['procedures_done'] ?? '',

    /* Detail — health advice */
    'lifestyle_advice'     => $row['lifestyle_advice'] ?? '',
    'patient_education'    => $row['patient_education'] ?? '',

    /* Detail — health potential */
    'smoking_status'       => $row['smoking_status'] ?? 'NA',
    'alcohol_use'          => $row['alcohol_use'] ?? 'NA',
    'physical_activity'    => $row['physical_activity'] ?? 'NA',
    'nutritional_status'   => $row['nutritional_status'] ?? 'NA',
    'mental_health_screen' => $row['mental_health_screen'] ?? 'Not screened',

    /* Detail — medical history */
    'past_medical_history' => $row['past_medical_history'] ?? '',
    'family_history'       => $row['family_history'] ?? '',
    'current_medications'  => $row['current_medications'] ?? '',
    'known_allergies'      => $row['known_allergies'] ?? '',
    'immunization_history' => $row['immunization_history'] ?? '',

    /* Detail — social */
    'occupation'              => $row['cd_occupation']  ?? $row['r_occupation']  ?? '',
    'civil_status'            => $row['cd_civil_status'] ?? $row['r_civil_status'] ?? '',
    'educational_attainment'  => $row['educational_attainment'] ?? '',
    'living_conditions'       => $row['living_conditions'] ?? '',

    /* Detail — assessment */
    'assessment'   => $row['cd_assessment'] ?? $row['assessment'] ?? '',
    'plan'         => $row['plan'] ?? '',
    'prognosis'    => $row['prognosis'] ?? 'NA',
]]);
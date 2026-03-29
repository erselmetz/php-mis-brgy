<?php
/**
 * Professional clinical narrative + visit cards for care-visits print.
 * Used only by print.php — consultations + consultation_detail + module tables.
 */

/** SQL fragment: consultations columns with stable aliases (pass table alias: cons or c). */
function care_print_consult_projection(string $a): string {
    return "
    {$a}.id AS consult_id,
    {$a}.care_visit_id AS cons_care_visit_id,
    {$a}.complaint AS cons_complaint,
    {$a}.diagnosis AS cons_diagnosis,
    {$a}.treatment AS cons_treatment,
    {$a}.notes AS cons_notes_meta,
    {$a}.consult_status AS cons_status,
    {$a}.health_worker AS cons_health_worker,
    {$a}.temp_celsius AS cons_temp,
    {$a}.bp_systolic AS cons_bp_sys,
    {$a}.bp_diastolic AS cons_bp_dia,
    {$a}.pulse_rate AS cons_pulse,
    {$a}.respiratory_rate AS cons_rr,
    {$a}.o2_saturation AS cons_spo2,
    {$a}.weight_kg AS cons_weight,
    {$a}.height_cm AS cons_height,
    {$a}.bmi AS cons_bmi,
    {$a}.waist_cm AS cons_waist,
    {$a}.health_advice AS cons_health_advice,
    {$a}.risk_level AS cons_risk,
    {$a}.is_referred AS cons_is_referred,
    {$a}.referred_to AS cons_referred_to,
    {$a}.follow_up_date AS cons_follow_up
    ";
}

function care_print_detail_projection(): string {
    return "
    cd.chief_complaint AS cd_chief_complaint,
    cd.complaint_duration AS cd_complaint_duration,
    cd.complaint_onset AS cd_complaint_onset,
    cd.primary_diagnosis AS cd_primary_diagnosis,
    cd.secondary_diagnosis AS cd_secondary_diagnosis,
    cd.icd_code AS cd_icd,
    cd.treatment AS cd_treatment_detail,
    cd.medicines_prescribed AS cd_meds,
    cd.procedures_done AS cd_procedures,
    cd.health_advice AS cd_health_advice,
    cd.lifestyle_advice AS cd_lifestyle,
    cd.patient_education AS cd_patient_edu,
    cd.smoking_status AS cd_smoking,
    cd.alcohol_use AS cd_alcohol,
    cd.physical_activity AS cd_activity,
    cd.nutritional_status AS cd_nutrition,
    cd.mental_health_screen AS cd_mental,
    cd.past_medical_history AS cd_pmh,
    cd.family_history AS cd_fh,
    cd.current_medications AS cd_meds_curr,
    cd.known_allergies AS cd_allergies,
    cd.immunization_history AS cd_imm_hx,
    cd.occupation AS cd_occupation,
    cd.civil_status AS cd_civil,
    cd.educational_attainment AS cd_edu,
    cd.living_conditions AS cd_living,
    cd.assessment AS cd_assessment,
    cd.plan AS cd_plan,
    cd.prognosis AS cd_prognosis
    ";
}

function care_print_legacy_remarks(?string $notesMeta): string {
    $meta = meta_normalize(meta_decode($notesMeta ?? ''));
    $parts = [];
    if (!empty($meta['remarks'])) {
        $parts[] = trim((string)$meta['remarks']);
    }
    if (!empty($meta['time'])) {
        $parts[] = 'Time recorded: ' . trim((string)$meta['time']);
    }
    if (!empty($meta['sub_type']) && $meta['sub_type'] !== 'all') {
        $parts[] = 'Sub-type: ' . trim((string)$meta['sub_type']);
    }
    return trim(implode("\n", array_filter($parts)));
}

/** Build <dl> rows; skip empty values. */
function care_print_dl_section(string $title, array $pairs): string {
    $out = '';
    foreach ($pairs as $label => $val) {
        if ($val === null) {
            continue;
        }
        $t = is_string($val) ? trim($val) : (string)$val;
        if ($t === '' || $t === 'NA' || $t === 'Not screened') {
            continue;
        }
        $out .= '<dt>' . h($label) . '</dt><dd>' . care_print_dd_content($t) . '</dd>';
    }
    if ($out === '') {
        return '';
    }
    return '<div class="vc-sec"><div class="vc-sec-title">' . h($title) . '</div><dl class="vc-dl">' . $out . '</dl></div>';
}

function care_print_dd_content(string $text): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    return (str_contains($text, "\n") || strlen($text) > 120) ? nl2br(h($text)) : h($text);
}

function care_print_vitals_block(array $r): string {
    $pairs = [];
    if (($v = $r['cons_temp'] ?? null) !== null && $v !== '') {
        $pairs['Temperature (°C)'] = (string)$v;
    }
    $sys = $r['cons_bp_sys'] ?? null;
    $dia = $r['cons_bp_dia'] ?? null;
    if (($sys !== null && $sys !== '') || ($dia !== null && $dia !== '')) {
        $pairs['Blood pressure (mmHg)'] = trim((string)$sys . '/' . (string)$dia, '/');
    }
    foreach (
        [
            'Pulse (bpm)'       => $r['cons_pulse'] ?? null,
            'Respiratory rate'   => $r['cons_rr'] ?? null,
            'O₂ saturation (%)'  => $r['cons_spo2'] ?? null,
            'Weight (kg)'        => $r['cons_weight'] ?? null,
            'Height (cm)'        => $r['cons_height'] ?? null,
            'BMI'                => $r['cons_bmi'] ?? null,
            'Waist (cm)'         => $r['cons_waist'] ?? null,
        ] as $lbl => $v
    ) {
        if ($v !== null && $v !== '') {
            $pairs[$lbl] = (string)$v;
        }
    }
    return care_print_dl_section('Vital signs and anthropometry', $pairs);
}

function care_print_consult_core_block(array $r): string {
    $pairs = [
        'Chief complaint'   => $r['cons_complaint'] ?? null,
        'Diagnosis'         => $r['cons_diagnosis'] ?? null,
        'Management / treatment' => $r['cons_treatment'] ?? null,
        'Health advice (consultation)' => $r['cons_health_advice'] ?? null,
        'Consultation status' => $r['cons_status'] ?? null,
        'Attending health worker' => $r['cons_health_worker'] ?? null,
    ];
    $leg = care_print_legacy_remarks($r['cons_notes_meta'] ?? null);
    if ($leg !== '') {
        $pairs['Administrative / form remarks'] = $leg;
    }
    return care_print_dl_section('Consultation (encounter form)', $pairs);
}

function care_print_risk_referral_block(array $r): string {
    $pairs = [
        'Risk level'    => $r['cons_risk'] ?? null,
        'Referred to'   => $r['cons_referred_to'] ?? null,
        'Follow-up date'=> $r['cons_follow_up'] ?? null,
    ];
    $ref = $r['cons_is_referred'] ?? null;
    if ($ref !== null && $ref !== '') {
        $pairs['Referred'] = ((int)$ref) ? 'Yes' : 'No';
    }
    return care_print_dl_section('Risk classification and referral', $pairs);
}

function care_print_detail_clinical_block(array $r): string {
    $html = '';
    $html .= care_print_dl_section('Chief complaint (detail)', [
        'Chief complaint'      => $r['cd_chief_complaint'] ?? null,
        'Duration'            => $r['cd_complaint_duration'] ?? null,
        'Onset'               => $r['cd_complaint_onset'] ?? null,
    ]);
    $html .= care_print_dl_section('Diagnosis (detail record)', [
        'Primary diagnosis'   => $r['cd_primary_diagnosis'] ?? null,
        'Secondary diagnosis' => $r['cd_secondary_diagnosis'] ?? null,
        'ICD code'            => $r['cd_icd'] ?? null,
    ]);
    $html .= care_print_dl_section('Treatment and procedures', [
        'Treatment notes'     => $r['cd_treatment_detail'] ?? null,
        'Medicines prescribed'=> $r['cd_meds'] ?? null,
        'Procedures done'     => $r['cd_procedures'] ?? null,
    ]);
    $html .= care_print_dl_section('Health education', [
        'Health advice'       => $r['cd_health_advice'] ?? null,
        'Lifestyle advice'    => $r['cd_lifestyle'] ?? null,
        'Patient education'   => $r['cd_patient_edu'] ?? null,
    ]);
    $html .= care_print_dl_section('Health status profile', [
        'Smoking'             => $r['cd_smoking'] ?? null,
        'Alcohol use'         => $r['cd_alcohol'] ?? null,
        'Physical activity'   => $r['cd_activity'] ?? null,
        'Nutritional status'  => $r['cd_nutrition'] ?? null,
        'Mental health screen'=> $r['cd_mental'] ?? null,
    ]);
    $html .= care_print_dl_section('Medical and social history', [
        'Past medical history'=> $r['cd_pmh'] ?? null,
        'Family history'      => $r['cd_fh'] ?? null,
        'Current medications' => $r['cd_meds_curr'] ?? null,
        'Known allergies'     => $r['cd_allergies'] ?? null,
        'Immunization history'=> $r['cd_imm_hx'] ?? null,
        'Occupation'          => $r['cd_occupation'] ?? null,
        'Civil status'        => $r['cd_civil'] ?? null,
        'Educational attainment' => $r['cd_edu'] ?? null,
        'Living conditions'   => $r['cd_living'] ?? null,
    ]);
    $html .= care_print_dl_section('Assessment and plan', [
        'Assessment'          => $r['cd_assessment'] ?? null,
        'Plan'                => $r['cd_plan'] ?? null,
        'Prognosis'           => $r['cd_prognosis'] ?? null,
    ]);
    return $html;
}

function care_print_full_consult_html(array $r): string {
    if (empty($r['consult_id'])) {
        return '';
    }
    return care_print_vitals_block($r)
        . care_print_consult_core_block($r)
        . care_print_risk_referral_block($r)
        . care_print_detail_clinical_block($r);
}

function care_print_module_prenatal(array $r): string {
    $pairs = [
        'ANC visit number'    => isset($r['visit_number']) && $r['visit_number'] !== '' && $r['visit_number'] !== null ? 'Visit ' . $r['visit_number'] : null,
        'LMP'                 => $r['lmp_date'] ?? null,
        'EDD'                 => $r['edd_date'] ?? null,
        'Age of gestation'    => isset($r['aog_weeks']) && $r['aog_weeks'] !== '' && $r['aog_weeks'] !== null ? $r['aog_weeks'] . ' weeks' : null,
        'Weight (kg)'         => $r['weight_kg'] ?? null,
        'Blood pressure'      => (isset($r['bp_systolic'], $r['bp_diastolic']) && $r['bp_systolic'] !== '' && $r['bp_diastolic'] !== '')
            ? $r['bp_systolic'] . '/' . $r['bp_diastolic'] . ' mmHg' : null,
        'Fundal height (cm)'  => $r['fundal_height_cm'] ?? null,
        'Fetal heart rate'    => isset($r['fetal_heart_rate']) && $r['fetal_heart_rate'] !== '' ? $r['fetal_heart_rate'] . ' bpm' : null,
        'Fetal presentation'  => $r['fetal_presentation'] ?? null,
        'Tetanus toxoid dose' => $r['tt_dose'] ?? null,
        'Risk level'          => $r['risk_level'] ?? null,
        'Module health worker'=> $r['health_worker'] ?? null,
        'Chief complaint (ANC)' => $r['chief_complaint'] ?? null,
        'Assessment'        => $r['assessment'] ?? null,
        'Plan'                => $r['plan'] ?? null,
    ];
    return care_print_dl_section('Antenatal care module (care visit)', $pairs);
}

function care_print_module_postnatal(array $r): string {
    $pairs = [
        'PNC visit number'    => isset($r['visit_number']) && $r['visit_number'] !== '' && $r['visit_number'] !== null ? 'PNC ' . $r['visit_number'] : null,
        'Delivery date'       => $r['delivery_date'] ?? null,
        'Delivery type'       => $r['delivery_type'] ?? null,
        'Blood pressure'      => (isset($r['bp_systolic'], $r['bp_diastolic']) && $r['bp_systolic'] !== '' && $r['bp_diastolic'] !== '')
            ? $r['bp_systolic'] . '/' . $r['bp_diastolic'] . ' mmHg' : null,
        'Lochia'              => $r['lochia_type'] ?? null,
        'Breastfeeding status'=> $r['breastfeeding_status'] ?? null,
        'PPD screening score' => $r['ppd_score'] ?? null,
        'Newborn weight (g)'  => isset($r['newborn_weight_g']) && $r['newborn_weight_g'] !== '' && $r['newborn_weight_g'] !== null
            ? number_format((int)$r['newborn_weight_g']) : null,
        'APGAR (1 / 5 min)'   => (isset($r['apgar_1min']) && $r['apgar_1min'] !== '' && $r['apgar_1min'] !== null)
            ? $r['apgar_1min'] . ' / ' . ($r['apgar_5min'] ?? '') : null,
        'Module health worker'=> $r['health_worker'] ?? null,
    ];
    return care_print_dl_section('Postnatal care module (care visit)', $pairs);
}

function care_print_module_fp(array $r): string {
    $method = trim((string)($r['method'] ?? ''));
    $mo = $method !== '' ? $method : null;
    if ($mo && trim((string)($r['method_other'] ?? '')) !== '') {
        $mo .= ' (' . trim((string)$r['method_other']) . ')';
    }
    $pairs = [
        'Family planning method' => $mo,
        'New acceptor'          => isset($r['is_new_acceptor']) && $r['is_new_acceptor'] !== null ? (((int)$r['is_new_acceptor']) ? 'Yes' : 'No') : null,
        'Method switch'         => isset($r['is_method_switch']) && $r['is_method_switch'] !== null ? (((int)$r['is_method_switch']) ? 'Yes' : 'No') : null,
        'Next supply date'      => $r['next_supply_date'] ?? null,
        'Next check-up date'    => $r['next_checkup_date'] ?? null,
        'Pills / cycles given'  => isset($r['pills_given']) && $r['pills_given'] !== '' && $r['pills_given'] !== null && (int)$r['pills_given'] !== 0
            ? (string)(int)$r['pills_given'] : null,
        'Side effects reported' => $r['side_effects'] ?? null,
        'Module health worker'  => $r['health_worker'] ?? null,
    ];
    return care_print_dl_section('Family planning module (care visit)', $pairs);
}

function care_print_module_child_nutrition(array $r): string {
    $pairs = [
        'Age (months)'        => $r['age_months'] ?? null,
        'Weight (kg)'       => $r['weight_kg'] ?? null,
        'Height (cm)'       => $r['height_cm'] ?? null,
        'MUAC (cm)'          => $r['muac_cm'] ?? null,
        'WAZ'                => $r['waz'] ?? null,
        'HAZ'                => $r['haz'] ?? null,
        'Stunting status'    => $r['stunting_status'] ?? null,
        'Wasting status'     => $r['wasting_status'] ?? null,
        'Vitamin A supplemented' => !empty($r['vita_supplemented']) ? 'Yes' : null,
        'Deworming done'     => !empty($r['deworming_done']) ? 'Yes' : null,
        'Module health worker'=> $r['health_worker'] ?? null,
    ];
    return care_print_dl_section('Child nutrition module (care visit)', $pairs);
}

function care_print_module_for_type(string $type, array $r): string {
    return match ($type) {
        'prenatal'        => care_print_module_prenatal($r),
        'postnatal'       => care_print_module_postnatal($r),
        'family_planning' => care_print_module_fp($r),
        'child_nutrition' => care_print_module_child_nutrition($r),
        default           => '',
    };
}

function care_print_visit_notes_block(?string $cvNotes): string {
    $t = trim((string)($cvNotes ?? ''));
    if ($t === '') {
        return '';
    }
    return '<div class="vc-sec"><div class="vc-sec-title">Care visit notes</div><div class="vc-narr">' . nl2br(h($t)) . '</div></div>';
}

function care_print_render_visit_card(array $r, string $type, bool $hidePatientName): string {
    $src = ($r['_source'] ?? '') === 'consultation'
        ? 'Consultation record (encounter form)'
        : 'Care visit record';
    if (!empty($r['consult_id']) && ($r['_source'] ?? '') !== 'consultation') {
        $src .= ' — linked consultation attached';
    }

    $prog = '';
    if (in_array($type, ['general', 'maternal', 'other'], true)) {
        $ct = trim((string)($r['care_type'] ?? $r['consult_type'] ?? ''));
        if ($ct !== '') {
            $prog = '<span class="vc-prog">Programme: ' . h(str_replace('_', ' ', $ct)) . '</span>';
        }
    }

    $patient = $hidePatientName ? '' : '<span class="vc-patient">' . h($r['resident_name'] ?? '') . '</span>';

    $mod = care_print_module_for_type($type, $r);
    $clin = care_print_full_consult_html($r);
    $vn = care_print_visit_notes_block($r['cv_notes'] ?? $r['notes'] ?? null);

    $body = $mod . $clin . $vn;
    if ($body === '') {
        $body = '<div class="vc-empty">No structured clinical fields captured for this entry.</div>';
    }

    return '<article class="visit-card">'
        . '<header class="vc-head">'
        . '<span class="vc-date mono">' . h($r['visit_date'] ?? '') . '</span>'
        . $patient
        . $prog
        . '<span class="vc-src">' . h($src) . '</span>'
        . '</header>'
        . '<div class="vc-body">' . $body . '</div>'
        . '</article>';
}

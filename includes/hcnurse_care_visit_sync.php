<?php
/**
 * Sync care_visit + module rows when a consultation is added/edited with a program type.
 * Ensures care_visits, prenatal_visit, postnatal_visit, family_planning_record,
 * child_nutrition_visit get populated even when users only use the Consultation flow.
 *
 * Requires hcnurse_health_metrics.php (for hcnurse_table_exists) or define it before including.
 */
if (!function_exists('hcnurse_table_exists')) {
    function hcnurse_table_exists(mysqli $conn, string $table): bool {
        $t = $conn->real_escape_string($table);
        $r = $conn->query("SHOW TABLES LIKE '{$t}'");
        return $r && $r->num_rows > 0;
    }
}

/**
 * Create care_visit + module row from consultation data. Returns care_visit_id or null.
 *
 * @param array $data Keys: resident_id, visit_date, consult_type, weight_kg, height_cm,
 *                    bp_systolic, bp_diastolic, complaint, diagnosis, health_worker, created_by
 */
function hcnurse_sync_care_visit_from_consultation(mysqli $conn, array $data): ?int {
    $rid = (int)($data['resident_id'] ?? 0);
    $vDate = trim($data['visit_date'] ?? '');
    $type = trim($data['consult_type'] ?? 'general');
    $userId = (int)($data['created_by'] ?? 0);
    $notes = trim($data['complaint'] ?? '') ?: null;
    if ($notes && !empty($data['diagnosis'])) {
        $notes = ($notes . ' | ' . trim($data['diagnosis']));
    }
    $healthWorker = trim($data['health_worker'] ?? '') ?: null;
    $weight = isset($data['weight_kg']) && $data['weight_kg'] !== '' ? (float)$data['weight_kg'] : null;
    $height = isset($data['height_cm']) && $data['height_cm'] !== '' ? (float)$data['height_cm'] : null;
    $bpSys = isset($data['bp_systolic']) && $data['bp_systolic'] !== '' ? (int)$data['bp_systolic'] : null;
    $bpDia = isset($data['bp_diastolic']) && $data['bp_diastolic'] !== '' ? (int)$data['bp_diastolic'] : null;

    if ($rid <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $vDate)) {
        return null;
    }

    $syncTypes = ['prenatal', 'postnatal', 'family_planning', 'child_nutrition', 'immunization'];
    if (!in_array($type, $syncTypes, true)) {
        return null;
    }

    if (!hcnurse_table_exists($conn, 'care_visits')) {
        return null;
    }

    $st = $conn->prepare("INSERT INTO care_visits(resident_id,care_type,visit_date,notes,created_by) VALUES(?,?,?,?,?)");
    if (!$st) return null;
    $st->bind_param('isssi', $rid, $type, $vDate, $notes, $userId);
    $st->execute();
    $visitId = (int)$conn->insert_id;
    if ($visitId <= 0) return null;

    $p = function ($v) { return ($v === '' || $v === null) ? null : $v; };
    $pint = function ($v) use ($p) { $n = $p($v); return $n === null ? null : (int)$n; };

    if ($type === 'prenatal' && hcnurse_table_exists($conn, 'prenatal_visit')) {
        $lmp = $p($data['lmp_date'] ?? null);
        $st = $conn->prepare("INSERT INTO prenatal_visit
            (resident_id,care_visit_id,lmp_date,edd_date,aog_weeks,visit_number,
             weight_kg,bp_systolic,bp_diastolic,fundal_height_cm,fetal_heart_rate,fetal_presentation,
             folic_acid_given,iron_given,iron_tablets_qty,calcium_given,iodine_given,
             tt_dose,tt_date,hgb_result,urinalysis_done,blood_type_done,hiv_test_done,hiv_result,
             syphilis_done,syphilis_result,risk_level,risk_notes,chief_complaint,assessment,plan,health_worker)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        if ($st) {
            $edd = $lmp ? date('Y-m-d', strtotime($lmp . ' +280 days')) : null;
            $aog = $pint($data['aog_weeks'] ?? null);
            $vn = (int)($data['visit_number'] ?? 1);
            $cc = $p($data['complaint'] ?? null);
            $ass = $p($data['assessment'] ?? null);
            $planVal = $p($data['plan'] ?? null);
            $risk = $p($data['risk_level'] ?? 'Low') ?: 'Low';
            $fh = null;
            $fhr = null;
            $st->bind_param('iissiiddiisiiiiisssiiiiisssssss',
                $rid, $visitId, $lmp, $edd, $aog, $vn,
                $weight, $bpSys, $bpDia, $fh, $fhr, 'Unknown',
                0, 0, 0, 0, 0, 'None', null, null, 0, 0, 0, 'Not done', 0, 'Not done',
                $risk, null, $cc, $ass, $planVal, $healthWorker
            );
            $st->execute();
        }
    } elseif ($type === 'postnatal' && hcnurse_table_exists($conn, 'postnatal_visit')) {
        $st = $conn->prepare("INSERT INTO postnatal_visit
            (resident_id,care_visit_id,delivery_date,delivery_type,delivery_facility,birth_attendant,
             visit_number,weight_kg,bp_systolic,bp_diastolic,lochia_type,fundal_involution,
             episiotomy_healing,cs_wound_healing,breastfeeding_status,ppd_score,ppd_referred,
             newborn_weight_g,newborn_length_cm,apgar_1min,apgar_5min,cord_status,
             jaundice,newborn_screening_done,bcg_given,hb_vaccine_given,fp_counseled,fp_method_chosen,
             chief_complaint,assessment,plan,health_worker)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        if ($st) {
            $cc = $p($data['complaint'] ?? null);
            $ass = $p($data['assessment'] ?? null);
            $planVal = $p($data['plan'] ?? null);
            $st->bind_param('iissssiddisssssiiidiisiiiiisssss',
                $rid, $visitId, null, 'Unknown', null, null, 1,
                $weight, $bpSys, $bpDia, 'Not checked', 'Not checked', 'NA', 'NA', 'NA', null, 0,
                null, null, null, null, 'NA', 0, 0, 0, 0, 0, null, $cc, $ass, $planVal, $healthWorker
            );
            $st->execute();
        }
    } elseif ($type === 'family_planning' && hcnurse_table_exists($conn, 'family_planning_record')) {
        $st = $conn->prepare("INSERT INTO family_planning_record
            (resident_id,care_visit_id,method,method_other,method_start_date,next_supply_date,next_checkup_date,
             is_new_acceptor,is_method_switch,prev_method,side_effects,counseling_notes,pills_given,injectables_given,health_worker)
            VALUES (?,?,'Pills',?,?,?,?,?,?,?,?,?,?,?,?)");
        if ($st) {
            $st->bind_param('iissssiisssiis', $rid, $visitId, null, null, null, null, 0, 0, null, null, null, 0, 0, $healthWorker);
            $st->execute();
        }
    } elseif ($type === 'child_nutrition' && hcnurse_table_exists($conn, 'child_nutrition_visit')) {
        $st = $conn->prepare("INSERT INTO child_nutrition_visit
            (resident_id,care_visit_id,visit_date,age_months,weight_kg,height_cm,muac_cm,waz,haz,whz,
             stunting_status,wasting_status,underweight_status,breastfeeding,complementary_intro,feeding_problems,
             vita_supplemented,vita_dose,vita_date,iron_supplemented,zinc_given,deworming_done,deworming_date,
             counseling_given,counseling_notes,referred,referral_reason,health_worker)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        if ($st) {
            $st->bind_param('iisiiddssssssssssisississis',
                $rid, $visitId, $vDate, null, $weight, $height, null, null, null, null,
                'Not assessed', 'Not assessed', 'Not assessed', 'NA', null, null,
                0, 'NA', null, 0, 0, 0, null, 0, null, 0, null, $healthWorker
            );
            $st->execute();
        }
    }
    /* immunization: care_visit created above; immunizations table needs vaccine_name — skip module row */

    return $visitId;
}

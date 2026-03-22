$(function () {
    $('body').show();

    /* DataTable */
    const tbl = $('#consultTable').DataTable({
        pageLength: 25, order: [[1, 'desc']], dom: 'tip',
        language: { info: 'Showing _START_–_END_ of _TOTAL_', paginate: { previous: '‹', next: '›' } }
    });
    $('#conSearch').on('input', function () { tbl.search(this.value).draw(); });

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    function showAlert(title, msg, type, cb) {
        const col = type === 'success' ? 'var(--ok-fg)' : 'var(--danger-fg)';
        const id = 'al_' + Date.now();
        $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
        </div>`);
        $(`#${id}`).dialog({
            autoOpen: true, modal: true, width: 400, resizable: false, close: cb || null,
            buttons: { 'OK': function () { $(this).dialog('close').remove(); } }
        });
    }

    /* ── STEP NAVIGATION ── */
    window.goStep = function (n) {
        const total = 7;
        // Validate step 1: need resident
        if (n > 1 && !$('#cf_res_id').val()) {
            showAlert('Patient required', 'Please select a patient first.', 'danger');
            return;
        }
        // Validate step 4: need complaint
        if (n > 4 && !$('#cf_complaint').val().trim()) {
            showAlert('Chief complaint required', 'Please enter the chief complaint.', 'danger');
            return;
        }
        // Mark previous steps done
        $('.fs-step').each(function () {
            const s = parseInt($(this).data('step'));
            $(this).removeClass('active done');
            if (s < n) $(this).addClass('done');
            if (s === n) $(this).addClass('active');
        });
        $('.form-panel').removeClass('active');
        $(`#panel${n}`).addClass('active');
    };

    $('.fs-step').on('click', function () {
        goStep(parseInt($(this).data('step')));
    });

    /* ── BMI auto-compute ── */
    function computeBMI() {
        const w = parseFloat($('[name="weight_kg"]').val());
        const h = parseFloat($('[name="height_cm"]').val());
        if (w > 0 && h > 0) {
            const bmi = (w / ((h / 100) ** 2)).toFixed(1);
            $('#cf_bmi_display').val(bmi);
            const cls = bmi < 18.5 ? 'Underweight' : bmi < 25 ? 'Normal' : bmi < 30 ? 'Overweight' : 'Obese';
            const bgMap = { Underweight: '#eff6ff', Normal: '#edfaf3', Overweight: '#fef9ec', Obese: '#fdeeed' };
            const clrMap = { Underweight: '#1e40af', Normal: '#1a5c35', Overweight: '#7a5700', Obese: '#7a1f1a' };
            $('#cf_bmi_class').text(cls + ' (' + bmi + ')')
                .css({ background: bgMap[cls], color: clrMap[cls], 'border-color': clrMap[cls] + '44' })
                .show();
        } else {
            $('#cf_bmi_display').val('');
            $('#cf_bmi_class').hide();
        }
    }
    $('[name="weight_kg"],[name="height_cm"]').on('input', computeBMI);

    /* ── Resident autocomplete ── */
    $('#cf_res_name').autocomplete({
        minLength: 1,
        appendTo: '#consultModal',
        position: { my: 'left top', at: 'left bottom', collision: 'none' },
        source: function (req, res) {
            $.getJSON('api/resident.php', { term: req.term }, res);
        },
        select: function (e, ui) {
            e.preventDefault();
            $('#cf_res_id').val(ui.item.id);
            $('#cf_res_name').val(ui.item.label);
            loadPatientCard(ui.item.id);
            return false;
        }
    });
    $('#cf_res_name').on('autocompleteopen', function () {
        $('.ui-autocomplete').css('z-index', 9999);
    });

    function loadPatientCard(id) {
        $.getJSON('../resident/get_resident.php', { id }, function (d) {
            if (d.error) return;
            const age = calcAge(d.birthdate);
            const name = [d.first_name, d.middle_name, d.last_name, d.suffix].filter(Boolean).join(' ');
            $('#pc_name').text(name);
            $('#pc_age').text((d.birthdate || '—') + (age ? ' · ' + age + ' yrs old' : ''));
            $('#pc_gender').text(d.gender || '—');
            $('#pc_civil').text(d.civil_status || '—');
            $('#pc_contact').text(d.contact_no || '—');
            $('#pc_occ').text(d.occupation || '—');
            $('#pc_addr').text(d.address || '—');
            $('#patientCard').show();
            /* Pre-fill social history fields */
            $('#cf_occ').val(d.occupation || '');
            $('#cf_civil').val(d.civil_status || '');
        });
    }
    function calcAge(bd) {
        if (!bd) return '';
        const b = new Date(bd + 'T00:00:00'), t = new Date();
        let a = t.getFullYear() - b.getFullYear();
        if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) a--;
        return a;
    }

    /* ── Open Add modal ── */
    $('#consultModal').dialog({
        autoOpen: false, modal: true, width: 860, resizable: false,
        open: function () { /* handled by goStep */ }
    });
    $('#btnAdd').on('click', function () {
         $('[name="consult_type"]').prop('disabled', false);
        $('#consultForm')[0].reset();
        $('#cf_id').val('');
        $('#cf_res_id').val('');
        $('#cf_res_name').val('');
        $('#patientCard').hide();
        $('#cf_date').val(new Date().toISOString().slice(0, 10));
        $('[name="health_worker"]').val(SESSION_WORKER);
        $('#cf_bmi_display').val(''); $('#cf_bmi_class').hide();
        goStep(1);
        $('.fs-step').removeClass('active done');
        $('.fs-step[data-step="1"]').addClass('active');
        $('#consultModal').dialog('option', 'title', 'New Consultation').dialog('open');
    });

    /* ── Form submit (both quick-save from any panel and final save) ── */
    function submitForm() {
        const rid = $('#cf_res_id').val();
        if (!rid) { showAlert('Error', 'Please select a patient.', 'danger'); return; }
        if (!$('#cf_complaint').val().trim()) {
            if (confirm('Chief complaint is empty. Save anyway?') === false) return;
        }
        const id = $('#cf_id').val();
        const url = id ? 'api/edit.php' : 'api/add.php';
        const $btn = $('#btnFinalSave');
        $btn.prop('disabled', true).text('Saving…');
        $.ajax({
            url, type: 'POST', data: $('#consultForm').serialize(), dataType: 'json',
            success(res) {
                if (!res.success) { showAlert('Error', res.message || 'Failed.', 'danger'); return; }
                $('#consultModal').dialog('close');
                showAlert('Saved', res.message || 'Consultation saved.', 'success', () => location.reload());
            },
            error(xhr) { showAlert('Error', 'Server error (' + xhr.status + ').', 'danger'); },
            complete() { $btn.prop('disabled', false).text('✓ Save Consultation'); }
        });
    }
    $('#consultForm').on('submit', function (e) { e.preventDefault(); submitForm(); });
    $('#btnQuickSave').on('click', submitForm);

    /* ── View modal ── */
    $('#viewModal').dialog({
        autoOpen: false, modal: true, width: 900, resizable: true,
        buttons: { 'Close': function () { $(this).dialog('close'); } }
    });

    window.viewConsult = function (id) {
        $.getJSON('api/view.php', { id }, function (res) {
            if (!res.success) { showAlert('Error', res.message || 'Not found.', 'danger'); return; }
            const d = res.data;

            $('#vm_name').text(d.fullname || '—');
            $('#vm_meta').text([
                'Consultation #' + String(d.id).padStart(5, '0'),
                d.consultation_date,
                d.consult_type ? d.consult_type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '',
                d.health_worker ? 'by ' + d.health_worker : '',
            ].filter(Boolean).join(' · '));

            /* Badges */
            const typeConfig = { general: { c: '#5a5a5a', b: '#f3f1ec' }, maternal: { c: '#9f1239', b: '#fff1f2' }, family_planning: { c: '#1e40af', b: '#eff6ff' }, prenatal: { c: '#92400e', b: '#fffbeb' }, postnatal: { c: '#134e4a', b: '#f0fdfa' }, child_nutrition: { c: '#14532d', b: '#f0fdf4' }, immunization: { c: '#4c1d95', b: '#f5f3ff' }, other: { c: '#5a5a5a', b: '#f3f1ec' } };
            const tc = typeConfig[d.consult_type] || typeConfig.general;
            const riskClr = { Low: ['#1a5c35', '#edfaf3'], Moderate: ['#7a5700', '#fef9ec'], High: ['#7a1f1a', '#fdeeed'] };
            const rc = riskClr[d.risk_level] || riskClr.Low;
            let badges = `<span class="type-badge" style="background:${tc.b};color:${tc.c};border-color:${tc.c}33;">${esc((d.consult_type || 'general').replace(/_/g, ' '))}</span>`;
            badges += `<span class="type-badge" style="background:${rc[1]};color:${rc[0]};border-color:${rc[0]}33;">Risk: ${esc(d.risk_level)}</span>`;
            if (d.consult_status) badges += `<span class="type-badge" style="background:var(--paper-lt);color:var(--ink-muted);border-color:var(--rule);">${esc(d.consult_status)}</span>`;
            if (d.is_referred) badges += `<span class="type-badge" style="background:#fef9ec;color:#7a5700;border-color:#fde68a;">Referred</span>`;
            $('#vm_badges').html(badges);

            /* Vitals */
            const v = { temp: d.temp_celsius ? d.temp_celsius + '°C' : '—', bp: (d.bp_systolic && d.bp_diastolic) ? d.bp_systolic + '/' + d.bp_diastolic : '—', pulse: d.pulse_rate ? d.pulse_rate + ' bpm' : '—', rr: d.respiratory_rate ? d.respiratory_rate + '/min' : '—', spo2: d.o2_saturation ? d.o2_saturation + '%' : '—', weight: d.weight_kg ? d.weight_kg + ' kg' : '—', height: d.height_cm ? d.height_cm + ' cm' : '—', bmi: d.bmi ? (d.bmi + (d.bmi_class ? ' (' + d.bmi_class + ')' : '')) : '—' };
            ['temp', 'bp', 'pulse', 'rr', 'spo2', 'weight', 'height', 'bmi'].forEach(k => $('#vm_' + k).text(v[k]));

            /* Fields */
            function f(id, val) { $(`#vm_${id}`).text(val || '').toggleClass('vm-val-empty', !val).text(val || '—'); }
            f('complaint', d.chief_complaint || d.complaint);
            f('duration', [d.complaint_duration, d.complaint_onset].filter(Boolean).join(' · '));
            f('diagnosis', d.primary_diagnosis || d.diagnosis);
            f('secondary', d.secondary_diagnosis);
            f('icd', d.icd_code);
            f('treatment', d.treatment);
            f('meds', d.medicines_prescribed);
            f('procedures', d.procedures_done);
            f('advice', d.health_advice);
            f('lifestyle', d.lifestyle_advice);
            f('education', d.patient_education);
            f('assessment', d.assessment);
            f('plan', d.plan);
            f('pmhx', d.past_medical_history);
            f('fhx', d.family_history);
            f('meds_curr', d.current_medications);
            f('allergies', d.known_allergies);

            /* Health profile mini-grid */
            const profileItems = [
                ['Smoking', d.smoking_status], ['Alcohol', d.alcohol_use],
                ['Activity', d.physical_activity], ['Nutrition', d.nutritional_status],
                ['Mental health', d.mental_health_screen], ['Prognosis', d.prognosis]
            ];
            $('#vm_profile').html(profileItems.map(([l, v]) =>
                `<div class="vm-field"><div class="vm-lbl">${esc(l)}</div><div class="vm-val">${esc(v || '—')}</div></div>`
            ).join(''));

            $('#viewModal').dialog('option', 'title', 'Consult — ' + d.fullname).dialog('open');
        }).fail(() => showAlert('Error', 'Failed to load record.', 'danger'));
    };

    /* ── Edit ── */
    window.editConsult = function (id) {
        $.getJSON('api/view.php', { id }, function (res) {
            if (!res.success) { showAlert('Error', res.message || 'Not found.', 'danger'); return; }
            const d = res.data;

            $('#cf_id').val(d.id);
            $('#cf_res_id').val(d.resident_id);
            $('#cf_res_name').val(d.fullname);
            loadPatientCard(d.resident_id);

            $('#cf_date').val(d.consultation_date || '');
            $('[name="consultation_time"]').val(d.time || '');
            $('[name="consult_type"]').prop('disabled', true).val(d.consult_type || 'general');
            $('[name="sub_type"]').val(d.sub_type || '');
            $('[name="health_worker"]').val(d.health_worker || SESSION_WORKER);
            $('[name="consult_status"]').val(d.consult_status || 'Ongoing');
            $('[name="risk_level"]').val(d.risk_level || 'Low');
            $('[name="follow_up_date"]').val(d.follow_up_date || '');
            $('[name="referred_to"]').val(d.referred_to || '');
            $('[name="is_referred"]').prop('checked', d.is_referred == 1);

            /* Vitals */
            $('[name="temp_celsius"]').val(d.temp_celsius || '');
            $('[name="bp_systolic"]').val(d.bp_systolic || '');
            $('[name="bp_diastolic"]').val(d.bp_diastolic || '');
            $('[name="pulse_rate"]').val(d.pulse_rate || '');
            $('[name="respiratory_rate"]').val(d.respiratory_rate || '');
            $('[name="o2_saturation"]').val(d.o2_saturation || '');
            $('[name="weight_kg"]').val(d.weight_kg || '');
            $('[name="height_cm"]').val(d.height_cm || '');
            $('[name="waist_cm"]').val(d.waist_cm || '');
            computeBMI();

            /* Clinical */
            $('#cf_complaint').val(d.chief_complaint || d.complaint || '');
            $('[name="complaint_duration"]').val(d.complaint_duration || '');
            $('[name="complaint_onset"]').val(d.complaint_onset || 'Sudden');
            $('#cf_diagnosis').val(d.primary_diagnosis || d.diagnosis || '');
            $('[name="secondary_diagnosis"]').val(d.secondary_diagnosis || '');
            $('[name="icd_code"]').val(d.icd_code || '');
            $('[name="treatment"]').val(d.treatment || '');
            $('[name="medicines_prescribed"]').val(d.medicines_prescribed || '');
            $('[name="procedures_done"]').val(d.procedures_done || '');

            /* Advice */
            $('[name="health_advice"]').val(d.health_advice || '');
            $('[name="lifestyle_advice"]').val(d.lifestyle_advice || '');
            $('[name="patient_education"]').val(d.patient_education || '');
            $('[name="assessment"]').val(d.assessment || '');
            $('[name="plan"]').val(d.plan || '');
            $('[name="prognosis"]').val(d.prognosis || 'NA');

            /* Health profile */
            $('[name="smoking_status"]').val(d.smoking_status || 'NA');
            $('[name="alcohol_use"]').val(d.alcohol_use || 'NA');
            $('[name="physical_activity"]').val(d.physical_activity || 'NA');
            $('[name="nutritional_status"]').val(d.nutritional_status || 'NA');
            $('[name="mental_health_screen"]').val(d.mental_health_screen || 'Not screened');
            $('#cf_occ').val(d.occupation || '');
            $('#cf_civil').val(d.civil_status || '');
            $('[name="educational_attainment"]').val(d.educational_attainment || '');
            $('[name="living_conditions"]').val(d.living_conditions || '');

            /* History */
            $('[name="past_medical_history"]').val(d.past_medical_history || '');
            $('[name="family_history"]').val(d.family_history || '');
            $('[name="current_medications"]').val(d.current_medications || '');
            $('[name="known_allergies"]').val(d.known_allergies || '');
            $('[name="immunization_history"]').val(d.immunization_history || '');
            $('[name="remarks"]').val(d.remarks || '');

            /* Reset stepper to step 1 */
            $('.fs-step').removeClass('active done');
            $('.fs-step[data-step="1"]').addClass('active');
            $('.form-panel').removeClass('active');
            $('#panel1').addClass('active');

            $('#consultModal').dialog('option', 'title', 'Edit — ' + d.fullname).dialog('open');
        }).fail(() => showAlert('Error', 'Failed to load.', 'danger'));
    };

    /* ── Generate ── */
    $('#generateModal').dialog({ autoOpen: false, modal: true, width: 500, resizable: false });
    $('#gen_period').on('change', function () { $('#gen_month_wrap').toggle($(this).val() === 'monthly'); });
    $('#gen_doc').on('change', function () { $('#gen_purpose_wrap').toggle($(this).val() === 'certificate'); });
    $('#btnGenerateReport').on('click', () => {
        $('#gen_doc').val('report').trigger('change');
        $('#gen_period').val('monthly').trigger('change');
        $('#generateModal').dialog('open');
    });
    window.doGenerate = function () {
        const p = new URLSearchParams($('#generateForm').serialize());
        window.open('api/generate.php?' + p.toString(), '_blank');
        $('#generateModal').dialog('close');
    };
});
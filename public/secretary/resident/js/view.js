$(function() {
    $("#birthdate").datepicker({
        dateFormat: 'yy-mm-dd',
        changeYear: true,
        yearRange: '-100:+0'
    });

    function computeAge(bd) {
        if (!bd) return null;
        const parts = bd.split('-');
        if (parts.length !== 3) return null;
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        if (isNaN(d)) return null;
        const diff = Date.now() - d.getTime();
        return Math.floor(diff / 31557600000);
    }

    function updatePreview() {
        const data = {};
        $('#residentForm').serializeArray().forEach(f => data[f.name] = f.value);

        const nameParts = [data.first_name, data.middle_name, data.last_name, data.suffix].filter(Boolean);
        $('#previewName').text(nameParts.join(' ') || '—');
        $('#previewHH').text(data.household_id || '—');
        $('#previewQuick').text((data.gender ? data.gender + ' · ' : '') + (data.occupation || '—'));
        $('#previewGender').text(data.gender || '—');
        $('#previewBirthdate').text(data.birthdate || '—');
        $('#previewBirthplace').text(data.birthplace || '—');
        $('#previewCivil').text(data.civil_status || '—');
        $('#previewReligion').text(data.religion || '—');
        $('#previewOccupation').text(data.occupation || '—');
        $('#previewCitizenship').text(data.citizenship || '—');
        $('#previewContact').text(data.contact_no || '—');
        $('#previewAddress').text(data.address || '—');
        $('#previewVoter').text(data.voter_status || '—');
        $('#previewDisability').text(data.disability_status || '—'); // ✅ New line
        $('#previewRemarks').text(data.remarks || '—');

        const age = computeAge(data.birthdate);
        $('#ageBadge').text(age !== null ? age + ' years old' : '');
    }

    $('#residentForm').on('input change', 'input,textarea,select', updatePreview);
    updatePreview();

    $('#saveBtn').click(() => {
        const payload = {};
        $('#residentForm').serializeArray().forEach(f => payload[f.name] = f.value);
        payload.id = residentId;

        $.ajax({
            url: 'update_resident.php',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(res) {
                $('<div>' + res.message + '</div>').dialog({
                    modal: true,
                    title: res.success ? 'Saved' : 'Error',
                    width: 420,
                    buttons: {
                        Ok: function() {
                            $(this).dialog('close');
                        }
                    },
                    classes: {
                        'ui-dialog': 'rounded-lg shadow-lg',
                        'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                        'ui-dialog-title': 'font-semibold',
                        'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                    },
                });
            },
            error: function() {
                $('<div>Failed to connect to server.</div>').dialog({
                    modal: true,
                    title: 'Error',
                    width: 420,
                    buttons: {
                        Ok: function() {
                            $(this).dialog('close');
                        }
                    },
                    classes: {
                        'ui-dialog': 'rounded-lg shadow-lg',
                        'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                        'ui-dialog-title': 'font-semibold',
                        'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                    },
                });
            }
        });
    });


    $('#refreshBtn').click(() => {
        updatePreview();
    });

    $('#exportJson').click(() => {
        const payload = {};
        $('#residentForm').serializeArray().forEach(f => payload[f.name] = f.value);
        const blob = new Blob([JSON.stringify(payload, null, 2)], {
            type: 'application/json'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'resident.json';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });

    $('#showRaw').click(() => {
        const payload = {};
        $('#residentForm').serializeArray().forEach(f => payload[f.name] = f.value);
        $('#rawPre').text(JSON.stringify(payload, null, 2));
        $('#dialog').dialog({
            width: 600,
            modal: true,
            buttons: {
                Close: function() {
                    $(this).dialog('close');
                }
            },
            classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
            }
        });
    });
});

// ajax to fetch resident data and populate the form
// --- Load Resident Info ---
const residentId = new URLSearchParams(window.location.search).get('id');
if (residentId) {
    $.getJSON(`get_resident.php?id=${residentId}`, function(res) {
        if (res.error) {
            alert(res.error);
            return;
        }
        // Fill all form fields
        for (const key in res) {
            if ($(`[name=${key}]`).length) {
                $(`[name=${key}]`).val(res[key]);
            }
        }
        updatePreview();
    });
}
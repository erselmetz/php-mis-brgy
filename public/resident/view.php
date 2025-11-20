<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View - Resident - MIS Barangay</title>
    <?php loadAllAssets(); ?>


    <style>
        .chip {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 50rem;
            background: rgba(0, 0, 0, 0.05);
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }
        .hidden {
            display: none;
        }
        /* Mobile responsiveness improvements */
        @media (max-width: 576px) {
            .row {
                --bs-gutter-x: 0.5rem;
            }
            main {
                padding: 1rem !important;
            }
        }
        /* Form validation styles - Bootstrap compatible */
        input.border-danger {
            border-color: #dc3545 !important;
        }
        input.border-success {
            border-color: #198754 !important;
        }
        input:invalid:not(:placeholder-shown) {
            border-color: #dc3545;
        }
        input:valid:not(:placeholder-shown) {
            border-color: #198754;
        }
    </style>
</head>

<body class="bg-light">
    <?php include_once '../navbar.php'; ?>
    <div class="d-flex bg-light">
        <?php include_once '../sidebar.php'; ?>

        <main class="p-4 w-100">
            <div class="mb-4">
                <a href="/resident/residents" class="d-inline-flex align-items-center text-primary mb-3 text-decoration-none">
                    <svg class="me-1" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Residents List
                </a>
            </div>
            <h1 class="h3 mb-4">View Resident</h1>

            <!-- ✅ Start of Resident Information Section -->
            <div class="container-lg mx-auto">
                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                <section class="bg-white p-4 rounded-3 shadow-sm border">
                    <h2 class="h6 mb-3">Edit Resident</h2>
                    <form id="residentForm" autocomplete="off">
                        <div class="row gx-3 gy-3">
                            <div class="col-12 col-sm-6"> 
                                <label class="form-label">
                                    Household ID
                                    <span class="text-muted small ms-1" title="Optional: Link this resident to a household">(?)</span>
                                </label> 
                                <input name="household_id" id="household_id" type="number" class="form-control" /> 
                            </div>
                            <div class="col-12 col-sm-6"> 
                                <label class="form-label">Birthdate <span class="text-danger">*</span></label>
                                <input name="birthdate" id="birthdate" type="text" class="form-control" placeholder="YYYY-MM-DD" required /> 
                                <div class="form-text">Format: YYYY-MM-DD</div>
                            </div>
                            <div class="col-12 col-sm-6"> 
                                <label class="form-label">First name <span class="text-danger">*</span></label>
                                <input name="first_name" id="first_name" type="text" class="form-control" required /> 
                            </div>
                            <div class="col-12 col-sm-6"> 
                                <label class="form-label">Middle name</label> 
                                <input name="middle_name" id="middle_name" type="text" class="form-control" /> 
                            </div>
                            <div class="col-12 col-sm-6"> 
                                <label class="form-label">Last name <span class="text-danger">*</span></label>
                                <input name="last_name" id="last_name" type="text" class="form-control" required /> 
                            </div>
                            <div class="col-12 col-sm-6"> 
                                <label class="form-label">Suffix</label> 
                                <input name="suffix" id="suffix" type="text" class="form-control" placeholder="Jr., Sr., III, etc." /> 
                            </div>
                            <div class="col-12 col-sm-6"> <label class="form-label">Gender</label> <select name="gender" id="gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select> </div>
                            <div class="col-12 col-sm-6"> <label class="form-label">Birthplace</label> <input name="birthplace" id="birthplace" type="text" class="form-control" /> </div>
                            <div class="col-12 col-sm-6"> <label class="form-label">Civil status</label> <select name="civil_status" id="civil_status" class="form-select">
                                    <option>Single</option>
                                    <option>Married</option>
                                    <option>Separated</option>
                                    <option>Widowed</option>
                                </select> </div>
                            <div class="col-12 col-sm-6"> <label class="form-label">Religion</label> <input name="religion" id="religion" type="text" class="form-control" /> </div>
                            <div class="col-12 col-sm-6"> <label class="form-label">Occupation</label> <input name="occupation" id="occupation" type="text" class="form-control" /> </div>
                            <div class="col-12 col-sm-6"> <label class="form-label">Citizenship</label> <input name="citizenship" id="citizenship" type="text" class="form-control" value="Filipino" /> </div>
                            <div class="col-12 col-sm-6"> 
                                <label class="form-label">Contact No.</label> 
                                <input name="contact_no" id="contact_no" type="text" class="form-control" placeholder="09123456789" pattern="^09\d{9}$" /> 
                                <div class="form-text">Format: 09XXXXXXXXX (11 digits)</div>
                            </div>
                            <div class="col-12"> <label class="form-label">Address</label> <input name="address" id="address" type="text" class="form-control" /> </div>
                            <div class="col-12 col-sm-6"> <label class="form-label">Voter status</label> <select name="voter_status" id="voter_status" class="form-select">
                                    <option>No</option>
                                    <option>Yes</option>
                                </select> </div>
                            <div class="col-12 col-sm-6"> <label class="form-label">Disability status</label> <select name="disability_status" id="disability_status" class="form-select">
                                    <option>No</option>
                                    <option>Yes</option>
                                </select> </div>
                            <div class="col-12"> <label class="form-label">Remarks</label> <textarea name="remarks" id="remarks" rows="3" class="form-control"></textarea> </div>
                        </div>
                        <div class="mt-3 d-flex align-items-center gap-2">
                            <button id="saveBtn" type="button" class="btn btn-primary">
                                <span class="saveBtnText">Save</span>
                                <span class="saveBtnLoader hidden">Saving...</span>
                            </button>
                        </div>
                    </form>
                </section>
                    </div>
                    <div class="col-12 col-lg-6">
                <!-- Preview Section -->
                <aside class="bg-white p-4 rounded-3 shadow-sm border">
                    <div class="d-flex align-items-start justify-content-between">
                        <h2 class="h5">Live Preview</h2>
                        <div id="ageBadge" class="small text-muted"></div>
                    </div>

                    <div id="previewCard" class="mt-3 border rounded p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h3 id="previewName" class="h5 text-dark">—</h3>
                                <div id="previewQuick" class="small text-muted mt-1">—</div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Household ID</div>
                                <div id="previewHH" class="fw-semibold">—</div>
                            </div>
                        </div>

                        <dl class="mt-3 small text-body">
                            <div><span class="fw-semibold">Gender:</span> <span id="previewGender">—</span></div>
                            <div><span class="fw-semibold">Birthdate:</span> <span id="previewBirthdate">—</span></div>
                            <div><span class="fw-semibold">Birthplace:</span> <span id="previewBirthplace">—</span></div>
                            <div><span class="fw-semibold">Civil status:</span> <span id="previewCivil">—</span></div>
                            <div><span class="fw-semibold">Religion:</span> <span id="previewReligion">—</span></div>
                            <div><span class="fw-semibold">Occupation:</span> <span id="previewOccupation">—</span></div>
                            <div><span class="fw-semibold">Citizenship:</span> <span id="previewCitizenship">—</span></div>
                            <div><span class="fw-semibold">Contact no.:</span> <span id="previewContact">—</span></div>
                            <div><span class="fw-semibold">Address:</span> <span id="previewAddress">—</span></div>
                            <div><span class="fw-semibold">Voter status:</span> <span id="previewVoter">—</span></div>

                            <!-- ✅ New Disability Preview -->
                            <div><span class="fw-semibold">Disability status:</span> <span id="previewDisability">—</span></div>

                            <div><span class="fw-semibold">Remarks:</span>
                                <div id="previewRemarks" class="mt-1 small text-muted fst-italic">—</div>
                            </div>
                        </dl>
                    </div>

                </aside>
                    </div>
                </div>
            </div>

            <!-- ✅ End of Resident Information Section -->
        </main>
    </div>

    <script>
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

            // Auto-update preview on any form change (real-time - no refresh button needed)
            $('#residentForm').on('input change keyup paste', 'input,textarea,select', function() {
                updatePreview();
            });

            // Initial preview update
            updatePreview();

            // --- Load Resident Info (moved inside ready so updatePreview() is available) ---
            const residentId = new URLSearchParams(window.location.search).get('id');
            if (residentId) {
                // Show loading indicator
                $('#residentForm').css('opacity', '0.6');
                $('#saveBtn').prop('disabled', true);
                $('.saveBtnText').addClass('hidden');
                $('.saveBtnLoader').removeClass('hidden').text('Loading...');

                $.getJSON(`get_resident.php?id=${residentId}`, function(res) {
                    // Hide loading indicator
                    $('#residentForm').css('opacity', '1');
                    $('#saveBtn').prop('disabled', false);
                    $('.saveBtnText').removeClass('hidden').text('Save');
                    $('.saveBtnLoader').addClass('hidden');

                    if (res.error) {
                        $('<div>' + res.error + '</div>').dialog({
                            modal: true,
                            title: 'Error',
                            width: 420,
                            buttons: {
                                Ok: function() {
                                    $(this).dialog('close');
                                }
                            }
                        });
                        return;
                    }
                    // Fill all form fields
                    for (const key in res) {
                        if ($(`[name="${key}"]`).length) {
                            $(`[name="${key}"]`).val(res[key]);
                        }
                    }
                    updatePreview();
                }).fail(function() {
                    // Hide loading indicator on error
                    $('#residentForm').css('opacity', '1');
                    $('#saveBtn').prop('disabled', false);
                    $('.saveBtnText').removeClass('hidden').text('Save');
                    $('.saveBtnLoader').addClass('hidden');

                    $('<div>Failed to load resident data. Please try again.</div>').dialog({
                        modal: true,
                        title: 'Error',
                        width: 420,
                        buttons: {
                            Ok: function() {
                                $(this).dialog('close');
                            }
                        }
                    });
                });
            }

            // Real-time validation feedback
            $('#first_name, #last_name').on('blur', function() {
                const $input = $(this);
                if ($input.val().trim() === '') {
                    $input.addClass('border-danger').removeClass('border-success');
                } else {
                    $input.addClass('border-success').removeClass('border-danger');
                }
            });

            $('#contact_no').on('blur', function() {
                const $input = $(this);
                const value = $input.val().trim();
                if (value && !/^09\d{9}$/.test(value)) {
                    $input.addClass('border-danger').removeClass('border-success');
                } else if (value) {
                    $input.addClass('border-success').removeClass('border-danger');
                }
            });

            $('#birthdate').on('blur', function() {
                const $input = $(this);
                const value = $input.val().trim();
                if (value && !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                    $input.addClass('border-danger').removeClass('border-success');
                } else if (value) {
                    $input.addClass('border-success').removeClass('border-danger');
                }
            });

            $('#saveBtn').click(() => {
                // Validate required fields
                const firstName = $('#first_name').val().trim();
                const lastName = $('#last_name').val().trim();
                const birthdate = $('#birthdate').val().trim();
                const contactNo = $('#contact_no').val().trim();
                
                let errors = [];
                
                if (!firstName) {
                    errors.push('First Name is required');
                    $('#first_name').addClass('border-danger');
                }
                if (!lastName) {
                    errors.push('Last Name is required');
                    $('#last_name').addClass('border-danger');
                }
                if (!birthdate) {
                    errors.push('Birthdate is required');
                    $('#birthdate').addClass('border-danger');
                } else if (!/^\d{4}-\d{2}-\d{2}$/.test(birthdate)) {
                    errors.push('Birthdate must be in YYYY-MM-DD format');
                    $('#birthdate').addClass('border-danger');
                }
                if (contactNo && !/^09\d{9}$/.test(contactNo)) {
                    errors.push('Contact number must be in format 09XXXXXXXXX');
                    $('#contact_no').addClass('border-danger');
                }
                
                if (errors.length > 0) {
                    $('<div><ul class="list-unstyled">' + errors.map(e => '<li>• ' + e + '</li>').join('') + '</ul></div>').dialog({
                        modal: true,
                        title: 'Validation Error',
                        width: 420,
                        buttons: {
                            Ok: function() {
                                $(this).dialog('close');
                            }
                        }
                    });
                    return;
                }

                // Show loading state
                $('#saveBtn').prop('disabled', true);
                $('.saveBtnText').addClass('hidden');
                $('.saveBtnLoader').removeClass('hidden');

                const payload = {};
                $('#residentForm').serializeArray().forEach(f => payload[f.name] = f.value);
                payload.id = residentId;

                $.ajax({
                    url: '/resident/update_resident',
                    type: 'POST',
                    data: payload,
                    dataType: 'json',
                    success: function(res) {
                        // Hide loading state
                        $('#saveBtn').prop('disabled', false);
                        $('.saveBtnText').removeClass('hidden');
                        $('.saveBtnLoader').addClass('hidden');
                        
                        $('<div>' + res.message + '</div>').dialog({
                            modal: true,
                            title: res.success ? 'Saved' : 'Error',
                            width: 420,
                            buttons: {
                                Ok: function() {
                                    $(this).dialog('close');
                                }
                            }
                        });
                    },
                    error: function() {
                        // Hide loading state
                        $('#saveBtn').prop('disabled', false);
                        $('.saveBtnText').removeClass('hidden');
                        $('.saveBtnLoader').addClass('hidden');
                        
                        $('<div>Failed to connect to server.</div>').dialog({
                            modal: true,
                            title: 'Error',
                            width: 420,
                            buttons: {
                                Ok: function() {
                                    $(this).dialog('close');
                                }
                            }
                        });
                    }
                });
            });


        });
    </script>
</body>

</html>
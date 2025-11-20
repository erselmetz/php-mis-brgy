<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access

// Form submission handled via API

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Resident - MIS Barangay</title>
  <?php loadAllAssets(); ?>
</head>

<body class="bg-light" style="display:none;">

  <?php include_once '../navbar.php'; ?>

  <div class="d-flex bg-light">
    <?php include_once '../sidebar.php'; ?>
    <main class="p-4 w-100">
      <h2 class="h3 fw-semibold mb-4">Resident List</h2>
      <!-- ✅ Add Button -->
      <div class="p-4">
        <button id="openResidentModalBtn"
          class="btn btn-primary fw-semibold px-4 py-2 shadow">
          ➕ Add Resident
        </button>
      </div>
      <!-- Residents Table -->
      <div class="bg-white rounded-3 shadow-sm border overflow-hidden p-4">
        <table id="residentsTable" class="display w-100 small border rounded-3">
          <thead class="bg-light text-dark">
            <tr>
              <th class="p-2 text-start">Full Name</th>
              <th class="p-2 text-start">Gender</th>
              <th class="p-2 text-start">Birthdate</th>
              <th class="p-2 text-start">Age</th>
              <th class="p-2 text-start">Civil Status</th>
              <th class="p-2 text-start">Religion</th>
              <th class="p-2 text-start">Occupation</th>
              <th class="p-2 text-start">Citizenship</th>
              <th class="p-2 text-start">Contact No</th>
              <th class="p-2 text-start">Address</th>
              <th class="p-2 text-start">Voter</th>
            </tr>
          </thead>
          <tbody>
            <!-- Data loaded via API -->
          </tbody>
        </table>
      </div>
    </main>
  </div>
  <!-- Add Resident Modal -->
  <div class="modal fade" id="addResidentModal" tabindex="-1" aria-labelledby="addResidentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addResidentModalLabel">Add New Resident</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            <!-- Form Section -->
            <div class="col-12 col-lg-6">
              <form id="addResidentForm">
                <div id="residentAlertContainer"></div>

      <!-- Household -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Household ID (optional)</label>
        <input type="number" name="household_id" placeholder="Enter household ID"
          class="form-control">
      </div>

      <!-- Name Fields -->
      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-medium">First Name</label>
          <input type="text" name="first_name" placeholder="First Name" 
            class="form-control">
        </div>
        <div class="col-6">
          <label class="form-label small fw-medium">Middle Name</label>
          <input type="text" name="middle_name" placeholder="Middle Name"
            class="form-control">
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-medium">Last Name</label>
          <input type="text" name="last_name" placeholder="Last Name"
            class="form-control">
        </div>
        <div class="col-6">
          <label class="form-label small fw-medium">Suffix</label>
          <input type="text" name="suffix" placeholder="e.g. Jr., Sr."
            class="form-control">
        </div>
      </div>

      <!-- Gender & Birthdate -->
      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-medium">Gender</label>
          <select name="gender" class="form-select">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <div class="col-6">
          <label class="form-label small fw-medium">Birthdate</label>
          <input type="date" name="birthdate" class="form-control">
        </div>
      </div>

      <!-- Birthplace -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Birthplace</label>
        <input type="text" name="birthplace" placeholder="Enter birthplace"
          class="form-control">
      </div>

      <!-- Civil Status & Religion -->
      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-medium">Civil Status</label>
          <select name="civil_status" class="form-select">
            <option value="Single">Single</option>
            <option value="Married">Married</option>
            <option value="Widowed">Widowed</option>
            <option value="Separated">Separated</option>
          </select>
        </div>

        <div class="col-6">
          <label class="form-label small fw-medium">Religion</label>
          <input type="text" name="religion" placeholder="e.g. Catholic"
            class="form-control">
        </div>
      </div>

      <!-- Occupation & Citizenship -->
      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-medium">Occupation</label>
          <input type="text" name="occupation" placeholder="Occupation"
            class="form-control">
        </div>

        <div class="col-6">
          <label class="form-label small fw-medium">Citizenship</label>
          <input type="text" name="citizenship" value="Filipino"
            class="form-control">
        </div>
      </div>

      <!-- Contact & Address -->
      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-medium">Contact No.</label>
          <input type="text" name="contact_no" placeholder="09XXXXXXXXX"
            class="form-control">
        </div>

        <div class="col-6">
          <label class="form-label small fw-medium">Address</label>
          <input type="text" name="address" placeholder="Enter address"
            class="form-control">
        </div>
      </div>

      <!-- Voter Status -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Voter Status</label>
        <select name="voter_status" class="form-select">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Disability Status -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Disability Status</label>
        <select name="disability_status" class="form-select">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Remarks -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Remarks</label>
        <textarea name="remarks" rows="2" placeholder="Additional notes..."
          class="form-control"></textarea>
      </div>

                <!-- Submit -->
                <div class="pt-2">
                  <button type="submit"
                    class="w-100 btn btn-primary py-2 fw-semibold">
                    Add Resident
                  </button>
                </div>
              </form>
            </div>

            <!-- Live Preview Section -->
            <div class="col-12 col-lg-6">
              <div class="bg-light p-4 rounded-3 border">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <h3 class="h6 fw-medium">Live Preview</h3>
          <div id="modalAgeBadge" class="small text-muted"></div>
        </div>

        <div id="modalPreviewCard" class="bg-white border rounded-3 p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <h4 id="modalPreviewName" class="h5 fw-semibold text-dark">—</h4>
              <div id="modalPreviewQuick" class="small text-muted mt-1">—</div>
            </div>
            <div class="text-end">
              <div class="small text-muted">Household ID</div>
              <div id="modalPreviewHH" class="fw-medium">—</div>
            </div>
          </div>

          <dl class="row g-2 small text-body">
            <div class="col-12"><span class="fw-medium">Gender:</span> <span id="modalPreviewGender">—</span></div>
            <div class="col-12"><span class="fw-medium">Birthdate:</span> <span id="modalPreviewBirthdate">—</span></div>
            <div class="col-12"><span class="fw-medium">Birthplace:</span> <span id="modalPreviewBirthplace">—</span></div>
            <div class="col-12"><span class="fw-medium">Civil status:</span> <span id="modalPreviewCivil">—</span></div>
            <div class="col-12"><span class="fw-medium">Religion:</span> <span id="modalPreviewReligion">—</span></div>
            <div class="col-12"><span class="fw-medium">Occupation:</span> <span id="modalPreviewOccupation">—</span></div>
            <div class="col-12"><span class="fw-medium">Citizenship:</span> <span id="modalPreviewCitizenship">—</span></div>
            <div class="col-12"><span class="fw-medium">Contact no.:</span> <span id="modalPreviewContact">—</span></div>
            <div class="col-12"><span class="fw-medium">Address:</span> <span id="modalPreviewAddress">—</span></div>
            <div class="col-12"><span class="fw-medium">Voter status:</span> <span id="modalPreviewVoter">—</span></div>
            <div class="col-12"><span class="fw-medium">Disability status:</span> <span id="modalPreviewDisability">—</span></div>
            <div class="col-12"><span class="fw-medium">Remarks:</span>
              <div id="modalPreviewRemarks" class="mt-1 small text-muted fst-italic">—</div>
            </div>
          </dl>
        </div>
      </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    $(function() {
      $("body").show();
      
      // Helper function to calculate age
      function calculateAge(birthdate) {
        if (!birthdate) return '';
        const parts = birthdate.split('-');
        if (parts.length !== 3) return '';
        const birth = new Date(parts[0], parts[1] - 1, parts[2]);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
          age--;
        }
        return age;
      }
      
      let residentsTable = $("#residentsTable").DataTable({
        ajax: {
          url: '/api/residents',
          dataSrc: function(json) {
            if (json.status === 'success' && json.data) {
              return json.data;
            }
            return [];
          },
          error: function(xhr, error, thrown) {
            console.error('Error loading residents:', error);
            $('#residentsTable tbody').html('<tr><td colspan="11" class="p-4 text-center text-muted">Error loading data. Please refresh.</td></tr>');
          }
        },
        columns: [
          {
            data: null,
            render: function(data, type, row) {
              const fullName = [row.first_name, row.middle_name, row.last_name, row.suffix].filter(Boolean).join(' ');
              return '<a href="/resident/view?id=' + row.id + '" class="text-primary text-decoration-none">' + 
                     escapeHtml(fullName) + '</a>';
            }
          },
          { data: 'gender' },
          { data: 'birthdate' },
          {
            data: 'birthdate',
            render: function(data) {
              return calculateAge(data);
            }
          },
          { data: 'civil_status' },
          { data: 'religion' },
          { data: 'occupation' },
          { data: 'citizenship' },
          { data: 'contact_no' },
          { data: 'address' },
          { data: 'voter_status' }
        ],
        order: [[0, 'desc']],
        pageLength: 25
      });
      
      function escapeHtml(text) {
        if (!text) return '';
        const map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
      }
      
      function showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show mb-3" role="alert">' +
          escapeHtml(message) +
          '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
          '</div>';
        $('#residentAlertContainer').html(alertHtml);
        setTimeout(function() {
          $('#residentAlertContainer .alert').fadeOut(function() {
            $(this).remove();
          });
        }, 5000);
      }
      
      // Handle form submission
      $('#addResidentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
          action: 'create',
          household_id: $('[name="household_id"]').val() || null,
          first_name: $('[name="first_name"]').val().trim(),
          middle_name: $('[name="middle_name"]').val().trim(),
          last_name: $('[name="last_name"]').val().trim(),
          suffix: $('[name="suffix"]').val().trim(),
          gender: $('[name="gender"]').val(),
          birthdate: $('[name="birthdate"]').val(),
          birthplace: $('[name="birthplace"]').val().trim(),
          civil_status: $('[name="civil_status"]').val() || 'Single',
          religion: $('[name="religion"]').val().trim(),
          occupation: $('[name="occupation"]').val().trim(),
          citizenship: $('[name="citizenship"]').val() || 'Filipino',
          contact_no: $('[name="contact_no"]').val().trim(),
          address: $('[name="address"]').val().trim(),
          voter_status: $('[name="voter_status"]').val() || 'No',
          disability_status: $('[name="disability_status"]').val() || 'No',
          remarks: $('[name="remarks"]').val().trim()
        };
        
        // Validation
        if (!formData.first_name || !formData.last_name || !formData.gender || !formData.birthdate) {
          showAlert('Please fill in all required fields (First Name, Last Name, Gender, and Birthdate).', 'error');
          return;
        }
        
        $.ajax({
          url: '/api/residents',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify(formData),
          success: function(response) {
            if (response.status === 'success') {
              showAlert('Resident added successfully!', 'success');
              $('#addResidentForm')[0].reset();
              // Reset defaults
              $('[name="civil_status"]').val('Single');
              $('[name="citizenship"]').val('Filipino');
              $('[name="voter_status"]').val('No');
              $('[name="disability_status"]').val('No');
              updateModalPreview();
              const modal = bootstrap.Modal.getInstance(document.getElementById('addResidentModal'));
              if (modal) modal.hide();
              residentsTable.ajax.reload();
            } else {
              showAlert(response.message || 'Error adding resident', 'error');
            }
          },
          error: function(xhr) {
            let errorMsg = 'Error adding resident';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMsg = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
              errorMsg = Object.values(xhr.responseJSON.errors).join(', ');
            }
            showAlert(errorMsg, 'error');
          }
        });
      });
      
      // Live preview update function
      function updateModalPreview() {
        // Find form - it might be inside the dialog after jQuery UI moves it
        const form = $('#addResidentForm');
        if (!form.length) {
          return;
        }
        
        // Read values directly from form elements to ensure we get defaults
        const data = {
          household_id: form.find('[name="household_id"]').val() || '',
          first_name: form.find('[name="first_name"]').val() || '',
          middle_name: form.find('[name="middle_name"]').val() || '',
          last_name: form.find('[name="last_name"]').val() || '',
          suffix: form.find('[name="suffix"]').val() || '',
          gender: form.find('[name="gender"]').val() || '',
          birthdate: form.find('[name="birthdate"]').val() || '',
          birthplace: form.find('[name="birthplace"]').val() || '',
          civil_status: form.find('[name="civil_status"]').val() || 'Single',
          religion: form.find('[name="religion"]').val() || '',
          occupation: form.find('[name="occupation"]').val() || '',
          citizenship: form.find('[name="citizenship"]').val() || 'Filipino',
          contact_no: form.find('[name="contact_no"]').val() || '',
          address: form.find('[name="address"]').val() || '',
          voter_status: form.find('[name="voter_status"]').val() || 'No',
          disability_status: form.find('[name="disability_status"]').val() || 'No',
          remarks: form.find('[name="remarks"]').val() || ''
        };

        // Update preview elements - check if they exist first
        const nameParts = [data.first_name, data.middle_name, data.last_name, data.suffix].filter(Boolean);
        if ($('#modalPreviewName').length) $('#modalPreviewName').text(nameParts.join(' ') || '—');
        if ($('#modalPreviewHH').length) $('#modalPreviewHH').text(data.household_id || '—');
        if ($('#modalPreviewQuick').length) $('#modalPreviewQuick').text((data.gender ? data.gender + ' · ' : '') + (data.occupation || '—'));
        if ($('#modalPreviewGender').length) $('#modalPreviewGender').text(data.gender || '—');
        if ($('#modalPreviewBirthdate').length) $('#modalPreviewBirthdate').text(data.birthdate || '—');
        if ($('#modalPreviewBirthplace').length) $('#modalPreviewBirthplace').text(data.birthplace || '—');
        if ($('#modalPreviewCivil').length) $('#modalPreviewCivil').text(data.civil_status || 'Single');
        if ($('#modalPreviewReligion').length) $('#modalPreviewReligion').text(data.religion || '—');
        if ($('#modalPreviewOccupation').length) $('#modalPreviewOccupation').text(data.occupation || '—');
        if ($('#modalPreviewCitizenship').length) $('#modalPreviewCitizenship').text(data.citizenship || 'Filipino');
        if ($('#modalPreviewContact').length) $('#modalPreviewContact').text(data.contact_no || '—');
        if ($('#modalPreviewAddress').length) $('#modalPreviewAddress').text(data.address || '—');
        if ($('#modalPreviewVoter').length) $('#modalPreviewVoter').text(data.voter_status || 'No');
        if ($('#modalPreviewDisability').length) $('#modalPreviewDisability').text(data.disability_status || 'No');
        if ($('#modalPreviewRemarks').length) $('#modalPreviewRemarks').text(data.remarks || '—');

        // Calculate age
        if ($('#modalAgeBadge').length) {
          if (data.birthdate) {
            const parts = data.birthdate.split('-');
            if (parts.length === 3) {
              const d = new Date(parts[0], parts[1] - 1, parts[2]);
              if (!isNaN(d)) {
                const diff = Date.now() - d.getTime();
                const age = Math.floor(diff / 31557600000);
                $('#modalAgeBadge').text(age + ' years old');
              } else {
                $('#modalAgeBadge').text('');
              }
            } else {
              $('#modalAgeBadge').text('');
            }
          } else {
            $('#modalAgeBadge').text('');
          }
        }
      }

      // Use event delegation on document to handle form changes (works even after dialog moves DOM)
      $(document).on('input change keyup paste', '#addResidentForm input, #addResidentForm textarea, #addResidentForm select', function() {
        updateModalPreview();
      });
      
      // Initialize modal event handlers
      const addResidentModal = document.getElementById('addResidentModal');
      if (addResidentModal) {
        addResidentModal.addEventListener('show.bs.modal', function() {
          // Reset form when modal opens
          const form = $('#addResidentForm');
          if (form.length) {
            form[0].reset();
            // Set default values explicitly after reset
            form.find('[name="civil_status"]').val('Single');
            form.find('[name="citizenship"]').val('Filipino');
            form.find('[name="voter_status"]').val('No');
            form.find('[name="disability_status"]').val('No');
            
            // Update preview immediately - use multiple attempts to ensure it works
            setTimeout(function() {
              updateModalPreview();
            }, 50);
            setTimeout(function() {
              updateModalPreview();
            }, 200);
            setTimeout(function() {
              updateModalPreview();
            }, 500);
          }
          $('#residentAlertContainer').empty();
        });
      }
      
      $("#openResidentModalBtn").on("click", function() {
        const modal = new bootstrap.Modal(document.getElementById('addResidentModal'));
        modal.show();
      });
    })
  </script>
</body>

</html>

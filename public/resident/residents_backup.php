<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  include_once __DIR__ . '/add.php';
}
// Use prepared statement for better security (though no user input here, good practice)
$stmt = $conn->prepare("SELECT * FROM residents ORDER BY id DESC");
if ($stmt === false) {
  error_log('Residents query error: ' . $conn->error);
  $result = false;
} else {
  $stmt->execute();
  $result = $stmt->get_result();
}

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
              <th class="p-2 text-left">Full Name</th>
              <th class="p-2 text-left">Gender</th>
              <th class="p-2 text-left">Birthdate</th>
              <th class="p-2 text-left">Age</th>
              <th class="p-2 text-left">Civil Status</th>
              <th class="p-2 text-left">Religion</th>
              <th class="p-2 text-left">Occupation</th>
              <th class="p-2 text-left">Citizenship</th>
              <th class="p-2 text-left">Contact No</th>
              <th class="p-2 text-left">Address</th>
              <th class="p-2 text-left">Voter</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result !== false): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="p-2">
                  <a href="/resident/view?id=<?= $row['id']; ?>" class="text-primary text-decoration-none">
                    <?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . ' ' . $row['suffix']); ?>
                  </a>
                </td>
                <td class="p-2"><?= htmlspecialchars($row['gender']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['birthdate']); ?></td>
                <td class="p-2"><?= htmlspecialchars(AutoComputeAge($row['birthdate'])); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['civil_status']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['religion']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['occupation']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['citizenship']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['contact_no']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['address']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['voter_status']); ?></td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="11" class="p-4 text-center text-muted">Error loading residents. Please try again later.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
  <!-- ✅ Hidden Modal (jQuery UI Dialog) -->
  <div id="addResidentModal" title="Add New Resident">
    <div class="row g-4" style="max-height: 70vh; overflow-y: auto;">
      <!-- Form Section -->
      <div>
        <form id="addResidentForm" method="POST">
          <input type="hidden" name="action" value="add_resident">
          <?php if (isset($error)) echo "<p class='text-red-600 font-medium'>$error</p>"; ?>

      <!-- Household -->
      <div>
        <label class="form-label small fw-medium">Household ID (optional)</label>
        <input type="number" name="household_id" placeholder="Enter household ID"
          class="w-100 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary">
      </div>

      <!-- Name Fields -->
      <div class="row row-cols-1 row-cols-md-2 g-2">
        <div>
          <label class="form-label small fw-medium">First Name</label>
          <input type="text" name="first_name" placeholder="First Name" 
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>
        <div>
          <label class="form-label small fw-medium">Middle Name</label>
          <input type="text" name="middle_name" placeholder="Middle Name"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>
      </div>

      <div class="row row-cols-1 row-cols-md-2 g-2">
        <div>
          <label class="form-label small fw-medium">Last Name</label>
          <input type="text" name="last_name" placeholder="Last Name"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>
        <div>
          <label class="form-label small fw-medium">Suffix</label>
          <input type="text" name="suffix" placeholder="e.g. Jr., Sr."
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>
      </div>

      <!-- Gender & Birthdate -->
      <div class="row row-cols-1 row-cols-md-2 g-2">
        <div>
          <label class="form-label small fw-medium">Gender</label>
          <select name="gender"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-select">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <div>
          <label class="form-label small fw-medium">Birthdate</label>
            <input type="date" name="birthdate"
              class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>
      </div>

      <!-- Birthplace -->
      <div>
        <label class="form-label small fw-medium">Birthplace</label>
        <input type="text" name="birthplace" placeholder="Enter birthplace"
          class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
      </div>

      <!-- Civil Status & Religion -->
      <div class="row row-cols-1 row-cols-md-2 g-2">
        <div>
          <label class="form-label small fw-medium">Civil Status</label>
          <select name="civil_status"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-select">
            <option value="Single">Single</option>
            <option value="Married">Married</option>
            <option value="Widowed">Widowed</option>
            <option value="Separated">Separated</option>
          </select>
        </div>

        <div>
          <label class="form-label small fw-medium">Religion</label>
          <input type="text" name="religion" placeholder="e.g. Catholic"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>
      </div>

      <!-- Occupation & Citizenship -->
      <div class="row row-cols-1 row-cols-md-2 g-2">
        <div>
          <label class="form-label small fw-medium">Occupation</label>
          <input type="text" name="occupation" placeholder="Occupation"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>

        <div>
          <label class="form-label small fw-medium">Citizenship</label>
          <input type="text" name="citizenship" value="Filipino"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>
      </div>

      <!-- Contact & Purok -->
      <div class="row row-cols-1 row-cols-md-2 g-2">
        <div>
          <label class="form-label small fw-medium">Contact No.</label>
          <input type="text" name="contact_no" placeholder="09XXXXXXXXX"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>

        <div>
          <label class="form-label small fw-medium">Address</label>
          <input type="text" name="address" placeholder="Enter address"
            class="w-100 px-3 py-2 border border-gray-300 rounded form-control">
        </div>
      </div>

      <!-- Voter Status -->
      <div>
        <label class="form-label small fw-medium">Voter Status</label>
        <select name="voter_status" class="form-select">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Disability Status -->
      <div>
        <label class="form-label small fw-medium">Disability Status</label>
        <select name="disability_status" class="form-select">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Remarks -->
      <div>
        <label class="form-label small fw-medium">Remarks</label>
        <textarea name="remarks" rows="2" placeholder="Additional notes..." class="form-control"></textarea>
      </div>

          <!-- Submit -->
          <div class="pt-2">
            <button type="submit" class="w-100 btn btn-primary py-2 fw-semibold">Add Resident</button>
          </div>
        </form>
      </div>

      <!-- Live Preview Section -->
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
  <script>
    $(function() {
      $("body").show();
      $("#residentsTable").DataTable();
      
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
      
      // Initialize modal (hidden by default) - Modernized
      $("#addResidentModal").dialog({
        autoOpen: false,
        modal: true,
        width: 1000,
        height: 700,
        resizable: true,
        classes: {
          'ui-dialog': 'rounded shadow-lg',
          'ui-dialog-titlebar': 'dialog-titlebar-primary rounded-top',
          'ui-dialog-title': 'fw-semibold',
          'ui-dialog-buttonpane': 'dialog-buttonpane-light rounded-bottom'
        },
        show: {
          effect: "fadeIn",
          duration: 200
        },
        hide: {
          effect: "fadeOut",
          duration: 200
        },
        open: function() {
          $('.ui-dialog-buttonpane button').addClass('btn btn-primary');
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
        }
      });
      $("#openResidentModalBtn").on("click", function() {
        $("#addResidentModal").dialog("open");
      });
    })
  </script>
</body>

</html>
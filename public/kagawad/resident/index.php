<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  include_once __DIR__ . '/add.php';
}
// Use prepared statement for better security (though no user input here, good practice)
$stmt = $conn->prepare("SELECT * FROM residents WHERE deleted_at IS NULL ORDER BY id DESC");
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

<body class="bg-gray-100" style="display:none;">

  <?php include_once '../layout/navbar.php'; ?>

  <div class="flex bg-gray-100">
    <?php include_once '../layout/sidebar.php'; ?>
    <main class="p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Resident List</h2>
      <!-- ✅ Add Button -->
      <div class="p-6 flex gap-4">
        <button id="openResidentModalBtn"
          class="bg-theme-secondary hover-theme-darker text-white font-semibold px-4 py-2 rounded shadow">
          ➕ Add Resident
        </button>
        <button id="manageHouseholdsBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-xl text-sm font-semibold">
          🏠 Manage Households
        </button>
        <button id="archiveResidentsBtn" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">
          Residence Archive
        </button>
      </div>
      <!-- Residents Table -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden p-4">
        <table id="residentsTable" class="display w-full text-sm border border-gray-200 rounded-lg">
          <thead class="bg-gray-50 text-gray-700">
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
              <th class="p-2 text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result !== false): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="p-2">
                    <?= htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . ' ' . $row['suffix']); ?>
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
                <td class="p-2 text-center">
                  <div class="flex justify-center gap-1">
                    <button type="button" class="view-resident-btn bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm" data-id="<?= $row['id']; ?>">
                      View
                    </button>
                    <button type="button" class="edit-resident-btn bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm" data-id="<?= $row['id']; ?>">
                      Edit
                    </button>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="11" class="p-4 text-center text-gray-500">Error loading residents. Please try again later.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
  <!-- ✅ View Resident Modal -->
  <div id="viewResidentModal" title="View Resident Details" class="hidden">
    <div class="p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Column -->
        <div class="space-y-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Personal Information</h3>
            <div class="space-y-2">
              <div><span class="font-medium">Full Name:</span> <span id="view-full-name">-</span></div>
              <div><span class="font-medium">Gender:</span> <span id="view-gender">-</span></div>
              <div><span class="font-medium">Birthdate:</span> <span id="view-birthdate">-</span></div>
              <div><span class="font-medium">Age:</span> <span id="view-age">-</span></div>
              <div><span class="font-medium">Birthplace:</span> <span id="view-birthplace">-</span></div>
            </div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Contact Information</h3>
            <div class="space-y-2">
              <div><span class="font-medium">Contact No:</span> <span id="view-contact">-</span></div>
              <div><span class="font-medium">Address:</span> <span id="view-address">-</span></div>
            </div>
          </div>
        </div>
        <!-- Right Column -->
        <div class="space-y-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Civil Information</h3>
            <div class="space-y-2">
              <div><span class="font-medium">Civil Status:</span> <span id="view-civil-status">-</span></div>
              <div><span class="font-medium">Religion:</span> <span id="view-religion">-</span></div>
              <div><span class="font-medium">Citizenship:</span> <span id="view-citizenship">-</span></div>
              <div><span class="font-medium">Voter Status:</span> <span id="view-voter-status">-</span></div>
            </div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Additional Information</h3>
            <div class="space-y-2">
              <div><span class="font-medium">Occupation:</span> <span id="view-occupation">-</span></div>
              <div><span class="font-medium">Disability Status:</span> <span id="view-disability-status">-</span></div>
              <div><span class="font-medium">Household ID:</span> <span id="view-household-id">-</span></div>
              <div><span class="font-medium">Remarks:</span> <span id="view-remarks">-</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ✅ Edit Resident Modal -->
  <div id="editResidentModal" title="Edit Resident" class="hidden">
    <form id="editResidentForm" class="space-y-4 max-h-[70vh] overflow-y-auto p-4">
      <input type="hidden" id="edit-resident-id" name="id">
      <!-- Household -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Household (optional)</label>
        <div class="relative">
          <input type="text" id="edit-household-search" placeholder="Search households..." class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
          <select id="edit-household-id" name="household_id" class="hidden">
            <option value="">-- Select Household --</option>
            <!-- Households will be loaded dynamically -->
          </select>
          <div id="edit-household-dropdown" class="absolute z-50 w-full bg-white border border-gray-300 rounded-b shadow-lg max-h-60 overflow-y-auto hidden">
            <!-- Household options will appear here -->
          </div>
        </div>
      </div>

      <!-- Name Fields -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">First Name</label>
          <input type="text" id="edit-first-name" name="first_name" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Middle Name</label>
          <input type="text" id="edit-middle-name" name="middle_name" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Last Name</label>
          <input type="text" id="edit-last-name" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Suffix</label>
          <input type="text" id="edit-suffix" name="suffix" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Gender & Birthdate -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Gender</label>
          <select id="edit-gender" name="gender" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Birthdate</label>
          <input type="date" id="edit-birthdate" name="birthdate" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Birthplace -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Birthplace</label>
        <input type="text" id="edit-birthplace" name="birthplace" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
      </div>

      <!-- Civil Status & Religion -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Civil Status</label>
          <select id="edit-civil-status" name="civil_status" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
            <option value="Single">Single</option>
            <option value="Married">Married</option>
            <option value="Widowed">Widowed</option>
            <option value="Separated">Separated</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Religion</label>
          <input type="text" id="edit-religion" name="religion" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Occupation & Citizenship -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Occupation</label>
          <input type="text" id="edit-occupation" name="occupation" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Citizenship</label>
          <input type="text" id="edit-citizenship" name="citizenship" value="Filipino" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Contact & Address -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Contact No.</label>
          <input type="text" id="edit-contact-no" name="contact_no" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <input type="text" id="edit-address" name="address" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Voter Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Voter Status</label>
        <select id="edit-voter-status" name="voter_status" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Disability Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Disability Status</label>
        <select id="edit-disability-status" name="disability_status" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Remarks -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Remarks</label>
        <textarea id="edit-remarks" name="remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary"></textarea>
      </div>
    </form>
  </div>

  <!-- ✅ Archive Modal -->
  <div id="archivedResidentsDialog" title="Archived Residents" class="hidden">
    <!-- Search -->
    <div class="p-4 border-b">
      <div class="relative">
        <input
          type="text"
          placeholder="Search archived residents..."
          class="w-full border rounded-md px-3 py-2 pr-10 text-sm focus:outline-none" />
        <span class="absolute right-3 top-2.5 text-gray-400">🔍</span>
      </div>
    </div>

    <!-- Table -->
    <div class="p-4 overflow-auto max-h-[360px]">
      <table class="w-full text-sm border-collapse">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 text-left">ID</th>
            <th class="p-2 text-left">Full Name</th>
            <th class="p-2 text-left">Date Archived</th>
            <th class="p-2 text-center">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <!-- Data will be loaded dynamically -->
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2 text-xs text-gray-500 border-t">
      Loading...
    </div>
  </div>

  <!-- ✅ Hidden Modal (jQuery UI Dialog) -->
  <div id="addResidentModal" title="Add New Resident" class="hidden max-h-[50vh]">
    <form method="POST" class="space-y-3 overflow-y-scroll">
      <input type="hidden" name="action" value="add_resident">
      <?php if (isset($error)): ?>
        <p class='text-red-600 font-medium'><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <!-- Household -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Household (optional)</label>
        <div class="relative">
          <input type="text" id="add-household-search" placeholder="Search households..." class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
          <select name="household_id" id="add-household-id" class="hidden">
            <option value="">-- Select Household --</option>
            <!-- Households will be loaded dynamically -->
          </select>
          <div id="add-household-dropdown" class="absolute z-50 w-full bg-white border border-gray-300 rounded-b shadow-lg max-h-60 overflow-y-auto hidden">
            <!-- Household options will appear here -->
          </div>
        </div>
      </div>

      <!-- Name Fields -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">First Name</label>
          <input type="text" name="first_name" placeholder="First Name" required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Middle Name</label>
          <input type="text" name="middle_name" placeholder="Middle Name"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Last Name</label>
          <input type="text" name="last_name" placeholder="Last Name" required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Suffix</label>
          <input type="text" name="suffix" placeholder="e.g. Jr., Sr."
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Gender & Birthdate -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Gender</label>
          <select name="gender" required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Birthdate</label>
          <input type="date" name="birthdate" required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Birthplace -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Birthplace</label>
        <input type="text" name="birthplace" placeholder="Enter birthplace"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
      </div>

      <!-- Civil Status & Religion -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Civil Status</label>
          <select name="civil_status"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
            <option value="Single">Single</option>
            <option value="Married">Married</option>
            <option value="Widowed">Widowed</option>
            <option value="Separated">Separated</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Religion</label>
          <input type="text" name="religion" placeholder="e.g. Catholic"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Occupation & Citizenship -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Occupation</label>
          <input type="text" name="occupation" placeholder="Occupation"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Citizenship</label>
          <input type="text" name="citizenship" value="Filipino"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Contact & Purok -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Contact No.</label>
          <input type="text" name="contact_no" placeholder="09XXXXXXXXX"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <input type="text" name="address" placeholder="Enter address"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Voter Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Voter Status</label>
        <select name="voter_status"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Disability Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Disability Status</label>
        <select name="disability_status"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Remarks -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Remarks</label>
        <textarea name="remarks" rows="2" placeholder="Additional notes..."
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary"></textarea>
      </div>

      <!-- Submit -->
      <div class="pt-2">
        <button type="submit"
          class="w-full bg-theme-secondary hover-theme-darker text-white py-2 rounded font-semibold">
          Add Resident
        </button>
      </div>
    </form>

  </div>

  <!-- ✅ Household Management Modal -->
  <div id="householdManagementModal" title="Household Management" class="hidden">
    <div class="p-4">
      <!-- Action Buttons -->
      <div class="flex gap-2 mb-4">
        <button id="createHouseholdBtn" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm">
          ➕ Create Household
        </button>
        <div class="flex-1"></div>
        <input type="text" id="householdSearchInput" placeholder="Search households..." class="border rounded px-3 py-2 text-sm">
      </div>

      <!-- Household List -->
      <div class="border rounded-lg overflow-hidden">
        <div class="bg-gray-50 px-4 py-2 border-b">
          <h3 class="font-semibold">Households</h3>
        </div>
        <div id="householdList" class="max-h-96 overflow-y-auto">
          <!-- Household items will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- ✅ Create/Edit Household Modal -->
  <div id="householdFormModal" title="Household Details" class="hidden">
    <form id="householdForm" class="p-4 space-y-4">
      <input type="hidden" id="householdFormId" name="id">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Household Number *</label>
        <input type="text" id="householdFormNo" name="household_no" required
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
        <input type="text" id="householdFormAddress" name="address" required
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
      </div>

      <div id="householdFormHeadContainer">
        <label class="block text-sm font-medium text-gray-700 mb-1">Head of Household *</label>
        <div class="relative">
          <input type="text" id="householdFormHeadSearch" placeholder="Search residents..." required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
          <input type="hidden" id="householdFormHeadId" name="head_resident_id">
          <div id="householdFormHeadDropdown" class="absolute z-50 w-full bg-white border border-gray-300 rounded-b shadow-lg max-h-60 overflow-y-auto hidden">
            <!-- Resident options will appear here -->
          </div>
        </div>
      </div>
    </form>
  </div>

  <script>
    $(function() {
      $("body").show();

      // Force disable autocomplete on all inputs for cleaner UX
      function disableAutocomplete() {
        $('input[type="text"], input[type="email"], input[type="password"], input[type="search"], input[type="tel"], input[type="url"], textarea, select').each(function() {
          $(this).attr('autocomplete', 'off');
          $(this).attr('autocorrect', 'off');
          $(this).attr('autocapitalize', 'off');
          $(this).attr('spellcheck', 'false');
          $(this).attr('data-form-type', 'other'); // Prevent browser form detection
        });
      }

      // Run on page load
      disableAutocomplete();

      // Also run when modals are opened (for dynamic content)
      $(document).on('dialogopen', function() {
        setTimeout(disableAutocomplete, 100);
      });

      $("#residentsTable").DataTable();

      // Initialize View Resident Modal
      $("#viewResidentModal").dialog({
        autoOpen: false,
        modal: true,
        width: 900,
        height: 600,
        resizable: true,
        classes: {
          'ui-dialog': 'rounded-lg shadow-lg',
          'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
          'ui-dialog-title': 'font-semibold',
          'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg',
          'ui-dialog-buttonpane button': 'bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded'
        },
        show: {
          effect: "fadeIn",
          duration: 200
        },
        hide: {
          effect: "fadeOut",
          duration: 200
        },
        buttons: {
          "Close": function() {
            $(this).dialog("close");
          }
        },
        open: function() {
          // Button styling is now handled in the classes object above
        }
      });

      // Initialize Edit Resident Modal
      $("#editResidentModal").dialog({
        autoOpen: false,
        modal: true,
        width: 800,
        height: 700,
        resizable: true,
        classes: {
          'ui-dialog': 'rounded-lg shadow-lg',
          'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
          'ui-dialog-title': 'font-semibold',
          'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg',
          'ui-dialog-buttonpane button': 'bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded'
        },
        show: {
          effect: "fadeIn",
          duration: 200
        },
        hide: {
          effect: "fadeOut",
          duration: 200
        },
        buttons: {
          "Save": function() {
            saveResidentEdits();
          },
          "Cancel": function() {
            $(this).dialog("close");
          }
        },
        open: function() {
          // Button styling is now handled in the classes object above
        }
      });

      // Initialize modal (hidden by default) - Modernized
      $("#addResidentModal").dialog({
        autoOpen: false,
        modal: true,
        width: 800,
        height: 600,
        resizable: true,
        classes: {
          'ui-dialog': 'rounded-lg shadow-lg',
          'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
          'ui-dialog-title': 'font-semibold',
          'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg',
          'ui-dialog-buttonpane button': 'bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded'
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
          // Load households for the dropdown
          loadHouseholdsForAddModal();
          // Button styling is now handled in the classes object above
        }
      });

      // View Resident Button Click Handler
      $(document).on("click", ".view-resident-btn", function() {
        const residentId = $(this).data("id");
        loadResidentForView(residentId);
        $("#viewResidentModal").dialog("open");
      });

      // Edit Resident Button Click Handler
      $(document).on("click", ".edit-resident-btn", function() {
        const residentId = $(this).data("id");
        loadResidentForEdit(residentId);
        $("#editResidentModal").dialog("open");
      });

      $("#openResidentModalBtn").on("click", function() {
        $("#addResidentModal").dialog("open");
      });
    });

    // Function to load resident data for view modal
    function loadResidentForView(residentId) {
      $.getJSON(`get_resident.php?id=${residentId}`, function(data) {
        if (data.error) {
          alert("Error loading resident data: " + data.error);
          return;
        }

        // Populate view modal fields
        const fullName = [data.first_name, data.middle_name, data.last_name, data.suffix].filter(Boolean).join(' ');
        $("#view-full-name").text(fullName || '-');
        $("#view-gender").text(data.gender || '-');
        $("#view-birthdate").text(data.birthdate || '-');
        $("#view-age").text(data.birthdate ? calculateAge(data.birthdate) + ' years old' : '-');
        $("#view-birthplace").text(data.birthplace || '-');
        $("#view-contact").text(data.contact_no || '-');
        $("#view-address").text(data.address || '-');
        $("#view-civil-status").text(data.civil_status || '-');
        $("#view-religion").text(data.religion || '-');
        $("#view-citizenship").text(data.citizenship || '-');
        $("#view-voter-status").text(data.voter_status || '-');
        $("#view-occupation").text(data.occupation || '-');
        $("#view-disability-status").text(data.disability_status || '-');
        $("#view-household-id").text(data.household_display || '-');
        $("#view-remarks").text(data.remarks || '-');
      }).fail(function() {
        alert("Failed to load resident data. Please try again.");
      });
    }

    // Function to load resident data for edit modal
    function loadResidentForEdit(residentId) {
      // First load households for the dropdown
      loadHouseholdsForDropdown();

      $.getJSON(`get_resident.php?id=${residentId}`, function(data) {
        if (data.error) {
          alert("Error loading resident data: " + data.error);
          return;
        }

        // Populate edit modal form fields
        $("#edit-resident-id").val(data.id || '');
        $("#edit-household-id").val(data.household_id || '');
        $("#edit-first-name").val(data.first_name || '');
        $("#edit-middle-name").val(data.middle_name || '');
        $("#edit-last-name").val(data.last_name || '');
        $("#edit-suffix").val(data.suffix || '');
        $("#edit-gender").val(data.gender || 'Male');
        $("#edit-birthdate").val(data.birthdate || '');
        $("#edit-birthplace").val(data.birthplace || '');
        $("#edit-civil-status").val(data.civil_status || 'Single');
        $("#edit-religion").val(data.religion || '');
        $("#edit-occupation").val(data.occupation || '');
        $("#edit-citizenship").val(data.citizenship || 'Filipino');
        $("#edit-contact-no").val(data.contact_no || '');
        $("#edit-address").val(data.address || '');
        $("#edit-voter-status").val(data.voter_status || 'No');
        $("#edit-disability-status").val(data.disability_status || 'No');
        $("#edit-remarks").val(data.remarks || '');

        // Set household search input value
        if (data.household_id && data.household_display) {
          $("#edit-household-search").val(data.household_display);
        } else {
          $("#edit-household-search").val('');
        }
      }).fail(function() {
        alert("Failed to load resident data. Please try again.");
      });
    }

    // Global variable to store households data
    let allHouseholds = [];

    // Function to load households for dropdown
    function loadHouseholdsForDropdown() {
      $.ajax({
        url: 'household_api.php',
        type: 'GET',
        data: { limit: 1000 }, // Load all households
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            allHouseholds = response.households; // Store for search functionality

            const hiddenSelect = $('#edit-household-id');
            const dropdownContainer = $('#edit-household-dropdown');

            // Clear existing options except the first one
            hiddenSelect.find('option:not(:first)').remove();
            dropdownContainer.empty();

            // Add household options to hidden select
            response.households.forEach(household => {
              const option = `<option value="${household.id}">${household.household_no} - ${household.head_name} (${household.address})</option>`;
              hiddenSelect.append(option);

              // Create visible dropdown item
              const dropdownItem = `
                <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 household-option"
                     data-id="${household.id}"
                     data-text="${household.household_no} - ${household.head_name} (${household.address})">
                  <div class="font-medium text-blue-600">${household.household_no}</div>
                  <div class="text-sm text-gray-600">${household.head_name} • ${household.address}</div>
                  <div class="text-xs text-gray-500">${household.total_members} members</div>
                </div>
              `;
              dropdownContainer.append(dropdownItem);
            });
          }
        },
        error: function() {
          console.error('Failed to load households for dropdown');
        }
      });
    }

    // Function to load households for add modal dropdown
    function loadHouseholdsForAddModal() {
      $.ajax({
        url: 'household_api.php',
        type: 'GET',
        data: { limit: 1000 }, // Load all households
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            const hiddenSelect = $('#add-household-id');
            const dropdownContainer = $('#add-household-dropdown');

            // Clear existing options except the first one
            hiddenSelect.find('option:not(:first)').remove();
            dropdownContainer.empty();

            // Add household options to hidden select
            response.households.forEach(household => {
              const option = `<option value="${household.id}">${household.household_no} - ${household.head_name} (${household.address})</option>`;
              hiddenSelect.append(option);

              // Create visible dropdown item
              const dropdownItem = `
                <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 household-option"
                     data-id="${household.id}"
                     data-text="${household.household_no} - ${household.head_name} (${household.address})">
                  <div class="font-medium text-blue-600">${household.household_no}</div>
                  <div class="text-sm text-gray-600">${household.head_name} • ${household.address}</div>
                  <div class="text-xs text-gray-500">${household.total_members} members</div>
                </div>
              `;
              dropdownContainer.append(dropdownItem);
            });
          }
        },
        error: function() {
          console.error('Failed to load households for add modal');
        }
      });
    }

    // Function to save resident edits
    function saveResidentEdits() {
      const formData = new FormData(document.getElementById('editResidentForm'));
      const data = Object.fromEntries(formData.entries());

      $.ajax({
        url: 'update_resident.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            // Show success message
            $('<div>' + response.message + '</div>').dialog({
              modal: true,
              title: 'Success',
              width: 420,
              buttons: {
                Ok: function() {
                  $(this).dialog('close');
                  $("#editResidentModal").dialog("close");
                  // Refresh the page to show updated data
                  location.reload();
                }
              },
              classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
              }
            });
          } else {
            alert('Error: ' + response.message);
          }
        },
        error: function() {
          alert('Failed to save resident data. Please try again.');
        }
      });
    }

    // Initialize Archive Modal
    $("#archivedResidentsDialog").dialog({
      autoOpen: false,
      modal: true,
      width: 600,
      resizable: false,
      draggable: true,
      classes: {
        "ui-dialog": "rounded-lg shadow-xl",
        "ui-dialog-title": "font-semibold text-sm",
        "ui-dialog-buttonpane": "hidden"
      },
      open: function() {
        loadArchivedResidents();
      }
    });

    // Archive Residents Button
    $("#archiveResidentsBtn").on("click", function() {
      $("#archivedResidentsDialog").dialog("open");
    });

      // Archive Resident Button Click Handler
      $(document).on("click", ".archive-resident-btn", function() {
        const residentId = $(this).data("id");
        const residentName = $(this).data("name");

        // Show confirmation dialog
        $('<div>Are you sure you want to archive <strong>' + residentName + '</strong>?<br><br>This action can be undone later.</div>').dialog({
          modal: true,
          title: 'Confirm Archive',
          width: 450,
          buttons: {
            "Archive": function() {
              $(this).dialog('close');
              archiveResident(residentId);
            },
            "Cancel": function() {
              $(this).dialog('close');
            }
          },
          classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-titlebar': 'bg-orange-500 text-white rounded-t-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
          },
          open: function() {
            $('.ui-dialog-buttonpane button:first').addClass('bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded mr-2');
            $('.ui-dialog-buttonpane button:last').addClass('bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded');
          }
        });
      });

      // Restore Resident Button Click Handler
      $(document).on("click", ".restore-btn", function() {
        const residentId = $(this).data("id");
        const residentName = $(this).data("name");

        // Show confirmation dialog
        $('<div>Are you sure you want to restore <strong>' + residentName + '</strong>?<br><br>This will make the resident active again.</div>').dialog({
          modal: true,
          title: 'Confirm Restore',
          width: 450,
          buttons: {
            "Restore": function() {
              $(this).dialog('close');
              restoreResident(residentId);
            },
            "Cancel": function() {
              $(this).dialog('close');
            }
          },
          classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-titlebar': 'bg-green-500 text-white rounded-t-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
          },
          open: function() {
            $('.ui-dialog-buttonpane button:first').addClass('bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded mr-2');
            $('.ui-dialog-buttonpane button:last').addClass('bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded');
          }
        });
      });

    // Search functionality for archived residents
    $(document).on("input", "#archivedResidentsDialog input[type='text']", function() {
      const searchTerm = $(this).val();
      loadArchivedResidents(searchTerm);
    });

    // Function to archive a resident
    function archiveResident(residentId) {
      $.ajax({
        url: 'archive_api.php',
        type: 'POST',
        data: {
          action: 'archive',
          resident_id: residentId
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            // Show success message
            $('<div>Resident archived successfully!</div>').dialog({
              modal: true,
              title: 'Success',
              width: 420,
              buttons: {
                Ok: function() {
                  $(this).dialog('close');
                  location.reload(); // Refresh to update the table
                }
              },
              classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
              },
              open: function() {
                $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded');
              }
            });
          } else {
            // Show error message
            $('<div>Error: ' + response.message + '</div>').dialog({
              modal: true,
              title: 'Archive Error',
              width: 420,
              buttons: {
                Ok: function() {
                  $(this).dialog('close');
                }
              },
              classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-red-500 text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
              },
              open: function() {
                $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded');
              }
            });
          }
        },
        error: function() {
          // Show connection error
          $('<div>Failed to archive resident. Please try again.</div>').dialog({
            modal: true,
            title: 'Connection Error',
            width: 420,
            buttons: {
              Ok: function() {
                $(this).dialog('close');
              }
            },
            classes: {
              'ui-dialog': 'rounded-lg shadow-lg',
              'ui-dialog-titlebar': 'bg-red-500 text-white rounded-t-lg',
              'ui-dialog-title': 'font-semibold',
              'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
            },
            open: function() {
              $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded');
            }
          });
        }
      });
    }

    // Function to restore an archived resident
    function restoreResident(residentId) {
      $.ajax({
        url: 'archive_api.php',
        type: 'POST',
        data: {
          action: 'restore',
          resident_id: residentId
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            // Show success message
            $('<div>Resident restored successfully!</div>').dialog({
              modal: true,
              title: 'Success',
              width: 420,
              buttons: {
                Ok: function() {
                  $(this).dialog('close');
                  loadArchivedResidents(); // Refresh the archive modal
                }
              },
              classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
              },
              open: function() {
                $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded');
              }
            });
          } else {
            // Show error message
            $('<div>Error: ' + response.message + '</div>').dialog({
              modal: true,
              title: 'Restore Error',
              width: 420,
              buttons: {
                Ok: function() {
                  $(this).dialog('close');
                }
              },
              classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-red-500 text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
              },
              open: function() {
                $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded');
              }
            });
          }
        },
        error: function() {
          // Show connection error
          $('<div>Failed to restore resident. Please try again.</div>').dialog({
            modal: true,
            title: 'Connection Error',
            width: 420,
            buttons: {
              Ok: function() {
                $(this).dialog('close');
              }
            },
            classes: {
              'ui-dialog': 'rounded-lg shadow-lg',
              'ui-dialog-titlebar': 'bg-red-500 text-white rounded-t-lg',
              'ui-dialog-title': 'font-semibold',
              'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
            },
            open: function() {
              $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded');
            }
          });
        }
      });
    }

    // Function to load archived residents
    function loadArchivedResidents(searchTerm = '') {
      $.ajax({
        url: 'archive_api.php',
        type: 'GET',
        data: {
          search: searchTerm,
          limit: 50,
          offset: 0
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            updateArchivedResidentsTable(response.residents);
            updateArchivedResidentsFooter(response.total);
          } else {
            console.error('Failed to load archived residents:', response.message);
          }
        },
        error: function() {
          console.error('Failed to load archived residents');
        }
      });
    }

    // Function to update the archived residents table
    function updateArchivedResidentsTable(residents) {
      const tbody = $('#archivedResidentsDialog tbody');
      tbody.empty();

      if (residents.length === 0) {
        tbody.html('<tr><td colspan="4" class="p-4 text-center text-gray-500">No archived residents found</td></tr>');
        return;
      }

      residents.forEach(resident => {
        const row = `
          <tr>
            <td class="p-2 font-semibold">${resident.id.toString().padStart(3, '0')}</td>
            <td class="p-2">${resident.full_name}</td>
            <td class="p-2">${resident.archived_date}</td>
            <td class="p-2 text-center">
              <button class="restore-btn bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm" data-id="${resident.id}" data-name="${resident.full_name}">
                Restore
              </button>
            </td>
          </tr>
        `;
        tbody.append(row);
      });
    }

    // Function to update the footer with count
    function updateArchivedResidentsFooter(total) {
      const footer = $('#archivedResidentsDialog .border-t');
      footer.html(`<div class="px-4 py-2 text-xs text-gray-500">Showing ${total} archived resident${total !== 1 ? 's' : ''}</div>`);
    }

    // Initialize Household Management Modal
    $("#householdManagementModal").dialog({
      autoOpen: false,
      modal: true,
      width: 800,
      height: 600,
      resizable: true,
      classes: {
        "ui-dialog": "rounded-lg shadow-xl",
        "ui-dialog-title": "font-semibold text-sm",
        "ui-dialog-buttonpane": "hidden"
      },
      open: function() {
        loadHouseholds();
      }
    });

    // Initialize Household Form Modal
    $("#householdFormModal").dialog({
      autoOpen: false,
      modal: true,
      width: 500,
      resizable: false,
      buttons: {
        "Save": function() {
          saveHousehold();
        },
        "Cancel": function() {
          $(this).dialog('close');
        }
      },
      classes: {
        'ui-dialog': 'rounded-lg shadow-lg',
        'ui-dialog-titlebar': 'bg-blue-500 text-white rounded-t-lg',
        'ui-dialog-title': 'font-semibold',
        'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
      },
      open: function() {
        $('.ui-dialog-buttonpane button:first').addClass('bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mr-2');
        $('.ui-dialog-buttonpane button:last').addClass('bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded');
      }
    });

    // Household search and dropdown functionality
    function initializeHouseholdSearch(searchInputId, dropdownId, hiddenSelectId) {
      const searchInput = $(`#${searchInputId}`);
      const dropdown = $(`#${dropdownId}`);
      const hiddenSelect = $(`#${hiddenSelectId}`);

      // Initially hide the dropdown
      dropdown.addClass('hidden');

      // Only show dropdown on explicit click (not focus to avoid auto-focus issues)
      searchInput.on('click', function(e) {
        e.stopPropagation();
        // Only show if not already visible
        if (dropdown.hasClass('hidden')) {
          showHouseholdDropdown(dropdownId);
          filterHouseholdOptions(searchInputId, dropdownId);
        }
      });

      // Hide dropdown when clicking outside
      $(document).on('click', function(e) {
        if (!$(e.target).closest(`#${searchInputId}, #${dropdownId}`).length) {
          dropdown.addClass('hidden');
        }
      });

      // Search functionality
      searchInput.on('input', function() {
        const searchValue = $(this).val().trim();
        if (searchValue === '') {
          // Clear selection when input is empty
          hiddenSelect.val('');
          // Hide dropdown when input is cleared
          dropdown.addClass('hidden');
        } else {
          // Clear hidden value if user is typing but hasn't selected from dropdown
          // This prevents stale data when user types a new search
          const currentHiddenValue = hiddenSelect.val();
          if (currentHiddenValue && !isValidHouseholdSelection(searchInputId, dropdownId, currentHiddenValue)) {
            hiddenSelect.val('');
          }
          // Show dropdown when user starts typing
          showHouseholdDropdown(dropdownId);
        }
        filterHouseholdOptions(searchInputId, dropdownId);
      });

      // Handle option selection
      dropdown.on('click', '.household-option', function() {
        const selectedId = $(this).data('id');
        const selectedText = $(this).data('text');

        hiddenSelect.val(selectedId);
        searchInput.val(selectedText);
        dropdown.addClass('hidden'); // Hide dropdown after selection
      });
    }

    function showHouseholdDropdown(dropdownId) {
      $(`#${dropdownId}`).removeClass('hidden');
    }

    function filterHouseholdOptions(searchInputId, dropdownId) {
      const searchTerm = $(`#${searchInputId}`).val().toLowerCase();
      const options = $(`#${dropdownId} .household-option`);

      options.each(function() {
        const optionText = $(this).data('text').toLowerCase();
        if (optionText.includes(searchTerm)) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    }

    function isValidHouseholdSelection(searchInputId, dropdownId, hiddenValue) {
      const options = $(`#${dropdownId} .household-option`);
      let isValid = false;

      options.each(function() {
        if ($(this).data('id') == hiddenValue) {
          isValid = true;
          return false; // break out of each loop
        }
      });

      return isValid;
    }

    // Function to initialize resident search for head of household
    function initializeResidentSearch(searchInputId, dropdownId, hiddenId, nameId) {
      const searchInput = $(`#${searchInputId}`);
      const dropdown = $(`#${dropdownId}`);
      const hiddenInput = $(`#${hiddenId}`);
      const nameInput = $(`#${nameId}`);

      // Toggle dropdown on input focus/click
      searchInput.on('focus click', function(e) {
        e.stopPropagation();
        dropdown.removeClass('hidden');
        filterResidentOptions(searchInputId, dropdownId);
      });

      // Hide dropdown when clicking outside
      $(document).on('click', function(e) {
        if (!$(e.target).closest(`#${searchInputId}, #${dropdownId}`).length) {
          dropdown.addClass('hidden');
        }
      });

      // Search functionality
      searchInput.on('input', function() {
        filterResidentOptions(searchInputId, dropdownId);
        dropdown.removeClass('hidden');
      });

      // Handle option selection
      dropdown.on('click', '.resident-option', function() {
        const selectedId = $(this).data('id');
        const selectedName = $(this).data('name');

        hiddenInput.val(selectedId);
        nameInput.val(selectedName);
        searchInput.val(selectedName);
        dropdown.addClass('hidden');
      });
    }

    function filterResidentOptions(searchInputId, dropdownId) {
      const searchTerm = $(`#${searchInputId}`).val().toLowerCase();
      const options = $(`#${dropdownId} .resident-option`);

      options.each(function() {
        const residentName = $(this).data('name').toLowerCase();
        const residentAddress = ($(this).data('address') || '').toLowerCase();
        if (residentName.includes(searchTerm) || residentAddress.includes(searchTerm)) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    }

    // Initialize searchable dropdowns
    initializeHouseholdSearch('edit-household-search', 'edit-household-dropdown', 'edit-household-id');
    initializeHouseholdSearch('add-household-search', 'add-household-dropdown', 'add-household-id');

    // Initialize head of household search
    initializeResidentSearch('householdFormHeadSearch', 'householdFormHeadDropdown', 'householdFormHeadId', 'householdFormHead');

    // Household Management Button
    $("#manageHouseholdsBtn").on("click", function() {
      $("#householdManagementModal").dialog("open");
    });

    // Create Household Button
    $("#createHouseholdBtn").on("click", function() {
      resetHouseholdForm();
      loadResidentsForHeadSelection();
      $("#householdFormModal").dialog("option", "title", "Create Household");
      $("#householdFormModal").dialog("open");
    });

    // Search functionality
    $("#householdSearchInput").on("input", function() {
      const searchTerm = $(this).val();
      loadHouseholds(searchTerm);
    });

    // Function to load households
    function loadHouseholds(searchTerm = '') {
      $.ajax({
        url: 'household_api.php',
        type: 'GET',
        data: {
          search: searchTerm,
          limit: 100
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            renderHouseholdList(response.households);
          } else {
            console.error('Failed to load households:', response.message);
          }
        },
        error: function() {
          console.error('Failed to load households');
        }
      });
    }

    // Function to render household list
    function renderHouseholdList(households) {
      const container = $('#householdList');
      container.empty();

      if (households.length === 0) {
        container.html('<div class="p-4 text-center text-gray-500">No households found</div>');
        return;
      }

      households.forEach(household => {
        const item = `
          <div class="border-b p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="font-semibold text-blue-600">${household.household_no}</div>
                <div class="text-sm text-gray-600 mt-1">
                  <div>🏠 ${household.address}</div>
                  <div>👤 Head: ${household.head_name}</div>
                  <div>👥 Members: ${household.total_members}</div>
                </div>
              </div>
              <div class="flex gap-2">
                <button class="edit-household-btn bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm"
                        data-id="${household.id}" data-household='${JSON.stringify(household)}'>
                  ✏️ Edit
                </button>
                <button class="archive-household-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm"
                        data-id="${household.id}" data-name="${household.household_no}">
                  🗑️ Archive
                </button>
              </div>
            </div>
          </div>
        `;
        container.append(item);
      });

      // Attach event handlers
      $('.edit-household-btn').on('click', function() {
        const household = JSON.parse($(this).attr('data-household'));
        editHousehold(household);
      });

      $('.archive-household-btn').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        archiveHousehold(id, name);
      });
    }

    // Function to reset household form
    function resetHouseholdForm() {
      $('#householdForm')[0].reset();
      $('#householdFormId').val('');
      $('#householdFormHeadId').val('');
      $('#householdFormHeadSearch').val('');
      // Show head selection for new households
      $('#householdFormHeadContainer').show();
      $('#householdFormHeadSearch').prop('required', true);
    }

    // Function to load residents for head of household selection
    function loadResidentsForHeadSelection() {
      $.ajax({
        url: 'get_residents_for_head.php',
        type: 'GET',
        data: { limit: 1000 },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            const dropdownContainer = $('#householdFormHeadDropdown');
            dropdownContainer.empty();

            response.residents.forEach(resident => {
              const fullName = [resident.first_name, resident.middle_name, resident.last_name, resident.suffix].filter(Boolean).join(' ');
              const residentItem = `
                <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 resident-option"
                     data-id="${resident.id}"
                     data-name="${fullName}"
                     data-address="${resident.address || ''}">
                  <div class="font-medium text-blue-600">${fullName}</div>
                  <div class="text-sm text-gray-600">${resident.address || 'No address'}</div>
                  <div class="text-xs text-gray-500">Age: ${resident.age || 'Unknown'}</div>
                </div>
              `;
              dropdownContainer.append(residentItem);
            });
          }
        },
        error: function() {
          console.error('Failed to load residents for head selection');
        }
      });
    }

    // Function to edit household
    function editHousehold(household) {
      $('#householdFormId').val(household.id);
      $('#householdFormNo').val(household.household_no);
      $('#householdFormAddress').val(household.address);
      // Note: Head cannot be changed after household creation
      $('#householdFormHeadContainer').hide();
      $('#householdFormHeadSearch').prop('required', false);

      $("#householdFormModal").dialog("option", "title", "Edit Household");
      $("#householdFormModal").dialog("open");
    }

    // Function to save household
    function saveHousehold() {
      const formData = new FormData(document.getElementById('householdForm'));
      const data = Object.fromEntries(formData.entries());
      const isEdit = data.id ? true : false;

      // Validate required fields
      if (!data.household_no || !data.address) {
        alert('Please fill in all required fields.');
        return;
      }

      // For creation, head_resident_id is required
      if (!isEdit && !data.head_resident_id) {
        alert('Please select a head of household.');
        return;
      }

      const action = isEdit ? 'update' : 'create';
      data.action = action;

      $.ajax({
        url: 'household_api.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            // Show success message
            $('<div>' + response.message + '</div>').dialog({
              modal: true,
              title: 'Success',
              width: 420,
              buttons: {
                Ok: function() {
                  $(this).dialog('close');
                  $("#householdFormModal").dialog('close');
                  loadHouseholds(); // Refresh the list
                }
              },
              classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-green-500 text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
              },
              open: function() {
                $('.ui-dialog-buttonpane button').addClass('bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded');
              }
            });
          } else {
            alert('Error: ' + response.message);
          }
        },
        error: function() {
          alert('Failed to save household. Please try again.');
        }
      });
    }

    // Function to archive household
    function archiveHousehold(id, name) {
      // Show confirmation dialog
      $('<div>Are you sure you want to archive household <strong>' + name + '</strong>?<br><br>This action cannot be undone and will permanently delete the household.</div>').dialog({
        modal: true,
        title: 'Confirm Archive',
        width: 450,
        buttons: {
          "Archive": function() {
            $(this).dialog('close');
            performArchiveHousehold(id);
          },
          "Cancel": function() {
            $(this).dialog('close');
          }
        },
        classes: {
          'ui-dialog': 'rounded-lg shadow-lg',
          'ui-dialog-titlebar': 'bg-red-500 text-white rounded-t-lg',
          'ui-dialog-title': 'font-semibold',
          'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
        },
        open: function() {
          $('.ui-dialog-buttonpane button:first').addClass('bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded mr-2');
          $('.ui-dialog-buttonpane button:last').addClass('bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded');
        }
      });
    }

    // Function to perform household archiving
    function performArchiveHousehold(id) {
      $.ajax({
        url: 'household_api.php',
        type: 'POST',
        data: {
          action: 'archive',
          id: id
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            // Show success message
            $('<div>' + response.message + '</div>').dialog({
              modal: true,
              title: 'Success',
              width: 420,
              buttons: {
                Ok: function() {
                  $(this).dialog('close');
                  loadHouseholds(); // Refresh the list
                }
              },
              classes: {
                'ui-dialog': 'rounded-lg shadow-lg',
                'ui-dialog-titlebar': 'bg-green-500 text-white rounded-t-lg',
                'ui-dialog-title': 'font-semibold',
                'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
              },
              open: function() {
                $('.ui-dialog-buttonpane button').addClass('bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded');
              }
            });
          } else {
            alert('Error: ' + response.message);
          }
        },
        error: function() {
          alert('Failed to archive household. Please try again.');
        }
      });
    }

    // Helper function to calculate age
    function calculateAge(birthdate) {
      if (!birthdate) return null;
      const parts = birthdate.split('-');
      if (parts.length !== 3) return null;
      const birthDate = new Date(parts[0], parts[1] - 1, parts[2]);
      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      return age;
    }
  </script>
</body>

</html>
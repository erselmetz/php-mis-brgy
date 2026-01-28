<?php
require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="autocomplete" content="off">
  <title>Resident - MIS Barangay</title>
  <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">

  <?php include_once '../layout/navbar.php'; ?>

  <div class="flex h-full bg-gray-100">
    <?php include_once '../layout/sidebar.php'; ?>

    <main class="pb-24 overflow-y-auto flex-1 p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Resident List</h2>
      <!-- ✅ Add Button -->
      <div class="p-6 flex gap-4">
        <button id="openResidentModalBtn"
          class="bg-theme-primary hover-theme-darker text-white font-semibold px-4 py-2 rounded shadow">
          ➕ Add Resident
        </button>
        <button id="manageHouseholdsBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-xl text-sm font-semibold">
          🏠 Manage Households
        </button>
        <button id="archiveResidentsBtn" class="bg-theme-primary hover:bg-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">
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
                      <button type="button" class="archive-resident-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm" data-id="<?= $row['id']; ?>" data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>">
                        Archive
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="12" class="p-4 text-center text-gray-500">Error loading residents. Please try again later.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <!-- foot -->
      <!-- <div class="flex justify-end mt-6">
        <button id="archiveResidentsBtn" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">Residence Archive</button>
      </div> -->
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
          <input type="text" id="edit-household-search" placeholder="Search households..." autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
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
          <input type="text" id="edit-first-name" name="first_name" required autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Middle Name</label>
          <input type="text" id="edit-middle-name" name="middle_name" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Last Name</label>
          <input type="text" id="edit-last-name" name="last_name" required autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Suffix</label>
          <input type="text" id="edit-suffix" name="suffix" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
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
        <input type="text" id="edit-birthplace" name="birthplace" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
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
          <input type="text" id="edit-religion" name="religion" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Occupation & Citizenship -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Occupation</label>
          <input type="text" id="edit-occupation" name="occupation" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Citizenship</label>
          <input type="text" id="edit-citizenship" name="citizenship" value="Filipino" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Contact & Address -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Contact No.</label>
          <input type="text" id="edit-contact-no" name="contact_no" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <input type="text" id="edit-address" name="address" autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
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

  <!-- Dialog Message for success=1 -->
  <?php if (isset($_GET['success']) && $_GET['success'] == '1'){
    echo DialogMessage("✅ Resident added successfully!");
  }
  ?>

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
          <input type="text" id="add-household-search" placeholder="Search households..." autocomplete="off" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
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
          <input type="text" name="first_name" placeholder="First Name" required autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Middle Name</label>
          <input type="text" name="middle_name" placeholder="Middle Name" autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Last Name</label>
          <input type="text" name="last_name" placeholder="Last Name" required autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Suffix</label>
          <input type="text" name="suffix" placeholder="e.g. Jr., Sr." autocomplete="off"
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
        <input type="text" name="birthplace" placeholder="Enter birthplace" autocomplete="off"
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
          <input type="text" name="religion" placeholder="e.g. Catholic" autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Occupation & Citizenship -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Occupation</label>
          <input type="text" name="occupation" placeholder="Occupation" autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Citizenship</label>
          <input type="text" name="citizenship" value="Filipino" autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>
      </div>

      <!-- Contact & Purok -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Contact No.</label>
          <input type="text" name="contact_no" placeholder="09XXXXXXXXX" autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <input type="text" name="address" placeholder="Enter address" autocomplete="off"
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
          class="w-full bg-theme-primary hover-theme-darker text-white py-2 rounded font-semibold">
          Add Resident
        </button>
      </div>
    </form>
    <!-- archive modal -->
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
            <tr>
              <td class="p-2 font-semibold">042</td>
              <td class="p-2">Juan Tamad</td>
              <td class="p-2">2023-10-01</td>
              <td class="p-2 text-center">
                <button class="restore-btn">Restore</button>
              </td>
            </tr>

            <tr>
              <td class="p-2 font-semibold">089</td>
              <td class="p-2">Maria Clara</td>
              <td class="p-2">2023-11-12</td>
              <td class="p-2 text-center">
                <button class="restore-btn">Restore</button>
              </td>
            </tr>

            <tr>
              <td class="p-2 font-semibold">105</td>
              <td class="p-2">Pedro Penduko</td>
              <td class="p-2">2023-12-05</td>
              <td class="p-2 text-center">
                <button class="restore-btn">Restore</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="px-4 py-2 text-xs text-gray-500 border-t">
        Showing 3 of 3 archived residents
      </div>

    </div>

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
        <input type="text" id="householdSearchInput" placeholder="Search households..." autocomplete="off" class="border rounded px-3 py-2 text-sm">
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
        <input type="text" id="householdFormNo" name="household_no" required autocomplete="off"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
        <input type="text" id="householdFormAddress" name="address" required autocomplete="off"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
      </div>

      <div id="householdFormHeadContainer">
        <label class="block text-sm font-medium text-gray-700 mb-1">Head of Household *</label>
        <div class="relative">
          <input type="text" id="householdFormHeadSearch" placeholder="Search residents..." required autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
          <input type="hidden" id="householdFormHeadId" name="head_resident_id">
          <div id="householdFormHeadDropdown" class="absolute z-50 w-full bg-white border border-gray-300 rounded-b shadow-lg max-h-60 overflow-y-auto hidden">
            <!-- Resident options will appear here -->
          </div>
        </div>
      </div>
    </form>
  </div>

  <script src="js/index.js"></script>
</body>

</html>
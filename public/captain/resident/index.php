<?php
require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();

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

  <!-- ✅ Hidden Modal (jQuery UI Dialog) -->

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
              <td class="p-2 font-semibold"></td>
              <td class="p-2"></td>
              <td class="p-2"></td>
              <td class="p-2 text-center">
                <button class="restore-btn"></button>
              </td>
            </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2 text-xs text-gray-500 border-t">
        <p>Note: loading archived residents...</p>
  </div>

      </div>

  </div>

  <!-- ✅ Household Management Modal -->
  <div id="householdManagementModal" title="Household Management" class="hidden">
    <div class="p-4">
      <!-- Action Buttons -->
      <div class="flex gap-2 mb-4">
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

  <script src="js/index.js"></script>
</body>

</html>
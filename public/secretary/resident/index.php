<?php
require_once __DIR__ . '/../../../includes/app.php';
requireAdmin();

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

<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">

  <?php include_once '../layout/navbar.php'; ?>

  <div class="flex h-full bg-gray-100">
    <?php include_once '../layout/sidebar.php'; ?>
    
    <main class="pb-24 overflow-y-auto flex-1 p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Resident List</h2>
      <!-- ✅ Add Button -->
      <div class="p-6">
        <button id="openResidentModalBtn"
          class="bg-theme-primary hover-theme-darker text-white font-semibold px-4 py-2 rounded shadow">
          ➕ Add Resident
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
            </tr>
          </thead>
          <tbody>
            <?php if ($result !== false): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td class="p-2">
                    <a href="view.php?id=<?= $row['id']; ?>" class="text-theme-accent hover:underline">
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
      <div class="flex justify-end mt-6">
        <button id="archiveResidentsBtn" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">Residence Archive</button>
      </div>
    </main>
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
        <label class="block text-sm font-medium text-gray-700">Household ID (optional)</label>
        <input type="number" name="household_id" placeholder="Enter household ID"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
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
  <script>
    $(function() {
      $("body").show();
      $("#residentsTable").DataTable();
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
          'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
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
          $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded');
        }
      });

      $("#openResidentModalBtn").on("click", function() {
        $("#addResidentModal").dialog("open");
      });


      $("#archivedResidentsDialog").dialog({
        autoOpen: false,
        modal: true,
        width: 500,
        resizable: false,
        draggable: true,
        classes: {
          "ui-dialog": "rounded-lg shadow-xl",
          "ui-dialog-title": "font-semibold text-sm",
          "ui-dialog-buttonpane": "hidden"
        }
      });

      // example open
      // $("#archivedResidentsDialog").dialog("open");
      $("#archiveResidentsBtn").on("click", function() {
        $("#archivedResidentsDialog").dialog("open");
      });
    })
  </script>
</body>

</html>
<?php
require_once '../includes/app.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  include_once 'resident/add.php';
}
$result = $conn->query("SELECT * FROM residents ORDER BY id DESC");

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Resident - MIS Barangay</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <?php loadAllStyles(); ?>
</head>

<body class="bg-gray-100">

  <?php include_once './navbar.php'; ?>

  <div class="flex bg-gray-100">
    <?php include_once './sidebar.php'; ?>
    <main class="p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Resident List</h2>
      <!-- ✅ Add Button -->
      <div class="p-6">
        <button id="openResidentModalBtn"
          class="bg-blue-700 hover:bg-blue-800 text-white font-semibold px-4 py-2 rounded shadow">
          ➕ Add Resident
        </button>
      </div>
      <div class="content">
        <div class="overflow-x-auto">
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
                <th class="p-2 text-left">Voter</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td class="p-2">
                    <a href="resident/view.php?id=<?= $row['id']; ?>" class="text-blue-600 hover:underline">
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
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
  <!-- ✅ Hidden Modal (jQuery UI Dialog) -->
  <div id="addResidentModal" title="Add New Resident" class="hidden max-h-[50vh]">
    <form method="POST" class="space-y-3 overflow-y-scroll ">
      <input type="hidden" name="action" value="add_resident">
      <?php if (isset($error)) echo "<p class='text-red-600 font-medium'>$error</p>"; ?>

      <!-- Household -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Household ID (optional)</label>
        <input type="number" name="household_id" placeholder="Enter household ID"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <!-- Name Fields -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">First Name</label>
          <input type="text" name="first_name" placeholder="First Name" required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Middle Name</label>
          <input type="text" name="middle_name" placeholder="Middle Name"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Last Name</label>
          <input type="text" name="last_name" placeholder="Last Name" required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Suffix</label>
          <input type="text" name="suffix" placeholder="e.g. Jr., Sr."
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <!-- Gender & Birthdate -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Gender</label>
          <select name="gender" required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Birthdate</label>
          <input type="date" name="birthdate" required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <!-- Birthplace -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Birthplace</label>
        <input type="text" name="birthplace" placeholder="Enter birthplace"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
      </div>

      <!-- Civil Status & Religion -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Civil Status</label>
          <select name="civil_status"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
            <option value="Single">Single</option>
            <option value="Married">Married</option>
            <option value="Widowed">Widowed</option>
            <option value="Separated">Separated</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Religion</label>
          <input type="text" name="religion" placeholder="e.g. Catholic"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <!-- Occupation & Citizenship -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Occupation</label>
          <input type="text" name="occupation" placeholder="Occupation"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Citizenship</label>
          <input type="text" name="citizenship" value="Filipino"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <!-- Contact & Purok -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">Contact No.</label>
          <input type="text" name="contact_no" placeholder="09XXXXXXXXX"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <input type="text" name="address" placeholder="Enter address"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <!-- Voter Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Voter Status</label>
        <select name="voter_status"
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>

      <!-- Remarks -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Remarks</label>
        <textarea name="remarks" rows="2" placeholder="Additional notes..."
          class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"></textarea>
      </div>

      <!-- Submit -->
      <div class="pt-2">
        <button type="submit"
          class="w-full bg-blue-700 hover:bg-blue-800 text-white py-2 rounded font-semibold">
          Add Resident
        </button>
      </div>
    </form>

  </div>
  <?php loadAllScripts(); ?>
  <script>
    $(function() {
      $("#residentsTable").DataTable();
      // Initialize modal (hidden by default)
      $("#addResidentModal").dialog({
        autoOpen: false,
        modal: true,
        width: window.screen,
        show: {
          effect: "fadeIn",
          duration: 200
        },
        hide: {
          effect: "fadeOut",
          duration: 200
        }
      });
      $("#openResidentModalBtn").on("click", function() {
        $("#addResidentModal").dialog("open");
      });
    })
  </script>
</body>

</html>
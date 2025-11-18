<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  include_once __DIR__ . '/add.php';
}

// Fetch households with member count
$stmt = $conn->prepare("
    SELECT h.*, 
           COUNT(r.id) as member_count
    FROM households h
    LEFT JOIN residents r ON r.household_id = h.id
    GROUP BY h.id
    ORDER BY h.id DESC
");
if ($stmt === false) {
  error_log('Households query error: ' . $conn->error);
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
  <title>Households - MIS Barangay</title>
  <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100" style="display:none;">

  <?php include_once '../navbar.php'; ?>

  <div class="flex bg-gray-100">
    <?php include_once '../sidebar.php'; ?>
    <main class="p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Household List</h2>
      <!-- ✅ Add Button -->
      <div class="p-6">
        <button id="openHouseholdModalBtn"
          class="bg-blue-700 hover:bg-blue-800 text-white font-semibold px-4 py-2 rounded shadow">
          ➕ Add Household
        </button>
      </div>
      <!-- Households Table -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden p-4">
        <table id="householdsTable" class="display w-full text-sm border border-gray-200 rounded-lg">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="p-2 text-left">Household No.</th>
              <th class="p-2 text-left">Head Name</th>
              <th class="p-2 text-left">Address</th>
              <th class="p-2 text-left">Total Members</th>
              <th class="p-2 text-left">Created At</th>
              <th class="p-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result !== false): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="p-2">
                  <a href="/household/view?id=<?= $row['id']; ?>" class="text-blue-600 hover:underline">
                    <?= htmlspecialchars($row['household_no']); ?>
                  </a>
                </td>
                <td class="p-2"><?= htmlspecialchars($row['head_name']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['address']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['member_count']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['created_at']); ?></td>
                <td class="p-2">
                  <a href="/household/view?id=<?= $row['id']; ?>" 
                     class="text-blue-600 hover:underline mr-2">View</a>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="p-4 text-center text-gray-500">Error loading households. Please try again later.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
  <!-- ✅ Hidden Modal (jQuery UI Dialog) -->
  <div id="addHouseholdModal" title="Add New Household" class="hidden max-h-[50vh]">
    <form method="POST" class="space-y-3 overflow-y-scroll">
      <input type="hidden" name="action" value="add_household">
      <?php if (isset($error)) echo "<p class='text-red-600 font-medium'>$error</p>"; ?>

      <!-- Household Number -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Household Number</label>
        <input type="text" name="household_no" placeholder="Enter household number (e.g., HH-2024-001)" required
          class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <!-- Head Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Head of Household Name</label>
        <input type="text" name="head_name" placeholder="Enter head of household name" required
          class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <!-- Address -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Address</label>
        <textarea name="address" rows="3" placeholder="Enter complete address" required
          class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
      </div>

      <!-- Submit -->
      <div class="pt-2">
        <button type="submit"
          class="w-full bg-blue-700 hover:bg-blue-800 text-white py-2 rounded font-semibold">
          Add Household
        </button>
      </div>
    </form>

  </div>
  <script>
    $(function() {
      $("body").show();
      $("#householdsTable").DataTable();
      // Initialize modal (hidden by default) - Modernized
      $("#addHouseholdModal").dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        height: 400,
        resizable: true,
        classes: {
          'ui-dialog': 'rounded-lg shadow-lg',
          'ui-dialog-titlebar': 'bg-blue-600 text-white rounded-t-lg',
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
          $('.ui-dialog-buttonpane button').addClass('bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded');
        }
      });
      $("#openHouseholdModalBtn").on("click", function() {
        $("#addHouseholdModal").dialog("open");
      });
    })
  </script>
</body>

</html>


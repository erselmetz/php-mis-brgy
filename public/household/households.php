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

<body class="bg-light" style="display:none;">

  <?php include_once '../navbar.php'; ?>

  <div class="d-flex bg-light">
    <?php include_once '../sidebar.php'; ?>
    <main class="p-4 w-100">
      <h2 class="h3 fw-semibold mb-4">Household List</h2>
      <!-- ✅ Add Button -->
      <div class="p-4">
        <button id="openHouseholdModalBtn"
          class="btn btn-primary fw-semibold px-4 py-2 shadow">
          ➕ Add Household
        </button>
      </div>
      <!-- Households Table -->
      <div class="bg-white rounded-3 shadow-sm border overflow-hidden p-4">
        <table id="householdsTable" class="display w-100 small border rounded-3">
          <thead class="bg-light text-dark">
            <tr>
              <th class="p-2 text-start">Household No.</th>
              <th class="p-2 text-start">Head Name</th>
              <th class="p-2 text-start">Address</th>
              <th class="p-2 text-start">Total Members</th>
              <th class="p-2 text-start">Created At</th>
              <th class="p-2 text-start">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result !== false): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="p-2">
                  <a href="/household/view?id=<?= $row['id']; ?>" class="text-primary text-decoration-none">
                    <?= htmlspecialchars($row['household_no']); ?>
                  </a>
                </td>
                <td class="p-2"><?= htmlspecialchars($row['head_name']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['address']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['member_count']); ?></td>
                <td class="p-2"><?= htmlspecialchars($row['created_at']); ?></td>
                <td class="p-2">
                  <a href="/household/view?id=<?= $row['id']; ?>" 
                     class="text-primary text-decoration-none me-2">View</a>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="p-4 text-center text-muted">Error loading households. Please try again later.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
  <!-- ✅ Hidden Modal (jQuery UI Dialog) -->
  <div id="addHouseholdModal" title="Add New Household">
    <form method="POST" class="overflow-y-auto" style="max-height: 70vh;">
      <input type="hidden" name="action" value="add_household">
      <?php if (isset($error)) echo "<p class='text-danger fw-medium'>$error</p>"; ?>

      <!-- Household Number -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Household Number</label>
        <input type="text" name="household_no" placeholder="Enter household number (e.g., HH-2024-001)" required
          class="form-control">
      </div>

      <!-- Head Name -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Head of Household Name</label>
        <input type="text" name="head_name" placeholder="Enter head of household name" required
          class="form-control">
      </div>

      <!-- Address -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Address</label>
        <textarea name="address" rows="3" placeholder="Enter complete address" required
          class="form-control"></textarea>
      </div>

      <!-- Submit -->
      <div class="pt-2">
        <button type="submit"
          class="w-100 btn btn-primary py-2 fw-semibold">
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
        }
      });
      $("#openHouseholdModalBtn").on("click", function() {
        $("#addHouseholdModal").dialog("open");
      });
    })
  </script>
</body>

</html>


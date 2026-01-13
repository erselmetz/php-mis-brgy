<?php
require_once __DIR__ . '/../../../includes/app.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    include_once __DIR__ . '/add_account.php';
    include_once __DIR__ . '/edit_account.php';
}
requireAdmin();

// Fetch all users with their officer information and resident details
$stmt = $conn->prepare("
    SELECT u.*, 
           o.id as officer_id, o.resident_id, o.position as officer_position, o.term_start, o.term_end, o.status as officer_status,
           r.first_name, r.middle_name, r.last_name, r.suffix,
           CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, ' ', COALESCE(r.suffix, '')) as resident_full_name
    FROM users u
    LEFT JOIN officers o ON u.id = o.user_id
    LEFT JOIN residents r ON o.resident_id = r.id
    ORDER BY u.id DESC
");
if ($stmt === false) {
  error_log('Account query error: ' . $conn->error);
  $result = false;
} else {
  $stmt->execute();
  $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Accounts Management</title>
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display: none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex h-full bg-gray-100">
        <?php include '../layout/sidebar.php'; ?>
        <main class="pb-24 overflow-y-auto flex-1 p-6 w-screen">
            <h2 class="text-2xl font-semibold mb-4">Staff & Officers Management</h2>

            <!-- ✅ Add Button -->
            <div class="p-6">
                <button id="openModalBtn"
                    class="bg-theme-primary hover-theme-darker text-white font-semibold px-4 py-2 rounded shadow">
                    ➕ Add New Account / Officer
                </button>
            </div>
            <!-- Accounts Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden p-4">
                <table id="accountsTable" class="display w-full text-sm border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="p-2 text-left">Name</th>
                            <th class="p-2 text-left">Username</th>
                            <th class="p-2 text-left">Role</th>
                            <th class="p-2 text-left">Status</th>
                            <th class="p-2 text-left">Created</th>
                            <th class="p-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result !== false): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="p-2"><?= htmlspecialchars($row['name']); ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['username']); ?></td>
                                <td class="p-2"><?= ucfirst($row['role']); ?></td>
                                <td class="p-2">
                                    <?php
                                    $statusColor = $row['status'] === 'active' 
                                        ? 'bg-green-100 text-green-800' 
                                        : 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 rounded text-xs font-semibold <?= $statusColor ?>">
                                        <?= ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="p-2"><?= (new DateTime($row['created_at']))->format('Y-m-d') ?></td>
                                <td class="p-2">
                                    <button
                                        class="edit-btn bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 transition text-sm"
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-username="<?= htmlspecialchars($row['username']) ?>"
                                        data-role="<?= htmlspecialchars($row['role']) ?>"
                                        data-status="<?= htmlspecialchars($row['status']) ?>"
                                        data-officer-id="<?= $row['officer_id'] ?? '' ?>"
                                        data-officer-position="<?= htmlspecialchars($row['officer_position'] ?? '') ?>"
                                        data-term-start="<?= $row['term_start'] ?? '' ?>"
                                        data-term-end="<?= $row['term_end'] ?? '' ?>"
                                        data-officer-status="<?= htmlspecialchars($row['officer_status'] ?? '') ?>"
                                        data-resident-id="<?= $row['resident_id'] ?? '' ?>"
                                        data-resident-name="<?= htmlspecialchars(trim($row['resident_full_name'] ?? '')) ?>">
                                        ✏️ Edit
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-500">Error loading accounts. Please try again later.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end mt-6 space-x-2">
                <button id="archiveCurrentTermBtn" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-full text-sm font-semibold shadow-md transition">
                    📦 Archive Current Term
                </button>
                <button id="termHistoryBtn" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-full text-sm font-semibold shadow-md transition">
                    📋 Term History
                </button>
            </div>
        </main>
    </div>

    <!-- Edit Account Dialog -->
    <div id="editAccountDialog" title="Edit Account" class="hidden">
        <form id="editAccountForm" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="edit_account">
            <input type="hidden" name="id" id="editAccountId">
            <input type="hidden" name="officer_id" id="editOfficerId">
            <input type="hidden" name="resident_id" id="editResidentId">

            <div>
                <label class="block text-gray-700 font-medium">Full Name</label>
                <input type="text" name="fullname" id="editFullname" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Username</label>
                <input type="text" name="username" id="editUsername" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Role</label>
                <select name="role" id="editRole" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
                    <option value="captain">Captain</option>
                    <option value="kagawad">Kagawad</option>
                    <option value="secretary">Secretary</option>
                    <option value="hcnurse">hcnurse</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Status</label>
                <select name="status" id="editStatus" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Password (leave blank to keep current)</label>
                <input type="password" name="password" id="editPassword"
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
            </div>

            <hr class="my-4">

            <div>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="editIsOfficer" class="rounded">
                    <input type="hidden" name="is_officer" id="editIsOfficerHidden" value="0">
                    <span class="text-gray-700 font-medium">This user is an Officer</span>
                </label>
            </div>

            <!-- Officer Fields -->
            <div id="editOfficerFields" class="hidden space-y-3">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resident (Optional)</label>
                    <input type="text" id="editResidentSearch" 
                        placeholder="Search by name or address..."
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    <div id="editResidentSearchResults" 
                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    <div id="editSelectedResident" class="mt-2 hidden">
                        <div class="flex items-center justify-between bg-theme-secondary border border-theme-secondary rounded px-3 py-2">
                            <span class="text-sm text-gray-700">
                                <span class="font-medium" id="editSelectedResidentName"></span>
                            </span>
                            <button type="button" onclick="clearEditResidentSelection()" class="text-red-600 hover:text-red-800 text-sm">
                                ✕ Clear
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Leave blank if officer is not a registered resident</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                    <input type="text" name="officer_position" id="editOfficerPosition"
                        placeholder="e.g., Barangay Captain, Barangay Secretary, etc."
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Term Start *</label>
                        <input type="date" name="term_start" id="editTermStart"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Term End *</label>
                        <input type="date" name="term_end" id="editTermEnd"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Officer Status</label>
                    <select name="officer_status" id="editOfficerStatus"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </form>
    </div>


    <!-- add account thru modal -->
    <div id="addAccountModal" title="Add New Account / Officer" class="hidden">
        <form method="POST" class="space-y-3" id="addAccountForm">
            <input type="hidden" name="action" value="add_account">
            <input type="hidden" name="resident_id" id="addResidentId" value="">
            <?php if (isset($error)): ?>
                <p class='text-red-600 font-medium'><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <div>
                <label class="block text-gray-700 font-medium">Full Name</label>
                <input type="text" name="fullname" id="addFullname" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Username</label>
                <input type="text" name="username" id="addUsername" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Role</label>
                <select name="role" id="addRole" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
                    <option value="captain">Captain</option>
                    <option value="kagawad">Kagawad</option>
                    <option value="secretary">Secretary</option>
                    <option value="hcnurse">hcnurse</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Status</label>
                <select name="status" id="addStatus" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Password</label>
                <input type="password" name="password" id="addPassword" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-theme-primary focus:outline-none">
            </div>

            <hr class="my-4">

            <div>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="addIsOfficer" class="rounded">
                    <input type="hidden" name="is_officer" id="addIsOfficerHidden" value="0">
                    <span class="text-gray-700 font-medium">This user is an Officer</span>
                </label>
            </div>

            <!-- Officer Fields -->
            <div id="addOfficerFields" class="hidden space-y-3">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resident (Optional)</label>
                    <input type="text" id="addResidentSearch" 
                        placeholder="Search by name or address..."
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    <div id="addResidentSearchResults" 
                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    <div id="addSelectedResident" class="mt-2 hidden">
                        <div class="flex items-center justify-between bg-theme-secondary border border-theme-secondary rounded px-3 py-2">
                            <span class="text-sm text-gray-700">
                                <span class="font-medium" id="addSelectedResidentName"></span>
                            </span>
                            <button type="button" onclick="clearAddResidentSelection()" class="text-red-600 hover:text-red-800 text-sm">
                                ✕ Clear
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Leave blank if officer is not a registered resident</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                    <input type="text" name="officer_position" id="addOfficerPosition"
                        placeholder="e.g., Barangay Captain, Barangay Secretary, etc."
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Term Start *</label>
                        <input type="date" name="term_start" id="addTermStart"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Term End *</label>
                        <input type="date" name="term_end" id="addTermEnd"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Officer Status</label>
                    <select name="officer_status" id="addOfficerStatus"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Archive Current Term Dialog -->
    <div id="archiveCurrentTermDialog" title="Archive Current Term" class="hidden">
        <div class="p-4">
            <p class="mb-4 text-gray-700">
                This will archive all accounts/officers except the latest new account with role "secretary". 
                Are you sure you want to proceed?
            </p>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-yellow-800">
                    <strong>⚠️ Warning:</strong> This action cannot be undone. All archived records will be moved to the archive.
                </p>
            </div>
        </div>
    </div>

    <!-- Term History Dialog -->
    <div id="termHistoryDialog" title="Term History" class="hidden">
        <!-- Search -->
        <div class="p-4 border-b">
            <div class="relative">
                <input
                    type="text"
                    id="termHistorySearchInput"
                    placeholder="Search by officer name or position..."
                    class="w-full border rounded-md px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-1" />
                <span class="absolute right-3 top-2.5 text-gray-400">🔍</span>
            </div>
        </div>

        <!-- Table -->
        <div class="p-4 overflow-auto max-h-[400px]">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-2 w-[15%]">Officer</th>
                        <th class="p-2 w-[15%]">Position</th>
                        <th class="p-2 w-[12%]">Action</th>
                        <th class="p-2 w-[20%]">Term Period</th>
                        <th class="p-2 w-[12%]">Status</th>
                        <th class="p-2 w-[15%]">Changed By</th>
                        <th class="p-2 w-[11%]">Date/Time</th>
                    </tr>
                </thead>
                <tbody id="termHistoryTableBody" class="divide-y">
                    <!-- Dynamic content will be loaded here -->
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 text-xs text-gray-500 border-t">
            <span id="termHistoryFooter">Loading...</span>
        </div>
    </div>

    <script src="js/index.js"></script>
    <script>
        // Show success/error messages using showMessage function (PHP-specific)
        <?php if (isset($success) && $success != ""): ?>
            showMessage('Success', <?php echo json_encode($success); ?>);
        <?php endif; ?>
        
        <?php if (isset($error) && $error != ""): ?>
            showMessage('Error', <?php echo json_encode($error); ?>, true);
        <?php endif; ?>
    </script>
</body>

</html>
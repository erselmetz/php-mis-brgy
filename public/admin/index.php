<?php
include_once __DIR__ . '/../../includes/app.php';

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

<body style="display: none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include '../layout/sidebar.php'; ?>
        <main class="p-20 w-screen">
            <h2 class="text-2xl font-semibold mb-4">Staff & Officers Management</h2>

            <!-- show success message -->
            <?php if (isset($success) && $success != "") echo DialogMessage($success) ?>

            <!-- show error message -->
            <?php if (isset($error) && $error != "") echo DialogMessage($error) ?>

            <!-- ✅ Add Button -->
            <div class="p-6">
                <button id="openModalBtn"
                    class="bg-blue-700 hover:bg-blue-800 text-white font-semibold px-4 py-2 rounded shadow">
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
                            <th class="p-2 text-left">Position</th>
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
                                    <?php if ($row['officer_id']): ?>
                                        <span class="text-blue-600 font-medium"><?= htmlspecialchars($row['officer_position']); ?></span>
                                        <?php if ($row['resident_full_name']): ?>
                                            <br><span class="text-xs text-gray-500">(<?= htmlspecialchars(trim($row['resident_full_name'])); ?>)</span>
                                        <?php endif; ?>
                                    <?php elseif ($row['position']): ?>
                                        <span class="text-gray-700"><?= htmlspecialchars($row['position']); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic">—</span>
                                    <?php endif; ?>
                                </td>
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
                                        data-position="<?= htmlspecialchars($row['position'] ?? '') ?>"
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
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Username</label>
                <input type="text" name="username" id="editUsername" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Role</label>
                <select name="role" id="editRole" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="staff">Staff</option>
                    <option value="tanod">Tanod</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Status</label>
                <select name="status" id="editStatus" required
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Password (leave blank to keep current)</label>
                <input type="password" name="password" id="editPassword"
                    class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="editResidentSearchResults" 
                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    <div id="editSelectedResident" class="mt-2 hidden">
                        <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded px-3 py-2">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Term Start *</label>
                        <input type="date" name="term_start" id="editTermStart"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Term End *</label>
                        <input type="date" name="term_end" id="editTermEnd"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Officer Status</label>
                    <select name="officer_status" id="editOfficerStatus"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Non-Officer Position Field -->
            <div id="editPositionField" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Position (if not an officer)</label>
                <input type="text" name="position" id="editPosition"
                    placeholder="e.g., Clerk, Secretary, etc."
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </form>
    </div>


    <!-- add account thru modal -->
    <div id="addAccountModal" title="Add New Account / Officer" class="hidden">
        <form method="POST" class="space-y-4" id="addAccountForm">
            <input type="hidden" name="action" value="add_account">
            <input type="hidden" name="resident_id" id="addResidentId" value="">
            <?php if (isset($error)): ?>
                <p class='text-red-600 font-medium'><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="fullname" placeholder="Full Name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" placeholder="Username" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" placeholder="Password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="staff">Staff</option>
                    <option value="tanod">Tanod</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <hr class="my-4">

            <div>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="addIsOfficer" class="rounded">
                    <input type="hidden" name="is_officer" id="addIsOfficerHidden" value="0">
                    <span class="text-sm font-medium text-gray-700">This user is an Officer</span>
                </label>
            </div>

            <!-- Officer Fields -->
            <div id="addOfficerFields" class="hidden space-y-3">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resident (Optional)</label>
                    <input type="text" id="addResidentSearch" 
                        placeholder="Search by name or address..."
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="addResidentSearchResults" 
                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    <div id="addSelectedResident" class="mt-2 hidden">
                        <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded px-3 py-2">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Term Start *</label>
                        <input type="date" name="term_start" id="addTermStart"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Term End *</label>
                        <input type="date" name="term_end" id="addTermEnd"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Officer Status</label>
                    <select name="officer_status" id="addOfficerStatus"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Non-Officer Position Field -->
            <div id="addPositionField" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Position (if not an officer)</label>
                <input type="text" name="position" id="addPosition"
                    placeholder="e.g., Clerk, Secretary, etc."
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white py-2 rounded font-semibold">
                    Add Account
                </button>
            </div>
        </form>
    </div>
    <script>
        $(function() {
            $('body').show();
            $('#accountsTable').DataTable();

            // Toggle officer fields for add form
            $("#addIsOfficer").on("change", function() {
                if ($(this).is(":checked")) {
                    $("#addOfficerFields").show();
                    $("#addPositionField").hide();
                    $("#addIsOfficerHidden").val("1");
                    $("#addOfficerPosition").prop("required", true);
                    $("#addTermStart").prop("required", true);
                    $("#addTermEnd").prop("required", true);
                } else {
                    $("#addOfficerFields").hide();
                    $("#addPositionField").show();
                    $("#addIsOfficerHidden").val("0");
                    $("#addOfficerPosition").prop("required", false);
                    $("#addTermStart").prop("required", false);
                    $("#addTermEnd").prop("required", false);
                }
            });

            // Toggle officer fields for edit form
            $("#editIsOfficer").on("change", function() {
                if ($(this).is(":checked")) {
                    $("#editOfficerFields").show();
                    $("#editPositionField").hide();
                    $("#editIsOfficerHidden").val("1");
                    $("#editOfficerPosition").prop("required", true);
                    $("#editTermStart").prop("required", true);
                    $("#editTermEnd").prop("required", true);
                } else {
                    $("#editOfficerFields").hide();
                    $("#editPositionField").show();
                    $("#editIsOfficerHidden").val("0");
                    $("#editOfficerPosition").prop("required", false);
                    $("#editTermStart").prop("required", false);
                    $("#editTermEnd").prop("required", false);
                }
            });

            // Resident search for add form
            $("#addResidentSearch").on("input", function() {
                const query = $(this).val().trim();
                if (query.length < 2) {
                    $("#addResidentSearchResults").hide();
                    return;
                }

                $.ajax({
                    url: "/certificate/search_residents.php",
                    method: "GET",
                    data: { q: query },
                    success: function(res) {
                        let data = [];
                        try {
                            data = JSON.parse(res);
                        } catch (e) {
                            console.error("Error parsing response:", e);
                            data = [];
                        }

                        if (data.length === 0) {
                            $("#addResidentSearchResults").html('<div class="p-3 text-sm text-gray-600">No results found</div>').show();
                            return;
                        }

                        const html = data.map(r => {
                            const fullName = `${r.first_name} ${r.middle_name || ''} ${r.last_name}`.trim();
                            return `
                                <div class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" data-id="${r.id}" data-name="${fullName}">
                                    <div class="font-medium text-gray-800">${fullName}</div>
                                    <div class="text-sm text-gray-600">${r.address || 'No address'}</div>
                                </div>
                            `;
                        }).join('');
                        $("#addResidentSearchResults").html(html).show();
                    },
                    error: function(xhr, status, error) {
                        console.error("Search error:", error);
                        $("#addResidentSearchResults").html('<div class="p-3 text-sm text-red-600">Error searching residents</div>').show();
                    }
                });
            });

            // Resident search for edit form
            $("#editResidentSearch").on("input", function() {
                const query = $(this).val().trim();
                if (query.length < 2) {
                    $("#editResidentSearchResults").hide();
                    return;
                }

                $.ajax({
                    url: "/certificate/search_residents.php",
                    method: "GET",
                    data: { q: query },
                    success: function(res) {
                        let data = [];
                        try {
                            data = JSON.parse(res);
                        } catch (e) {
                            console.error("Error parsing response:", e);
                            data = [];
                        }

                        if (data.length === 0) {
                            $("#editResidentSearchResults").html('<div class="p-3 text-sm text-gray-600">No results found</div>').show();
                            return;
                        }

                        const html = data.map(r => {
                            const fullName = `${r.first_name} ${r.middle_name || ''} ${r.last_name}`.trim();
                            return `
                                <div class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" data-id="${r.id}" data-name="${fullName}">
                                    <div class="font-medium text-gray-800">${fullName}</div>
                                    <div class="text-sm text-gray-600">${r.address || 'No address'}</div>
                                </div>
                            `;
                        }).join('');
                        $("#editResidentSearchResults").html(html).show();
                    },
                    error: function(xhr, status, error) {
                        console.error("Search error:", error);
                        $("#editResidentSearchResults").html('<div class="p-3 text-sm text-red-600">Error searching residents</div>').show();
                    }
                });
            });

            // Select resident from search results (add)
            $(document).on("click", "#addResidentSearchResults div[data-id]", function() {
                const id = $(this).data("id");
                const name = $(this).data("name");
                $("#addResidentId").val(id);
                $("#addResidentSearch").val(name);
                $("#addSelectedResidentName").text(name);
                $("#addSelectedResident").show();
                $("#addResidentSearchResults").hide();
            });

            // Select resident from search results (edit)
            $(document).on("click", "#editResidentSearchResults div[data-id]", function() {
                const id = $(this).data("id");
                const name = $(this).data("name");
                $("#editResidentId").val(id);
                $("#editResidentSearch").val(name);
                $("#editSelectedResidentName").text(name);
                $("#editSelectedResident").show();
                $("#editResidentSearchResults").hide();
            });

            // Hide dropdown on outside click
            $(document).click(function(e) {
                if (!$(e.target).closest("#addResidentSearch, #addResidentSearchResults, #addSelectedResident").length) {
                    $("#addResidentSearchResults").hide();
                }
                if (!$(e.target).closest("#editResidentSearch, #editResidentSearchResults, #editSelectedResident").length) {
                    $("#editResidentSearchResults").hide();
                }
            });

            function clearAddResidentSelection() {
                $("#addResidentId").val('');
                $("#addResidentSearch").val('');
                $("#addSelectedResident").hide();
            }

            function clearEditResidentSelection() {
                $("#editResidentId").val('');
                $("#editResidentSearch").val('');
                $("#editSelectedResident").hide();
            }

            // Initialize modal (hidden by default) - Modernized
            $("#addAccountModal").dialog({
                autoOpen: false,
                modal: true,
                width: 600,
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
                    // Reset form
                    $("#addAccountForm")[0].reset();
                    $("#addIsOfficer").prop("checked", false);
                    $("#addOfficerFields").hide();
                    $("#addPositionField").hide();
                    clearAddResidentSelection();
                }
            });

            // Open modal when button clicked
            $("#openModalBtn").on("click", function() {
                $("#addAccountModal").dialog("open");
            });

            $('.edit-btn').on('click', function() {
                // Get data from button
                const id = $(this).data('id');
                const name = $(this).data('name');
                const username = $(this).data('username');
                const role = $(this).data('role');
                const status = $(this).data('status');
                const position = $(this).data('position') || '';
                const officerId = $(this).data('officer-id') || '';
                const officerPosition = $(this).data('officer-position') || '';
                const termStart = $(this).data('term-start') || '';
                const termEnd = $(this).data('term-end') || '';
                const officerStatus = $(this).data('officer-status') || 'Active';
                const residentId = $(this).data('resident-id') || '';
                const residentName = $(this).data('resident-name') || '';

                // Fill form fields
                $('#editAccountId').val(id);
                $('#editFullname').val(name);
                $('#editUsername').val(username);
                $('#editRole').val(role);
                $('#editStatus').val(status);
                $('#editPassword').val('');
                $('#editPosition').val(position);
                $('#editOfficerId').val(officerId);
                $('#editResidentId').val(residentId);

                // Check if user is an officer
                if (officerId) {
                    $("#editIsOfficer").prop("checked", true);
                    $("#editOfficerFields").show();
                    $("#editPositionField").hide();
                    $("#editOfficerPosition").val(officerPosition);
                    $("#editTermStart").val(termStart);
                    $("#editTermEnd").val(termEnd);
                    $("#editOfficerStatus").val(officerStatus);
                    if (residentId && residentName) {
                        $("#editResidentSearch").val(residentName);
                        $("#editSelectedResidentName").text(residentName);
                        $("#editSelectedResident").show();
                    } else {
                        clearEditResidentSelection();
                    }
                } else {
                    $("#editIsOfficer").prop("checked", false);
                    $("#editOfficerFields").hide();
                    $("#editPositionField").show();
                    clearEditResidentSelection();
                }

                // Open dialog - Modernized
                $("#editAccountDialog").dialog({
                    modal: true,
                    width: 600,
                    resizable: true,
                    classes: {
                        'ui-dialog': 'rounded-lg shadow-lg',
                        'ui-dialog-titlebar': 'bg-blue-600 text-white rounded-t-lg',
                        'ui-dialog-title': 'font-semibold',
                        'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                    },
                    buttons: {
                        "Save Changes": function() {
                            $('#editAccountForm').submit(); // submit form via POST
                            $(this).dialog("close");
                        },
                        "Cancel": function() {
                            $(this).dialog("close");
                        }
                    },
                    open: function() {
                        $(".ui-dialog-buttonpane button:contains('Save Changes')")
                            .addClass("bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mr-2");
                        $(".ui-dialog-buttonpane button:contains('Cancel')")
                            .addClass("bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded");
                    }
                });
            });
        });
    </script>
</body>

</html>
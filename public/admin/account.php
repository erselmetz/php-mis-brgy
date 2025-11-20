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
    <?php include '../navbar.php'; ?>
    <div class="d-flex bg-light">
        <?php include '../sidebar.php'; ?>
        <main class="p-4 w-100">
            <h2 class="h3 fw-semibold mb-4">Staff & Officers Management</h2>

            <!-- show success message -->
            <?php if (isset($success) && $success != "") echo DialogMessage($success) ?>

            <!-- show error message -->
            <?php if (isset($error) && $error != "") echo DialogMessage($error) ?>

            <!-- ✅ Add Button -->
            <div class="p-4">
                <button id="openModalBtn"
                    class="btn btn-primary fw-semibold px-4 py-2 shadow">
                    ➕ Add New Account / Officer
                </button>
            </div>
            <!-- Accounts Table -->
            <div class="bg-white rounded-3 shadow-sm border overflow-hidden p-4">
                <table id="accountsTable" class="display w-100 small border rounded-3">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="p-2 text-start">Name</th>
                            <th class="p-2 text-start">Username</th>
                            <th class="p-2 text-start">Role</th>
                            <th class="p-2 text-start">Position</th>
                            <th class="p-2 text-start">Status</th>
                            <th class="p-2 text-start">Created</th>
                            <th class="p-2 text-start">Actions</th>
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
                                        <span class="text-primary fw-medium"><?= htmlspecialchars($row['officer_position']); ?></span>
                                        <?php if ($row['resident_full_name']): ?>
                                            <br><span class="small text-muted">(<?= htmlspecialchars(trim($row['resident_full_name'])); ?>)</span>
                                        <?php endif; ?>
                                    <?php elseif ($row['position']): ?>
                                        <span class="text-dark"><?= htmlspecialchars($row['position']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-2">
                                    <?php
                                    $statusColor = $row['status'] === 'active' 
                                        ? 'bg-success bg-opacity-10 text-success' 
                                        : 'bg-secondary bg-opacity-10 text-secondary';
                                    ?>
                                    <span class="badge <?= $statusColor ?>">
                                        <?= ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="p-2"><?= (new DateTime($row['created_at']))->format('Y-m-d') ?></td>
                                <td class="p-2">
                                    <button
                                        class="edit-btn btn btn-warning btn-sm"
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
                                <td colspan="7" class="p-4 text-center text-muted">Error loading accounts. Please try again later.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Edit Account Dialog -->
    <div id="editAccountDialog" title="Edit Account">
        <form id="editAccountForm" method="POST">
            <input type="hidden" name="action" value="edit_account">
            <input type="hidden" name="id" id="editAccountId">
            <input type="hidden" name="officer_id" id="editOfficerId">
            <input type="hidden" name="resident_id" id="editResidentId">

            <div class="mb-3">
                <label class="form-label fw-medium">Full Name</label>
                <input type="text" name="fullname" id="editFullname" required class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Username</label>
                <input type="text" name="username" id="editUsername" required class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Role</label>
                <select name="role" id="editRole" required class="form-select">
                    <option value="staff">Staff</option>
                    <option value="tanod">Tanod</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Status</label>
                <select name="status" id="editStatus" required class="form-select">
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Password (leave blank to keep current)</label>
                <input type="password" name="password" id="editPassword" class="form-control">
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" id="editIsOfficer" class="form-check-input">
                    <input type="hidden" name="is_officer" id="editIsOfficerHidden" value="0">
                    <label class="form-check-label fw-medium">This user is an Officer</label>
                </div>
            </div>

            <!-- Officer Fields -->
            <div id="editOfficerFields" class="d-none">
                <div class="position-relative mb-3">
                    <label class="form-label small fw-medium">Resident (Optional)</label>
                    <input type="text" id="editResidentSearch" 
                        placeholder="Search by name or address..." class="form-control">
                    <div id="editResidentSearchResults" 
                        class="position-absolute mt-1 w-100 bg-white border rounded-3 shadow-lg d-none overflow-auto" style="z-index: 1050; max-height: 15rem;"></div>
                    <div id="editSelectedResident" class="mt-2 d-none">
                        <div class="d-flex align-items-center justify-content-between bg-primary bg-opacity-10 border border-primary rounded px-3 py-2">
                            <span class="small">
                                <span class="fw-medium" id="editSelectedResidentName"></span>
                            </span>
                            <button type="button" onclick="clearEditResidentSelection()" class="btn btn-sm btn-link text-danger p-0">
                                ✕ Clear
                            </button>
                        </div>
                    </div>
                    <p class="form-text">Leave blank if officer is not a registered resident</p>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Position *</label>
                    <input type="text" name="officer_position" id="editOfficerPosition"
                        placeholder="e.g., Barangay Captain, Barangay Secretary, etc." class="form-control">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium">Term Start *</label>
                        <input type="date" name="term_start" id="editTermStart" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Term End *</label>
                        <input type="date" name="term_end" id="editTermEnd" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Officer Status</label>
                    <select name="officer_status" id="editOfficerStatus" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Non-Officer Position Field -->
            <div id="editPositionField" class="d-none mb-3">
                <label class="form-label small fw-medium">Position (if not an officer)</label>
                <input type="text" name="position" id="editPosition"
                    placeholder="e.g., Clerk, Secretary, etc." class="form-control">
            </div>
        </form>
    </div>


    <!-- add account thru modal -->
    <div id="addAccountModal" title="Add New Account / Officer">
        <form method="POST" id="addAccountForm" style="max-height: 70vh; overflow-y: auto;">
            <input type="hidden" name="action" value="add_account">
            <input type="hidden" name="resident_id" id="addResidentId" value="">
            <?php if (isset($error)) echo "<p class='text-danger fw-medium'>$error</p>"; ?>

            <div class="mb-3">
                <label class="form-label small fw-medium">Full Name</label>
                <input type="text" name="fullname" placeholder="Full Name" required class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-medium">
                    Username <?= helpTooltip(HelpMessages::USERNAME) ?>
                </label>
                <input type="text" name="username" placeholder="Username" required class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-medium">Password</label>
                <input type="password" name="password" placeholder="Password" required class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-medium">
                    Role <?= helpTooltip("Admin: Full access. Staff: Resident & certificate management. Tanod: Blotter only.") ?>
                </label>
                <select name="role" required class="form-select">
                    <option value="staff">Staff</option>
                    <option value="tanod">Tanod</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" id="addIsOfficer" class="form-check-input">
                    <input type="hidden" name="is_officer" id="addIsOfficerHidden" value="0">
                    <label class="form-check-label small fw-medium">This user is an Officer</label>
                </div>
            </div>

            <!-- Officer Fields -->
            <div id="addOfficerFields" class="d-none">
                <div class="position-relative mb-3">
                    <label class="form-label small fw-medium">Resident (Optional)</label>
                    <input type="text" id="addResidentSearch" 
                        placeholder="Search by name or address..." class="form-control">
                    <div id="addResidentSearchResults" 
                        class="position-absolute mt-1 w-100 bg-white border rounded-3 shadow-lg d-none overflow-auto" style="z-index: 1050; max-height: 15rem;"></div>
                    <div id="addSelectedResident" class="mt-2 d-none">
                        <div class="d-flex align-items-center justify-content-between bg-primary bg-opacity-10 border border-primary rounded px-3 py-2">
                            <span class="small">
                                <span class="fw-medium" id="addSelectedResidentName"></span>
                            </span>
                            <button type="button" onclick="clearAddResidentSelection()" class="btn btn-sm btn-link text-danger p-0">
                                ✕ Clear
                            </button>
                        </div>
                    </div>
                    <p class="form-text">Leave blank if officer is not a registered resident</p>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">
                        Position * <?= helpTooltip(HelpMessages::OFFICER_POSITION) ?>
                    </label>
                    <input type="text" name="officer_position" id="addOfficerPosition"
                        placeholder="e.g., Barangay Captain, Barangay Secretary, etc." class="form-control">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium">Term Start *</label>
                        <input type="date" name="term_start" id="addTermStart" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Term End *</label>
                        <input type="date" name="term_end" id="addTermEnd" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-medium">Officer Status</label>
                    <select name="officer_status" id="addOfficerStatus" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Non-Officer Position Field -->
            <div id="addPositionField" class="d-none mb-3">
                <label class="form-label small fw-medium">Position (if not an officer)</label>
                <input type="text" name="position" id="addPosition"
                    placeholder="e.g., Clerk, Secretary, etc." class="form-control">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-100 btn btn-primary py-2 fw-semibold">
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
                    $("#addOfficerFields").removeClass('d-none');
                    $("#addPositionField").addClass('d-none');
                    $("#addIsOfficerHidden").val("1");
                    $("#addOfficerPosition").prop("required", true);
                    $("#addTermStart").prop("required", true);
                    $("#addTermEnd").prop("required", true);
                } else {
                    $("#addOfficerFields").addClass('d-none');
                    $("#addPositionField").removeClass('d-none');
                    $("#addIsOfficerHidden").val("0");
                    $("#addOfficerPosition").prop("required", false);
                    $("#addTermStart").prop("required", false);
                    $("#addTermEnd").prop("required", false);
                }
            });

            // Toggle officer fields for edit form
            $("#editIsOfficer").on("change", function() {
                if ($(this).is(":checked")) {
                    $("#editOfficerFields").removeClass('d-none');
                    $("#editPositionField").addClass('d-none');
                    $("#editIsOfficerHidden").val("1");
                    $("#editOfficerPosition").prop("required", true);
                    $("#editTermStart").prop("required", true);
                    $("#editTermEnd").prop("required", true);
                } else {
                    $("#editOfficerFields").addClass('d-none');
                    $("#editPositionField").removeClass('d-none');
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
                    url: "/certificate/search_residents",
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
                            $("#addResidentSearchResults").html('<div class="p-3 small text-muted">No results found</div>').removeClass('d-none');
                            return;
                        }

                        const html = data.map(r => {
                            const fullName = `${r.first_name} ${r.middle_name || ''} ${r.last_name}`.trim();
                            return `
                                <div class="px-4 py-2 search-result-item border-bottom" data-id="${r.id}" data-name="${fullName}" style="cursor: pointer;">
                                    <div class="fw-medium text-dark">${fullName}</div>
                                    <div class="small text-muted">${r.address || 'No address'}</div>
                                </div>
                            `;
                        }).join('');
                        $("#addResidentSearchResults").html(html).removeClass('d-none');
                    },
                    error: function(xhr, status, error) {
                        console.error("Search error:", error);
                        $("#addResidentSearchResults").html('<div class="p-3 small text-danger">Error searching residents</div>').removeClass('d-none');
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
                    url: "/certificate/search_residents",
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
                            $("#editResidentSearchResults").html('<div class="p-3 small text-muted">No results found</div>').removeClass('d-none');
                            return;
                        }

                        const html = data.map(r => {
                            const fullName = `${r.first_name} ${r.middle_name || ''} ${r.last_name}`.trim();
                            return `
                                <div class="px-4 py-2 search-result-item border-bottom" data-id="${r.id}" data-name="${fullName}" style="cursor: pointer;">
                                    <div class="fw-medium text-dark">${fullName}</div>
                                    <div class="small text-muted">${r.address || 'No address'}</div>
                                </div>
                            `;
                        }).join('');
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
                $("#addSelectedResident").removeClass('d-none');
                $("#addResidentSearchResults").addClass('d-none');
            });

            // Select resident from search results (edit)
            $(document).on("click", "#editResidentSearchResults div[data-id]", function() {
                const id = $(this).data("id");
                const name = $(this).data("name");
                $("#editResidentId").val(id);
                $("#editResidentSearch").val(name);
                $("#editSelectedResidentName").text(name);
                $("#editSelectedResident").removeClass('d-none');
                $("#editResidentSearchResults").addClass('d-none');
            });

            // Hide dropdown on outside click
            $(document).click(function(e) {
                if (!$(e.target).closest("#addResidentSearch, #addResidentSearchResults, #addSelectedResident").length) {
                    $("#addResidentSearchResults").addClass('d-none');
                }
                if (!$(e.target).closest("#editResidentSearch, #editResidentSearchResults, #editSelectedResident").length) {
                    $("#editResidentSearchResults").addClass('d-none');
                }
            });

            // Make functions global so they can be called from onclick
            window.clearAddResidentSelection = function() {
                $("#addResidentId").val('');
                $("#addResidentSearch").val('');
                $("#addSelectedResident").addClass('d-none');
            }

            window.clearEditResidentSelection = function() {
                $("#editResidentId").val('');
                $("#editResidentSearch").val('');
                $("#editSelectedResident").addClass('d-none');
            }

            // Initialize modal (hidden by default)
            $("#addAccountModal").dialog({
                autoOpen: false,
                modal: true,
                width: 600,
                height: 650,
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
                    // Reset form
                    $("#addAccountForm")[0].reset();
                    $("#addIsOfficer").prop("checked", false);
                    $("#addOfficerFields").addClass('d-none');
                    $("#addPositionField").addClass('d-none');
                    $("#addIsOfficerHidden").val("0");
                    clearAddResidentSelection();
                },
                close: function() {
                    // Clean up on close to prevent duplication
                    $("#addAccountForm")[0].reset();
                    $("#addOfficerFields").addClass('d-none');
                    $("#addPositionField").addClass('d-none');
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
                    $("#editIsOfficerHidden").val("1");
                    $("#editOfficerFields").removeClass('d-none');
                    $("#editPositionField").addClass('d-none');
                    $("#editOfficerPosition").val(officerPosition);
                    $("#editTermStart").val(termStart);
                    $("#editTermEnd").val(termEnd);
                    $("#editOfficerStatus").val(officerStatus);
                    if (residentId && residentName) {
                        $("#editResidentSearch").val(residentName);
                        $("#editSelectedResidentName").text(residentName);
                        $("#editSelectedResident").removeClass('d-none');
                    } else {
                        clearEditResidentSelection();
                    }
                } else {
                    $("#editIsOfficer").prop("checked", false);
                    $("#editIsOfficerHidden").val("0");
                    $("#editOfficerFields").addClass('d-none');
                    $("#editPositionField").removeClass('d-none');
                    clearEditResidentSelection();
                }

                // Open dialog
                $("#editAccountDialog").dialog({
                    modal: true,
                    width: 600,
                    height: 650,
                    resizable: true,
                    classes: {
                        'ui-dialog': 'rounded shadow-lg',
                        'ui-dialog-titlebar': 'dialog-titlebar-primary rounded-top',
                        'ui-dialog-title': 'fw-semibold',
                        'ui-dialog-buttonpane': 'dialog-buttonpane-light rounded-bottom'
                    },
                    buttons: {
                        "Save Changes": function() {
                            $('#editAccountForm').submit();
                            $(this).dialog("close");
                        },
                        "Cancel": function() {
                            $(this).dialog("close");
                        }
                    },
                    open: function() {
                        $(".ui-dialog-buttonpane button:contains('Save Changes')")
                            .addClass("btn btn-primary me-2");
                        $(".ui-dialog-buttonpane button:contains('Cancel')")
                            .addClass("btn btn-secondary");
                    },
                    close: function() {
                        // Clean up on close to prevent duplication
                        $("#editAccountForm")[0].reset();
                        $("#editOfficerFields").addClass('d-none');
                        $("#editPositionField").addClass('d-none');
                        clearEditResidentSelection();
                    }
                });
            });
        });
    </script>
</body>

</html>
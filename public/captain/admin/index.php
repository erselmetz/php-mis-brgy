<?php
require_once __DIR__ . '/../../../includes/app.php';

requireCaptain();

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

<body class="overflow-hidden bg-gray-100 h-screen" style="display: none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include '../layout/sidebar.php'; ?>
        <main class="overflow-y-auto h-screen pb-24 p-6 flex-1">
            <h2 class="text-2xl font-semibold mb-4">Staff & Officers Management</h2>

            <!-- show success message -->
            <?php if (isset($success) && $success != "") echo DialogMessage($success) ?>

            <!-- show error message -->
            <?php if (isset($error) && $error != "") echo DialogMessage($error) ?>

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
                    url: "../certificate/search_residents.php",
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
                                <div class="px-4 py-2 hover-theme-light cursor-pointer border-b border-gray-100 last:border-b-0" data-id="${r.id}" data-name="${fullName}">
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
                    url: "../certificate/search_residents.php",
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
                                <div class="px-4 py-2 hover-theme-light cursor-pointer border-b border-gray-100 last:border-b-0" data-id="${r.id}" data-name="${fullName}">
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
                    $('.ui-dialog-buttonpane button').addClass('bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded');
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
        });
    </script>
</body>

</html>
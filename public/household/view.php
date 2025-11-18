<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access

$household_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($household_id <= 0) {
    header("Location: /household/households");
    exit;
}

// Fetch household details
$stmt = $conn->prepare("SELECT * FROM households WHERE id = ?");
$stmt->bind_param('i', $household_id);
$stmt->execute();
$household = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$household) {
    header("Location: /household/households");
    exit;
}

// Fetch household members
$stmt = $conn->prepare("
    SELECT * FROM residents 
    WHERE household_id = ? 
    ORDER BY last_name, first_name
");
$stmt->bind_param('i', $household_id);
$stmt->execute();
$members = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View - Household - MIS Barangay</title>
    <?php loadAllAssets(); ?>

    <style>
        .chip {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            background: rgba(0, 0, 0, 0.05);
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include_once '../navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include_once '../sidebar.php'; ?>

        <main class="p-6 w-screen">
            <h1 class="text-2xl font-semibold mb-6">View Household</h1>

            <!-- ✅ Start of Household Information Section -->
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Form Section -->
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h2 class="text-lg font-medium mb-4">Edit Household</h2>
                    <form id="householdForm" autocomplete="off">
                        <div class="grid grid-cols-1 sm:grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Household Number</label>
                                <input name="household_no" id="household_no" type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Head of Household Name</label>
                                <input name="head_name" id="head_name" type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea name="address" id="address" rows="3"
                                    class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" required></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Total Members</label>
                                <input name="total_members" id="total_members" type="number" readonly
                                    class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm bg-gray-100" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <button id="saveBtn" type="button"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow-sm">Save</button>
                            <a href="/household/households" class="px-4 py-2 border rounded-lg">Back to List</a>
                        </div>
                    </form>
                </section>

                <!-- Preview Section -->
                <aside class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex items-start justify-between">
                        <h2 class="text-lg font-medium">Household Details</h2>
                    </div>

                    <div id="previewCard" class="mt-4 border rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 id="previewHouseholdNo" class="text-xl font-semibold text-gray-800">—</h3>
                                <div id="previewHead" class="text-sm text-gray-600 mt-1">—</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Members</div>
                                <div id="previewMembers" class="font-medium">—</div>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-2 text-sm text-gray-700">
                            <div><span class="font-medium">Address:</span>
                                <div id="previewAddress" class="mt-1 text-sm text-gray-600">—</div>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-4">
                        <button id="refreshBtn" class="px-3 py-1 border rounded-lg text-sm">Refresh</button>
                    </div>
                </aside>
            </div>

            <!-- Household Members Section -->
            <div class="max-w-6xl mx-auto mt-6">
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h2 class="text-lg font-medium mb-4">Household Members (<?= $members->num_rows ?>)</h2>
                    <?php if ($members->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-gray-200 rounded-lg">
                            <thead class="bg-gray-50 text-gray-700">
                                <tr>
                                    <th class="p-2 text-left">Name</th>
                                    <th class="p-2 text-left">Gender</th>
                                    <th class="p-2 text-left">Age</th>
                                    <th class="p-2 text-left">Civil Status</th>
                                    <th class="p-2 text-left">Occupation</th>
                                    <th class="p-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($member = $members->fetch_assoc()): ?>
                                <tr>
                                    <td class="p-2">
                                        <a href="/resident/view?id=<?= $member['id']; ?>"
                                            class="text-blue-600 hover:underline">
                                            <?= htmlspecialchars($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name'] . ' ' . $member['suffix']); ?>
                                        </a>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars($member['gender']); ?></td>
                                    <td class="p-2"><?= htmlspecialchars(AutoComputeAge($member['birthdate'])); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($member['civil_status']); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($member['occupation']); ?></td>
                                    <td class="p-2">
                                        <a href="/resident/view?id=<?= $member['id']; ?>"
                                            class="text-blue-600 hover:underline">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No members assigned to this household yet.</p>
                    <?php endif; ?>
                </section>
            </div>
            <!-- ✅ End of Household Information Section -->
        </main>
    </div>

    <script>
        $(function() {
            function updatePreview() {
                const data = {};
                $('#householdForm').serializeArray().forEach(f => data[f.name] = f.value);

                $('#previewHouseholdNo').text(data.household_no || '—');
                $('#previewHead').text(data.head_name || '—');
                $('#previewMembers').text(data.total_members || '0');
                $('#previewAddress').text(data.address || '—');
            }

            $('#householdForm').on('input change', 'input,textarea,select', updatePreview);
            updatePreview();

            $('#saveBtn').click(() => {
                const payload = {};
                $('#householdForm').serializeArray().forEach(f => payload[f.name] = f.value);
                payload.id = householdId;

                $.ajax({
                    url: '/household/update_household',
                    type: 'POST',
                    data: payload,
                    dataType: 'json',
                    success: function(res) {
                        $('<div>' + res.message + '</div>').dialog({
                            modal: true,
                            title: res.success ? 'Saved' : 'Error',
                            width: 420,
                            buttons: {
                                Ok: function() {
                                    $(this).dialog('close');
                                    if (res.success) {
                                        location.reload();
                                    }
                                }
                            }
                        });
                    },
                    error: function() {
                        $('<div>Failed to connect to server.</div>').dialog({
                            modal: true,
                            title: 'Error',
                            width: 420,
                            buttons: {
                                Ok: function() {
                                    $(this).dialog('close');
                                }
                            }
                        });
                    }
                });
            });

            $('#refreshBtn').click(() => {
                updatePreview();
            });
        });
        // ajax to fetch household data and populate the form
        // --- Load Household Info ---
        const householdId = new URLSearchParams(window.location.search).get('id');
        if (householdId) {
            $.getJSON(`get_household.php?id=${householdId}`, function(res) {
                if (res.error) {
                    alert(res.error);
                    return;
                }
                // Fill all form fields
                for (const key in res) {
                    if ($(`[name=${key}]`).length) {
                        $(`[name=${key}]`).val(res[key]);
                    }
                }
                // Trigger preview update
                $('#householdForm').trigger('change');
            });
        }
    </script>
</body>

</html>


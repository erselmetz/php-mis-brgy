<?php
require_once __DIR__ . '/../../../includes/app.php';
requireAdmin();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_blotter') {
        $complainant_name = trim($_POST['complainant_name'] ?? '');
        $complainant_address = trim($_POST['complainant_address'] ?? '');
        $complainant_contact = trim($_POST['complainant_contact'] ?? '');
        $respondent_name = trim($_POST['respondent_name'] ?? '');
        $respondent_address = trim($_POST['respondent_address'] ?? '');
        $respondent_contact = trim($_POST['respondent_contact'] ?? '');
        $incident_date = $_POST['incident_date'] ?? '';
        $incident_time = $_POST['incident_time'] ?? '';
        $incident_location = trim($_POST['incident_location'] ?? '');
        $incident_description = trim($_POST['incident_description'] ?? '');
        $status = $_POST['status'] ?? 'pending';

        // Validation
        if (empty($complainant_name) || empty($respondent_name) || empty($incident_date) || empty($incident_location) || empty($incident_description)) {
            $error = "Please fill in all required fields.";
        } else {
            // Generate case number
            $year = date('Y');
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blotter WHERE case_number LIKE ?");
            $pattern = "BLT-$year-%";
            $stmt->bind_param("s", $pattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count = $row['count'] + 1;
            $case_number = "BLT-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);

            // Insert blotter record
            $stmt = $conn->prepare("
                INSERT INTO blotter (
                    case_number, complainant_name, complainant_address, complainant_contact,
                    respondent_name, respondent_address, respondent_contact,
                    incident_date, incident_time, incident_location, incident_description,
                    status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $created_by = $_SESSION['user_id'];
            $stmt->bind_param(
                "ssssssssssssi",
                $case_number,
                $complainant_name,
                $complainant_address,
                $complainant_contact,
                $respondent_name,
                $respondent_address,
                $respondent_contact,
                $incident_date,
                $incident_time,
                $incident_location,
                $incident_description,
                $status,
                $created_by
            );

            if ($stmt->execute()) {
                $success = "Blotter case added successfully. Case Number: $case_number";
            } else {
                $error = "Error adding blotter: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all blotter records
$stmt = $conn->prepare("SELECT b.*, u.name as created_by_name FROM blotter b LEFT JOIN users u ON b.created_by = u.id ORDER BY b.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blotter Management - MIS Barangay</title>
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex h-full bg-gray-100">
        <?php include '../layout/sidebar.php'; ?>
        <main class="pb-24 overflow-y-auto flex-1 p-6 w-screen">
            <h2 class="text-2xl font-semibold mb-4">Blotter Management</h2>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Add Button -->
            <div class="mb-4">
                <button id="openBlotterModalBtn" class="bg-theme-primary hover-theme-darker text-white font-semibold px-4 py-2 rounded shadow">
                    ➕ Add New Blotter Case
                </button>
            </div>

            <!-- Blotter Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden p-4">
                <table id="blotterTable" class="display w-full text-sm border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="p-2 text-left">Case Number</th>
                            <th class="p-2 text-left">Complainant</th>
                            <th class="p-2 text-left">Respondent</th>
                            <th class="p-2 text-left">Incident Date</th>
                            <th class="p-2 text-left">Location</th>
                            <th class="p-2 text-left">Status</th>
                            <th class="p-2 text-left">Created By</th>
                            <th class="p-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result !== false): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="p-2">
                                        <a href="view.php?id=<?= $row['id'] ?>" class="text-theme-accent hover:underline font-semibold">
                                            <?= htmlspecialchars($row['case_number']) ?>
                                        </a>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars($row['complainant_name']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['respondent_name']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['incident_date']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['incident_location']) ?></td>
                                    <td class="p-2">
                                        <?php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'under_investigation' => 'bg-theme-primary text-theme-accent',
                                            'resolved' => 'bg-green-100 text-green-800',
                                            'dismissed' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $statusColor = $statusColors[$row['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 py-1 rounded text-xs font-semibold <?= $statusColor ?>">
                                            <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></td>
                                    <td class="p-2">
                                        <a href="view.php?id=<?= $row['id'] ?>" class="text-theme-accent hover:underline">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-500">No blotter cases found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end mt-6 space-x-2">
                <button id="archivedBlotterDialogBtn" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">Archive Case</button>
                <button class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">History</button>
            </div>
        </main>
    </div>

    <!-- Add Blotter Modal -->
    <div id="addBlotterModal" title="Add New Blotter Case" class="hidden">
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_blotter">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Complainant Name *</label>
                    <input type="text" name="complainant_name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Complainant Contact</label>
                    <input type="text" name="complainant_contact"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Complainant Address</label>
                <textarea name="complainant_address" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Respondent Name *</label>
                    <input type="text" name="respondent_name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Respondent Contact</label>
                    <input type="text" name="respondent_contact"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Respondent Address</label>
                <textarea name="respondent_address" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Incident Date *</label>
                    <input type="date" name="incident_date" required
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Incident Time</label>
                    <input type="time" name="incident_time"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Incident Location *</label>
                <input type="text" name="incident_location" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Incident Description *</label>
                <textarea name="incident_description" rows="4" required
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    <option value="pending">Pending</option>
                    <option value="under_investigation">Under Investigation</option>
                    <option value="resolved">Resolved</option>
                    <option value="dismissed">Dismissed</option>
                </select>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-theme-primary hover-theme-darker text-white py-2 rounded font-semibold">
                    Add Blotter Case
                </button>
            </div>
        </form>
    </div>
    <!-- end of add blotter modal-->

    <!-- archived blotter dialog -->
    <div id="archivedBlotterDialog" title="Archived Blotter Cases" class="hidden">

        <!-- Search -->
        <div class="p-4 border-b">
            <div class="relative">
                <input
                    type="text"
                    placeholder="Search archived cases..."
                    class="w-full border rounded-md px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-1" />
                <span class="absolute right-3 top-2.5 text-gray-400">🔍</span>
            </div>
        </div>

        <!-- Table -->
        <div class="p-4 overflow-auto max-h-[360px]">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-2">Entry No.</th>
                        <th class="p-2">Parties / Incident</th>
                        <th class="p-2">Date Archived</th>
                        <th class="p-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="p-2 font-semibold">BL-2022-045</td>
                        <td class="p-2">
                            R. Dalisay vs T. Asiong
                            <div class="text-xs text-gray-500">Physical Injuries</div>
                        </td>
                        <td class="p-2">2023-01-10</td>
                        <td class="p-2 text-center">
                            <button class="restore-btn">Restore</button>
                        </td>
                    </tr>

                    <tr>
                        <td class="p-2 font-semibold">BL-2022-112</td>
                        <td class="p-2">
                            K. Kadamay vs L. Lito
                            <div class="text-xs text-gray-500">Noise Complaint</div>
                        </td>
                        <td class="p-2">2023-02-15</td>
                        <td class="p-2 text-center">
                            <button class="restore-btn">Restore</button>
                        </td>
                    </tr>

                    <tr>
                        <td class="p-2 font-semibold">BL-2023-005</td>
                        <td class="p-2">
                            B. Batumbakal vs Unknown
                            <div class="text-xs text-gray-500">Vandalism</div>
                        </td>
                        <td class="p-2">2023-03-01</td>
                        <td class="p-2 text-center">
                            <button class="restore-btn">Restore</button>
                        </td>
                    </tr>

                    <tr>
                        <td class="p-2 font-semibold">BL-2023-012</td>
                        <td class="p-2">
                            J. Rizal vs D. Ibarra
                            <div class="text-xs text-gray-500">Property Dispute</div>
                        </td>
                        <td class="p-2">2023-06-20</td>
                        <td class="p-2 text-center">
                            <button class="restore-btn">Restore</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 text-xs text-gray-500 border-t">
            Showing 4 of 48 archived records
        </div>
    </div>
    <!-- end of archived blotter dialog -->

    <script>
        $(function() {
            $('body').show();
            $('#blotterTable').DataTable({
                order: [
                    [0, 'desc']
                ],
                pageLength: 25
            });

            $("#addBlotterModal").dialog({
                autoOpen: false,
                modal: true,
                width: 700,
                height: 600,
                resizable: true,
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-title': 'font-semibold',
                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                },
                open: function() {
                    $('.ui-dialog-buttonpane button')
                        .addClass('bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded');
                }
            });

            $("#archivedBlotterDialog").dialog({
                autoOpen: false,
                modal: true,
                width: 600,
                height: 500,
                resizable: true,
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-title': 'font-semibold',
                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
                },
                open: function() {
                    $('.ui-dialog-buttonpane button')
                        .addClass('bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded');
                }
            });

            $("#openBlotterModalBtn").on("click", function() {
                $("#addBlotterModal").dialog("open");
            });

            $("#archivedBlotterDialogBtn").on("click", function() {
                $("#archivedBlotterDialog").dialog("open");
            });
        });
    </script>
</body>

</html>
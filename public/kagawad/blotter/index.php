<?php
require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

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

// Fetch all blotter records (excluding archived)
$stmt = $conn->prepare("SELECT b.*, u.name as created_by_name FROM blotter b LEFT JOIN users u ON b.created_by = u.id WHERE b.archived_at IS NULL ORDER BY b.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="autocomplete" content="off">
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
                                        <span class="view-blotter-btn text-theme-accent hover:underline font-semibold cursor-pointer" data-id="<?= $row['id'] ?>">
                                            <?= htmlspecialchars($row['case_number']) ?>
                                        </span>
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
                                        <span class="view-blotter-btn text-theme-accent hover:underline cursor-pointer" data-id="<?= $row['id'] ?>">View</span>
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
                <button id="historyBlotterDialogBtn" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">History</button>
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
                    id="archiveSearchInput"
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
                        <th class="p-2 w-[18%]">Entry No.</th>
                        <th class="p-2 w-[45%]">Parties / Incident</th>
                        <th class="p-2 w-[20%]">Date Archived</th>
                        <th class="p-2 text-center w-[17%]">Action</th>
                    </tr>
                </thead>
                <tbody id="archivedBlotterTableBody" class="divide-y">
                    <!-- Dynamic content will be loaded here -->
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 text-xs text-gray-500 border-t">
            <span id="archivedBlotterFooter">Loading...</span>
        </div>
    </div>
    <!-- end of archived blotter dialog -->

    <!-- View Blotter Modal -->
    <div id="viewBlotterModal" title="Blotter Case Details" class="hidden">
        <form id="blotterForm" class="space-y-4">
            <?= csrfTokenField() ?>
            <input type="hidden" name="id" id="blotter_id">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Case Information -->
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Case Information</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Case Number</label>
                        <p id="case_number_display" class="text-gray-900 font-semibold"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select name="status" id="status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                            <option value="pending">Pending</option>
                            <option value="under_investigation">Under Investigation</option>
                            <option value="resolved">Resolved</option>
                            <option value="dismissed">Dismissed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Created By</label>
                        <p id="created_by_display" class="text-gray-900"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Created</label>
                        <p id="created_at_display" class="text-gray-900"></p>
                    </div>
                </div>

                <!-- Incident Information -->
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Incident Information</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Incident Date *</label>
                        <input type="date" name="incident_date" id="incident_date" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Incident Time</label>
                        <input type="time" name="incident_time" id="incident_time"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
                        <input type="text" name="incident_location" id="incident_location" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <textarea name="incident_description" id="incident_description" rows="3" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
                    </div>
                </div>

                <!-- Complainant Information -->
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Complainant Information</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="complainant_name" id="complainant_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="complainant_address" id="complainant_address" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                        <input type="text" name="complainant_contact" id="complainant_contact"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                </div>

                <!-- Respondent Information -->
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Respondent Information</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="respondent_name" id="respondent_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="respondent_address" id="respondent_address" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                        <input type="text" name="respondent_contact" id="respondent_contact"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                </div>
            </div>

            <!-- Resolution Section -->
            <div class="mt-4 pt-4 border-t">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Resolution</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resolved Date</label>
                        <input type="date" name="resolved_date" id="resolved_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resolution Notes</label>
                        <textarea name="resolution" id="resolution" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <!-- end of view blotter modal -->

    <!-- History Dialog -->
    <div id="historyBlotterDialog" title="Blotter Case History" class="hidden">
        <!-- Search -->
        <div class="p-4 border-b">
            <div class="relative">
                <input
                    type="text"
                    id="historySearchInput"
                    placeholder="Search by case number..."
                    class="w-full border rounded-md px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-1" />
                <span class="absolute right-3 top-2.5 text-gray-400">🔍</span>
            </div>
        </div>

        <!-- Table -->
        <div class="p-4 overflow-auto max-h-[400px]">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-2 w-[15%]">Case Number</th>
                        <th class="p-2 w-[12%]">Action</th>
                        <th class="p-2 w-[28%]">Status Change</th>
                        <th class="p-2 w-[15%]">Changed By</th>
                        <th class="p-2 w-[30%]">Date/Time</th>
                    </tr>
                </thead>
                <tbody id="historyBlotterTableBody" class="divide-y">
                    <!-- Dynamic content will be loaded here -->
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 text-xs text-gray-500 border-t">
            <span id="historyBlotterFooter">Loading...</span>
        </div>
    </div>
    <!-- end of history dialog -->

    <script src="js/index.js"></script>
</body>

</html>
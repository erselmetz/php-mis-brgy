<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse(); // Only HC Nurse can access

// Handle POST like resident.php style
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_consultation') {
        include_once __DIR__ . '/add.php';
    }
}

// Load consultations with resident info
$sql = "
  SELECT
    c.id,
    c.resident_id,
    c.complaint,
    c.diagnosis,
    c.treatment,
    c.notes,
    c.consultation_date,
    r.first_name,
    r.middle_name,
    r.last_name,
    r.suffix,
    r.birthdate
  FROM consultations c
  INNER JOIN residents r ON r.id = c.resident_id
  WHERE r.deleted_at IS NULL
  ORDER BY c.consultation_date DESC, c.id DESC
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log('Consultations query error: ' . $conn->error);
    $result = false;
} else {
    $stmt->execute();
    $result = $stmt->get_result();
}

function fullNameRow($row): string
{
    $parts = [
        trim($row['first_name'] ?? ''),
        trim($row['middle_name'] ?? ''),
        trim($row['last_name'] ?? ''),
        trim($row['suffix'] ?? ''),
    ];
    $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
    return $name !== '' ? $name : '—';
}

$assignedHealthWorker = $_SESSION['name'] ?? 'HC Nurse';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Consultation - MIS Barangay</title>
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>

    <div class="flex bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="p-6 w-screen min-h-screen relative">
            <h2 class="text-2xl font-semibold mb-4">Consultation</h2>

            <!-- Top Controls (same as mockup) -->
            <div class="flex items-center justify-between mb-4">
                <button id="openConsultationModalBtn"
                    class="bg-theme-primary hover-theme-darker text-white font-semibold px-10 py-2 rounded shadow">
                    Add Consulatation
                </button>

                <input id="consultSearchInput" type="text" placeholder="Search"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-theme-primary">
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden p-4">
                <table id="consultationsTable" class="display w-full text-sm border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="p-2 text-left">Fullname</th>
                            <th class="p-2 text-left">Age</th>
                            <th class="p-2 text-left">Date of Visit</th>
                            <th class="p-2 text-left">Chief complaint</th>
                            <th class="p-2 text-left">Diagnosis</th>
                            <th class="p-2 text-left">Treatment provided</th>
                            <th class="p-2 text-left">Assigned health worker</th>
                            <th class="p-2 text-left">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result !== false): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                $fullname = fullNameRow($row);
                                $age = !empty($row['birthdate']) ? AutoComputeAge($row['birthdate']) : '—';

                                // No "status" column in schema -> UI status only
                                $dateVisit = $row['consultation_date'] ?? '';
                                $status = 'Ongoing';
                                if (!empty($dateVisit) && strtotime($dateVisit) < strtotime(date('Y-m-d'))) $status = 'Dismissed';
                                ?>
                                <tr>
                                    <td class="p-2">
                                        <a href="#" class="text-blue-600 hover:underline view-consult-btn" data-id="<?= (int)$row['id']; ?>">
                                            <?= htmlspecialchars($fullname); ?>
                                        </a>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars((string)$age); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($dateVisit); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['complaint'] ?? ''); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['diagnosis'] ?? ''); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['treatment'] ?? ''); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($assignedHealthWorker); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($status); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-500">Error loading consultations. Please try again later.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bottom-right button (same as mockup) -->
            <button id="generateConsultReportBtn"
                class="fixed bottom-8 right-10 bg-theme-secondary hover-theme-darker text-white font-semibold px-12 py-3 rounded shadow">
                Generate a Report
            </button>

            <!-- Add Consultation Modal -->
            <div id="addConsultationModal" title="Add New Consultation" class="hidden">
                <form id="addConsultationForm" method="POST" class="space-y-4 max-h-[70vh] overflow-y-auto p-4">
                    <input type="hidden" name="action" value="add_consultation">
                    <input type="hidden" name="resident_id" id="resident_id">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Resident/Patient Name</label>
                        <input type="text" id="resident_name" placeholder="Search or select resident..."
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="text" id="consultation_date" name="consultation_date" placeholder="mm/dd/yyyy"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time</label>
                            <input type="time" name="consultation_time"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason for Consultation / Chief Complaint</label>
                        <textarea name="complaint" rows="3" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary resize-none"
                            placeholder="Enter details..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Diagnosis</label>
                        <textarea name="diagnosis" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary resize-none"
                            placeholder="Enter diagnosis..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Treatment / Prescription</label>
                        <textarea name="treatment" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary resize-none"
                            placeholder="Enter treatment details..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Attending Health Worker</label>
                        <input type="text" name="health_worker" placeholder="Select or enter name..."
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
                            <option value="Completed" selected>Completed</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Dismissed">Dismissed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Remarks</label>
                        <textarea name="remarks" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary resize-none"
                            placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <?php loadAllScripts(); ?>

    <script>
        $(window).on('load', function() {
            $("body").show();

            const table = $('#consultationsTable').DataTable({
                pageLength: 100,
                lengthChange: false,
                info: false,
                dom: 'rt<"flex items-center justify-between mt-4"p>', // hide default search
            });

            $('#consultSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            $("#addConsultationModal").dialog({
                autoOpen: false,
                modal: true,
                width: 650,
                resizable: false
            });

            $("#openConsultationModalBtn").on("click", function() {
                $("#addConsultationModal").dialog("open");
            });

            $("#closeConsultationModalBtn").on("click", function() {
                $("#addConsultationModal").dialog("close");
            });

            $("#generateConsultReportBtn").on("click", function() {
                alert("Next step: report export/print UI.");
            });

            $(document).on("click", ".view-consult-btn", function(e) {
                e.preventDefault();
                alert("Next step: view modal (ID: " + $(this).data("id") + ")");
            });
            // datepicker (same ecosystem)
            $("#consultation_date").datepicker({
                dateFormat: "mm/dd/yy"
            });

            $("#addConsultationModal").dialog({
                autoOpen: false,
                modal: true,
                resizable: true,
                classes: {
                    'ui-dialog': 'rounded-lg shadow-lg',
                    'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
                    'ui-dialog-title': 'font-semibold',
                    'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg',
                    'ui-dialog-buttonpane button': 'bg-theme-primary hover:bg-theme-secondary text-white px-4 py-2 rounded'
                },
                show: {
                    effect: "fadeIn",
                    duration: 200
                },
                hide: {
                    effect: "fadeOut",
                    duration: 200
                },
                buttons: {
                    "Add Consultation": function() {
                        $("#addConsultationForm").trigger("submit");
                    },
                    "Cancel": function() {
                        $(this).dialog("close");
                    }
                }
            });

            $("#openConsultationModalBtn").on("click", function() {
                $("#addConsultationModal").dialog("open");
            });

            // Convert mm/dd/yyyy -> yyyy-mm-dd before submit
            $("#addConsultationForm").on("submit", function() {
                const d = $("#consultation_date").val().trim();
                if (d) {
                    const parts = d.split("/");
                    if (parts.length === 3) {
                        const mm = parts[0].padStart(2, "0");
                        const dd = parts[1].padStart(2, "0");
                        const yyyy = parts[2];
                        $("#consultation_date").val(`${yyyy}-${mm}-${dd}`);
                    }
                }
                return true;
            });
        });
    </script>
</body>

</html>
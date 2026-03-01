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

function unpackNotes(string $notes): array
{
    $out = ['time' => '', 'health_worker' => '', 'status' => '', 'remarks' => ''];
    if (preg_match('/Time:\s*([^|]+)/i', $notes, $m))
        $out['time'] = trim($m[1]);
    if (preg_match('/Health Worker:\s*([^|]+)/i', $notes, $m))
        $out['health_worker'] = trim($m[1]);
    if (preg_match('/Status:\s*([^|]+)/i', $notes, $m))
        $out['status'] = trim($m[1]);
    if (preg_match('/Remarks:\s*(.+)$/i', $notes, $m))
        $out['remarks'] = trim($m[1]);
    return $out;
}

$assignedHealthWorker = 'HC Nurse';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Consultation - MIS Barangay</title>
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>

    <div class="flex bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="p-6 w-screen h-screen relative overflow-y-auto">
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
                            <th class="p-2 text-left">Action</th>
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
                                $extras = unpackNotes($row['notes'] ?? '');
                                if (!empty($dateVisit) && strtotime($dateVisit) < strtotime(date('Y-m-d')))
                                    $status = 'Dismissed';
                                ?>
                                <tr>
                                    <td class="p-2">
                                        <a href="#" class="text-blue-600 hover:underline viewConsultBtn"
                                            data-id="<?= (int) $row['id']; ?>">
                                            <?= htmlspecialchars($fullname); ?>
                                        </a>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars((string) $age); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($dateVisit); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['complaint'] ?? ''); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['diagnosis'] ?? ''); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['treatment'] ?? ''); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($extras['health_worker'] ?? ''); ?></td>
                                    <td class="p-2"><?= htmlspecialchars($extras['status']); ?></td>
                                    <td class="p-2">
                                        <button
                                            class="viewConsultBtn px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200"
                                            data-id="<?= (int) $row['id'] ?>">
                                            View
                                        </button>
                                        <button
                                            class="editConsultBtn px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700 hover:bg-yellow-200"
                                            data-id="<?= (int) $row['id'] ?>">
                                            Edit
                                        </button>
                                        <button
                                            class="generateBtnResident px-2 py-1 text-xs rounded bg-purple-100 text-purple-700 hover:bg-purple-200"
                                            data-resident-id="<?= (int) $row['id'] ?>">
                                            Generate
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-500">Error loading consultations. Please
                                    try again later.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bottom-right button (same as mockup) -->
            <button id="generateConsultReportBtn"
                class="fixed bottom-8 right-10 bg-theme-primary hover-theme-darker text-white font-semibold px-12 py-3 rounded shadow">
                Generate a Report
            </button>

            <!-- Add Consultation Modal -->
            <div id="addConsultationModal" title="Add New Consultation" class="hidden">
                <form id="addConsultationForm" method="POST" class="space-y-4 max-h-[70vh] overflow-y-auto p-4">
                    <input type="hidden" name="action" value="add_consultation">
                    <input type="hidden" name="resident_id" id="add_resident_id">

                    <!-- Patient -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resident/Patient Name</label>
                        <input type="text" id="add_resident_name" placeholder="Search or select resident..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        <p class="text-xs text-gray-400 mt-1">Select a resident from the list to avoid errors.</p>
                    </div>

                    <!-- Date + Time -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="text" id="consultation_date" name="consultation_date" placeholder="mm/dd/yyyy"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                            <input type="time" name="consultation_time"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary">
                        </div>
                    </div>

                    <!-- Consultation Type + Subtype -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Consultation Type</label>
                            <select name="consultation_type" id="consultation_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-theme-primary">
                                <option value="immunization">Immunization</option>
                                <option value="maternal">Maternal</option>
                                <option value="family_planning">Family Planning</option>
                                <option value="prenatal">Prenatal</option>
                                <option value="postnatal">Postnatal</option>
                                <option value="child_nutrition">Child Nutrition</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sub Type (Optional)</label>
                            <input type="text" name="sub_type" id="sub_type"
                                placeholder="e.g., BCG / pills / mother_only"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary">
                        </div>
                    </div>

                    <!-- Complaint -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Chief Complaint</label>
                        <textarea name="complaint" rows="3" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary resize-none"
                            placeholder="Enter details..."></textarea>
                    </div>

                    <!-- Diagnosis + Treatment -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                            <textarea name="diagnosis" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary resize-none"
                                placeholder="Enter diagnosis..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Treatment / Prescription</label>
                            <textarea name="treatment" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary resize-none"
                                placeholder="Enter treatment details..."></textarea>
                        </div>
                    </div>

                    <!-- Worker + Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Attending Health Worker</label>
                            <input type="text" name="health_worker" placeholder="Enter name..." value="<?= $_SESSION['name']?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-theme-primary">
                                <option value="Completed" selected>Completed</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Dismissed">Dismissed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <textarea name="remarks" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary resize-none"
                            placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>


            <!-- View Consultation Modal -->
            <div id="viewConsultationModal" title="View Consultation" class="hidden">
                <div class="p-4 space-y-4 text-sm">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="text-gray-500">Resident</div>
                            <div id="v_fullname" class="font-semibold text-gray-800">—</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Date of Visit</div>
                            <div id="v_date" class="font-semibold text-gray-800">—</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <div class="text-gray-500">Time</div>
                            <div id="v_time" class="font-semibold text-gray-800">—</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Status</div>
                            <div id="v_status" class="font-semibold text-gray-800">—</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Health Worker</div>
                            <div id="v_worker" class="font-semibold text-gray-800">—</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500">Chief Complaint</div>
                        <div id="v_complaint" class="font-semibold text-gray-800 whitespace-pre-wrap">—</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Diagnosis</div>
                        <div id="v_diagnosis" class="font-semibold text-gray-800 whitespace-pre-wrap">—</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Treatment / Prescription</div>
                        <div id="v_treatment" class="font-semibold text-gray-800 whitespace-pre-wrap">—</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Remarks</div>
                        <div id="v_remarks" class="font-semibold text-gray-800 whitespace-pre-wrap">—</div>
                    </div>
                </div>
            </div>

            <!-- Edit Consultation Modal -->
            <div id="editConsultationModal" title="Edit Consultation" class="hidden">
                <form id="editConsultationForm" class="space-y-4 max-h-[70vh] overflow-y-auto p-4">
                    <div tabindex="0" class="outline-none"></div>
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="resident_id" id="edit_resident_id">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Resident/Patient Name</label>
                        <input type="text" id="edit_resident_name" disabled
                            class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-gray-700">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="text" id="edit_consultation_date" name="consultation_date"
                                placeholder="mm/dd/yyyy"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time</label>
                            <input type="time" id="edit_consultation_time" name="consultation_time"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Chief Complaint</label>
                        <textarea id="edit_complaint" name="complaint" rows="3" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Diagnosis</label>
                        <textarea id="edit_diagnosis" name="diagnosis" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Treatment</label>
                        <textarea id="edit_treatment" name="treatment" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Attending Health Worker</label>
                        <input id="edit_health_worker" name="health_worker" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="edit_status" name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary">
                            <option value="Completed">Completed</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Dismissed">Dismissed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Remarks</label>
                        <textarea id="edit_remarks" name="remarks" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-theme-primary resize-none"></textarea>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- generate dialog per resident -->
    <div id="generateDialog" title="Generate Document" class="hidden">
        <form id="generateForm" class="p-4 space-y-4">
            <input type="hidden" id="gen_resident_id" name="resident_id" value="">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                <select id="gen_doc" name="doc"
                    class="w-full px-3 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-theme-primary">
                    <option value="summary">Printable Summary</option>
                    <option value="report">Report (All Residents)</option>
                    <option value="certificate">Certificate-like Document</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Summary & Certificate uses selected resident. Report uses all.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                <select id="gen_period" name="period"
                    class="w-full px-3 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-theme-primary">
                    <option value="daily">Daily (Today)</option>
                    <option value="weekly">Weekly (This Week)</option>
                    <option value="monthly" selected>Monthly (Select Month)</option>
                </select>
            </div>

            <div id="gen_month_wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Month</label>
                <input type="month" id="gen_month" name="month"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-theme-primary"
                    value="<?= date('Y-m'); ?>">
            </div>

            <div id="gen_purpose_wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Purpose (Certificate)</label>
                <input type="text" id="gen_purpose" name="purpose"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-theme-primary"
                    placeholder="e.g., For school requirement / Medical clearance / etc.">
            </div>
        </form>
    </div>

    <?php loadAllScripts(); ?>

    <script src="js/index.js"></script>
</body>

</html>
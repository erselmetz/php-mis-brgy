<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type = $_GET['type'] ?? 'maternal';

// UI labels per main type
$mainTitles = [
    'maternal' => 'Maternal & Child Records',
    'family_planning' => 'Family Planning Records',
    'prenatal' => 'Prenatal & Postnatal Records',
    'postnatal' => 'Prenatal & Postnatal Records',
    'child_nutrition' => 'Child Nutrition Records',
    'immunization' => 'Immunization Records',
];
$pageTitle = $mainTitles[$type] ?? 'Health Records';

// filters (persist via URL)
$period = $_GET['period'] ?? 'all';      // all|daily|weekly|monthly
$month  = $_GET['month'] ?? date('Y-m'); // YYYY-MM
$q      = $_GET['q'] ?? '';
$sub    = $_GET['sub'] ?? 'all';         // subtype
$totalRecords = 0; // TODO: replace later after AJAX loads (or server-side count)
$currentTypeLabel = 'Mother'; // TODO: replace based on subtype mapping
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllStyles(); ?>
    <?= loadAsset('node_js', 'chart.js/dist/chart.umd.min.js') ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include '../layout/navbar.php'; ?>

    <div class="flex h-full">
        <?php include '../layout/sidebar.php'; ?>

        <main class="flex-1 p-6 overflow-y-auto h-screen pb-24">

            <!-- Header Card -->
            <section class="bg-white border rounded-xl shadow-sm p-6 mb-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="text-3xl"></div>
                            <h1 class="text-3xl font-extrabold text-gray-800">
                                <?= htmlspecialchars($pageTitle); ?>
                            </h1>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Manage and review maternal and child health consultations and patient data
                        </p>
                    </div>

                    <button id="printRecordsBtn"
                        class="border border-theme-primary text-theme-primary px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                        🖨️ <span class="font-semibold">Print Records</span>
                    </button>
                </div>

                <div class="mt-5 flex items-center justify-between">
                    <div class="inline-flex items-center gap-2 bg-gray-50 border rounded-lg px-4 py-3 text-theme-primary">
                        📄 <span class="font-bold"><span id="totalRecordsLabel"><?= (int)$totalRecords; ?></span> Total Record(s)</span>
                        <span class="text-gray-400">|</span>
                        <span class="font-semibold">Type: <span id="currentSubTypeLabel"><?= htmlspecialchars($currentTypeLabel); ?></span></span>
                    </div>
                    <div></div>
                </div>
            </section>

            <!-- Filter by Time Period -->
            <section class="bg-white border rounded-xl shadow-sm p-6 mb-6">
                <div class="flex items-center gap-2 text-gray-700 font-bold mb-4">
                    🔻 <span>Filter by Time Period</span>
                </div>

                <div class="flex flex-wrap gap-2 mb-4">
                    <button class="periodBtn px-4 py-2 rounded-lg border flex items-center gap-2"
                        data-period="all">
                        📅 All Time
                    </button>

                    <button class="periodBtn px-4 py-2 rounded-lg border flex items-center gap-2"
                        data-period="daily">
                        🗓️ Daily
                    </button>

                    <button class="periodBtn px-4 py-2 rounded-lg border flex items-center gap-2"
                        data-period="weekly">
                        🗓️ Weekly
                    </button>

                    <button class="periodBtn px-4 py-2 rounded-lg border flex items-center gap-2"
                        data-period="monthly">
                        🗓️ Monthly
                    </button>
                </div>

                <hr class="my-4">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Month:</label>

                        <!-- Month Picker -->
                        <input type="month" id="monthPicker"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-theme-primary"
                            value="<?= htmlspecialchars($month); ?>">
                    </div>

                    <div>
                        <button id="applyPeriodBtn"
                            class="w-full bg-theme-primary text-white px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 hover:opacity-90">
                            🔍 Apply
                        </button>
                    </div>
                </div>
            </section>

            <!-- Search + Type filter row -->
            <section class="bg-white border rounded-xl shadow-sm p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center">

                    <!-- Search -->
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-2 border rounded-full px-4 py-3 bg-white">
                            🔎
                            <input type="text" id="searchInput"
                                class="w-full outline-none text-sm"
                                placeholder="Search patient, BP, complications, notes..."
                                value="<?= htmlspecialchars($q); ?>">
                        </div>
                    </div>

                    <!-- Type filter + clear -->
                    <div class="flex items-center justify-end gap-3">
                        <div class="flex items-center gap-2 whitespace-nowrap font-semibold text-gray-700">
                            Filter by Type:
                            <select id="subTypeSelect"
                                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-theme-primary">
                                <!-- JS will populate based on main type -->
                            </select>
                        </div>

                        <button id="clearFiltersBtn"
                            class="px-4 py-2 border rounded-lg hover:bg-gray-50 flex items-center gap-2">
                            ✖ Clear Filters
                        </button>
                    </div>
                </div>
            </section>

            <!-- Table / Empty State -->
            <section id="tableSection" class="bg-white border rounded-xl shadow-sm p-6 hidden">
                <div class="overflow-x-auto">
                    <table id="recordsTable" class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left px-4 py-3">Date</th>
                                <th class="text-left px-4 py-3">Resident</th>
                                <th class="text-left px-4 py-3">Details</th>
                                <th class="text-left px-4 py-3">Complaint</th>
                                <th class="text-left px-4 py-3">Status</th>
                                <th class="text-center px-4 py-3 w-32">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <!-- Empty State -->
            <section id="emptyState" class="bg-white border rounded-xl shadow-sm p-10 text-center">
                <div class="text-6xl mb-3">📋</div>
                <h2 class="text-2xl font-extrabold text-gray-700 mb-2">
                    No <?= htmlspecialchars($pageTitle); ?> Found
                </h2>
                <p class="text-gray-500">
                    There are no health records matching your criteria.
                </p>
            </section>


        </main>
        <!-- Add/Edit Dialog -->
        <div id="healthRecordDialog" title="Health Record" class="hidden">
            <form id="healthRecordForm" class="space-y-4">

                <input type="hidden" name="id" id="record_id">
                <input type="hidden" name="care_type" value="<?= htmlspecialchars($type); ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Record Date</label>
                    <input type="text" name="record_date" id="record_date"
                        class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-theme-primary"
                        placeholder="mm/dd/yyyy">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Resident</label>
                    <input type="text" id="resident_name"
                        class="w-full px-3 py-2 border rounded"
                        placeholder="Search resident...">
                    <input type="hidden" name="resident_id" id="resident_id">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Details (JSON)</label>
                    <textarea name="details_json" id="details_json"
                        class="w-full px-3 py-2 border rounded"
                        rows="4"
                        placeholder='{"example_key":"value"}'></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" id="notes"
                        class="w-full px-3 py-2 border rounded"
                        rows="3"></textarea>
                </div>

            </form>
        </div>
        <!-- Edit Consultation Modal -->
        <div id="editConsultationModal" title="Edit Consultation" class="hidden">
            <form id="editConsultationForm" method="POST" class="space-y-4 max-h-[70vh] overflow-y-auto p-4">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="resident_id" id="edit_resident_id">

                <!-- Patient -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resident/Patient Name</label>
                    <input disabled type="text" id="edit_resident_name" placeholder="Search or select resident..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-theme-primary">
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
                        <input type="text" name="sub_type" id="sub_type" placeholder="e.g., BCG / pills / mother_only"
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
                        <input type="text" name="health_worker" placeholder="Enter name..."
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
    </div>

    <?php loadAllScripts(); ?>

    <script>
        // Main type from URL (?type=...)
        const HEALTH_RECORD_TYPE = <?= json_encode($type); ?>;

        // Current filters (from URL)
        const INIT_FILTERS = {
            period: <?= json_encode($period); ?>,
            month: <?= json_encode($month); ?>,
            q: <?= json_encode($q); ?>,
            sub: <?= json_encode($sub); ?>
        };

        function loadRecords() {
            const params = {
                type: HEALTH_RECORD_TYPE, // e.g. immunization
                period: window.__period || "all",
                month: $("#monthPicker").val() || "",
                search: $("#searchInput").val() || "",
                sub: $("#subTypeSelect").val() || "all"
            };

            $.getJSON("api/health_records_api.php", params)
                .done(function(res) {
                    if (res.status !== "ok") return;

                    $("#totalRecordsLabel").text(res.total);

                    const $tbody = $("#recordsTable tbody");
                    $tbody.empty();

                    if (res.total === 0) {
                        $("#tableSection").addClass("hidden");
                        $("#emptyState").removeClass("hidden");
                        return;
                    }

                    $("#emptyState").addClass("hidden");
                    $("#tableSection").removeClass("hidden");

                    res.data.forEach(row => {

                        const status = row.meta.status || '';
                        let statusBadge = '';

                        if (status === 'Completed') {
                            statusBadge = `<span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">${status}</span>`;
                        } else if (status === 'Ongoing') {
                            statusBadge = `<span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">${status}</span>`;
                        } else {
                            statusBadge = `<span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700">${status}</span>`;
                        }

                        const tr = `
                        <tr class="border-b hover:bg-gray-50">  
                            <td class="px-4 py-3">${row.consultation_date}</td>
                            <td class="px-4 py-3 font-semibold">${row.resident_name}</td>
                            <td class="px-4 py-3">${row.details_preview || ''}</td>
                            <td class="px-4 py-3">${row.complaint || ''}</td>
                            <td class="px-4 py-3">${statusBadge}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <button 
                                        class="viewBtn px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200"
                                        data-id="${row.id}">
                                        View
                                    </button>

                                    <button 
                                        class="editBtn px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700 hover:bg-yellow-200"
                                        data-id="${row.id}">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                        `;

                        $tbody.append(tr);
                    });

                })
                .fail(function(xhr) {
                    console.log("AJAX failed:", xhr.status, xhr.responseText);
                });
        }

        // call once on load
        loadRecords();
    </script>

    <script src="js/index.js"></script>
</body>

</html>
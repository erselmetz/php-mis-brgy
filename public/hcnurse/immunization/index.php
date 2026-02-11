<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse(); // Only HC Nurse can access

/* =========================
   TABLE PICKER (resident vs residents)
========================= */
function pickResidentTable(mysqli $conn): string
{
    $candidates = ['residents', 'resident'];
    foreach ($candidates as $t) {
        $safe = $conn->real_escape_string($t);
        $res = $conn->query("SHOW TABLES LIKE '{$safe}'");
        if ($res && $res->num_rows > 0) return $t;
    }
    return 'residents'; // fallback
}

$residentTable = pickResidentTable($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Immunization - MIS Barangay</title>
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>

    <div class="flex bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="p-6 w-screen h-screen relative overflow-y-auto">
            <!-- =========================
            HC NURSE: IMMUNIZATION MAIN
            ========================= -->
            <section class="mt-6">
                <div class="bg-white border rounded-lg shadow-sm p-4">
                    <div class="flex items-start gap-4">

                        <!-- LEFT: RESIDENT LIST -->
                        <div class="w-[260px]">
                            <div class="font-semibold mb-2">Residents</div>
                            <input type="text" id="immResidentSearch" placeholder="Search residents..."
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary" />

                            <div id="immResidentList"
                                class="mt-2 border border-gray-300 rounded overflow-hidden bg-white overflow-y-auto h-[700px] p-2 border rounded bg-gray-50">
                                <!-- JS fills list -->
                            </div>
                        </div>

                        <!-- RIGHT: RECORDS + FORM -->
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div class="font-semibold">
                                    Immunization Records for <span id="immSelectedName" class="text-gray-700">—</span>
                                </div>

                                <button id="immPrintBtn"
                                    class="px-3 py-2 rounded border bg-white hover:bg-gray-50 text-sm"
                                    type="button" disabled>
                                    Generate a Report
                                </button>
                            </div>

                            <!-- RECORDS TABLE -->
                            <div class="mt-3 border rounded overflow-hidden">
                                <table id="immRecordsTable" class="display w-full">
                                    <thead>
                                        <tr>
                                            <th>Vaccine Type</th>
                                            <th>Date Administered</th>
                                            <th>Next Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <!-- ADD FORM -->
                            <div class="mt-4">
                                <div class="font-semibold mb-2">Add New Immunization</div>

                                <form id="immAddForm" class="space-y-3">
                                    <input type="hidden" name="action" value="add_immunization">
                                    <input type="hidden" name="resident_id" id="immResidentId" value="">

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Vaccine Type</label>
                                        <input type="text" name="vaccine_name" id="immVaccineName" placeholder="Select or enter vaccine type"
                                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                                        <div class="text-xs text-gray-500 mt-1">Example: COVID-19, Measles, Polio, Hepatitis B</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Dose (Optional)</label>
                                        <input type="text" name="dose" id="immDose" placeholder="e.g., Dose 1 / Booster"
                                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Date Administered</label>
                                        <input type="text" name="date_given" id="immDateGiven" placeholder="mm/dd/yyyy"
                                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Next Due Date (Optional)</label>
                                        <input type="text" name="next_schedule" id="immNextSchedule" placeholder="mm/dd/yyyy"
                                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Notes/Status</label>
                                        <textarea name="remarks" id="immRemarks" rows="3" placeholder="Additional notes..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"></textarea>
                                    </div>

                                    <button id="immAddBtn" type="submit"
                                        class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded font-semibold disabled:opacity-60 disabled:cursor-not-allowed"
                                        disabled>
                                        Add Immunization
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <?php loadAllScripts(); ?>

    <script src="js/index.js"></script>
</body>

</html>
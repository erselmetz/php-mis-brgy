<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View - Resident - MIS Barangay</title>
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
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="p-6 w-screen">
            <h1 class="text-2xl font-semibold mb-6">View Resident</h1>

            <!-- ✅ Start of Resident Information Section -->
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6"> <!-- Form Section -->
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h2 class="text-lg font-medium mb-4">Edit Resident</h2>
                    <form id="residentForm" autocomplete="off">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div> <label class="block text-sm font-medium text-gray-700">Household ID</label> <input name="household_id" id="household_id" type="number" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Birthdate</label> <input name="birthdate" id="birthdate" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" placeholder="YYYY-MM-DD" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">First name</label> <input name="first_name" id="first_name" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" required /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Middle name</label> <input name="middle_name" id="middle_name" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Last name</label> <input name="last_name" id="last_name" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" required /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Suffix</label> <input name="suffix" id="suffix" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Gender</label> <select name="gender" id="gender" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Birthplace</label> <input name="birthplace" id="birthplace" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Civil status</label> <select name="civil_status" id="civil_status" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm">
                                    <option>Single</option>
                                    <option>Married</option>
                                    <option>Separated</option>
                                    <option>Widowed</option>
                                </select> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Religion</label> <input name="religion" id="religion" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Occupation</label> <input name="occupation" id="occupation" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Citizenship</label> <input name="citizenship" id="citizenship" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" value="Filipino" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Contact No.</label> <input name="contact_no" id="contact_no" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" placeholder="09xx-xxx-xxxx" /> </div>
                            <div class="sm:col-span-2"> <label class="block text-sm font-medium text-gray-700">Address</label> <input name="address" id="address" type="text" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm" /> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Voter status</label> <select name="voter_status" id="voter_status" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm">
                                    <option>No</option>
                                    <option>Yes</option>
                                </select> </div>
                            <div> <label class="block text-sm font-medium text-gray-700">Disability status</label> <select name="disability_status" id="disability_status" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm">
                                    <option>No</option>
                                    <option>Yes</option>
                                </select> </div>
                            <div class="sm:col-span-2"> <label class="block text-sm font-medium text-gray-700">Remarks</label> <textarea name="remarks" id="remarks" rows="3" class="mt-1 block w-full rounded-lg border-gray-200 shadow-sm"></textarea> </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2"> <button id="saveBtn" type="button" class="px-4 py-2 bg-theme-primary text-white rounded-lg shadow-sm">Save</button> <button id="exportJson" type="button" class="ml-auto px-4 py-2 border rounded-lg">Export JSON</button> </div>
                    </form>
                </section>

                <!-- Preview Section -->
                <aside class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex items-start justify-between">
                        <h2 class="text-lg font-medium">Live Preview</h2>
                        <div id="ageBadge" class="text-sm text-gray-600"></div>
                    </div>

                    <div id="previewCard" class="mt-4 border rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 id="previewName" class="text-xl font-semibold text-gray-800">—</h3>
                                <div id="previewQuick" class="text-sm text-gray-600 mt-1">—</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Household ID</div>
                                <div id="previewHH" class="font-medium">—</div>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-2 text-sm text-gray-700">
                            <div><span class="font-medium">Gender:</span> <span id="previewGender">—</span></div>
                            <div><span class="font-medium">Birthdate:</span> <span id="previewBirthdate">—</span></div>
                            <div><span class="font-medium">Birthplace:</span> <span id="previewBirthplace">—</span></div>
                            <div><span class="font-medium">Civil status:</span> <span id="previewCivil">—</span></div>
                            <div><span class="font-medium">Religion:</span> <span id="previewReligion">—</span></div>
                            <div><span class="font-medium">Occupation:</span> <span id="previewOccupation">—</span></div>
                            <div><span class="font-medium">Citizenship:</span> <span id="previewCitizenship">—</span></div>
                            <div><span class="font-medium">Contact no.:</span> <span id="previewContact">—</span></div>
                            <div><span class="font-medium">Address:</span> <span id="previewAddress">—</span></div>
                            <div><span class="font-medium">Voter status:</span> <span id="previewVoter">—</span></div>

                            <!-- ✅ New Disability Preview -->
                            <div><span class="font-medium">Disability status:</span> <span id="previewDisability">—</span></div>

                            <div><span class="font-medium">Remarks:</span>
                                <div id="previewRemarks" class="mt-1 text-sm text-gray-600 italic">—</div>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-4">
                        <button id="showRaw" class="px-3 py-1 border rounded-lg text-sm">Show raw data</button>
                        <button id="refreshBtn" class="px-3 py-1 border rounded-lg text-sm">Refresh</button>
                    </div>
                </aside>
            </div>

            <!-- jQuery UI dialog -->
            <div id="dialog" title="Raw data" style="display:none;">
                <pre id="rawPre"
                    style="white-space:pre-wrap; word-break:break-word; max-height:400px; overflow:auto;"></pre>
            </div>
            <!-- ✅ End of Resident Information Section -->
        </main>
    </div>

    <script src="js/view.js"></script>
</body>

</html>
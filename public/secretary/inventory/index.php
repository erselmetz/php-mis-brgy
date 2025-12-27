<?php
require_once __DIR__ . '/../../../includes/app.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory - MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 h-screen overflow-hidden font-sans" style="display: none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>
        <main class="pb-24 overflow-y-auto flex-1 p-6 w-screen">
            <h1 class="text-green-600 text-2xl font-semibold mb-6">INVENTORY</h1>

            <div class="flex flex-wrap items-center justify-between mb-4 space-y-2 sm:space-y-0">
                <div class="flex space-x-2">
                    <button id="addInventoryBtn" type="button" class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-full text-sm font-semibold">Add Asset</button>
                    <button class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-full text-sm font-semibold">Add Category</button>
                </div>
                <div>
                    <input type="text" placeholder="Search" class="border border-gray-300 rounded px-3 py-2 w-48">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-2 border-b text-sm text-gray-600">Asset ID</th>
                            <th class="text-left px-4 py-2 border-b text-sm text-gray-600">Asset Name</th>
                            <th class="text-left px-4 py-2 border-b text-sm text-gray-600">Location</th>
                            <th class="text-left px-4 py-2 border-b text-sm text-gray-600">Condition</th>
                            <th class="text-left px-4 py-2 border-b text-sm text-gray-600">Category</th>
                            <th class="text-left px-4 py-2 border-b text-sm text-gray-600">Quantity</th>
                            <th class="text-left px-4 py-2 border-b text-sm text-gray-600">Currently Using</th>
                            <th class="text-left px-4 py-2 border-b text-sm text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="px-4 py-2 text-blue-600"><a href="#">BB-0001</a></td>
                            <td class="px-4 py-2">Patrol Mobile</td>
                            <td class="px-4 py-2">Parking Lot</td>
                            <td class="px-4 py-2">Maintenance</td>
                            <td class="px-4 py-2">Vehicle</td>
                            <td class="px-4 py-2">1</td>
                            <td class="px-4 py-2">None</td>
                            <td class="px-4 py-2">Maintenance</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-blue-600"><a href="#">BB-0002</a></td>
                            <td class="px-4 py-2">Broom</td>
                            <td class="px-4 py-2">Utilities Room</td>
                            <td class="px-4 py-2">Good</td>
                            <td class="px-4 py-2">Utilities</td>
                            <td class="px-4 py-2">4</td>
                            <td class="px-4 py-2">Yes</td>
                            <td class="px-4 py-2">Active</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-6">
                <button id="auditTrailBtn" type="button" class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-full text-sm font-semibold">Audit Trail</button>
            </div>

            <!-- modal template area -->
            <!-- ADD INVENTORY ITEM MODAL -->
            <div id="inventoryModal" title="Add New Inventory Item" class="p-0 hidden">
                <form class="bg-gray-100">

                    <div class="p-4 space-y-3 text-sm">

                        <div>
                            <label class="block font-semibold mb-1">Name of Asset</label>
                            <input type="text" class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Category</label>
                            <select class="w-full border border-gray-400 px-3 py-2 rounded">
                                <option>Select Category</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Quantity</label>
                            <input type="number" class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Physical Location</label>
                            <input type="text" class="w-full border border-gray-400 px-3 py-2 rounded">
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Condition</label>
                            <select class="w-full border border-gray-400 px-3 py-2 rounded">
                                <option>Select Condition</option>
                                <option>Good</option>
                                <option>Maintenance</option>
                                <option>Damaged</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">
                                Description (license plate if it's vehicle)
                            </label>
                            <textarea class="w-full border border-gray-400 px-3 py-2 rounded h-24"></textarea>
                        </div>

                    </div>

                    <!-- FOOTER BUTTON -->
                    <div class="bg-theme-primary text-center py-3">
                        <button type="submit"
                            class="text-white font-semibold text-sm">
                            Add Inventory Item
                        </button>
                    </div>

                </form>
            </div>

            <!-- ASSET AUDIT TRAIL MODAL -->
            <div id="assetAuditModal" class="hidden p-0">

                <!-- HEADER INFO -->
                <div class="p-5 border-b">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Asset Name</p>
                            <p class="font-semibold">
                                Barangay Patrol Vehicle (Toyota Hilux)
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500">Property Code</p>
                            <p class="font-semibold">
                                PROP-VH-2023-01
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FILTERS -->
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 border-b">
                    <input type="date"
                        class="border border-gray-300 px-3 py-2 rounded text-sm"
                        placeholder="Filter by Date">

                    <input type="text"
                        class="border border-gray-300 px-3 py-2 rounded text-sm"
                        placeholder="Search personnel">
                </div>

                <!-- TABLE -->
                <div class="px-5 pt-4 pb-2">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700">
                                <th class="text-left px-3 py-2">Time & Date</th>
                                <th class="text-left px-3 py-2">User / Personnel</th>
                                <th class="text-left px-3 py-2">Location (Place)</th>
                                <th class="text-left px-3 py-2">Purpose</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">

                            <tr>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">Oct 26, 2023</p>
                                    <p class="text-xs text-gray-500">08:30 AM - 05:00 PM</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">Juan Dela Cruz</p>
                                    <p class="text-xs text-gray-500">Chief Tanod</p>
                                </td>
                                <td class="px-3 py-3">Sitio Libis</td>
                                <td class="px-3 py-3 text-gray-600">Clearing Op.</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">Oct 25, 2023</p>
                                    <p class="text-xs text-gray-500">09:00 PM - 04:00 AM</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">Pedro Penduko</p>
                                    <p class="text-xs text-gray-500">Barangay Driver</p>
                                </td>
                                <td class="px-3 py-3">Barangay Proper</td>
                                <td class="px-3 py-3 text-gray-600">Night Patrol</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">Oct 24, 2023</p>
                                    <p class="text-xs text-gray-500">07:00 AM - 02:00 PM</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">Maria Clara</p>
                                    <p class="text-xs text-gray-500">BHW Head</p>
                                </td>
                                <td class="px-3 py-3">Health Center</td>
                                <td class="px-3 py-3 text-gray-600">Vaccine Transport</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">Oct 20, 2023</p>
                                    <p class="text-xs text-gray-500">08:00 AM - 12:00 PM</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">Jose Rizal</p>
                                    <p class="text-xs text-gray-500">Kagawad</p>
                                </td>
                                <td class="px-3 py-3">Munisipyo</td>
                                <td class="px-3 py-3 text-gray-600">Official Meeting</td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <!-- FOOTER -->
                <div class="p-5 flex items-center justify-between border-t">
                    <div class="space-x-2">
                        <button class="bg-theme-primary hover-theme-darker text-white px-5 py-2 rounded text-sm">
                            Export Logs
                        </button>
                        <button id="closeAuditModal"
                            class="border border-gray-300 px-5 py-2 rounded text-sm">
                            Close
                        </button>
                    </div>
                    <div class="text-xs text-gray-500">
                        Page 1 of 5
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script>
        $(function() {
            $("body").show();

            $("#inventoryModal").dialog({
                autoOpen: false,
                modal: true,
                width: 600,
                resizable: false,
                draggable: true,
                classes: {
                    "ui-dialog": "rounded-lg shadow-lg",
                    "ui-dialog-titlebar": "bg-theme-primary text-white",
                    "ui-dialog-title": "font-semibold",
                    "ui-dialog-titlebar-close": "text-white"
                }
            });

            $("#addInventoryBtn").on("click", function() {
                $("#inventoryModal").dialog("open");
            });

            $("#assetAuditModal").dialog({
                autoOpen: false,
                modal: true,
                width: 900,
                height: 650,
                resizable: false,
                draggable: false,
                classes: {
                    "ui-dialog": "rounded-lg shadow-lg",
                    "ui-dialog-titlebar": "bg-theme-primary text-white",
                    "ui-dialog-title": "font-semibold",
                    "ui-dialog-titlebar-close": "text-white"
                },
                title: "Asset Audit Trail"
            });

            $("#auditTrailBtn").on("click", function() {
                $("#assetAuditModal").dialog("open");
            });

            $("#closeAuditModal").on("click", function() {
                $("#assetAuditModal").dialog("close");
            });
        });
    </script>
</body>

</html>
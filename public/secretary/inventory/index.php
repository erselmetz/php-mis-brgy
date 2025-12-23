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
                    <button class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-full text-sm font-semibold">Add Asset</button>
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
                <button class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-full text-sm font-semibold">Audit Trail</button>
            </div>

        </main>
    </div>

    <script>
        $(function() {
            $("body").show();


        });
    </script>
</body>

</html>
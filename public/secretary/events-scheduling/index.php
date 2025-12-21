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

<body class="bg-gray-100 font-sans" style="display: none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include_once '../layout/sidebar.php'; ?>
        <main class="p-6 w-screen">
            <div class="flex justify-between items-center mb-4">
                <div class="flex space-x-2">
                    <input type="text" placeholder="Search" class="border border-gray-300 rounded px-3 py-2 w-48">
                    <button class="bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded-xl text-sm font-semibold">Search Event/Schedule</button>
                </div>
            </div>

            <div class="bg-white border rounded-lg shadow p-6 flex flex-col lg:flex-row space-y-6 lg:space-y-0 lg:space-x-6">

                <!-- Left: Event List -->
                <div class="flex flex-col w-full lg:w-1/3 border rounded p-4 h-[400px] overflow-y-auto scrollbar-hide">
                    <button class="flex items-center space-x-2 text-green-600 font-semibold mb-4">
                        <span class="text-xl">➕</span>
                        <span>NEW EVENT/SCHEDULE</span>
                    </button>

                    <div class="space-y-3">
                        <div class="border p-3 rounded">
                            <span class="text-green-600 font-medium">Jan 12 @ 6pm</span>
                            <p>Lighting the Barangay Court</p>
                        </div>
                        <div class="border p-3 rounded">
                            <span class="text-red-600 font-medium">Jan 21 @ 9am</span>
                            <p>BARANGAY MEETING URGENT</p>
                        </div>
                        <div class="border p-3 rounded">
                            <span class="text-gray-800 font-medium">Jan 22 @ 11am</span>
                            <p>IMMUNIZATION ON PUROK 2</p>
                        </div>
                        <div class="border p-2 rounded text-sm">Feb 1 @ 9am</div>
                    </div>
                </div>

                <!-- Right: Calendar -->
                <div class="flex-1 border rounded p-4">
                    <div class="grid grid-cols-7 text-center text-gray-500 text-xs mb-2">
                        <div>MON</div>
                        <div>TUE</div>
                        <div>WED</div>
                        <div>THU</div>
                        <div>FRI</div>
                        <div>SAT</div>
                        <div>SUN</div>
                    </div>
                    <div class="grid grid-cols-7 gap-2 text-center text-gray-700 text-sm">
                        <!-- Example days -->
                        <div class="py-2">26</div>
                        <div class="py-2">27</div>
                        <div class="py-2">28</div>
                        <div class="py-2">29</div>
                        <div class="py-2">30</div>
                        <div class="py-2">1</div>
                        <div class="py-2">2</div>
                        <div class="py-2">3</div>
                        <div class="py-2">4</div>
                        <div class="py-2">5</div>
                        <div class="py-2">6</div>
                        <div class="py-2">7</div>
                        <div class="py-2">8</div>
                        <div class="py-2">9</div>
                        <div class="py-2">10</div>
                        <div class="py-2">11</div>
                        <div class="py-2 bg-green-600 text-white rounded-full">12</div>
                        <div class="py-2">13</div>
                        <div class="py-2">14</div>
                        <div class="py-2">15</div>
                        <div class="py-2">16</div>
                        <div class="py-2">17</div>
                        <div class="py-2">18</div>
                        <div class="py-2">19</div>
                        <div class="py-2">20</div>
                        <div class="py-2">21</div>
                        <div class="py-2 bg-red-400 text-white rounded-full">21</div>
                        <div class="py-2 bg-gray-300 rounded-full">22</div>
                        <div class="py-2">23</div>
                        <div class="py-2">24</div>
                        <div class="py-2">25</div>
                        <div class="py-2">26</div>
                        <div class="py-2">27</div>
                        <div class="py-2">28</div>
                        <div class="py-2 bg-gray-300 rounded-full">1</div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-xl text-sm font-semibold">History</button>
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
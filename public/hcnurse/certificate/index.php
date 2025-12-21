<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate - MIS Barangay</title>
    <?php loadAllAssets(); 
    echo showDialogReloadScript(); ?>
</head>

<body class="bg-gray-100" style="display: none;">
    <?php include '../layout/navbar.php'; ?>

    <div class="flex bg-gray-100">
        <?php include '../layout/sidebar.php'; ?>

        <main class="p-6 w-screen">
            <h2 class="text-2xl font-semibold mb-6">Certificate</h2>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php elseif (!empty($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- 🔍 Resident Search -->
            <div class="relative max-w-xl bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-6">
                <label for="residentSearch" class="block text-gray-700 font-medium mb-2">Search Resident</label>
                <input id="residentSearch" type="text"
                    placeholder="Search by name, ID, or address..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-theme-primary">
                <div id="searchResults"
                    class="absolute z-10 mt-1 w-[30rem] bg-white border border-gray-200 rounded-lg shadow-lg hidden"></div>
            </div>

            <!-- 🧾 Resident Info + History -->
            <div id="residentDetails"></div>

            <!-- Dialog -->
            <!-- Dialog Template -->
            <div id="dialog-message" title="" style="display:none;">
                <p id="dialog-text"></p>
            </div>

        </main>
    </div>

    <script>
        $(document).ready(function() {
            // ✅ Show body after assets load
            $("body").show();
            // ✅ If URL has ?id=, load immediately
            const params = new URLSearchParams(window.location.search);
            if (params.has("id")) {
                loadResident(params.get("id"));
            }

            // ✅ Search AJAX
            $("#residentSearch").on("input", function() {
                const query = $(this).val().trim();
                if (query.length < 2) {
                    $("#searchResults").hide();
                    return;
                }

                $.ajax({
                    url: "/certificate/search_residents.php",
                    method: "GET",
                    data: {
                        q: query
                    },
                    success: function(res) {
                        let data = [];
                        try {
                            data = JSON.parse(res);
                        } catch {}

                        if (data.length === 0) {
                            $("#searchResults").html('<div class="p-3 text-sm text-gray-600">No results found</div>').show();
                            return;
                        }

                        const html = data.map(r => `
              <div class="px-4 py-2 hover-theme-light cursor-pointer" data-id="${r.id}">
                <div class="font-medium text-gray-800">${r.first_name} ${r.middle_name ?? ''} ${r.last_name}</div>
                <div class="text-sm text-gray-600">${r.address}</div>
              </div>
            `).join('');
                        $("#searchResults").html(html).show();
                    }
                });
            });

            // ✅ Select Resident (AJAX reload content)
            $(document).on("click", "#searchResults div[data-id]", function() {
                const id = $(this).data("id");
                $("#searchResults").hide();
                $("#residentDetails").html('<div class="text-center text-gray-500 py-6">Loading resident data...</div>');
                loadResident(id);
                history.pushState({}, "", "?id=" + id);
            });

            // ✅ Hide dropdown on outside click
            $(document).click(function(e) {
                if (!$(e.target).closest("#residentSearch, #searchResults").length)
                    $("#searchResults").hide();
            });

            // ✅ Load resident info + table
            function loadResident(id) {
                $.ajax({
                    url: "/certificate/load_resident_details.php",
                    method: "GET",
                    data: {
                        id
                    },
                    success: function(html) {
                        $("#residentDetails").html(html);
                        
                        // Destroy existing DataTable instance if it exists
                        if ($.fn.DataTable.isDataTable('#historyTable')) {
                            $('#historyTable').DataTable().destroy();
                        }
                        
                        // Wait a bit for DOM to be ready, then initialize DataTable
                        setTimeout(function() {
                            const $table = $('#historyTable');
                            if ($table.length) {
                                // Check if table has actual data rows (not just the "no data" row)
                                const $rows = $table.find('tbody tr');
                                const hasData = $rows.length > 0 && !$rows.first().find('td[colspan]').length;
                                
                                if (hasData) {
                                    // Verify all rows have the correct number of cells (5 columns)
                                    let allRowsValid = true;
                                    $rows.each(function() {
                                        const cellCount = $(this).find('td').not('[colspan]').length;
                                        if (cellCount !== 5) {
                                            allRowsValid = false;
                                            return false; // break
                                        }
                                    });
                                    
                                    if (allRowsValid) {
                                        $table.DataTable({
                                            pageLength: 10,
                                            order: [[3, 'desc']], // Sort by Requested At column (4th column, index 3)
                                            columnDefs: [
                                                { orderable: false, targets: 4 } // Disable sorting on Actions column
                                            ]
                                        });
                                    }
                                }
                            }
                        }, 100);
                    },
                    error: function() {
                        $("#residentDetails").html('<div class="text-center text-red-500 py-6">Failed to load resident data.</div>');
                    }
                });
            }
        });
    </script>
</body>

</html>
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

<body class="bg-light" style="display: none;">
    <?php include '../navbar.php'; ?>

    <div class="d-flex bg-light">
        <?php include '../sidebar.php'; ?>

        <main class="p-4 w-100">
            <h2 class="h3 fw-semibold mb-4">Certificate</h2>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php elseif (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- 🔍 Resident Search -->
            <div class="position-relative w-100 bg-white p-4 rounded-3 shadow-sm border mb-4">
                <label for="residentSearch" class="form-label fw-medium">Search Resident</label>
                <input id="residentSearch" type="text"
                    placeholder="Search by name, ID, or address..."
                    class="form-control">
                <div id="searchResults"
                    class="position-absolute mt-1 start-0 end-0 bg-white border rounded-3 shadow-lg d-none" style="z-index: 1050;"></div>
            </div>

            <!-- 🧾 Resident Info + History -->
            <div id="residentDetails"></div>


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
                    $("#searchResults").addClass('d-none');
                    return;
                }

                $.ajax({
                    url: "/certificate/search_residents",
                    method: "GET",
                    data: {
                        q: query
                    },
                    success: function(res) {
                        let data = [];
                        try {
                            data = res;
                        } catch {}

                        if (data.length === 0) {
                            $("#searchResults").html('<div class="p-3 small text-muted">No results found</div>').removeClass('d-none');
                            return;
                        }

                        const html = data.map(r => `
              <div class="px-4 py-2 search-result-item" data-id="${r.id}" style="cursor: pointer;">
                <div class="fw-medium text-dark">${r.first_name} ${r.middle_name ?? ''} ${r.last_name}</div>
                <div class="small text-muted">${r.address}</div>
              </div>
            `).join('');
                        $("#searchResults").html(html).removeClass('d-none');
                    }
                });
            });

            // ✅ Select Resident (AJAX reload content)
            $(document).on("click", "#searchResults div[data-id]", function() {
                const id = $(this).data("id");
                $("#searchResults").addClass('d-none');
                $("#residentDetails").html('<div class="text-center text-muted py-4">Loading resident data...</div>');
                loadResident(id);
                history.pushState({}, "", "?id=" + id);
            });

            // ✅ Hide dropdown on outside click
            $(document).click(function(e) {
                if (!$(e.target).closest("#residentSearch, #searchResults").length)
                    $("#searchResults").addClass('d-none');
            });
            
            // Add hover effect for search results
            $(document).on('mouseenter', '.search-result-item', function() {
                $(this).css('background-color', '#e7f3ff');
            }).on('mouseleave', '.search-result-item', function() {
                $(this).css('background-color', '');
            });

            // ✅ Load resident info + table
            function loadResident(id) {
                $.ajax({
                    url: "/certificate/load_resident_details",
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
                        $("#residentDetails").html('<div class="text-center text-danger py-4">Failed to load resident data.</div>');
                    }
                });
            }
        });
    </script>
</body>

</html>
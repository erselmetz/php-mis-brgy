<?php
require_once '../../includes/app.php';
requireLogin();

$role = $_SESSION['role'] ?? '';

// Staff/Admin Dashboard Data
$totalPopulation = 0;
$maleCount = 0;
$femaleCount = 0;
$seniorCount = 0;
$pwdCount = 0;
$voter_registered_count = 0;
$voter_unregistered_count = 0;

// Tanod Dashboard Data
$pendingCount = 0;
$underInvestigationCount = 0;
$resolvedCount = 0;
$dismissedCount = 0;
$totalBlotterCases = 0;

if ($role === 'staff' || $role === 'admin') {
    /**
     * Fetch resident statistics using a single optimized query
     * This query calculates all statistics in one pass for better performance
     * No user input is used, so prepared statements aren't strictly necessary,
     * but we use them for consistency and best practices
     */
    $sql = "
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) AS male_count,
            SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) AS female_count,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 THEN 1 ELSE 0 END) AS senior_count,
            SUM(CASE WHEN disability_status = 'Yes' THEN 1 ELSE 0 END) AS pwd_count,
            SUM(CASE WHEN voter_status = 'Yes' THEN 1 ELSE 0 END) AS voter_registered_count,
            SUM(CASE WHEN voter_status = 'No' THEN 1 ELSE 0 END) AS voter_unregistered_count
        FROM residents
    ";
    
    // Execute query and handle errors gracefully
    $result = $conn->query($sql);
    if ($result === false) {
        // Log error for debugging but don't expose to user
        error_log('Dashboard query error: ' . $conn->error);
        // Set default values to prevent undefined variable errors
        $totalPopulation = 0;
        $maleCount = 0;
        $femaleCount = 0;
        $seniorCount = 0;
        $pwdCount = 0;
        $voter_registered_count = 0;
        $voter_unregistered_count = 0;
    } elseif ($result && $row = $result->fetch_assoc()) {
        // Safely convert to integers (default to 0 if null)
        $totalPopulation = (int)($row['total'] ?? 0);
        $maleCount = (int)($row['male_count'] ?? 0);
        $femaleCount = (int)($row['female_count'] ?? 0);
        $seniorCount = (int)($row['senior_count'] ?? 0);
        $pwdCount = (int)($row['pwd_count'] ?? 0);
        $voter_registered_count = (int)($row['voter_registered_count'] ?? 0);
        $voter_unregistered_count = (int)($row['voter_unregistered_count'] ?? 0);
    }
}

if ($role === 'tanod' || $role === 'admin') {
    /**
     * Fetch blotter statistics using a single optimized query
     * Calculates all status counts in one database query for efficiency
     */
    $sql = "
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) AS under_investigation_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed_count
        FROM blotter
    ";
    
    // Execute query and handle errors gracefully
    $result = $conn->query($sql);
    if ($result === false) {
        // Log error for debugging but don't expose to user
        error_log('Blotter dashboard query error: ' . $conn->error);
        // Set default values to prevent undefined variable errors
        $totalBlotterCases = 0;
        $pendingCount = 0;
        $underInvestigationCount = 0;
        $resolvedCount = 0;
        $dismissedCount = 0;
    } elseif ($result && $row = $result->fetch_assoc()) {
        // Safely convert to integers (default to 0 if null)
        $totalBlotterCases = (int)($row['total'] ?? 0);
        $pendingCount = (int)($row['pending_count'] ?? 0);
        $underInvestigationCount = (int)($row['under_investigation_count'] ?? 0);
        $resolvedCount = (int)($row['resolved_count'] ?? 0);
        $dismissedCount = (int)($row['dismissed_count'] ?? 0);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - MIS Barangay</title>
  <?php loadAllStyles(); ?>
</head>
<body class="bg-gray-100">
  <?php include '../layout/navbar.php'; ?>
  <div class="flex bg-gray-100">
    <?php include '../layout/sidebar.php'; ?>
    <main class="p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Dashboard</h2>
      
      <?php if ($role === 'staff' || $role === 'admin'): ?>
        <!-- Staff/Admin Dashboard -->
        
        <!-- Population Report -->
        <div class="bg-white p-6 shadow-sm rounded-xl mb-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Population Report</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Total -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="total">
              <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                <i data-lucide="users" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Total</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $totalPopulation ?></h2>
              </div>
            </div>
            <!-- Male -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="male">
              <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                <i data-lucide="user-round" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Male</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $maleCount ?></h2>
              </div>
            </div>
            <!-- Female -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="female">
              <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                <i data-lucide="circle-user-round" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Female</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $femaleCount ?></h2>
              </div>
            </div>
          </div>
        </div>

        <!-- Senior Citizen / PWD -->
        <div class="bg-white p-6 shadow-sm rounded-xl mb-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Senior Citizen / PWD</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
            <!-- Seniors -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="seniors">
              <div class="p-3 bg-purple-100 rounded-full text-purple-600">
                <i data-lucide="user-round" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Seniors</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $seniorCount ?></h2>
              </div>
            </div>
            <!-- PWDs -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="pwd">
              <div class="p-3 bg-cyan-100 rounded-full text-cyan-600">
                <i data-lucide="wheelchair" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">PWDs</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $pwdCount ?></h2>
              </div>
            </div>
          </div>
        </div>

        <!-- Voter's Report -->
        <div class="bg-white p-6 shadow-sm rounded-xl mb-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Voter's Report</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Registered Voters -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="voter_registered">
              <div class="p-3 bg-indigo-100 rounded-full text-indigo-600">
                <i data-lucide="id-card" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Registered Voters</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $voter_registered_count ?></h2>
              </div>
            </div>
            <!-- Unregistered Voters -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="voter_unregistered">
              <div class="p-3 bg-red-100 rounded-full text-red-600">
                <i data-lucide="x-circle" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Unregistered Voters</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $voter_unregistered_count ?></h2>
              </div>
            </div>
          </div>
        </div>
        
      <?php endif; ?>
      
      <?php if ($role === 'tanod' || $role === 'admin'): ?>
        <!-- Tanod Dashboard -->
        <div class="bg-white p-6 shadow-sm rounded-xl mb-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Blotter Cases Summary</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Cases -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200">
              <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                <i data-lucide="file-text" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Total Cases</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $totalBlotterCases ?></h2>
              </div>
            </div>
            <!-- Pending -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="pending">
              <div class="p-3 bg-yellow-100 rounded-full text-yellow-600">
                <i data-lucide="clock" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Pending</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $pendingCount ?></h2>
              </div>
            </div>
            <!-- Under Investigation -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="under_investigation">
              <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                <i data-lucide="search" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Under Investigation</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $underInvestigationCount ?></h2>
              </div>
            </div>
            <!-- Resolved -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="resolved">
              <div class="p-3 bg-green-100 rounded-full text-green-600">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Resolved</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $resolvedCount ?></h2>
              </div>
            </div>
            <!-- Dismissed -->
            <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition" data-filter="dismissed">
              <div class="p-3 bg-gray-100 rounded-full text-gray-600">
                <i data-lucide="x-circle" class="w-6 h-6"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-500">Dismissed</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $dismissedCount ?></h2>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <!-- Data Dialog -->
  <div id="dataDialog" title="Data Details" style="display:none;">
    <div class="p-4">
      <div id="dataTableContainer">
        <table id="dataTable" class="display w-full text-sm">
          <thead>
            <tr id="tableHeaders"></tr>
          </thead>
          <tbody id="tableBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <?php loadAllScripts(); ?>
  <script>
    $(document).ready(function() {
      // Initialize DataTable dialog - Modernized
      $("#dataDialog").dialog({
        autoOpen: false,
        modal: true,
        width: 1000,
        height: 650,
        resizable: true,
        classes: {
          'ui-dialog': 'rounded-lg shadow-lg',
          'ui-dialog-titlebar': 'bg-blue-600 text-white rounded-t-lg',
          'ui-dialog-title': 'font-semibold',
          'ui-dialog-buttonpane': 'bg-gray-50 rounded-b-lg'
        },
        position: { my: "center", at: "center", of: window }
      });

      // Handle card clicks
      $('[data-filter]').on('click', function() {
        const filter = $(this).data('filter');
        const role = '<?= $role ?>';
        
        // Skip non-clickable cards
        if (!$(this).hasClass('cursor-pointer')) {
          return;
        }
        
        loadFilteredData(filter, role);
      });

      function loadFilteredData(filter, role) {
        // Show loading
        $("#dataDialog").dialog("open");
        $("#dataTableContainer").html('<div class="text-center p-8">Loading data...</div>');
        
        $.ajax({
          url: '/api/dashboard_data.php',
          method: 'GET',
          data: { filter: filter },
          dataType: 'json',
          success: function(response) {
            console.log('API Response:', response);
            if (response && response.status === 'success') {
              if (response.data && response.data.length > 0) {
                displayData(response.data, filter, role);
              } else {
                $("#dataTableContainer").html('<div class="text-center p-8 text-gray-500">No data found for this filter.</div>');
              }
            } else {
              $("#dataTableContainer").html('<div class="text-center p-8 text-red-500">' + (response.message || 'Error loading data. Please try again.') + '</div>');
            }
          },
          error: function(xhr, status, error) {
            console.error('AJAX Error:', {
              status: xhr.status,
              statusText: xhr.statusText,
              error: error,
              responseText: xhr.responseText
            });
            let errorMsg = 'Error loading data. Please try again later.';
            if (xhr.responseText) {
              try {
                const errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse.message) {
                  errorMsg = errorResponse.message;
                }
              } catch (e) {
                // Not JSON, use default message
              }
            }
            $("#dataTableContainer").html('<div class="text-center p-8 text-red-500">' + errorMsg + '</div>');
          }
        });
      }

      function displayData(data, filter, role) {
        try {
          if (!data || data.length === 0) {
            $("#dataTableContainer").html('<div class="text-center p-8 text-gray-500">No data found for this filter.</div>');
            return;
          }
          
          // Determine data type based on the first item's structure
          const isBlotterData = data[0].hasOwnProperty('case_number') || data[0].hasOwnProperty('complainant_name');
          const isResidentData = data[0].hasOwnProperty('first_name') || data[0].hasOwnProperty('last_name');
          
          let headers = [];
          let tableHtml = '<table id="dataTable" class="display w-full text-sm"><thead><tr>';
          
          if (isBlotterData) {
            // Blotter data
            headers = ['Case Number', 'Complainant', 'Respondent', 'Incident Date', 'Location', 'Status'];
            tableHtml += '<th>Case Number</th><th>Complainant</th><th>Respondent</th><th>Incident Date</th><th>Location</th><th>Status</th>';
          } else if (isResidentData) {
            // Resident data
            headers = ['Name', 'Gender', 'Birthdate', 'Age', 'Address', 'Contact'];
            tableHtml += '<th>Name</th><th>Gender</th><th>Birthdate</th><th>Age</th><th>Address</th><th>Contact</th>';
          } else {
            console.error('Unknown data type:', data[0]);
            $("#dataTableContainer").html('<div class="text-center p-8 text-red-500">Unknown data format.</div>');
            return;
          }
          
          tableHtml += '</tr></thead><tbody>';
          
          data.forEach(function(item) {
            tableHtml += '<tr>';
            if (isBlotterData) {
              tableHtml += '<td><a href="/blotter/view.php?id=' + item.id + '" class="text-blue-600 hover:underline">' + escapeHtml(item.case_number || '') + '</a></td>';
              tableHtml += '<td>' + escapeHtml(item.complainant_name || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.respondent_name || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.incident_date || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.incident_location || '') + '</td>';
              tableHtml += '<td><span class="px-2 py-1 rounded text-xs font-semibold ' + getStatusClass(item.status) + '">' + escapeHtml((item.status || '').replace('_', ' ')) + '</span></td>';
            } else if (isResidentData) {
              const fullName = escapeHtml((item.first_name || '') + ' ' + (item.middle_name || '') + ' ' + (item.last_name || '') + ' ' + (item.suffix || '')).trim();
              tableHtml += '<td><a href="/resident/view.php?id=' + item.id + '" class="text-blue-600 hover:underline">' + fullName + '</a></td>';
              tableHtml += '<td>' + escapeHtml(item.gender || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.birthdate || '') + '</td>';
              tableHtml += '<td>' + calculateAge(item.birthdate) + '</td>';
              tableHtml += '<td>' + escapeHtml(item.address || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.contact_no || '') + '</td>';
            }
            tableHtml += '</tr>';
          });
          
          tableHtml += '</tbody></table>';
          $("#dataTableContainer").html(tableHtml);
          
          // Initialize DataTable
          if ($.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable().destroy();
          }
          $('#dataTable').DataTable({
            pageLength: 25,
            order: isBlotterData ? [[0, 'desc']] : [[0, 'asc']]
          });
        } catch (error) {
          console.error('Error displaying data:', error);
          $("#dataTableContainer").html('<div class="text-center p-8 text-red-500">Error displaying data: ' + error.message + '</div>');
        }
      }

      function escapeHtml(text) {
        const map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return (text || '').toString().replace(/[&<>"']/g, m => map[m]);
      }

      function getStatusClass(status) {
        const classes = {
          'pending': 'bg-yellow-100 text-yellow-800',
          'under_investigation': 'bg-blue-100 text-blue-800',
          'resolved': 'bg-green-100 text-green-800',
          'dismissed': 'bg-gray-100 text-gray-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
      }

      function calculateAge(birthdate) {
        if (!birthdate) return '';
        const today = new Date();
        const birth = new Date(birthdate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
          age--;
        }
        return age;
      }
    });
  </script>
</body>
</html>

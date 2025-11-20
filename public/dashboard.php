<?php
require_once '../includes/app.php';
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
    // Fetch resident statistics
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
    
    $result = $conn->query($sql);
    if ($result === false) {
        error_log('Dashboard query error: ' . $conn->error);
    } elseif ($result && $row = $result->fetch_assoc()) {
        $totalPopulation = (int)$row['total'];
        $maleCount = (int)$row['male_count'];
        $femaleCount = (int)$row['female_count'];
        $seniorCount = (int)$row['senior_count'];
        $pwdCount = (int)$row['pwd_count'];
        $voter_registered_count = (int)$row['voter_registered_count'];
        $voter_unregistered_count = (int)$row['voter_unregistered_count'];
    }
}

if ($role === 'tanod' || $role === 'admin') {
    // Fetch blotter statistics
    $sql = "
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) AS under_investigation_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed_count
        FROM blotter
    ";
    
    $result = $conn->query($sql);
    if ($result === false) {
        error_log('Blotter dashboard query error: ' . $conn->error);
    } elseif ($result && $row = $result->fetch_assoc()) {
        $totalBlotterCases = (int)$row['total'];
        $pendingCount = (int)$row['pending_count'];
        $underInvestigationCount = (int)$row['under_investigation_count'];
        $resolvedCount = (int)$row['resolved_count'];
        $dismissedCount = (int)$row['dismissed_count'];
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
<body class="bg-light">
  <?php include './navbar.php'; ?>
  <div class="d-flex bg-light">
    <?php include './sidebar.php'; ?>
    <main class="p-4 w-100">
      <h2 class="h3 fw-semibold mb-4">Dashboard</h2>
      
      <?php if ($role === 'staff' || $role === 'admin'): ?>
        <!-- Staff/Admin Dashboard -->
        
        <!-- Population Report -->
        <div class="bg-white p-4 shadow-sm rounded-3 mb-4 border">
          <h3 class="h6 fw-semibold text-dark mb-3">Population Report</h3>
          <div class="row g-3">
            <!-- Total -->
            <div class="col-12 col-sm-6 col-lg-4">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="total">
                <div class="p-3 bg-primary bg-opacity-10 rounded-circle text-primary">
                  <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Total</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $totalPopulation ?></h2>
                </div>
              </div>
            </div>
            <!-- Male -->
            <div class="col-12 col-sm-6 col-lg-4">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="male">
                <div class="p-3 bg-primary bg-opacity-10 rounded-circle text-primary">
                  <i data-lucide="user-round" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Male</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $maleCount ?></h2>
                </div>
              </div>
            </div>
            <!-- Female -->
            <div class="col-12 col-sm-6 col-lg-4">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="female">
                <div class="p-3 bg-primary bg-opacity-10 rounded-circle text-primary">
                  <i data-lucide="circle-user-round" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Female</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $femaleCount ?></h2>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Senior Citizen / PWD -->
        <div class="bg-white p-4 shadow-sm rounded-3 mb-4 border">
          <h3 class="h6 fw-semibold text-dark mb-3">Senior Citizen / PWD</h3>
          <div class="row g-3">
            <!-- Seniors -->
            <div class="col-12 col-sm-6">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="seniors">
                <div class="p-3 rounded-circle text-white" style="background-color: #e9d5ff;">
                  <i data-lucide="user-round" class="w-6 h-6" style="color: #9333ea;"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Seniors</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $seniorCount ?></h2>
                </div>
              </div>
            </div>
            <!-- PWDs -->
            <div class="col-12 col-sm-6">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="pwd">
                <div class="p-3 bg-info bg-opacity-10 rounded-circle text-info">
                  <i data-lucide="wheelchair" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">PWDs</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $pwdCount ?></h2>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Voter's Report -->
        <div class="bg-white p-4 shadow-sm rounded-3 mb-4 border">
          <h3 class="h6 fw-semibold text-dark mb-3">Voter's Report</h3>
          <div class="row g-3">
            <!-- Registered Voters -->
            <div class="col-12 col-sm-6">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="voter_registered">
                <div class="p-3 rounded-circle" style="background-color: #e0e7ff; color: #4f46e5;">
                  <i data-lucide="id-card" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Registered Voters</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $voter_registered_count ?></h2>
                </div>
              </div>
            </div>
            <!-- Unregistered Voters -->
            <div class="col-12 col-sm-6">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="voter_unregistered">
                <div class="p-3 bg-danger bg-opacity-10 rounded-circle text-danger">
                  <i data-lucide="x-circle" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Unregistered Voters</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $voter_unregistered_count ?></h2>
                </div>
              </div>
            </div>
          </div>
        </div>
        
      <?php endif; ?>
      
      <?php if ($role === 'tanod' || $role === 'admin'): ?>
        <!-- Tanod Dashboard -->
        <div class="bg-white p-4 shadow-sm rounded-3 mb-4 border">
          <h3 class="h6 fw-semibold text-dark mb-3">Blotter Cases Summary</h3>
          <div class="row g-3">
            <!-- Total Cases -->
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border">
                <div class="p-3 bg-primary bg-opacity-10 rounded-circle text-primary">
                  <i data-lucide="file-text" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Total Cases</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $totalBlotterCases ?></h2>
                </div>
              </div>
            </div>
            <!-- Pending -->
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="pending">
                <div class="p-3 bg-warning bg-opacity-10 rounded-circle text-warning">
                  <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Pending</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $pendingCount ?></h2>
                </div>
              </div>
            </div>
            <!-- Under Investigation -->
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="under_investigation">
                <div class="p-3 bg-primary bg-opacity-10 rounded-circle text-primary">
                  <i data-lucide="search" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Under Investigation</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $underInvestigationCount ?></h2>
                </div>
              </div>
            </div>
            <!-- Resolved -->
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="resolved">
                <div class="p-3 bg-success bg-opacity-10 rounded-circle text-success">
                  <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Resolved</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $resolvedCount ?></h2>
                </div>
              </div>
            </div>
            <!-- Dismissed -->
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="d-flex align-items-center p-3 rounded-3 bg-light border" style="cursor: pointer;" data-filter="dismissed">
                <div class="p-3 bg-secondary bg-opacity-10 rounded-circle text-secondary">
                  <i data-lucide="x-circle" class="w-6 h-6"></i>
                </div>
                <div class="ms-3">
                  <p class="small text-muted mb-0">Dismissed</p>
                  <h2 class="h4 fw-bold text-dark mb-0"><?= $dismissedCount ?></h2>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <!-- Data Modal -->
  <div class="modal fade" id="dataModal" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dataModalLabel">Data Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="dataTableContainer">
            <table id="dataTable" class="display w-full text-sm">
              <thead>
                <tr id="tableHeaders"></tr>
              </thead>
              <tbody id="tableBody"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <?php loadAllScripts(); ?>
  <script>
    $(document).ready(function() {
      // Modal will be initialized when opened

      // Handle card clicks
      $('[data-filter]').on('click', function() {
        const filter = $(this).data('filter');
        const role = '<?= $role ?>';
        
        // Check if card has cursor pointer style
        if ($(this).css('cursor') !== 'pointer') {
          return;
        }
        
        loadFilteredData(filter, role);
      });

      function loadFilteredData(filter, role) {
        // Show loading
        const modal = new bootstrap.Modal(document.getElementById('dataModal'));
        modal.show();
        $("#dataTableContainer").html('<div class="text-center p-8">Loading data...</div>');
        
        $.ajax({
          url: '/api/dashboard_data',
          method: 'GET',
          data: { filter: filter },
          dataType: 'json',
          success: function(response) {
            if (response && response.status === 'success') {
              if (response.data && response.data.length > 0) {
                displayData(response.data, filter, role);
              } else {
                $("#dataTableContainer").html('<div class="text-center p-8 text-gray-500">No data found for this filter.</div>');
              }
            } else {
              $("#dataTableContainer").html('<div class="text-center p-4 text-danger">' + (response.message || 'Error loading data. Please try again.') + '</div>');
            }
          },
          error: function(xhr, status, error) {
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
            $("#dataTableContainer").html('<div class="text-center p-4 text-danger">' + errorMsg + '</div>');
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
            headers = ['Name', 'Gender', 'Birthdate', 'Age', 'Address', 'Contact', 'Voter Status'];
            tableHtml += '<th>Name</th><th>Gender</th><th>Birthdate</th><th>Age</th><th>Address</th><th>Contact</th><th>Voter Status</th>';
          } else {
            console.error('Unknown data type:', data[0]);
            $("#dataTableContainer").html('<div class="text-center p-4 text-danger">Unknown data format.</div>');
            return;
          }
          
          tableHtml += '</tr></thead><tbody>';
          
          data.forEach(function(item) {
            tableHtml += '<tr>';
            if (isBlotterData) {
              tableHtml += '<td><a href="/blotter/view?id=' + item.id + '" class="text-primary text-decoration-none">' + escapeHtml(item.case_number || '') + '</a></td>';
              tableHtml += '<td>' + escapeHtml(item.complainant_name || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.respondent_name || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.incident_date || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.incident_location || '') + '</td>';
              tableHtml += '<td><span class="badge ' + getStatusClass(item.status) + '">' + escapeHtml((item.status || '').replace('_', ' ')) + '</span></td>';
            } else if (isResidentData) {
              const fullName = escapeHtml((item.first_name || '') + ' ' + (item.middle_name || '') + ' ' + (item.last_name || '') + ' ' + (item.suffix || '')).trim();
              tableHtml += '<td><a href="/resident/view?id=' + item.id + '" class="text-primary text-decoration-none">' + fullName + '</a></td>';
              tableHtml += '<td>' + escapeHtml(item.gender || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.birthdate || '') + '</td>';
              tableHtml += '<td>' + calculateAge(item.birthdate) + '</td>';
              tableHtml += '<td>' + escapeHtml(item.address || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.contact_no || '') + '</td>';
              tableHtml += '<td>' + escapeHtml(item.voter_status || '') + '</td>';
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
          $("#dataTableContainer").html('<div class="text-center p-8 text-danger">Error displaying data: ' + error.message + '</div>');
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
          'pending': 'bg-warning bg-opacity-10 text-warning',
          'under_investigation': 'bg-primary bg-opacity-10 text-primary',
          'resolved': 'bg-success bg-opacity-10 text-success',
          'dismissed': 'bg-secondary bg-opacity-10 text-secondary'
        };
        return classes[status] || 'bg-secondary bg-opacity-10 text-secondary';
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

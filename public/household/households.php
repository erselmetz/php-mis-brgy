<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access

// Form submission handled via API

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Households - MIS Barangay</title>
  <?php loadAllAssets(); ?>
</head>

<body class="bg-light" style="display:none;">

  <?php include_once '../navbar.php'; ?>

  <div class="d-flex bg-light">
    <?php include_once '../sidebar.php'; ?>
    <main class="p-4 w-100">
      <h2 class="h3 fw-semibold mb-4">Household List</h2>
      <!-- ✅ Add Button -->
      <div class="p-4">
        <button id="openHouseholdModalBtn"
          class="btn btn-primary fw-semibold px-4 py-2 shadow">
          ➕ Add Household
        </button>
      </div>
      <!-- Households Table -->
      <div class="bg-white rounded-3 shadow-sm border overflow-hidden p-4">
        <table id="householdsTable" class="display w-100 small border rounded-3">
          <thead class="bg-light text-dark">
            <tr>
              <th class="p-2 text-start">Household No.</th>
              <th class="p-2 text-start">Head Name</th>
              <th class="p-2 text-start">Address</th>
              <th class="p-2 text-start">Total Members</th>
              <th class="p-2 text-start">Created At</th>
              <th class="p-2 text-start">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Data loaded via API -->
          </tbody>
        </table>
      </div>
    </main>
  </div>
  <!-- ✅ Hidden Modal (jQuery UI Dialog) -->
  <div id="addHouseholdModal" title="Add New Household">
    <form id="addHouseholdForm" class="overflow-y-auto" style="max-height: 70vh;">
      <div id="householdAlertContainer"></div>

      <!-- Household Number -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Household Number</label>
        <input type="text" name="household_no" placeholder="Enter household number (e.g., HH-2024-001)" required
          class="form-control">
      </div>

      <!-- Head Name -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Head of Household Name</label>
        <input type="text" name="head_name" placeholder="Enter head of household name" required
          class="form-control">
      </div>

      <!-- Address -->
      <div class="mb-3">
        <label class="form-label small fw-medium">Address</label>
        <textarea name="address" rows="3" placeholder="Enter complete address" required
          class="form-control"></textarea>
      </div>

      <!-- Submit -->
      <div class="pt-2">
        <button type="submit"
          class="w-100 btn btn-primary py-2 fw-semibold">
          Add Household
        </button>
      </div>
    </form>

  </div>
  <script>
    $(function() {
      $("body").show();
      
      let householdsTable = $("#householdsTable").DataTable({
        ajax: {
          url: '/api/v1/households',
          dataSrc: function(json) {
            if (json.status === 'success' && json.data) {
              return json.data;
            }
            return [];
          },
          error: function(xhr, error, thrown) {
            console.error('Error loading households:', error);
            $('#householdsTable tbody').html('<tr><td colspan="6" class="p-4 text-center text-muted">Error loading data. Please refresh.</td></tr>');
          }
        },
        columns: [
          {
            data: 'household_no',
            render: function(data, type, row) {
              return '<a href="/household/view?id=' + row.id + '" class="text-primary text-decoration-none">' + 
                     (data || '') + '</a>';
            }
          },
          { data: 'head_name' },
          { data: 'address' },
          { data: 'member_count', defaultContent: '0' },
          { data: 'created_at' },
          {
            data: null,
            render: function(data, type, row) {
              return '<a href="/household/view?id=' + row.id + '" class="text-primary text-decoration-none me-2">View</a>';
            }
          }
        ],
        order: [[0, 'desc']],
        pageLength: 25
      });
      
      function showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show mb-3" role="alert">' +
          escapeHtml(message) +
          '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
          '</div>';
        $('#householdAlertContainer').html(alertHtml);
        setTimeout(function() {
          $('#householdAlertContainer .alert').fadeOut(function() {
            $(this).remove();
          });
        }, 5000);
      }
      
      function escapeHtml(text) {
        const map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
      }
      
      // Handle form submission
      $('#addHouseholdForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
          action: 'create',
          household_no: $('[name="household_no"]').val().trim(),
          head_name: $('[name="head_name"]').val().trim(),
          address: $('[name="address"]').val().trim()
        };
        
        if (!formData.household_no || !formData.head_name || !formData.address) {
          showAlert('Please fill in all required fields.', 'error');
          return;
        }
        
        $.ajax({
          url: '/api/v1/households',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify(formData),
          success: function(response) {
            if (response.status === 'success') {
              showAlert('Household added successfully!', 'success');
              $('#addHouseholdForm')[0].reset();
              $("#addHouseholdModal").dialog("close");
              householdsTable.ajax.reload();
            } else {
              showAlert(response.message || 'Error adding household', 'error');
            }
          },
          error: function(xhr) {
            let errorMsg = 'Error adding household';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMsg = xhr.responseJSON.message;
            }
            showAlert(errorMsg, 'error');
          }
        });
      });
      
      // Initialize modal
      $("#addHouseholdModal").dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        height: 400,
        resizable: true,
        classes: {
          'ui-dialog': 'rounded shadow-lg',
          'ui-dialog-titlebar': 'dialog-titlebar-primary rounded-top',
          'ui-dialog-title': 'fw-semibold',
          'ui-dialog-buttonpane': 'dialog-buttonpane-light rounded-bottom'
        },
        show: {
          effect: "fadeIn",
          duration: 200
        },
        hide: {
          effect: "fadeOut",
          duration: 200
        },
        open: function() {
          $('.ui-dialog-buttonpane button').addClass('btn btn-primary');
          $('#addHouseholdForm')[0].reset();
          $('#householdAlertContainer').empty();
        }
      });
      
      $("#openHouseholdModalBtn").on("click", function() {
        $("#addHouseholdModal").dialog("open");
      });
    })
  </script>
</body>

</html>


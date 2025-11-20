<?php
require_once '../../includes/app.php';
requireTanod(); // Only Tanod (and admin) can access
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blotter Management - MIS Barangay</title>
    <?php loadAllAssets(); ?>
</head>
<body class="bg-light" style="display:none;">
    <?php include '../navbar.php'; ?>
    <div class="d-flex bg-light">
        <?php include '../sidebar.php'; ?>
        <main class="p-4 w-100">
            <h2 class="h3 fw-semibold mb-4">Blotter Management</h2>
            
            <div id="alertContainer"></div>
            
            <!-- Add Button -->
            <div class="mb-4">
                <button id="openBlotterModalBtn" class="btn btn-primary fw-semibold px-4 py-2 shadow">
                    ➕ Add New Blotter Case
                </button>
            </div>
            
            <!-- Blotter Table -->
            <div class="bg-white rounded-3 shadow-sm border overflow-hidden p-4">
                <table id="blotterTable" class="display w-100 small border rounded-3">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="p-2 text-start">Case Number</th>
                            <th class="p-2 text-start">Complainant</th>
                            <th class="p-2 text-start">Respondent</th>
                            <th class="p-2 text-start">Incident Date</th>
                            <th class="p-2 text-start">Location</th>
                            <th class="p-2 text-start">Status</th>
                            <th class="p-2 text-start">Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via API -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Add Blotter Modal -->
    <div id="addBlotterModal" title="Add New Blotter Case">
        <form id="addBlotterForm" style="max-height: 70vh; overflow-y: auto;">
            
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-medium">Complainant Name *</label>
                    <input type="text" name="complainant_name" required class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-medium">Complainant Contact</label>
                    <input type="text" name="complainant_contact" class="form-control">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-medium">Complainant Address</label>
                <textarea name="complainant_address" rows="2" class="form-control"></textarea>
            </div>
            
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-medium">Respondent Name *</label>
                    <input type="text" name="respondent_name" required class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-medium">Respondent Contact</label>
                    <input type="text" name="respondent_contact" class="form-control">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-medium">Respondent Address</label>
                <textarea name="respondent_address" rows="2" class="form-control"></textarea>
            </div>
            
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-medium">Incident Date *</label>
                    <input type="date" name="incident_date" required class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-medium">Incident Time</label>
                    <input type="time" name="incident_time" class="form-control">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-medium">Incident Location *</label>
                <input type="text" name="incident_location" required class="form-control">
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-medium">Incident Description *</label>
                <textarea name="incident_description" rows="4" required class="form-control"></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-medium">Status</label>
                <select name="status" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="under_investigation">Under Investigation</option>
                    <option value="resolved">Resolved</option>
                    <option value="dismissed">Dismissed</option>
                </select>
            </div>
            
            <div class="pt-2">
                <button type="submit" class="w-100 btn btn-primary py-2 fw-semibold">
                    Add Blotter Case
                </button>
            </div>
        </form>
    </div>
    
    <script>
        $(function() {
            $('body').show();
            
            let blotterTable = $('#blotterTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                ajax: {
                    url: '/api/v1/blotter',
                    dataSrc: function(json) {
                        if (json.status === 'success' && json.data) {
                            return json.data;
                        }
                        return [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Error loading blotter data:', error);
                        $('#blotterTable tbody').html('<tr><td colspan="7" class="p-4 text-center text-muted">Error loading data. Please refresh the page.</td></tr>');
                    }
                },
                columns: [
                    {
                        data: 'case_number',
                        render: function(data, type, row) {
                            return '<a href="/blotter/view?id=' + row.id + '" class="text-primary text-decoration-none fw-semibold">' + 
                                   (data || '') + '</a>';
                        }
                    },
                    { data: 'complainant_name' },
                    { data: 'respondent_name' },
                    { data: 'incident_date' },
                    { data: 'incident_location' },
                    {
                        data: 'status',
                        render: function(data) {
                            const statusColors = {
                                'pending': 'bg-warning bg-opacity-10 text-warning',
                                'under_investigation': 'bg-primary bg-opacity-10 text-primary',
                                'resolved': 'bg-success bg-opacity-10 text-success',
                                'dismissed': 'bg-secondary bg-opacity-10 text-secondary'
                            };
                            const statusColor = statusColors[data] || 'bg-secondary bg-opacity-10 text-secondary';
                            const displayName = data ? data.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '';
                            return '<span class="badge ' + statusColor + '">' + displayName + '</span>';
                        }
                    },
                    { data: 'created_by_name', defaultContent: 'N/A' }
                ]
            });
            
            // Show alert function
            function showAlert(message, type) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show mb-4" role="alert">' +
                    htmlspecialchars(message) +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
                $('#alertContainer').html(alertHtml);
                setTimeout(function() {
                    $('#alertContainer .alert').fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            function htmlspecialchars(str) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return str.replace(/[&<>"']/g, function(m) { return map[m]; });
            }
            
            // Handle form submission
            $('#addBlotterForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'create',
                    complainant_name: $('[name="complainant_name"]').val().trim(),
                    complainant_address: $('[name="complainant_address"]').val().trim(),
                    complainant_contact: $('[name="complainant_contact"]').val().trim(),
                    respondent_name: $('[name="respondent_name"]').val().trim(),
                    respondent_address: $('[name="respondent_address"]').val().trim(),
                    respondent_contact: $('[name="respondent_contact"]').val().trim(),
                    incident_date: $('[name="incident_date"]').val(),
                    incident_time: $('[name="incident_time"]').val() || null,
                    incident_location: $('[name="incident_location"]').val().trim(),
                    incident_description: $('[name="incident_description"]').val().trim(),
                    status: $('[name="status"]').val() || 'pending'
                };
                
                // Validation
                if (!formData.complainant_name || !formData.respondent_name || !formData.incident_date || 
                    !formData.incident_location || !formData.incident_description) {
                    showAlert('Please fill in all required fields.', 'error');
                    return;
                }
                
                $.ajax({
                    url: '/api/v1/blotter',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert('Blotter case added successfully. Case Number: ' + response.data.case_number, 'success');
                            $('#addBlotterForm')[0].reset();
                            $("#addBlotterModal").dialog("close");
                            blotterTable.ajax.reload();
                        } else {
                            showAlert(response.message || 'Error adding blotter case', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error adding blotter case';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        showAlert(errorMsg, 'error');
                    }
                });
            });
            
            $("#addBlotterModal").dialog({
                autoOpen: false,
                modal: true,
                width: 700,
                height: 600,
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
                    $('#addBlotterForm')[0].reset();
                }
            });
            
            $("#openBlotterModalBtn").on("click", function() {
                $("#addBlotterModal").dialog("open");
            });
        });
    </script>
</body>
</html>


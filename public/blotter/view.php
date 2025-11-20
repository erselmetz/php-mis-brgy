<?php
require_once '../../includes/app.php';
requireTanod(); // Only Tanod (and admin) can access

$id = intval($_GET['id'] ?? 0);

if ($id === 0) {
    header("Location: /blotter");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Blotter Case - MIS Barangay</title>
    <?php loadAllAssets(); ?>
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .info-section {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .info-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .info-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-size: 1rem;
            color: #212529;
            margin-bottom: 1rem;
        }
        .description-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../navbar.php'; ?>
    <div class="d-flex bg-light">
        <?php include '../sidebar.php'; ?>
        <main class="p-4 w-100">
            <div class="mb-4">
                <a href="/blotter" class="d-inline-flex align-items-center text-primary mb-3 text-decoration-none">
                    <svg class="me-1" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Blotter List
                </a>
            </div>
            
            <h1 class="h3 mb-4">Blotter Case Details</h1>
            
            <div id="alertContainer"></div>
            
            <div id="blotterDetails" class="text-center text-muted py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2">Loading blotter details...</div>
            </div>
            
            <!-- Update Status Form -->
            <div id="updateStatusForm" class="card shadow-sm mb-4" style="display: none;">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Update Case Status</h5>
                </div>
                <div class="card-body">
                    <form id="statusUpdateForm">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="statusSelect" required class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="under_investigation">Under Investigation</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="dismissed">Dismissed</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Resolved Date</label>
                                <input type="date" name="resolved_date" id="resolvedDate" class="form-control">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Resolution Notes</label>
                            <textarea name="resolution" id="resolutionNotes" rows="4" class="form-control"></textarea>
                        </div>
                        
                        <div>
                            <button type="submit" class="btn btn-primary">
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        $(function() {
            const blotterId = <?= $id ?>;
            let blotterData = null;
            
            // Show alert function
            function showAlert(message, type) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                    escapeHtml(message) +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';
                $('#alertContainer').html(alertHtml);
                setTimeout(function() {
                    $('#alertContainer .alert').fadeOut(function() {
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
            
            function formatDate(dateStr) {
                if (!dateStr) return 'N/A';
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }
            
            function formatDateTime(dateStr) {
                if (!dateStr) return 'N/A';
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric', 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
            }
            
            function formatTime(timeStr) {
                if (!timeStr) return '';
                const [hours, minutes] = timeStr.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const displayHour = hour % 12 || 12;
                return displayHour + ':' + minutes + ' ' + ampm;
            }
            
            // Load blotter details
            function loadBlotterDetails() {
                $.ajax({
                    url: '/api/blotter?id=' + blotterId,
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 'success' && response.data) {
                            blotterData = response.data;
                            renderBlotterDetails(blotterData);
                            $('#updateStatusForm').show();
                        } else {
                            $('#blotterDetails').html(
                                '<div class="alert alert-danger">Blotter case not found.</div>'
                            );
                            setTimeout(function() {
                                window.location.href = '/blotter';
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 404) {
                            $('#blotterDetails').html(
                                '<div class="alert alert-danger">Blotter case not found.</div>'
                            );
                            setTimeout(function() {
                                window.location.href = '/blotter';
                            }, 2000);
                        } else {
                            $('#blotterDetails').html(
                                '<div class="alert alert-danger">Error loading blotter details.</div>'
                            );
                        }
                    }
                });
            }
            
            function renderBlotterDetails(blotter) {
                const statusClasses = {
                    'pending': 'status-badge bg-warning text-dark',
                    'under_investigation': 'status-badge bg-info text-white',
                    'resolved': 'status-badge bg-success text-white',
                    'dismissed': 'status-badge bg-secondary text-white'
                };
                const statusClass = statusClasses[blotter.status] || 'status-badge bg-secondary text-white';
                const statusDisplay = blotter.status ? 
                    blotter.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '';
                
                let html = '<div class="card shadow-sm mb-4">' +
                    '<div class="card-body">' +
                    '<div class="row g-4">' +
                    // Case Information Column
                    '<div class="col-12 col-lg-6">' +
                    '<div class="info-section">' +
                    '<h5 class="mb-3">Case Information</h5>' +
                    '<div class="info-label">Case Number</div>' +
                    '<div class="info-value fw-bold">' + escapeHtml(blotter.case_number || '') + '</div>' +
                    '<div class="info-label">Status</div>' +
                    '<div class="info-value">' +
                    '<span class="' + statusClass + '">' + statusDisplay + '</span>' +
                    '</div>' +
                    '<div class="info-label">Created By</div>' +
                    '<div class="info-value">' + escapeHtml(blotter.created_by_name || 'N/A') + '</div>' +
                    '<div class="info-label">Date Created</div>' +
                    '<div class="info-value">' + formatDateTime(blotter.created_at) + '</div>' +
                    '</div>' +
                    '</div>' +
                    // Incident Information Column
                    '<div class="col-12 col-lg-6">' +
                    '<div class="info-section">' +
                    '<h5 class="mb-3">Incident Information</h5>' +
                    '<div class="info-label">Incident Date</div>' +
                    '<div class="info-value">' + formatDate(blotter.incident_date) + '</div>';
                
                if (blotter.incident_time) {
                    html += '<div class="info-label">Incident Time</div>' +
                        '<div class="info-value">' + formatTime(blotter.incident_time) + '</div>';
                }
                
                html += '<div class="info-label">Location</div>' +
                    '<div class="info-value">' + escapeHtml(blotter.incident_location || '') + '</div>' +
                    '<div class="info-label">Description</div>' +
                    '<div class="info-value">' +
                    '<div class="description-box">' + escapeHtml(blotter.incident_description || '') + '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                // Complainant and Respondent Information
                html += '<div class="row g-4 mt-2">' +
                    '<div class="col-12 col-lg-6">' +
                    '<div class="card border">' +
                    '<div class="card-body">' +
                    '<h5 class="card-title mb-3">Complainant Information</h5>' +
                    '<div class="info-label">Name</div>' +
                    '<div class="info-value">' + escapeHtml(blotter.complainant_name || '') + '</div>';
                
                if (blotter.complainant_address) {
                    html += '<div class="info-label">Address</div>' +
                        '<div class="info-value">' + escapeHtml(blotter.complainant_address) + '</div>';
                }
                
                if (blotter.complainant_contact) {
                    html += '<div class="info-label">Contact</div>' +
                        '<div class="info-value">' + escapeHtml(blotter.complainant_contact) + '</div>';
                }
                
                html += '</div></div></div>' +
                    '<div class="col-12 col-lg-6">' +
                    '<div class="card border">' +
                    '<div class="card-body">' +
                    '<h5 class="card-title mb-3">Respondent Information</h5>' +
                    '<div class="info-label">Name</div>' +
                    '<div class="info-value">' + escapeHtml(blotter.respondent_name || '') + '</div>';
                
                if (blotter.respondent_address) {
                    html += '<div class="info-label">Address</div>' +
                        '<div class="info-value">' + escapeHtml(blotter.respondent_address) + '</div>';
                }
                
                if (blotter.respondent_contact) {
                    html += '<div class="info-label">Contact</div>' +
                        '<div class="info-value">' + escapeHtml(blotter.respondent_contact) + '</div>';
                }
                
                html += '</div></div></div></div>';
                
                // Resolution Section
                if (blotter.resolution) {
                    html += '<div class="card border-success mt-4">' +
                        '<div class="card-header bg-success bg-opacity-10">' +
                        '<h5 class="card-title mb-0 text-success">Resolution</h5>' +
                        '</div>' +
                        '<div class="card-body">' +
                        '<div class="description-box mb-3">' + escapeHtml(blotter.resolution) + '</div>';
                    if (blotter.resolved_date) {
                        html += '<div class="text-muted small">' +
                            '<strong>Resolved on:</strong> ' + formatDate(blotter.resolved_date) +
                            '</div>';
                    }
                    html += '</div></div>';
                }
                
                html += '</div>';
                
                $('#blotterDetails').html(html);
                
                // Set form values
                $('#statusSelect').val(blotter.status || 'pending');
                $('#resolvedDate').val(blotter.resolved_date || '');
                $('#resolutionNotes').val(blotter.resolution || '');
            }
            
            // Handle status update form
            $('#statusUpdateForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'update_status',
                    id: blotterId,
                    status: $('#statusSelect').val(),
                    resolution: $('#resolutionNotes').val().trim(),
                    resolved_date: $('#resolvedDate').val() || null
                };
                
                if (!formData.status) {
                    showAlert('Status is required.', 'error');
                    return;
                }
                
                $.ajax({
                    url: '/api/blotter',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert('Blotter case updated successfully.', 'success');
                            loadBlotterDetails(); // Reload to show updated data
                        } else {
                            showAlert(response.message || 'Error updating blotter case', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error updating blotter case';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        showAlert(errorMsg, 'error');
                    }
                });
            });
            
            // Load data on page load
            loadBlotterDetails();
        });
    </script>
</body>
</html>

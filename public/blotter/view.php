<?php
require_once '../../includes/app.php';
requireTanod(); // Only Tanod (and admin) can access

$id = intval($_GET['id'] ?? 0);

if ($id === 0) {
    header("Location: /blotter/blotter");
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
</head>
<body class="bg-gray-100">
    <?php include '../navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include '../sidebar.php'; ?>
        <main class="p-6 w-screen">
            <div class="mb-4">
                <a href="/blotter/blotter" class="text-blue-600 hover:underline">← Back to Blotter List</a>
            </div>
            
            <h2 class="text-2xl font-semibold mb-4">Blotter Case Details</h2>
            
            <div id="alertContainer"></div>
            
            <div id="blotterDetails" class="text-center text-muted py-4">Loading blotter details...</div>
            
            <!-- Update Status Form -->
            <div id="updateStatusForm" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Update Case Status</h3>
                <form id="statusUpdateForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select name="status" id="statusSelect" required
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="pending">Pending</option>
                                <option value="under_investigation">Under Investigation</option>
                                <option value="resolved">Resolved</option>
                                <option value="dismissed">Dismissed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Resolved Date</label>
                            <input type="date" name="resolved_date" id="resolvedDate"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resolution Notes</label>
                        <textarea name="resolution" id="resolutionNotes" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold px-4 py-2 rounded shadow">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
            
    <script>
        $(function() {
            const blotterId = <?= $id ?>;
            let blotterData = null;
            
            // Show alert function
            function showAlert(message, type) {
                const alertClass = type === 'success' ? 'bg-green-100 border-green-300 text-green-800' : 
                                  'bg-red-100 border-red-300 text-red-800';
                const alertHtml = '<div class="' + alertClass + ' border px-4 py-3 rounded-lg mb-4">' +
                    escapeHtml(message) +
                    '</div>';
                $('#alertContainer').html(alertHtml);
                setTimeout(function() {
                    $('#alertContainer div').fadeOut(function() {
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
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', 
                    hour: 'numeric', minute: '2-digit', hour12: true });
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
                    url: '/api/v1/blotter?id=' + blotterId,
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 'success' && response.data) {
                            blotterData = response.data;
                            renderBlotterDetails(blotterData);
                            $('#updateStatusForm').show();
                        } else {
                            $('#blotterDetails').html('<div class="text-danger">Blotter case not found.</div>');
                            setTimeout(function() {
                                window.location.href = '/blotter/blotter';
                            }, 2000);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 404) {
                            $('#blotterDetails').html('<div class="text-danger">Blotter case not found.</div>');
                            setTimeout(function() {
                                window.location.href = '/blotter/blotter';
                            }, 2000);
                        } else {
                            $('#blotterDetails').html('<div class="text-danger">Error loading blotter details.</div>');
                        }
                    }
                });
            }
            
            function renderBlotterDetails(blotter) {
                const statusColors = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'under_investigation': 'bg-blue-100 text-blue-800',
                    'resolved': 'bg-green-100 text-green-800',
                    'dismissed': 'bg-gray-100 text-gray-800'
                };
                const statusColor = statusColors[blotter.status] || 'bg-gray-100 text-gray-800';
                const statusDisplay = blotter.status ? blotter.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '';
                
                let html = '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">' +
                    '<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">' +
                    '<div>' +
                    '<h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Case Information</h3>' +
                    '<div class="space-y-3">' +
                    '<div><label class="text-sm font-medium text-gray-500">Case Number</label>' +
                    '<p class="text-gray-900 font-semibold">' + escapeHtml(blotter.case_number || '') + '</p></div>' +
                    '<div><label class="text-sm font-medium text-gray-500">Status</label>' +
                    '<p><span class="px-3 py-1 rounded text-sm font-semibold ' + statusColor + '">' + statusDisplay + '</span></p></div>' +
                    '<div><label class="text-sm font-medium text-gray-500">Created By</label>' +
                    '<p class="text-gray-900">' + escapeHtml(blotter.created_by_name || 'N/A') + '</p></div>' +
                    '<div><label class="text-sm font-medium text-gray-500">Date Created</label>' +
                    '<p class="text-gray-900">' + formatDateTime(blotter.created_at) + '</p></div>' +
                    '</div></div>' +
                    '<div>' +
                    '<h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Incident Information</h3>' +
                    '<div class="space-y-3">' +
                    '<div><label class="text-sm font-medium text-gray-500">Incident Date</label>' +
                    '<p class="text-gray-900">' + formatDate(blotter.incident_date) + '</p></div>';
                
                if (blotter.incident_time) {
                    html += '<div><label class="text-sm font-medium text-gray-500">Incident Time</label>' +
                        '<p class="text-gray-900">' + formatTime(blotter.incident_time) + '</p></div>';
                }
                
                html += '<div><label class="text-sm font-medium text-gray-500">Location</label>' +
                    '<p class="text-gray-900">' + escapeHtml(blotter.incident_location || '') + '</p></div>' +
                    '<div><label class="text-sm font-medium text-gray-500">Description</label>' +
                    '<p class="text-gray-900 whitespace-pre-wrap">' + escapeHtml(blotter.incident_description || '') + '</p></div>' +
                    '</div></div>' +
                    '<div>' +
                    '<h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Complainant Information</h3>' +
                    '<div class="space-y-3">' +
                    '<div><label class="text-sm font-medium text-gray-500">Name</label>' +
                    '<p class="text-gray-900">' + escapeHtml(blotter.complainant_name || '') + '</p></div>';
                
                if (blotter.complainant_address) {
                    html += '<div><label class="text-sm font-medium text-gray-500">Address</label>' +
                        '<p class="text-gray-900">' + escapeHtml(blotter.complainant_address) + '</p></div>';
                }
                
                if (blotter.complainant_contact) {
                    html += '<div><label class="text-sm font-medium text-gray-500">Contact</label>' +
                        '<p class="text-gray-900">' + escapeHtml(blotter.complainant_contact) + '</p></div>';
                }
                
                html += '</div></div>' +
                    '<div>' +
                    '<h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Respondent Information</h3>' +
                    '<div class="space-y-3">' +
                    '<div><label class="text-sm font-medium text-gray-500">Name</label>' +
                    '<p class="text-gray-900">' + escapeHtml(blotter.respondent_name || '') + '</p></div>';
                
                if (blotter.respondent_address) {
                    html += '<div><label class="text-sm font-medium text-gray-500">Address</label>' +
                        '<p class="text-gray-900">' + escapeHtml(blotter.respondent_address) + '</p></div>';
                }
                
                if (blotter.respondent_contact) {
                    html += '<div><label class="text-sm font-medium text-gray-500">Contact</label>' +
                        '<p class="text-gray-900">' + escapeHtml(blotter.respondent_contact) + '</p></div>';
                }
                
                html += '</div></div></div>';
                
                if (blotter.resolution) {
                    html += '<div class="mt-6 pt-6 border-t">' +
                        '<h3 class="text-lg font-semibold text-gray-800 mb-4">Resolution</h3>' +
                        '<p class="text-gray-900 whitespace-pre-wrap">' + escapeHtml(blotter.resolution) + '</p>';
                    if (blotter.resolved_date) {
                        html += '<p class="text-sm text-gray-500 mt-2">Resolved on: ' + formatDate(blotter.resolved_date) + '</p>';
                    }
                    html += '</div>';
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
                    url: '/api/v1/blotter',
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
        </main>
    </div>
</body>
</html>


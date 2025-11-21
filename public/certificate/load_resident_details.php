<?php
require_once '../../includes/app.php';
requireLogin();

if (!isset($_GET['id'])) exit;

$id = intval($_GET['id']);

// Use models directly
require_once '../api/BaseModel.php';
require_once '../api/residents/ResidentModel.php';
require_once '../api/certificates/CertificateModel.php';

$residentModel = new ResidentModel();
$resident = $residentModel->find($id);

if (!$resident) {
    echo '<div class="text-danger">Resident not found.</div>';
    exit;
}

$certificateModel = new CertificateModel();
$certificates = $certificateModel->getByResident($id);

?>

<div class="bg-white p-4 rounded-3 shadow-sm border mb-4">
    <h3 class="text-xl font-semibold mb-4 text-gray-800">Resident Information</h3>
    <div class="row row-cols-1 row-cols-md-2 g-4">
        <p><span class="font-medium text-gray-700">Full Name:</span> <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']) ?></p>
        <p><span class="font-medium text-gray-700">Birthdate:</span> <?= htmlspecialchars($resident['birthdate']) ?></p>
        <p><span class="font-medium text-gray-700">Gender:</span> <?= htmlspecialchars($resident['gender']) ?></p>
        <p><span class="font-medium text-gray-700">Address:</span> <?= htmlspecialchars($resident['address']) ?></p>
        <p><span class="font-medium text-gray-700">Voter Status:</span> <?= htmlspecialchars($resident['voter_status']) ?></p>
        <p><span class="font-medium text-gray-700">Disability Status:</span> <?= htmlspecialchars($resident['disability_status'] ?? 'None') ?></p>
    </div>

    <div class="mt-6 border-t pt-4">
        <h4 class="text-lg font-medium mb-2 text-gray-800">Create Certificate Request</h4>
        <form id="certificateRequestForm" method="POST" class="space-y-4">
            <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">

            <div>
                <label class="form-label small fw-medium">Certificate Type</label>
                <select name="certificate_type" required class="form-select">
                    <option value="Barangay Clearance">Barangay Clearance</option>
                    <option value="Indigency Certificate">Indigency Certificate</option>
                    <option value="Residency Certificate">Residency Certificate</option>
                </select>
            </div>

            <div>
                <label class="form-label small fw-medium">Purpose</label>
                <input type="text" name="purpose" placeholder="Enter purpose of certificate" required class="form-control">
            </div>

            <button id="submitBtn" type="submit" class="btn btn-primary px-4 py-2 fw-medium">
                Submit Request
            </button>
        </form>
    </div>
</div>

<div class="bg-white mt-4 p-4 rounded-xl shadow-sm border border-gray-200">
    <h4 class="text-lg font-medium mb-4 text-gray-800">Certificate Request History</h4>

    <div class="overflow-x-auto">
        <table id="historyTable" class="display w-100 text-sm border border-gray-200 rounded-3">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="p-2 text-left">Certificate Type</th>
                    <th class="p-2 text-left">Purpose</th>
                    <th class="p-2 text-left">Status</th>
                    <th class="p-2 text-left">Requested At</th>
                    <th class="p-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($certificates)): ?>
                    <?php foreach ($certificates as $row): ?>
                        <?php
                        $statusColor = [
                            'Pending' => 'badge bg-warning',
                            'pending' => 'badge bg-warning',
                            'Printed' => 'badge bg-primary',
                            'Approved' => 'badge bg-success',
                            'approved' => 'badge bg-success',
                            'Rejected' => 'badge bg-danger',
                            'rejected' => 'badge bg-danger'
                        ][$row['status']] ?? 'badge bg-secondary';
                        $statusDisplay = $row['status'];
                        $requestedAt = $row['requested_at'] ?? $row['created_at'] ?? '';
                        ?>
                        <tr>
                            <td class="p-2 text-gray-800"><?= htmlspecialchars($row['certificate_type']) ?></td>
                            <td class="p-2 text-gray-800"><?= htmlspecialchars($row['purpose']) ?></td>
                            <td class="p-2">
                                <span class="px-2 py-1 rounded text-xs font-semibold <?= $statusColor ?>">
                                    <?= htmlspecialchars($statusDisplay) ?>
                                </span>
                            </td>
                            <td class="p-2 text-gray-800"><?= $requestedAt ? htmlspecialchars(date('M d, Y h:i A', strtotime($requestedAt))) : 'N/A' ?></td>
                            <td class="p-2">
                                <?php if (in_array($row['status'], ['Pending', 'pending', 'Approved', 'approved'])): ?>
                                    <button onclick="printCertificate(<?= $row['id'] ?>, '<?= htmlspecialchars($row['certificate_type'], ENT_QUOTES) ?>')" 
                                        class="btn btn-primary px-3 py-1 rounded text-xs font-medium">
                                        🖨️ Print
                                    </button>
                                <?php elseif ($row['status'] === 'Printed'): ?>
                                    <button onclick="printCertificate(<?= $row['id'] ?>, '<?= htmlspecialchars($row['certificate_type'], ENT_QUOTES) ?>')" 
                                        class="btn btn-secondary px-3 py-1 rounded text-xs font-medium">
                                        🖨️ Re-print
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-4 text-center text-gray-500">No certificate requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#certificateRequestForm").on("submit", function(e) {
            e.preventDefault();
            const formData = {
                action: 'create',
                resident_id: $('input[name="resident_id"]').val(),
                certificate_type: $('select[name="certificate_type"]').val(),
                purpose: $('input[name="purpose"]').val().trim()
            };
            
            // Validation
            if (!formData.resident_id || !formData.certificate_type || !formData.purpose) {
                showDialogReload("❌ Error", "Please fill in all required fields (Certificate Type and Purpose).");
                return;
            }
            
            $.ajax({
                url: "/api/certificates",
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify(formData),
                dataType: "json",
                beforeSend: function() {
                    $("#submitBtn").prop("disabled", true).text("Submitting...");
                },
                success: function(response) {
                    $("#submitBtn").prop("disabled", false).text("Submit Request");
                    if (response.status === "success") {
                        // Use a non-reloading dialog for a better UX
                        const successDialog = generateDialog("✅ Success", response.message || "Certificate request submitted successfully.");
                        $('body').append(successDialog.html);
                        const modal = new bootstrap.Modal(document.getElementById(successDialog.id));
                        modal.show();

                        // When the success dialog is closed, reload the resident details to show the new certificate
                        $('#' + successDialog.id).on('hidden.bs.modal', function() {
                            const residentId = $('input[name="resident_id"]').val();
                            if (residentId) {
                                // Reload the content of the resident details section
                                $.get("/certificate/load_resident_details", { id: residentId }, function(html) {
                                    $("#residentDetails").html(html);
                                    initializeDataTable(); // Re-initialize DataTable on the new content
                                });
                            }
                            $(this).remove(); // Clean up the modal from the DOM
                        });

                        // Reset the form
                        $("#certificateRequestForm")[0].reset();

                    } else {
                        const errorDialog = generateDialog("❌ Error", response.message || "Failed to submit request.");
                        $('body').append(errorDialog.html);
                        new bootstrap.Modal(document.getElementById(errorDialog.id)).show();
                    }
                },
                error: function(xhr, status, error) {
                    $("#submitBtn").prop("disabled", false).text("Submit Request");
                    const errorMsg = xhr.responseJSON?.message || "AJAX error: " + error;
                    showDialogReload("❌ Error", errorMsg);
                }
            });
        });
    });

    // Helper function to generate Bootstrap modal HTML and a unique ID
    function generateDialog(title, message) {
        const modalId = 'dialog_' + Date.now();
        const safeTitle = $('<div/>').text(title).html();
        const safeMessage = $('<div/>').text(message).html();
        const html = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${modalId}Label">${safeTitle}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>${safeMessage}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        return { id: modalId, html: html };
    }

    // Helper function to initialize the DataTable
    function initializeDataTable() {
        setTimeout(function() {
            const $table = $('#historyTable');
            if ($table.length) {
                if ($.fn.DataTable.isDataTable('#historyTable')) {
                    $('#historyTable').DataTable().destroy();
                }
                const $rows = $table.find('tbody tr');
                const hasData = $rows.length > 0 && !$rows.first().find('td[colspan]').length;
                if (hasData) {
                    let allRowsValid = true;
                    $rows.each(function() {
                        if ($(this).find('td').not('[colspan]').length !== 5) {
                            allRowsValid = false;
                            return false;
                        }
                    });
                    if (allRowsValid) {
                        $table.DataTable({
                            pageLength: 10,
                            order: [[3, 'desc']],
                            columnDefs: [{ orderable: false, targets: 4 }]
                        });
                    }
                }
            }
        }, 100);
    }

    function printCertificate(certId, certType) {
        // Open print window
        const printWindow = window.open('/certificate/print?id=' + certId, '_blank', 'width=800,height=600');
        
        // Update status to "Printed" after printing
        $.ajax({
            url: '/api/certificates',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'update_status',
                id: certId,
                status: 'printed'
            }),
            success: function(response) {
                if (response.status === 'success') {
                    // Reload the page to show updated status
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            }
        });
    }
</script>

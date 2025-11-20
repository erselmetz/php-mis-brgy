<?php
require_once '../../includes/app.php';
requireLogin();

if (!isset($_GET['id'])) exit;

$id = intval($_GET['id']);

// Use models directly
require_once '../api/v1/BaseModel.php';
require_once '../api/v1/residents/ResidentModel.php';
require_once '../api/v1/certificates/CertificateModel.php';

$residentModel = new ResidentModel();
$resident = $residentModel->find($id);

if (!$resident) {
    echo '<div class="text-danger">Resident not found.</div>';
    exit;
}

$certificateModel = new CertificateModel();
$certificates = $certificateModel->getByResident($id);
?>

<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
    <h3 class="text-xl font-semibold mb-4 text-gray-800">Resident Information</h3>
    <div class="grid grid-cols-2 gap-4">
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
                <label class="block text-gray-700 mb-1 font-medium">Certificate Type</label>
                <select name="certificate_type" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                    <option value="Barangay Clearance">Barangay Clearance</option>
                    <option value="Indigency Certificate">Indigency Certificate</option>
                    <option value="Residency Certificate">Residency Certificate</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 mb-1 font-medium">Purpose</label>
                <input type="text" name="purpose" placeholder="Enter purpose of certificate" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
            </div>

            <button id="submitBtn" type="submit"
                class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                Submit Request
            </button>
        </form>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
    <h4 class="text-lg font-medium mb-4 text-gray-800">Certificate Request History</h4>

    <div class="overflow-x-auto">
        <table id="historyTable" class="display w-full text-sm border border-gray-200 rounded-lg">
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
                            'Pending' => 'bg-yellow-100 text-yellow-800',
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'Printed' => 'bg-blue-100 text-blue-800',
                            'Approved' => 'bg-green-100 text-green-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'Rejected' => 'bg-red-100 text-red-800',
                            'rejected' => 'bg-red-100 text-red-800'
                        ][$row['status']] ?? 'bg-gray-100 text-gray-800';
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
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium">
                                        🖨️ Print
                                    </button>
                                <?php elseif ($row['status'] === 'Printed'): ?>
                                    <button onclick="printCertificate(<?= $row['id'] ?>, '<?= htmlspecialchars($row['certificate_type'], ENT_QUOTES) ?>')" 
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium">
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
                resident_id: $('[name="resident_id"]').val(),
                certificate_type: $('[name="certificate_type"]').val(),
                purpose: $('[name="purpose"]').val()
            };
            
            $.ajax({
                url: "/api/v1/certificates",
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
                        showDialogReload("✅ Success", response.message);
                    } else {
                        showDialogReload("❌ Error", response.message || "Failed to submit request");
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

    function printCertificate(certId, certType) {
        // Open print window
        const printWindow = window.open('/certificate/print?id=' + certId, '_blank', 'width=800,height=600');
        
        // Update status to "Printed" after printing
        $.ajax({
            url: '/api/v1/certificates',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'update_status',
                id: certId,
                status: 'Printed'
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

<?php
require_once '../../includes/app.php';
requireLogin();

if (!isset($_GET['id'])) exit;

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();
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
                <select name="certificate_type"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                    <option value="Barangay Clearance">Barangay Clearance</option>
                    <option value="Indigency Certificate">Indigency Certificate</option>
                    <option value="Residency Certificate">Residency Certificate</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 mb-1 font-medium">Purpose</label>
                <input type="text" name="purpose" placeholder="Enter purpose of certificate"
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
        <table id="historyTable" class="display min-w-full border border-gray-200 rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b">Certificate Type</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b">Purpose</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b">Status</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b">Requested At</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT * FROM certificate_request WHERE resident_id = ? ORDER BY requested_at DESC");
                $stmt->bind_param("i", $resident['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $statusColor = [
                            'Pending' => 'bg-yellow-100 text-yellow-700',
                            'Approved' => 'bg-green-100 text-green-700',
                            'Rejected' => 'bg-red-100 text-red-700'
                        ][$row['status']] ?? 'bg-gray-100 text-gray-700';
                ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800"><?= htmlspecialchars($row['certificate_type']) ?></td>
                            <td class="px-4 py-2 text-sm text-gray-800"><?= htmlspecialchars($row['purpose']) ?></td>
                            <td class="px-4 py-2 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $statusColor ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-800"><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['requested_at']))) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-gray-500">No certificate requests found.</td>
                        <td class="text-center py-4 text-gray-500"></td>
                        <td class="text-center py-4 text-gray-500"></td>
                        <td class="text-center py-4 text-gray-500"></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    $(document).ready(function() {
        $("#certificateRequestForm").on("submit", function(e) {
            e.preventDefault(); // prevent normal form submit
            $.ajax({
                url: "/certificate/certificate_request_submit", // relative path works perfectly
                method: "POST",
                data: $(this).serialize(),
                dataType: "json",
                beforeSend: function() {
                    $("#submitBtn").prop("disabled", true).text("Submitting...");
                },
                success: function(response) {
                    $("#submitBtn").prop("disabled", false).text("Submit Request");

                    if (response.status === "success") {
                        showDialogReload("✅ Success",response.message);
                    } else {
                        showDialogReload("❌ Error",response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $("#submitBtn").prop("disabled", false).text("Submit Request");
                    alert("AJAX error: " + error);
                }
            });
        });
    });
</script>
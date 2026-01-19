<?php
require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();

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
                <?php
                $stmt = $conn->prepare("SELECT cr.*, u.name as issued_by_name FROM certificate_request cr LEFT JOIN users u ON cr.issued_by = u.id WHERE cr.resident_id = ? ORDER BY cr.requested_at DESC");
                $stmt->bind_param("i", $resident['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $statusColor = [
                            'Pending' => 'bg-yellow-100 text-yellow-800',
                            'Printed' => 'bg-theme-secondary text-theme-accent',
                            'Approved' => 'bg-green-100 text-green-800',
                            'Rejected' => 'bg-red-100 text-red-800'
                        ][$row['status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                        <tr>
                            <td class="p-2 text-gray-800"><?= htmlspecialchars($row['certificate_type']) ?></td>
                            <td class="p-2 text-gray-800"><?= htmlspecialchars($row['purpose']) ?></td>
                            <td class="p-2">
                                <span class="px-2 py-1 rounded text-xs font-semibold <?= $statusColor ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="p-2 text-gray-800"><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['requested_at']))) ?></td>
                            <td class="p-2">
                                <button onclick="printCertificate(<?= $row['id'] ?>, '<?= htmlspecialchars($row['certificate_type'], ENT_QUOTES) ?>')"
                                    class="bg-theme-primary hover-theme-darker text-white px-3 py-1 rounded text-xs font-medium">
                                    🖨️ view
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-4 text-center text-gray-500">No certificate requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="js/load_resident_details.js"></script>
<?php
require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();

$id = intval($_GET['id'] ?? 0);
$success = '';
$error = '';

if ($id === 0) {
    header("Location: ../blotter/");
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    /**
     * CSRF Protection
     * Validate CSRF token to prevent Cross-Site Request Forgery attacks
     */
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please refresh the page and try again.";
    } elseif ($_POST['action'] === 'update_status') {
        $status = sanitizeString($_POST['status'] ?? '', false);
        $resolution = sanitizeString($_POST['resolution'] ?? '');
        $resolved_date = $_POST['resolved_date'] ?? null;
        
        // Validate status against allowed values
        $allowedStatuses = ['pending', 'under_investigation', 'resolved', 'dismissed'];
        if (empty($status) || !in_array($status, $allowedStatuses)) {
            $error = "Invalid status value.";
        } elseif (!empty($resolved_date) && !validateDateFormat($resolved_date)) {
            $error = "Invalid date format for resolved date.";
        } else {
            $stmt = $conn->prepare("
                UPDATE blotter 
                SET status = ?, resolution = ?, resolved_date = ?
                WHERE id = ?
            ");
            
            $resolved_date_value = ($status === 'resolved' && !empty($resolved_date)) ? $resolved_date : null;
            $stmt->bind_param("sssi", $status, $resolution, $resolved_date_value, $id);
            
            if ($stmt->execute()) {
                $success = "Blotter case updated successfully.";
            } else {
                $error = "Error updating blotter: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch blotter details
$stmt = $conn->prepare("
    SELECT b.*, u.name as created_by_name 
    FROM blotter b 
    LEFT JOIN users u ON b.created_by = u.id 
    WHERE b.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../blotter/");
    exit;
}

$blotter = $result->fetch_assoc();
$stmt->close();
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
    <?php include '../layout/navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include '../layout/sidebar.php'; ?>
        <main class="p-6 w-screen">
            <div class="mb-4">
                <a href="../blotter/" class="text-theme-accent hover:underline">← Back to Blotter List</a>
            </div>
            
            <h2 class="text-2xl font-semibold mb-4">Blotter Case Details</h2>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Case Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Case Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Case Number</label>
                                <p class="text-gray-900 font-semibold"><?= htmlspecialchars($blotter['case_number']) ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Status</label>
                                <p>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'under_investigation' => 'bg-theme-secondary text-theme-accent',
                                        'resolved' => 'bg-green-100 text-green-800',
                                        'dismissed' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $statusColor = $statusColors[$blotter['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-3 py-1 rounded text-sm font-semibold <?= $statusColor ?>">
                                        <?= ucfirst(str_replace('_', ' ', $blotter['status'])) ?>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Created By</label>
                                <p class="text-gray-900"><?= htmlspecialchars($blotter['created_by_name'] ?? 'N/A') ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Date Created</label>
                                <p class="text-gray-900"><?= date('F d, Y h:i A', strtotime($blotter['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Incident Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Incident Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Incident Date</label>
                                <p class="text-gray-900"><?= date('F d, Y', strtotime($blotter['incident_date'])) ?></p>
                            </div>
                            <?php if ($blotter['incident_time']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Incident Time</label>
                                <p class="text-gray-900"><?= date('h:i A', strtotime($blotter['incident_time'])) ?></p>
                            </div>
                            <?php endif; ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Location</label>
                                <p class="text-gray-900"><?= htmlspecialchars($blotter['incident_location']) ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Description</label>
                                <p class="text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($blotter['incident_description']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Complainant Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Complainant Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Name</label>
                                <p class="text-gray-900"><?= htmlspecialchars($blotter['complainant_name']) ?></p>
                            </div>
                            <?php if ($blotter['complainant_address']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Address</label>
                                <p class="text-gray-900"><?= htmlspecialchars($blotter['complainant_address']) ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($blotter['complainant_contact']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Contact</label>
                                <p class="text-gray-900"><?= htmlspecialchars($blotter['complainant_contact']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Respondent Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Respondent Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Name</label>
                                <p class="text-gray-900"><?= htmlspecialchars($blotter['respondent_name']) ?></p>
                            </div>
                            <?php if ($blotter['respondent_address']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Address</label>
                                <p class="text-gray-900"><?= htmlspecialchars($blotter['respondent_address']) ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($blotter['respondent_contact']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Contact</label>
                                <p class="text-gray-900"><?= htmlspecialchars($blotter['respondent_contact']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($blotter['resolution']): ?>
                <div class="mt-6 pt-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Resolution</h3>
                    <p class="text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($blotter['resolution']) ?></p>
                    <?php if ($blotter['resolved_date']): ?>
                    <p class="text-sm text-gray-500 mt-2">Resolved on: <?= date('F d, Y', strtotime($blotter['resolved_date'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Update Status Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Update Case Status</h3>
                <form method="POST" class="space-y-4">
                    <?= csrfTokenField() ?>
                    <input type="hidden" name="action" value="update_status">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select name="status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                                <option value="pending" <?= $blotter['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="under_investigation" <?= $blotter['status'] === 'under_investigation' ? 'selected' : '' ?>>Under Investigation</option>
                                <option value="resolved" <?= $blotter['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="dismissed" <?= $blotter['status'] === 'dismissed' ? 'selected' : '' ?>>Dismissed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Resolved Date</label>
                            <input type="date" name="resolved_date" value="<?= $blotter['resolved_date'] ?? '' ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resolution Notes</label>
                        <textarea name="resolution" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-theme-primary"><?= htmlspecialchars($blotter['resolution'] ?? '') ?></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" class="bg-theme-secondary hover-theme-darker text-white font-semibold px-4 py-2 rounded shadow">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>


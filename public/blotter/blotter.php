<?php
require_once '../../includes/app.php';
requireTanod(); // Only Tanod (and admin) can access

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_blotter') {
        $complainant_name = trim($_POST['complainant_name'] ?? '');
        $complainant_address = trim($_POST['complainant_address'] ?? '');
        $complainant_contact = trim($_POST['complainant_contact'] ?? '');
        $respondent_name = trim($_POST['respondent_name'] ?? '');
        $respondent_address = trim($_POST['respondent_address'] ?? '');
        $respondent_contact = trim($_POST['respondent_contact'] ?? '');
        $incident_date = $_POST['incident_date'] ?? '';
        $incident_time = $_POST['incident_time'] ?? '';
        $incident_location = trim($_POST['incident_location'] ?? '');
        $incident_description = trim($_POST['incident_description'] ?? '');
        $status = $_POST['status'] ?? 'pending';
        
        // Validation
        if (empty($complainant_name) || empty($respondent_name) || empty($incident_date) || empty($incident_location) || empty($incident_description)) {
            $error = "Please fill in all required fields.";
        } else {
            // Generate case number
            $year = date('Y');
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blotter WHERE case_number LIKE ?");
            $pattern = "BLT-$year-%";
            $stmt->bind_param("s", $pattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count = $row['count'] + 1;
            $case_number = "BLT-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            
            // Insert blotter record
            $stmt = $conn->prepare("
                INSERT INTO blotter (
                    case_number, complainant_name, complainant_address, complainant_contact,
                    respondent_name, respondent_address, respondent_contact,
                    incident_date, incident_time, incident_location, incident_description,
                    status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param(
                "ssssssssssssi",
                $case_number,
                $complainant_name,
                $complainant_address,
                $complainant_contact,
                $respondent_name,
                $respondent_address,
                $respondent_contact,
                $incident_date,
                $incident_time,
                $incident_location,
                $incident_description,
                $status,
                $created_by
            );
            
            if ($stmt->execute()) {
                $success = "Blotter case added successfully. Case Number: $case_number";
            } else {
                $error = "Error adding blotter: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all blotter records
$stmt = $conn->prepare("SELECT b.*, u.name as created_by_name FROM blotter b LEFT JOIN users u ON b.created_by = u.id ORDER BY b.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
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
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
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
                        <?php if ($result !== false): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="p-2">
                                        <a href="/blotter/view?id=<?= $row['id'] ?>" class="text-primary text-decoration-none fw-semibold">
                                            <?= htmlspecialchars($row['case_number']) ?>
                                        </a>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars($row['complainant_name']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['respondent_name']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['incident_date']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['incident_location']) ?></td>
                                    <td class="p-2">
                                        <?php
                                        $statusColors = [
                                            'pending' => 'bg-warning bg-opacity-10 text-warning',
                                            'under_investigation' => 'bg-primary bg-opacity-10 text-primary',
                                            'resolved' => 'bg-success bg-opacity-10 text-success',
                                            'dismissed' => 'bg-secondary bg-opacity-10 text-secondary'
                                        ];
                                        $statusColor = $statusColors[$row['status']] ?? 'bg-secondary bg-opacity-10 text-secondary';
                                        ?>
                                        <span class="badge <?= $statusColor ?>">
                                            <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="p-4 text-center text-muted">No blotter cases found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Add Blotter Modal -->
    <div id="addBlotterModal" title="Add New Blotter Case">
        <form method="POST" style="max-height: 70vh; overflow-y: auto;">
            <input type="hidden" name="action" value="add_blotter">
            
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
            $('#blotterTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25
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
                }
            });
            
            $("#openBlotterModalBtn").on("click", function() {
                $("#addBlotterModal").dialog("open");
            });
        });
    </script>
</body>
</html>


<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if ($id === 0 || empty($status)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit;
}

// Validate status
$allowedStatuses = ['Pending', 'Printed', 'Approved', 'Rejected'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status.']);
    exit;
}

$stmt = $conn->prepare("UPDATE certificate_request SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);
} else {
    error_log('Certificate status update error: ' . $stmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
}

$stmt->close();
exit;


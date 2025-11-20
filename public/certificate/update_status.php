<?php
/**
 * Certificate Update Status - API Proxy
 * This file uses the API directly for backward compatibility
 */

require_once '../../includes/app.php';
requireStaff();

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

// Use API models directly
require_once '../api/BaseModel.php';
require_once '../api/certificates/CertificateModel.php';

try {
    $model = new CertificateModel();
    
    // Map legacy status values to API format
    $apiStatus = $status;
    if ($status === 'Pending') {
        $apiStatus = 'pending';
    } elseif ($status === 'Approved' || $status === 'Printed') {
        $apiStatus = 'approved';
    } elseif ($status === 'Rejected') {
        $apiStatus = 'rejected';
    }
    
    $success = $model->updateStatus($id, $apiStatus, $_SESSION['user_id'] ?? null);
    
    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
    }
    
} catch (Exception $e) {
    error_log('Certificate status update error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
}


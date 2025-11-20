<?php
/**
 * Certificate Request Submit - API Proxy
 * This file uses the API directly for backward compatibility
 */

require_once '../../includes/app.php';
requireStaff();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Use API models directly
require_once '../api/v1/BaseModel.php';
require_once '../api/v1/certificates/CertificateModel.php';

try {
    $model = new CertificateModel();
    
    $resident_id = intval($_POST['resident_id'] ?? 0);
    $certificate_type = trim($_POST['certificate_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $issued_by = $_SESSION['user_id'] ?? null;
    
    if (empty($resident_id) || empty($certificate_type)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }
    
    // Validate certificate type
    if (!$model->isValidCertificateType($certificate_type)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid certificate type.']);
        exit;
    }
    
    // Prepare data for insertion
    $certificateData = [
        'resident_id' => $resident_id,
        'certificate_type' => $certificate_type,
        'purpose' => $purpose,
        'issued_by' => $issued_by,
        'status' => 'Pending',
        'requested_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $certificateId = $model->insert($certificateData);
    
    if ($certificateId) {
        echo json_encode(['status' => 'success', 'message' => 'Certificate request submitted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    
} catch (Exception $e) {
    error_log('Certificate Request Submit Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}

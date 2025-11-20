<?php
/**
 * Resident Save - API Proxy
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
require_once '../api/v1/residents/ResidentModel.php';

try {
    $model = new ResidentModel();
    
    // Collect and sanitize POST data
    $residentData = [
        'household_id' => !empty($_POST['household_id']) ? intval($_POST['household_id']) : null,
        'birthdate' => trim($_POST['birthdate'] ?? ''),
        'first_name' => trim($_POST['first_name'] ?? ''),
        'middle_name' => trim($_POST['middle_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'suffix' => trim($_POST['suffix'] ?? ''),
        'gender' => $_POST['gender'] ?? null,
        'birthplace' => trim($_POST['birthplace'] ?? ''),
        'civil_status' => $_POST['civil_status'] ?? 'Single',
        'religion' => trim($_POST['religion'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'citizenship' => trim($_POST['citizenship'] ?? 'Filipino'),
        'contact_no' => trim($_POST['contact_no'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'voter_status' => $_POST['voter_status'] ?? 'No',
        'disability_status' => $_POST['disability_status'] ?? 'No',
        'remarks' => trim($_POST['remarks'] ?? ''),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Basic validation
    if (empty($residentData['first_name']) || empty($residentData['last_name'])) {
        echo json_encode(['status' => 'error', 'message' => 'First and last name are required.']);
        exit;
    }
    
    if (!empty($residentData['birthdate']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $residentData['birthdate'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid birthdate format.']);
        exit;
    }
    
    if (!empty($residentData['contact_no']) && !preg_match('/^09\d{9}$/', $residentData['contact_no'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid contact number format.']);
        exit;
    }
    
    $residentId = $model->insert($residentData);
    
    if ($residentId) {
        echo json_encode(['status' => 'success', 'message' => 'Resident saved successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    
} catch (Exception $e) {
    error_log('Resident Save Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}

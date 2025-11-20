<?php
/**
 * Resident Update - API Proxy
 * This file uses the API directly for backward compatibility
 */

require_once '../../includes/app.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

// Use API models directly
require_once '../api/v1/BaseModel.php';
require_once '../api/v1/residents/ResidentModel.php';

try {
    $model = new ResidentModel();
    
    // Prepare update data
    $updateData = [
        'household_id' => !empty($_POST['household_id']) ? intval($_POST['household_id']) : null,
        'first_name' => trim($_POST['first_name'] ?? ''),
        'middle_name' => trim($_POST['middle_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'suffix' => trim($_POST['suffix'] ?? ''),
        'gender' => $_POST['gender'] ?? null,
        'birthdate' => trim($_POST['birthdate'] ?? ''),
        'birthplace' => trim($_POST['birthplace'] ?? ''),
        'civil_status' => $_POST['civil_status'] ?? null,
        'religion' => trim($_POST['religion'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'citizenship' => trim($_POST['citizenship'] ?? ''),
        'contact_no' => trim($_POST['contact_no'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'voter_status' => $_POST['voter_status'] ?? null,
        'disability_status' => $_POST['disability_status'] ?? null,
        'remarks' => trim($_POST['remarks'] ?? ''),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $success = $model->update($id, $updateData);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Resident updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    
} catch (Exception $e) {
    error_log('Resident Update Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

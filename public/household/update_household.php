<?php
/**
 * Household Update - API Proxy
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
require_once '../api/v1/households/HouseholdModel.php';

try {
    $model = new HouseholdModel();
    
    // Collect and sanitize POST data
    $householdData = [
        'household_no' => trim($_POST['household_no'] ?? ''),
        'head_name' => trim($_POST['head_name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Validate required fields
    if (empty($householdData['household_no']) || empty($householdData['head_name']) || empty($householdData['address'])) {
        echo json_encode(['success' => false, 'message' => 'Household number, head name, and address are required']);
        exit;
    }
    
    // Check for duplicate household number (excluding current record)
    if ($model->householdNumberExists($householdData['household_no'], $id)) {
        echo json_encode(['success' => false, 'message' => 'Household number already exists']);
        exit;
    }
    
    // Update total_members based on actual resident count
    $model->updateMemberCount($id);
    
    $success = $model->update($id, $householdData);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Household updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    
} catch (Exception $e) {
    error_log('Household Update Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}


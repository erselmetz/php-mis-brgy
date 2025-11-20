<?php
/**
 * Household Save - API Proxy
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
require_once '../api/v1/households/HouseholdModel.php';

try {
    $model = new HouseholdModel();
    
    // Collect and sanitize POST data
    $householdData = [
        'household_no' => trim($_POST['household_no'] ?? ''),
        'head_name' => trim($_POST['head_name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'total_members' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Basic validation
    if (empty($householdData['household_no']) || empty($householdData['head_name']) || empty($householdData['address'])) {
        echo json_encode(['status' => 'error', 'message' => 'Household number, head name, and address are required.']);
        exit;
    }
    
    // Check for duplicate household number
    if ($model->householdNumberExists($householdData['household_no'])) {
        echo json_encode(['status' => 'error', 'message' => 'Household number already exists.']);
        exit;
    }
    
    $householdId = $model->insert($householdData);
    
    if ($householdId) {
        echo json_encode(['status' => 'success', 'message' => 'Household saved successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    
} catch (Exception $e) {
    error_log('Household Save Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}


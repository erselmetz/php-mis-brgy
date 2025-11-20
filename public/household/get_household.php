<?php
/**
 * Get Household - API Proxy
 * This file uses the API directly for backward compatibility
 */

require_once '../../includes/app.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing household ID']);
    exit;
}

$id = intval($_GET['id']);

// Use API models directly
require_once '../api/BaseModel.php';
require_once '../api/households/HouseholdModel.php';

try {
    $model = new HouseholdModel();
    $household = $model->find($id);
    
    if ($household) {
        echo json_encode($household);
    } else {
        echo json_encode(['error' => 'Household not found']);
    }
    
} catch (Exception $e) {
    error_log('Get Household Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to retrieve household']);
}


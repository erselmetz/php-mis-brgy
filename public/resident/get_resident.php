<?php
/**
 * Get Resident - API Proxy
 * This file uses the API directly for backward compatibility
 */

require_once '../../includes/app.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing resident ID']);
    exit;
}

$id = intval($_GET['id']);

// Use API models directly
require_once '../api/v1/BaseModel.php';
require_once '../api/v1/residents/ResidentModel.php';

try {
    $model = new ResidentModel();
    $resident = $model->find($id);
    
    if ($resident) {
        echo json_encode($resident);
    } else {
        echo json_encode(['error' => 'Resident not found']);
    }
    
} catch (Exception $e) {
    error_log('Get Resident Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to retrieve resident']);
}

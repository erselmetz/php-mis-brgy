<?php
require_once '../../includes/app.php';
require_once '../api/BaseModel.php';
require_once '../api/residents/ResidentModel.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

try {
    // Use ResidentModel directly with proper SQL query
    $model = new ResidentModel();
    $searchTerm = '%' . $q . '%';
    
    // Build SQL query directly to handle OR conditions properly
    global $conn;
    $sql = "SELECT id, first_name, middle_name, last_name, address 
            FROM residents 
            WHERE (first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR address LIKE ?)
            ORDER BY last_name, first_name 
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Search prepare error: " . $conn->error);
        echo json_encode([]);
        exit;
    }
    
    $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $residents = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Format response to match expected format
    $formatted = array_map(function($resident) {
        return [
            'id' => $resident['id'],
            'first_name' => $resident['first_name'] ?? '',
            'middle_name' => $resident['middle_name'] ?? '',
            'last_name' => $resident['last_name'] ?? '',
            'address' => $resident['address'] ?? ''
        ];
    }, $residents);
    
    echo json_encode($formatted);
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([]);
}

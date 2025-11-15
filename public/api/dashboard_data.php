<?php
require_once '../../includes/app.php';
requireLogin();
header('Content-Type: application/json');

$filter = $_GET['filter'] ?? '';
$role = $_SESSION['role'] ?? '';

$response = ['status' => 'error', 'data' => [], 'message' => ''];

try {
    if (empty($filter)) {
        throw new Exception('Filter parameter is required');
    }
    
    // Define filter types
    $tanodFilters = ['pending', 'under_investigation', 'resolved', 'dismissed'];
    $staffFilters = ['total', 'male', 'female', 'seniors', 'pwd', 'voter_registered', 'voter_unregistered'];
    
    // Determine which filter type this is
    $isTanodFilter = in_array($filter, $tanodFilters);
    $isStaffFilter = in_array($filter, $staffFilters);
    
    // Check permissions and filter type
    if ($isTanodFilter) {
        // Tanod dashboard filters
        if ($role !== 'tanod' && $role !== 'admin') {
            throw new Exception('Unauthorized access to Tanod filters');
        }
        
        switch ($filter) {
            case 'pending':
                $stmt = $conn->prepare("SELECT * FROM blotter WHERE status = 'pending' ORDER BY created_at DESC");
                break;
            case 'under_investigation':
                $stmt = $conn->prepare("SELECT * FROM blotter WHERE status = 'under_investigation' ORDER BY created_at DESC");
                break;
            case 'resolved':
                $stmt = $conn->prepare("SELECT * FROM blotter WHERE status = 'resolved' ORDER BY resolved_date DESC");
                break;
            case 'dismissed':
                $stmt = $conn->prepare("SELECT * FROM blotter WHERE status = 'dismissed' ORDER BY created_at DESC");
                break;
            default:
                throw new Exception('Invalid filter for Tanod');
        }
    } elseif ($isStaffFilter) {
        // Staff dashboard filters
        if ($role !== 'staff' && $role !== 'admin') {
            throw new Exception('Unauthorized access to Staff filters');
        }
        
        switch ($filter) {
            case 'total':
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'male':
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE gender = 'Male' 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'female':
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE gender = 'Female' 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'seniors':
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'pwd':
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE disability_status = 'Yes' 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'voter_registered':
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE voter_status = 'Yes' 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'voter_unregistered':
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE voter_status = 'No' 
                    ORDER BY last_name, first_name
                ");
                break;
            default:
                throw new Exception('Invalid filter for Staff');
        }
    } else {
        throw new Exception('Invalid filter type');
    }
    
    if ($stmt === false) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    
    $response = [
        'status' => 'success',
        'data' => $data,
        'count' => count($data),
        'filter' => $filter
    ];
    
} catch (Exception $e) {
    error_log('Dashboard API Error: ' . $e->getMessage());
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'filter' => $filter,
            'role' => $role,
            'error' => $e->getMessage()
        ]
    ];
}

// Ensure no output before JSON
if (ob_get_length()) {
    ob_clean();
}

echo json_encode($response);
exit;


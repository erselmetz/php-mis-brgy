<?php
/**
 * Dashboard Data API Endpoint
 * 
 * Provides filtered data for dashboard statistics.
 * Supports both staff/resident filters and tanod/blotter filters.
 * Uses prepared statements for security and proper role-based access control.
 */

require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();
header('Content-Type: application/json');

// Get filter parameter and validate
$filter = trim($_GET['filter'] ?? '');
$role = $_SESSION['role'] ?? '';

// Initialize response structure
$response = ['status' => 'error', 'data' => [], 'message' => ''];

try {
    // Validate filter parameter
    if (empty($filter)) {
        throw new Exception('Filter parameter is required');
    }
    
    /**
     * Define allowed filter types for each role
     * This whitelist approach prevents unauthorized filter access
     */
    $tanodFilters = ['pending', 'under_investigation', 'resolved', 'dismissed'];
    $staffFilters = ['total', 'male', 'female', 'seniors', 'pwd', 'voter_registered', 'voter_unregistered'];
    
    // Determine which filter type this is
    $isTanodFilter = in_array($filter, $tanodFilters);
    $isStaffFilter = in_array($filter, $staffFilters);
    
    /**
     * Role-based access control
     * Check permissions before processing filter
     */
    if ($isTanodFilter) {
        // Tanod dashboard filters - only tanod and admin can access
        if ($role !== 'secretary') {
            throw new Exception('Unauthorized access to Tanod filters');
        }
        
        /**
         * Build query based on filter type
         * Using prepared statements even though filter is validated
         * for consistency and future-proofing
         */
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
        // Staff dashboard filters - only staff and admin can access
        if ($role !== 'secretary' && $role !== 'admin') {
            throw new Exception('Unauthorized access to Staff filters');
        }
        
        /**
         * Build query based on filter type
         * Each filter retrieves residents matching specific criteria
         * Results are ordered by last name, then first name for consistency
         */
        switch ($filter) {
            case 'total':
                // Get all residents
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'male':
                // Get male residents only
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE gender = 'Male' 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'female':
                // Get female residents only
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE gender = 'Female' 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'seniors':
                // Get residents aged 60 and above
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'pwd':
                // Get residents with disabilities
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE disability_status = 'Yes' 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'voter_registered':
                // Get registered voters
                $stmt = $conn->prepare("
                    SELECT * FROM residents 
                    WHERE voter_status = 'Yes' 
                    ORDER BY last_name, first_name
                ");
                break;
            case 'voter_unregistered':
                // Get unregistered voters
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
    
    // Validate query preparation
    if ($stmt === false) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Validate query execution
    if ($result === false) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    // Fetch all results into array
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    
    // Build success response
    $response = [
        'status' => 'success',
        'data' => $data,
        'count' => count($data),
        'filter' => $filter
    ];
    
} catch (Exception $e) {
    /**
     * Error handling
     * Log full error details for debugging
     * Return user-friendly error message
     */
    error_log('Dashboard API Error: ' . $e->getMessage() . ' | Filter: ' . $filter . ' | Role: ' . $role);
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        // Include debug info only in development (remove in production)
        'debug' => [
            'filter' => $filter,
            'role' => $role,
            'error' => $e->getMessage()
        ]
    ];
}

// Ensure no output before JSON (prevents JSON parsing errors)
if (ob_get_length()) {
    ob_clean();
}

// Send JSON response
echo json_encode($response);
exit;


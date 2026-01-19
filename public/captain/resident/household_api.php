<?php
require_once '../../../includes/app.php';
requireCaptain();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
            // Get households list
            handleGetHouseholds();
            break;

        default:
            $response['message'] = 'Method not allowed';
            echo json_encode($response);
    }
} catch (Exception $e) {
    error_log('Household API Error: ' . $e->getMessage());
    $response['message'] = 'An error occurred';
    echo json_encode($response);
}

function handleGetHouseholds() {
    global $conn;

    $search = $_GET['search'] ?? '';
    $limit = (int)($_GET['limit'] ?? 50);

    // Build query
    $where = "1=1";

    if (!empty($search)) {
        $searchTerm = $conn->real_escape_string($search);

        // Search must use resident fields, NOT head_name alias
        $where .= " AND (
            h.household_no LIKE '%$searchTerm%' 
            OR hr.first_name LIKE '%$searchTerm%'
            OR hr.middle_name LIKE '%$searchTerm%'
            OR hr.last_name LIKE '%$searchTerm%'
            OR hr.suffix LIKE '%$searchTerm%'
            OR h.address LIKE '%$searchTerm%'
        )";
    }

    // Count total
    $countQuery = "
        SELECT COUNT(*) as total
        FROM households h
        LEFT JOIN residents hr ON h.head_id = hr.id
        WHERE $where
    ";
    $countResult = $conn->query($countQuery);
    $total = $countResult->fetch_assoc()['total'];

    // Get households + head name + dynamic member count
    $query = "
        SELECT 
            h.id, h.household_no, h.address, h.created_at,
            CONCAT_WS(' ', hr.first_name, hr.middle_name, hr.last_name, hr.suffix) AS head_name,
            COUNT(r.id) AS total_members
        FROM households h
        LEFT JOIN residents hr ON h.head_id = hr.id
        LEFT JOIN residents r ON h.id = r.household_id AND r.deleted_at IS NULL
        WHERE $where
        GROUP BY h.id
        ORDER BY h.household_no ASC
        LIMIT $limit
    ";

    $result = $conn->query($query);

    $households = [];
    while ($row = $result->fetch_assoc()) {
        $households[] = [
            'id' => $row['id'],
            'household_no' => $row['household_no'],
            'address' => $row['address'],
            'head_name' => $row['head_name'] ?: 'Unknown',
            'total_members' => (int)$row['total_members'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'households' => $households,
        'total' => $total
    ]);
}
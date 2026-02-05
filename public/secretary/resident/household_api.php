<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
            // Get households list
            handleGetHouseholds();
            break;

        case 'POST':
            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'create':
                    handleCreateHousehold();
                    break;

                case 'update':
                    handleUpdateHousehold();
                    break;

                case 'archive':
                    handleArchiveHousehold();
                    break;

                default:
                    $response['message'] = 'Invalid action';
                    echo json_encode($response);
            }
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
        $where .= " AND (h.household_no LIKE '%$searchTerm%' OR h.address LIKE '%$searchTerm%')";
    }

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM households h WHERE $where";
    $countResult = $conn->query($countQuery);
    $total = $countResult->fetch_assoc()['total'];

    // Get households with dynamic member count and head name
    $query = "SELECT h.id, h.household_no, h.address, h.created_at,
                     CONCAT_WS(' ', hr.first_name, hr.middle_name, hr.last_name, hr.suffix) as head_name,
                     COUNT(r.id) as total_members
              FROM households h
              LEFT JOIN residents hr ON h.head_id = hr.id
              LEFT JOIN residents r ON h.id = r.household_id AND r.deleted_at IS NULL
              WHERE $where
              GROUP BY h.id, h.household_no, h.address, h.created_at, hr.first_name, hr.middle_name, hr.last_name, hr.suffix
              ORDER BY h.household_no ASC
              LIMIT $limit";

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

function handleCreateHousehold() {
    global $conn;

    $household_no = trim($_POST['household_no'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $head_resident_id = (int)($_POST['head_resident_id'] ?? 0);

    // Validation
    if (empty($household_no) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Household number and address are required.']);
        return;
    }

    if (!$head_resident_id) {
        echo json_encode(['success' => false, 'message' => 'Please select a resident as the head of household.']);
        return;
    }

    // Check if household_no already exists
    $checkQuery = "SELECT id FROM households WHERE household_no = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('s', $household_no);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Household number already exists.']);
        return;
    }

    // Check if the selected resident exists and is not already in a household
    $residentCheckQuery = "SELECT id, household_id FROM residents WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($residentCheckQuery);
    $stmt->bind_param('i', $head_resident_id);
    $stmt->execute();
    $residentResult = $stmt->get_result();

    if ($residentResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected resident does not exist.']);
        return;
    }

    $resident = $residentResult->fetch_assoc();
    if ($resident['household_id']) {
        echo json_encode(['success' => false, 'message' => 'Selected resident is already part of another household.']);
        return;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create household
        $insertQuery = "INSERT INTO households (household_no, address, head_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('ssi', $household_no, $address, $head_resident_id);

        if (!$stmt->execute()) {
            throw new Exception('Failed to create household');
        }

        $householdId = $stmt->insert_id;

        // Assign resident to household
        $updateResidentQuery = "UPDATE residents SET household_id = ? WHERE id = ?";
        $stmt = $conn->prepare($updateResidentQuery);
        $stmt->bind_param('ii', $householdId, $head_resident_id);

        if (!$stmt->execute()) {
            throw new Exception('Failed to assign resident to household');
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Household created successfully and resident assigned as head.',
            'id' => $householdId
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to create household: ' . $e->getMessage()]);
    }
}

function handleUpdateHousehold() {
    global $conn;

    $id = (int)($_POST['id'] ?? 0);
    $household_no = trim($_POST['household_no'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!$id || empty($household_no) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Household ID, number, and address are required.']);
        return;
    }

    // Check if household exists
    $checkQuery = "SELECT id FROM households WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Household not found.']);
        return;
    }

    // Check if household_no is already used by another household
    $duplicateQuery = "SELECT id FROM households WHERE household_no = ? AND id != ?";
    $stmt = $conn->prepare($duplicateQuery);
    $stmt->bind_param('si', $household_no, $id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Household number already exists.']);
        return;
    }

    // Update household (head cannot be changed after creation)
    $updateQuery = "UPDATE households SET household_no = ?, address = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('ssi', $household_no, $address, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Household updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update household.']);
    }
}

function handleArchiveHousehold() {
    global $conn;

    $id = (int)($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Household ID is required.']);
        return;
    }

    // Check if household exists
    $checkQuery = "SELECT id FROM households WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Household not found.']);
        return;
    }

    // Check if household has residents (you might want to prevent archiving households with residents)
    $residentCheckQuery = "SELECT COUNT(*) as count FROM residents WHERE household_id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($residentCheckQuery);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $residentCount = $result->fetch_assoc()['count'];

    if ($residentCount > 0) {
        echo json_encode(['success' => false, 'message' => "Cannot archive household. It has $residentCount active resident(s). Please reassign or archive residents first."]);
        return;
    }

    // Archive household (you could add a deleted_at field to households table, or just delete it)
    $deleteQuery = "DELETE FROM households WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Household archived successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive household.']);
    }
}
?>
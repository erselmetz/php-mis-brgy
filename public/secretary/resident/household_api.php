<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
            handleGetHouseholds();
            break;

        case 'POST':
            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'create': handleCreateHousehold(); break;
                case 'update': handleUpdateHousehold(); break;
                case 'archive': handleArchiveHousehold(); break;
                case 'restore': handleRestoreHousehold(); break;
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
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    echo json_encode($response);
}

function handleGetHouseholds()
{
    global $conn;

    $search   = $_GET['search'] ?? '';
    $limit    = (int) ($_GET['limit'] ?? 50);
    $archived = isset($_GET['archived']) && $_GET['archived'] == '1';

    $whereBase = $archived ? "h.archived_at IS NOT NULL" : "h.archived_at IS NULL";

    if (!empty($search)) {
        $searchTerm = $conn->real_escape_string($search);
        $whereBase .= " AND (h.household_no LIKE '%$searchTerm%'
                          OR h.address      LIKE '%$searchTerm%'
                          OR CONCAT(hr.first_name,' ',hr.last_name) LIKE '%$searchTerm%')";
    }

    $countResult = $conn->query("SELECT COUNT(*) as total FROM households h WHERE $whereBase");
    $total = $countResult->fetch_assoc()['total'];

    $query = "SELECT h.id, h.household_no, h.address, h.created_at, h.head_id, h.archived_at,
                     CONCAT_WS(' ', hr.first_name, hr.middle_name, hr.last_name, hr.suffix) as head_name,
                     COUNT(r.id) as total_members
              FROM households h
              LEFT JOIN residents hr ON h.head_id = hr.id
              LEFT JOIN residents r  ON h.id = r.household_id AND r.deleted_at IS NULL
              WHERE $whereBase
              GROUP BY h.id, h.household_no, h.address, h.created_at, h.archived_at,
                       hr.first_name, hr.middle_name, hr.last_name, hr.suffix
              ORDER BY " . ($archived ? "h.archived_at DESC" : "h.household_no ASC") . "
              LIMIT $limit";

    $result = $conn->query($query);
    $households = [];
    while ($row = $result->fetch_assoc()) {
        $households[] = [
            'id'            => $row['id'],
            'household_no'  => $row['household_no'],
            'address'       => $row['address'],
            'head_name'     => $row['head_name'] ?: 'Unknown',
            'head_id'       => $row['head_id'] ?? null,
            'total_members' => (int) $row['total_members'],
            'created_at'    => $row['created_at'],
            'archived_at'   => $row['archived_at'],
        ];
    }

    echo json_encode(['success' => true, 'households' => $households, 'total' => $total]);
}

function handleCreateHousehold()
{
    global $conn;

    $household_no     = trim($_POST['household_no']     ?? '');
    $address          = trim($_POST['address']          ?? '');
    $head_resident_id = (int)($_POST['head_resident_id'] ?? 0);

    // Validate required fields
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
        echo json_encode(['success' => false, 'message' => "Household number '{$household_no}' already exists. Please use a different number."]);
        $stmt->close();
        return;
    }
    $stmt->close();

    // Check if the selected resident exists (active, not deleted)
    // NOTE: We do NOT block residents who already belong to another household
    // because the head of household IS a member of the household being created.
    $residentCheckQuery = "SELECT id, household_id FROM residents WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($residentCheckQuery);
    $stmt->bind_param('i', $head_resident_id);
    $stmt->execute();
    $residentResult = $stmt->get_result();
    $stmt->close();

    if ($residentResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected resident does not exist or has been archived.']);
        return;
    }

    // Fetch resident's full name for head_name column
    $nameStmt = $conn->prepare("SELECT CONCAT_WS(' ', first_name, middle_name, last_name, suffix) as full_name FROM residents WHERE id = ?");
    $nameStmt->bind_param('i', $head_resident_id);
    $nameStmt->execute();
    $nameRow = $nameStmt->get_result()->fetch_assoc();
    $nameStmt->close();
    $head_name = trim($nameRow['full_name'] ?? '');

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Create the household record
        $insertQuery = "INSERT INTO households (household_no, address, head_id, head_name) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        if ($stmt === false) {
            throw new Exception('Failed to prepare household insert: ' . $conn->error);
        }
        $stmt->bind_param('ssis', $household_no, $address, $head_resident_id, $head_name);
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert household: ' . $stmt->error);
        }
        $householdId = $stmt->insert_id;
        $stmt->close();

        // 2. Assign the head resident to this household
        $updateResidentQuery = "UPDATE residents SET household_id = ? WHERE id = ?";
        $stmt = $conn->prepare($updateResidentQuery);
        if ($stmt === false) {
            throw new Exception('Failed to prepare resident update: ' . $conn->error);
        }
        $stmt->bind_param('ii', $householdId, $head_resident_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to assign resident to household: ' . $stmt->error);
        }
        $stmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => "Household '{$household_no}' created successfully.",
            'id'      => $householdId
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log('Create Household Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleUpdateHousehold()
{
    global $conn;

    $id               = (int)($_POST['id']               ?? 0);
    $household_no     = trim($_POST['household_no']       ?? '');
    $address          = trim($_POST['address']            ?? '');
    $head_resident_id = (int)($_POST['head_resident_id']  ?? 0);

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
        $stmt->close();
        return;
    }
    $stmt->close();

    // Check duplicate household_no (excluding self)
    $duplicateQuery = "SELECT id FROM households WHERE household_no = ? AND id != ?";
    $stmt = $conn->prepare($duplicateQuery);
    $stmt->bind_param('si', $household_no, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Household number '{$household_no}' already exists."]);
        $stmt->close();
        return;
    }
    $stmt->close();

    $updateQuery = "UPDATE households SET household_no = ?, address = ?, head_id = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('ssii', $household_no, $address, $head_resident_id, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Household updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update household: ' . $stmt->error]);
    }
    $stmt->close();
}

function handleArchiveHousehold()
{
    global $conn;

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Household ID is required.']);
        return;
    }

    // Check exists and is not already archived
    $check = $conn->prepare("SELECT id FROM households WHERE id = ? AND archived_at IS NULL");
    $check->bind_param('i', $id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Household not found or already archived.']);
        $check->close();
        return;
    }
    $check->close();

    // Block if active residents still assigned
    $resCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM residents WHERE household_id = ? AND deleted_at IS NULL");
    $resCheck->bind_param('i', $id);
    $resCheck->execute();
    $cnt = $resCheck->get_result()->fetch_assoc()['cnt'];
    $resCheck->close();

    if ($cnt > 0) {
        echo json_encode(['success' => false,
            'message' => "Cannot archive household — it still has {$cnt} active resident(s). Reassign or archive residents first."]);
        return;
    }

    $stmt = $conn->prepare("UPDATE households SET archived_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Household archived successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive household: ' . $stmt->error]);
    }
    $stmt->close();
}

function handleRestoreHousehold()
{
    global $conn;

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Household ID is required.']);
        return;
    }

    $check = $conn->prepare("SELECT id FROM households WHERE id = ? AND archived_at IS NOT NULL");
    $check->bind_param('i', $id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Household not found or not archived.']);
        $check->close();
        return;
    }
    $check->close();

    $stmt = $conn->prepare("UPDATE households SET archived_at = NULL WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Household restored successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to restore household: ' . $stmt->error]);
    }
    $stmt->close();
}
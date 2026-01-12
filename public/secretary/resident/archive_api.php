<?php
require_once '../../../includes/app.php';
requireAdmin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
            // Get archived residents
            handleGetArchivedResidents();
            break;

        case 'POST':
            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'archive':
                    handleArchiveResident();
                    break;

                case 'restore':
                    handleRestoreResident();
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
    error_log('Archive API Error: ' . $e->getMessage());
    $response['message'] = 'An error occurred';
    echo json_encode($response);
}

function handleGetArchivedResidents() {
    global $conn;

    $search = $_GET['search'] ?? '';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);

    // Build query
    $where = "deleted_at IS NOT NULL";
    if (!empty($search)) {
        $searchTerm = $conn->real_escape_string($search);
        $where .= " AND (CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ', COALESCE(suffix, '')) LIKE '%$searchTerm%' OR id LIKE '%$searchTerm%')";
    }

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM residents WHERE $where";
    $countResult = $conn->query($countQuery);
    $total = $countResult->fetch_assoc()['total'];

    // Get archived residents
    $query = "SELECT id, first_name, middle_name, last_name, suffix, deleted_at
              FROM residents
              WHERE $where
              ORDER BY deleted_at DESC
              LIMIT $limit OFFSET $offset";

    $result = $conn->query($query);

    $residents = [];
    while ($row = $result->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name'] . ' ' . ($row['suffix'] ?? ''));
        $residents[] = [
            'id' => $row['id'],
            'full_name' => $fullName,
            'archived_date' => date('Y-m-d', strtotime($row['deleted_at']))
        ];
    }

    echo json_encode([
        'success' => true,
        'residents' => $residents,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

function handleArchiveResident() {
    global $conn;

    $residentId = (int)($_POST['resident_id'] ?? 0);

    if (!$residentId) {
        echo json_encode(['success' => false, 'message' => 'Resident ID is required']);
        return;
    }

    // Check if resident exists and is not already archived
    $checkQuery = "SELECT id FROM residents WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $residentId);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Resident not found or already archived']);
        return;
    }

    // Archive the resident
    $archiveQuery = "UPDATE residents SET deleted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($archiveQuery);
    $stmt->bind_param('i', $residentId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resident archived successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive resident']);
    }
}

function handleRestoreResident() {
    global $conn;

    $residentId = (int)($_POST['resident_id'] ?? 0);

    if (!$residentId) {
        echo json_encode(['success' => false, 'message' => 'Resident ID is required']);
        return;
    }

    // Check if resident exists and is archived
    $checkQuery = "SELECT id FROM residents WHERE id = ? AND deleted_at IS NOT NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $residentId);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Resident not found or not archived']);
        return;
    }

    // Restore the resident
    $restoreQuery = "UPDATE residents SET deleted_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($restoreQuery);
    $stmt->bind_param('i', $residentId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resident restored successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to restore resident']);
    }
}
?>
<?php
require_once __DIR__ . '/../../../includes/app.php';
requireAdmin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
            // Get archived blotter cases
            handleGetArchivedBlotters();
            break;

        case 'POST':
            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'archive':
                    handleArchiveBlotter();
                    break;

                case 'restore':
                    handleRestoreBlotter();
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
    error_log('Blotter Archive API Error: ' . $e->getMessage());
    $response['message'] = 'An error occurred';
    echo json_encode($response);
}

function handleGetArchivedBlotters() {
    global $conn;

    $search = sanitizeString($_GET['search'] ?? '');
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);

    // Get total count
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $countQuery = "SELECT COUNT(*) as total FROM blotter WHERE archived_at IS NOT NULL AND (case_number LIKE ? OR complainant_name LIKE ? OR respondent_name LIKE ? OR incident_description LIKE ?)";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
    } else {
        $countQuery = "SELECT COUNT(*) as total FROM blotter WHERE archived_at IS NOT NULL";
        $countResult = $conn->query($countQuery);
        $total = $countResult->fetch_assoc()['total'];
    }

    // Get archived blotters
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $query = "SELECT id, case_number, complainant_name, respondent_name, incident_description, archived_at
                  FROM blotter
                  WHERE archived_at IS NOT NULL AND (case_number LIKE ? OR complainant_name LIKE ? OR respondent_name LIKE ? OR incident_description LIKE ?)
                  ORDER BY archived_at DESC
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssii", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
    } else {
        $query = "SELECT id, case_number, complainant_name, respondent_name, incident_description, archived_at
                  FROM blotter
                  WHERE archived_at IS NOT NULL
                  ORDER BY archived_at DESC
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $blotters = [];
    while ($row = $result->fetch_assoc()) {
        $parties = htmlspecialchars($row['complainant_name']) . ' vs ' . htmlspecialchars($row['respondent_name']);
        $incident = htmlspecialchars(mb_substr($row['incident_description'], 0, 50)) . (mb_strlen($row['incident_description']) > 50 ? '...' : '');
        
        $blotters[] = [
            'id' => $row['id'],
            'case_number' => $row['case_number'],
            'parties' => $parties,
            'incident' => $incident,
            'archived_date' => date('Y-m-d', strtotime($row['archived_at']))
        ];
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'blotters' => $blotters,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

function handleArchiveBlotter() {
    global $conn;

    $blotterId = (int)($_POST['blotter_id'] ?? 0);

    if (!$blotterId) {
        echo json_encode(['success' => false, 'message' => 'Blotter ID is required']);
        return;
    }

    // Check if blotter exists and is not already archived
    $checkQuery = "SELECT id FROM blotter WHERE id = ? AND archived_at IS NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $blotterId);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Blotter case not found or already archived']);
        $stmt->close();
        return;
    }
    $stmt->close();

    // Archive the blotter
    $archiveQuery = "UPDATE blotter SET archived_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($archiveQuery);
    $stmt->bind_param('i', $blotterId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Blotter case archived successfully']);
    } else {
        error_log('Archive Blotter Error: ' . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to archive blotter case']);
    }
    $stmt->close();
}

function handleRestoreBlotter() {
    global $conn;

    $blotterId = (int)($_POST['blotter_id'] ?? 0);

    if (!$blotterId) {
        echo json_encode(['success' => false, 'message' => 'Blotter ID is required']);
        return;
    }

    // Check if blotter exists and is archived
    $checkQuery = "SELECT id FROM blotter WHERE id = ? AND archived_at IS NOT NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $blotterId);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Blotter case not found or not archived']);
        $stmt->close();
        return;
    }
    $stmt->close();

    // Restore the blotter
    $restoreQuery = "UPDATE blotter SET archived_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($restoreQuery);
    $stmt->bind_param('i', $blotterId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Blotter case restored successfully']);
    } else {
        error_log('Restore Blotter Error: ' . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to restore blotter case']);
    }
    $stmt->close();
}

/**
 * IMPORTANT: Do not close $conn here
 * It's a shared connection managed by db.php
 * Closing it would break other operations that use the same connection
 */

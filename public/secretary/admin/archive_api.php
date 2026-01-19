<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'POST':
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'archive_current_term':
                    handleArchiveCurrentTerm();
                    break;
                    
                case 'restore':
                    handleRestoreOfficer();
                    break;
                    
                default:
                    $response['message'] = 'Invalid action';
                    echo json_encode($response);
            }
            break;
            
        case 'GET':
            handleGetArchivedOfficers();
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            echo json_encode($response);
    }
} catch (Exception $e) {
    error_log('Archive API Error: ' . $e->getMessage());
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    echo json_encode($response);
}

function handleArchiveCurrentTerm() {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get the latest secretary account (should not be archived)
        $stmt = $conn->prepare("
            SELECT u.id, o.id as officer_id
            FROM users u
            LEFT JOIN officers o ON u.id = o.user_id
            WHERE u.role = 'secretary'
            ORDER BY u.created_at DESC
            LIMIT 1
        ");
        $stmt->execute();
        $latestSecretary = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $excludeUserId = $latestSecretary ? $latestSecretary['id'] : null;
        $excludeOfficerId = $latestSecretary ? $latestSecretary['officer_id'] : null;
        
        // Archive all officers except the latest secretary
        $archiveQuery = "
            UPDATE officers 
            SET archived_at = NOW(), status = 'Inactive'
            WHERE archived_at IS NULL
        ";
        
        if ($excludeOfficerId) {
            $archiveQuery .= " AND id != ?";
            $stmt = $conn->prepare($archiveQuery);
            $stmt->bind_param('i', $excludeOfficerId);
        } else {
            $stmt = $conn->prepare($archiveQuery);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to archive officers: ' . $stmt->error);
        }
        $archivedCount = $stmt->affected_rows;
        $stmt->close();
        
        // Record history for archived officers
        $historyStmt = $conn->prepare("
            INSERT INTO term_history (officer_id, user_id, action_type, old_status, new_status, user_name, notes)
            SELECT o.id, ?, 'archived', o.status, 'Inactive', ?, 'Archived current term - all officers except latest secretary'
            FROM officers o
            WHERE o.archived_at IS NOT NULL 
            AND o.archived_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $userId = $_SESSION['user_id'];
        $userName = $_SESSION['name'] ?? 'System';
        $historyStmt->bind_param('is', $userId, $userName);
        $historyStmt->execute();
        $historyStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully archived {$archivedCount} officer(s). Latest secretary account preserved.",
            'archived_count' => $archivedCount
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Archive Current Term Error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to archive current term: ' . $e->getMessage()
        ]);
    }
}

function handleGetArchivedOfficers() {
    global $conn;
    
    $search = sanitizeString($_GET['search'] ?? '');
    
    $query = "
        SELECT o.*, 
               u.name as user_name, u.username, u.role,
               r.first_name, r.middle_name, r.last_name,
               CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as resident_name
        FROM officers o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN residents r ON o.resident_id = r.id
        WHERE o.archived_at IS NOT NULL
    ";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $query .= " AND (
            u.name LIKE ? OR 
            u.username LIKE ? OR 
            o.position LIKE ? OR
            CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) LIKE ?
        )";
        $searchParam = "%{$search}%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        $types = 'ssss';
    }
    
    $query .= " ORDER BY o.archived_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $officers = [];
    while ($row = $result->fetch_assoc()) {
        $officers[] = [
            'id' => $row['id'],
            'user_name' => $row['user_name'] ?? 'N/A',
            'username' => $row['username'] ?? 'N/A',
            'role' => $row['role'] ?? 'N/A',
            'position' => $row['position'],
            'resident_name' => $row['resident_name'] ?? 'Not a resident',
            'term_start' => $row['term_start'],
            'term_end' => $row['term_end'],
            'status' => $row['status'],
            'archived_at' => $row['archived_at']
        ];
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'officers' => $officers,
        'total' => count($officers)
    ]);
}

function handleRestoreOfficer() {
    global $conn;
    
    $officerId = (int)($_POST['officer_id'] ?? 0);
    
    if (!$officerId) {
        echo json_encode(['success' => false, 'message' => 'Officer ID is required']);
        return;
    }
    
    // Check if officer exists and is archived
    $checkQuery = "SELECT id FROM officers WHERE id = ? AND archived_at IS NOT NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $officerId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Officer not found or not archived']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // Restore the officer
    $restoreQuery = "UPDATE officers SET archived_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($restoreQuery);
    $stmt->bind_param('i', $officerId);
    
    if ($stmt->execute()) {
        // Record history
        $historyStmt = $conn->prepare("
            INSERT INTO term_history (officer_id, user_id, action_type, user_name, notes)
            SELECT ?, ?, 'restored', ?, 'Officer restored from archive'
        ");
        $userId = $_SESSION['user_id'];
        $userName = $_SESSION['name'] ?? 'System';
        $historyStmt->bind_param('iis', $officerId, $userId, $userName);
        $historyStmt->execute();
        $historyStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Officer restored successfully']);
    } else {
        error_log('Restore Officer Error: ' . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to restore officer']);
    }
    $stmt->close();
}
?>

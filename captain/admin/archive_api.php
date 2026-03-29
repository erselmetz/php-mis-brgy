<?php
require_once __DIR__ . '/../../includes/app.php';
requireCaptain();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

// Helper: build full name from residents table
include_once __DIR__ . '/helper.php';

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
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        case 'GET':
            handleGetArchivedOfficers();
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Archive API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

/**
 * ARCHIVE CURRENT TERM
 * - archive all officers except latest secretary
 * - set officers.status='Inactive'
 * - set users.status='disabled'
 * - log history
 */
function handleArchiveCurrentTerm()
{
    global $conn;

    $conn->begin_transaction();

    try {
        // Latest secretary account (exclude from archive)
        $stmt = $conn->prepare("
            SELECT u.id AS user_id, o.id AS officer_id
            FROM users u
            LEFT JOIN officers o ON u.id = o.user_id
            WHERE u.role = 'secretary'
            ORDER BY u.created_at DESC
            LIMIT 1
        ");
        if (!$stmt) throw new Exception('Failed to find latest secretary: ' . $conn->error);
        $stmt->execute();
        $latestSecretary = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $excludeUserId = $latestSecretary['user_id'] ?? null;
        $excludeOfficerId = $latestSecretary['officer_id'] ?? null;

        /**
         * Step 1: Archive officers
         */
        $archiveQuery = "
            UPDATE officers
            SET archived_at = NOW(), status = 'Inactive'
            WHERE archived_at IS NULL
        ";
        if ($excludeOfficerId) {
            $archiveQuery .= " AND id != ?";
            $stmt = $conn->prepare($archiveQuery);
            if (!$stmt) throw new Exception('Archive prepare failed: ' . $conn->error);
            $stmt->bind_param('i', $excludeOfficerId);
        } else {
            $stmt = $conn->prepare($archiveQuery);
            if (!$stmt) throw new Exception('Archive prepare failed: ' . $conn->error);
        }

        if (!$stmt->execute()) {
            throw new Exception('Failed to archive officers: ' . $stmt->error);
        }
        $archivedCount = $stmt->affected_rows;
        $stmt->close();

        /**
         * Step 2: Disable corresponding users (unified status)
         * Disable all users that have an officer archived_at NOT NULL,
         * except latest secretary user_id
         */
        $disableUsersSql = "
            UPDATE users u
            INNER JOIN officers o ON o.user_id = u.id
            SET u.status = 'disabled'
            WHERE o.archived_at IS NOT NULL
        ";
        if ($excludeUserId) {
            $disableUsersSql .= " AND u.id != ?";
            $stmt = $conn->prepare($disableUsersSql);
            if (!$stmt) throw new Exception('Disable users prepare failed: ' . $conn->error);
            $stmt->bind_param('i', $excludeUserId);
        } else {
            $stmt = $conn->prepare($disableUsersSql);
            if (!$stmt) throw new Exception('Disable users prepare failed: ' . $conn->error);
        }

        if (!$stmt->execute()) {
            throw new Exception('Failed to disable users: ' . $stmt->error);
        }
        $stmt->close();

        /**
         * Step 3: History log
         * ⚠️ IMPORTANT: your original query used o.status after update
         * so old_status becomes wrong. We'll store old_status as 'Active'
         * since we know we just archived active term officers.
         */
        $historyStmt = $conn->prepare("
            INSERT INTO term_history (officer_id, user_id, action_type, old_status, new_status, user_name, notes)
            SELECT o.id, ?, 'archived', 'Active', 'Inactive', ?, 'Archived current term - all officers except latest secretary'
            FROM officers o
            WHERE o.archived_at IS NOT NULL
              AND o.archived_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        if (!$historyStmt) throw new Exception('History prepare failed: ' . $conn->error);

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userName = $_SESSION['name'] ?? 'System';
        $historyStmt->bind_param('is', $userId, $userName);

        if (!$historyStmt->execute()) {
            throw new Exception('Failed to write history: ' . $historyStmt->error);
        }
        $historyStmt->close();

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

function handleGetArchivedOfficers()
{
    global $conn;

    $search = sanitizeString($_GET['search'] ?? '');

    $query = "
        SELECT o.*,
               u.name as user_name, u.username, u.role, u.status as user_status,
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
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query prepare failed']);
        return;
    }

    if (!empty($params)) $stmt->bind_param($types, ...$params);

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

            // show both if you want debugging
            'status' => $row['status'],         // officer status
            'user_status' => $row['user_status'], // unified user status

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

/**
 * RESTORE OFFICER
 * - set officers.archived_at=NULL
 * - officers.status='Active'
 * - users.status='active'
 */
function handleRestoreOfficer()
{
    global $conn;

    $officerId = (int)($_POST['officer_id'] ?? 0);
    if (!$officerId) {
        echo json_encode(['success' => false, 'message' => 'Officer ID is required']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Get officer + user_id
        $stmt = $conn->prepare("SELECT id, user_id FROM officers WHERE id = ? AND archived_at IS NOT NULL LIMIT 1");
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
        $stmt->bind_param('i', $officerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Officer not found or not archived']);
            $conn->rollback();
            return;
        }

        $userIdToRestore = (int)$row['user_id'];

        // Restore officer + status
        $restoreQuery = "UPDATE officers SET archived_at = NULL, status = 'Active' WHERE id = ?";
        $stmt = $conn->prepare($restoreQuery);
        if (!$stmt) throw new Exception('Restore prepare failed: ' . $conn->error);
        $stmt->bind_param('i', $officerId);

        if (!$stmt->execute()) {
            throw new Exception('Failed to restore officer: ' . $stmt->error);
        }
        $stmt->close();

        // Restore user unified status
        $userRestore = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        if (!$userRestore) throw new Exception('User restore prepare failed: ' . $conn->error);
        $userRestore->bind_param('i', $userIdToRestore);

        if (!$userRestore->execute()) {
            throw new Exception('Failed to restore user: ' . $userRestore->error);
        }
        $userRestore->close();

        // History
        $historyStmt = $conn->prepare("
            INSERT INTO term_history (officer_id, user_id, action_type, old_status, new_status, user_name, notes)
            VALUES (?, ?, 'restored', 'Inactive', 'Active', ?, 'Officer restored from archive')
        ");
        if (!$historyStmt) throw new Exception('History prepare failed: ' . $conn->error);

        $byUserId = (int)($_SESSION['user_id'] ?? 0);
        $byUserName = $_SESSION['name'] ?? 'System';
        $historyStmt->bind_param('iis', $officerId, $byUserId, $byUserName);
        $historyStmt->execute();
        $historyStmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Officer restored successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Restore Officer Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to restore officer: ' . $e->getMessage()]);
    }
}
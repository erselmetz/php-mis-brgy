<?php
/**
 * Tanod Duty Schedule API
 * Actions: list | save | delete
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireKagawad();
header('Content-Type: application/json; charset=utf-8');

$action = trim($_REQUEST['action'] ?? '');

// ─── LIST ────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    try {
        $search = sanitizeString($_GET['search'] ?? '');
        $filterDate = $_GET['filter_date'] ?? '';
        $filterShift = sanitizeString($_GET['filter_shift'] ?? '');

        $where = [];
        $params = [];
        $types = '';

        if (!empty($search)) {
            $where[] = "(t.tanod_name LIKE ? OR t.duty_code LIKE ? OR t.post_location LIKE ?)";
            $s = "%{$search}%";
            $params = array_merge($params, [$s, $s, $s]);
            $types .= 'sss';
        }
        if (!empty($filterDate)) {
            $where[] = "t.duty_date = ?";
            $params[] = $filterDate;
            $types .= 's';
        }
        if (!empty($filterShift)) {
            $where[] = "t.shift = ?";
            $params[] = $filterShift;
            $types .= 's';
        }

        $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT t.*, 
                       u.name AS created_by_name,
                       u2.name AS updated_by_name
                FROM tanod_duty_schedule t
                LEFT JOIN users u  ON t.created_by = u.id
                LEFT JOIN users u2 ON t.updated_by  = u2.id
                {$whereClause}
                ORDER BY t.duty_date DESC, FIELD(t.shift,'morning','afternoon','night')";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc())
            $rows[] = $r;
        $stmt->close();

        // Shift counts for today
        $todayDate = date('Y-m-d');
        $counts = ['morning' => 0, 'afternoon' => 0, 'night' => 0];
        $cStmt = $conn->prepare("SELECT shift, COUNT(*) as cnt FROM tanod_duty_schedule WHERE duty_date = ? AND status = 'active' GROUP BY shift");
        $cStmt->bind_param('s', $todayDate);
        $cStmt->execute();
        $cRes = $cStmt->get_result();
        while ($cr = $cRes->fetch_assoc()) {
            $counts[$cr['shift']] = (int) $cr['cnt'];
        }
        $cStmt->close();

        echo json_encode(['status' => 'ok', 'data' => $rows, 'counts' => $counts]);
    } catch (Exception $e) {
        error_log('Tanod duty list error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to load records.', 'data' => []]);
    }
    exit;
}

// ─── SAVE (Insert / Update) ──────────────────────────────────────────────────
if ($action === 'save') {
    $id = (int) ($_POST['id'] ?? 0);
    $tanod_name = sanitizeString($_POST['tanod_name'] ?? '');
    $duty_date = $_POST['duty_date'] ?? '';
    $shift = sanitizeString($_POST['shift'] ?? 'morning');
    $post_location = sanitizeString($_POST['post_location'] ?? '');
    $notes = sanitizeString($_POST['notes'] ?? '');
    $status = sanitizeString($_POST['status'] ?? 'active');

    if (empty($tanod_name) || empty($duty_date)) {
        echo json_encode(['success' => false, 'message' => 'Tanod Name and Duty Date are required.']);
        exit;
    }
    if (!in_array($shift, ['morning', 'afternoon', 'night']))
        $shift = 'morning';
    if (!in_array($status, ['active', 'completed', 'cancelled']))
        $status = 'active';
    if (!validateDateFormat($duty_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
        exit;
    }

    $userId = $_SESSION['user_id'] ?? 0;

    try {
        if ($id > 0) {
            // UPDATE
            $checkStmt = $conn->prepare("SELECT id FROM tanod_duty_schedule WHERE id = ?");
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Record not found.']);
                exit;
            }
            $checkStmt->close();

            $stmt = $conn->prepare("UPDATE tanod_duty_schedule SET tanod_name=?, duty_date=?, shift=?, post_location=?, notes=?, status=?, updated_by=? WHERE id=?");
            $stmt->bind_param('ssssssii', $tanod_name, $duty_date, $shift, $post_location, $notes, $status, $userId, $id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Duty schedule updated successfully.']);
        } else {
            // INSERT — generate duty code
            $year = date('Y');
            $codeStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tanod_duty_schedule WHERE duty_code LIKE ?");
            $pattern = "TDS-{$year}-%";
            $codeStmt->bind_param('s', $pattern);
            $codeStmt->execute();
            $codeRow = $codeStmt->get_result()->fetch_assoc();
            $codeStmt->close();
            $duty_code = 'TDS-' . $year . '-' . str_pad((int) $codeRow['cnt'] + 1, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO tanod_duty_schedule (duty_code, tanod_name, duty_date, shift, post_location, notes, status, created_by) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssi', $duty_code, $tanod_name, $duty_date, $shift, $post_location, $notes, $status, $userId);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Duty schedule assigned successfully.', 'duty_code' => $duty_code]);
        }
    } catch (Exception $e) {
        error_log('Tanod duty save error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
    exit;
}

// ─── DELETE ──────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }
    try {
        $stmt = $conn->prepare("DELETE FROM tanod_duty_schedule WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Duty assignment deleted.']);
    } catch (Exception $e) {
        error_log('Tanod duty delete error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete record.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
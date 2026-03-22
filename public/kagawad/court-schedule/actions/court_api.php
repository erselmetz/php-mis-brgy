<?php
/**
 * Court / Facility Schedule API
 * Actions: list | save | delete | check_conflict
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
        $filterFac = sanitizeString($_GET['filter_facility'] ?? '');
        $filterStat = sanitizeString($_GET['filter_status'] ?? '');

        $where = [];
        $params = [];
        $types = '';

        if (!empty($search)) {
            $where[] = "(c.borrower_name LIKE ? OR c.reservation_code LIKE ? OR c.organization LIKE ? OR c.purpose LIKE ?)";
            $s = "%{$search}%";
            array_push($params, $s, $s, $s, $s);
            $types .= 'ssss';
        }
        if (!empty($filterDate)) {
            $where[] = "c.reservation_date = ?";
            $params[] = $filterDate;
            $types .= 's';
        }
        if (!empty($filterFac)) {
            $where[] = "c.facility = ?";
            $params[] = $filterFac;
            $types .= 's';
        }
        if (!empty($filterStat)) {
            $where[] = "c.status = ?";
            $params[] = $filterStat;
            $types .= 's';
        }

        $wc = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT c.*, u.name AS created_by_name, u2.name AS approved_by_name
                FROM court_schedule c
                LEFT JOIN users u  ON c.created_by  = u.id
                LEFT JOIN users u2 ON c.approved_by = u2.id
                {$wc}
                ORDER BY c.reservation_date DESC, c.time_start ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params))
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Counts
        $counts = ['pending' => 0, 'approved' => 0, 'denied' => 0, 'completed' => 0];
        $cRes = $conn->query("SELECT status, COUNT(*) AS cnt FROM court_schedule GROUP BY status");
        while ($cr = $cRes->fetch_assoc()) {
            if (isset($counts[$cr['status']]))
                $counts[$cr['status']] = (int) $cr['cnt'];
        }

        echo json_encode(['status' => 'ok', 'data' => $rows, 'counts' => $counts]);
    } catch (Exception $e) {
        error_log('Court list error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to load records.', 'data' => []]);
    }
    exit;
}

// ─── CHECK CONFLICT ──────────────────────────────────────────────────────────
if ($action === 'check_conflict') {
    $facility = sanitizeString($_GET['facility'] ?? '');
    $date = $_GET['date'] ?? '';
    $tStart = $_GET['time_start'] ?? '';
    $tEnd = $_GET['time_end'] ?? '';
    $excludeId = (int) ($_GET['exclude_id'] ?? 0);

    $sql = "SELECT id FROM court_schedule 
            WHERE facility = ? AND reservation_date = ? 
              AND status NOT IN ('denied','cancelled')
              AND time_start < ? AND time_end > ?
              AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $facility, $date, $tEnd, $tStart, $excludeId);
    $stmt->execute();
    $conflict = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    echo json_encode(['conflict' => $conflict]);
    exit;
}

// ─── SAVE ────────────────────────────────────────────────────────────────────
if ($action === 'save') {
    $id = (int) ($_POST['id'] ?? 0);
    $borrower = sanitizeString($_POST['borrower_name'] ?? '');
    $contact = sanitizeString($_POST['borrower_contact'] ?? '');
    $org = sanitizeString($_POST['organization'] ?? '');
    $facility = sanitizeString($_POST['facility'] ?? '');
    $res_date = $_POST['reservation_date'] ?? '';
    $time_start = $_POST['time_start'] ?? '';
    $time_end = $_POST['time_end'] ?? '';
    $purpose = sanitizeString($_POST['purpose'] ?? '');
    $status = sanitizeString($_POST['status'] ?? 'pending');
    $remarks = sanitizeString($_POST['remarks'] ?? '');

    if (empty($borrower) || empty($facility) || empty($res_date) || empty($time_start) || empty($time_end) || empty($purpose)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }
    if (!in_array($facility, ['basketball_court', 'multipurpose_area', 'gym'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid facility.']);
        exit;
    }
    if ($time_start >= $time_end) {
        echo json_encode(['success' => false, 'message' => 'Time End must be after Time Start.']);
        exit;
    }

    $userId = $_SESSION['user_id'] ?? 0;

    try {
        // Conflict check
        $cSql = "SELECT id FROM court_schedule WHERE facility=? AND reservation_date=? AND status NOT IN ('denied','cancelled') AND time_start < ? AND time_end > ? AND id != ?";
        $cStmt = $conn->prepare($cSql);
        $cStmt->bind_param('ssssi', $facility, $res_date, $time_end, $time_start, $id);
        $cStmt->execute();
        if ($cStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Schedule conflict detected! Another reservation exists for this facility and time slot.', 'conflict' => true]);
            exit;
        }
        $cStmt->close();

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE court_schedule SET borrower_name=?,borrower_contact=?,organization=?,facility=?,reservation_date=?,time_start=?,time_end=?,purpose=?,status=?,remarks=?,updated_by=? WHERE id=?");
            $stmt->bind_param('ssssssssssii', $borrower, $contact, $org, $facility, $res_date, $time_start, $time_end, $purpose, $status, $remarks, $userId, $id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Reservation updated successfully.']);
        } else {
            $year = date('Y');
            $codeStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM court_schedule WHERE reservation_code LIKE ?");
            $p = "CRS-{$year}-%";
            $codeStmt->bind_param('s', $p);
            $codeStmt->execute();
            $cnt = (int) $codeStmt->get_result()->fetch_assoc()['cnt'];
            $codeStmt->close();
            $res_code = 'CRS-' . $year . '-' . str_pad($cnt + 1, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO court_schedule (reservation_code,borrower_name,borrower_contact,organization,facility,reservation_date,time_start,time_end,purpose,status,remarks,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssssssi', $res_code, $borrower, $contact, $org, $facility, $res_date, $time_start, $time_end, $purpose, $status, $remarks, $userId);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Reservation created successfully.', 'reservation_code' => $res_code]);
        }
    } catch (Exception $e) {
        error_log('Court save error: ' . $e->getMessage());
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
        $stmt = $conn->prepare("DELETE FROM court_schedule WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Reservation deleted.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
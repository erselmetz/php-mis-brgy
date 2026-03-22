<?php
/**
 * Mobile Patrol / Roving Tanod Schedule API
 * Actions: list | save | delete
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireCaptain();
header('Content-Type: application/json; charset=utf-8');

$action = trim($_REQUEST['action'] ?? '');

// LIST
if ($action === 'list') {
    try {
        $search = sanitizeString($_GET['search'] ?? '');
        $filterDate = $_GET['filter_date'] ?? '';
        $filterStat = sanitizeString($_GET['filter_status'] ?? '');
        $filterWeekly = $_GET['filter_weekly'] ?? '';

        $where = [];
        $params = [];
        $types = '';

        if (!empty($search)) {
            $where[] = "(p.team_name LIKE ? OR p.patrol_code LIKE ? OR p.patrol_route LIKE ? OR p.area_covered LIKE ? OR p.tanod_members LIKE ?)";
            $s = "%{$search}%";
            array_push($params, $s, $s, $s, $s, $s);
            $types .= 'sssss';
        }
        if (!empty($filterDate)) {
            $where[] = "p.patrol_date = ?";
            $params[] = $filterDate;
            $types .= 's';
        }
        if (!empty($filterStat)) {
            $where[] = "p.status = ?";
            $params[] = $filterStat;
            $types .= 's';
        }
        if ($filterWeekly !== '') {
            $where[] = "p.is_weekly = ?";
            $params[] = (int) $filterWeekly;
            $types .= 'i';
        }

        $wc = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT p.*, u.name AS created_by_name, u2.name AS updated_by_name
                FROM patrol_schedule p
                LEFT JOIN users u  ON p.created_by = u.id
                LEFT JOIN users u2 ON p.updated_by  = u2.id
                {$wc}
                ORDER BY p.patrol_date DESC, p.time_start ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params))
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $counts = ['scheduled' => 0, 'ongoing' => 0, 'completed' => 0, 'cancelled' => 0, 'weekly' => 0];
        $cRes = $conn->query("SELECT status, COUNT(*) AS cnt FROM patrol_schedule GROUP BY status");
        while ($cr = $cRes->fetch_assoc()) {
            if (isset($counts[$cr['status']]))
                $counts[$cr['status']] = (int) $cr['cnt'];
        }
        $wkRes = $conn->query("SELECT COUNT(*) AS cnt FROM patrol_schedule WHERE is_weekly=1");
        if ($wkRes)
            $counts['weekly'] = (int) $wkRes->fetch_assoc()['cnt'];

        echo json_encode(['status' => 'ok', 'data' => $rows, 'counts' => $counts]);
    } catch (Exception $e) {
        error_log('Patrol list: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to load records.', 'data' => []]);
    }
    exit;
}

// SAVE
if ($action === 'save') {
    $id = (int) ($_POST['id'] ?? 0);
    $team_name = sanitizeString($_POST['team_name'] ?? '');
    $patrol_date = $_POST['patrol_date'] ?? '';
    $time_start = $_POST['time_start'] ?? '';
    $time_end = $_POST['time_end'] ?? '';
    $patrol_route = sanitizeString($_POST['patrol_route'] ?? '');
    $area_covered = sanitizeString($_POST['area_covered'] ?? '');
    $tanod_members = sanitizeString($_POST['tanod_members'] ?? '');
    $notes = sanitizeString($_POST['notes'] ?? '');
    $status = sanitizeString($_POST['status'] ?? 'scheduled');
    $is_weekly = (int) ($_POST['is_weekly'] ?? 0) ? 1 : 0;
    $week_day = $is_weekly ? (int) ($_POST['week_day'] ?? 0) : null;
    $userId = $_SESSION['user_id'] ?? 0;

    if (empty($team_name) || empty($patrol_date) || empty($time_start) || empty($time_end)) {
        echo json_encode(['success' => false, 'message' => 'Team Name, Date, Time Start and Time End are required.']);
        exit;
    }
    if (!in_array($status, ['scheduled', 'ongoing', 'completed', 'cancelled']))
        $status = 'scheduled';
    if ($time_start >= $time_end) {
        echo json_encode(['success' => false, 'message' => 'Time End must be after Time Start.']);
        exit;
    }

    try {
        if ($id > 0) {
            $sql = "UPDATE patrol_schedule SET team_name=?,patrol_date=?,time_start=?,time_end=?,patrol_route=?,area_covered=?,tanod_members=?,notes=?,status=?,is_weekly=?,week_day=?,updated_by=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssssssiiii', $team_name, $patrol_date, $time_start, $time_end, $patrol_route, $area_covered, $tanod_members, $notes, $status, $is_weekly, $week_day, $userId, $id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Patrol schedule updated successfully.']);
        } else {
            $year = date('Y');
            $cs = $conn->prepare("SELECT COUNT(*) AS cnt FROM patrol_schedule WHERE patrol_code LIKE ?");
            $pt = "PRS-{$year}-%";
            $cs->bind_param('s', $pt);
            $cs->execute();
            $cnt = (int) $cs->get_result()->fetch_assoc()['cnt'];
            $cs->close();
            $patrol_code = 'PRS-' . $year . '-' . str_pad($cnt + 1, 4, '0', STR_PAD_LEFT);

            $sql = "INSERT INTO patrol_schedule (patrol_code,team_name,patrol_date,time_start,time_end,patrol_route,area_covered,tanod_members,notes,status,is_weekly,week_day,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssssiii', $patrol_code, $team_name, $patrol_date, $time_start, $time_end, $patrol_route, $area_covered, $tanod_members, $notes, $status, $is_weekly, $week_day, $userId);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Patrol schedule created.', 'patrol_code' => $patrol_code]);
        }
    } catch (Exception $e) {
        error_log('Patrol save: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
    exit;
}

// DELETE
if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }
    try {
        $stmt = $conn->prepare("DELETE FROM patrol_schedule WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Patrol schedule deleted.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
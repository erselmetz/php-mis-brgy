<?php
/**
 * Appointments API
 * public/hcnurse/appointments/api/appointments_api.php
 *
 * Actions (GET):
 *   list        — paginated list with filters (date range, status, type, search)
 *   get         — single record
 *   today       — today's appointments count + list
 *   upcoming    — next 7 days
 *   calendar    — month view data (date => count)
 *
 * Actions (POST):
 *   save        — create or update
 *   update_status — quick status change
 *   delete      — soft delete via status=cancelled
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/* ── helpers ── */
function apptCode(mysqli $c): string {
    $y = date('Y');
    $r = $c->query("SELECT COUNT(*) n FROM appointments WHERE appt_code LIKE 'APT-{$y}-%'");
    $n = ($r ? (int)$r->fetch_assoc()['n'] : 0) + 1;
    return 'APT-' . $y . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
}
function p(mixed $v): mixed { return ($v === '' || $v === null) ? null : $v; }

/* ════════ list ════════ */
if ($action === 'list') {
    $from   = $_GET['from']   ?? date('Y-m-01');
    $to     = $_GET['to']     ?? date('Y-m-t');
    $status = $_GET['status'] ?? 'all';
    $type   = $_GET['type']   ?? 'all';
    $search = trim($_GET['search'] ?? '');

    $where  = "a.appt_date BETWEEN ? AND ?";
    $params = [$from, $to];
    $types  = 'ss';

    if ($status !== 'all') { $where .= " AND a.status=?"; $params[] = $status; $types .= 's'; }
    if ($type   !== 'all') { $where .= " AND a.appt_type=?"; $params[] = $type; $types .= 's'; }
    if ($search !== '') {
        $like = "%{$search}%";
        $where .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ? OR a.purpose LIKE ?)";
        array_push($params, $like, $like, $like, $like);
        $types .= 'ssss';
    }

    $sql = "SELECT a.*,
                   CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) full_name,
                   r.contact_no, r.birthdate, r.gender
            FROM appointments a
            INNER JOIN residents r ON r.id=a.resident_id AND r.deleted_at IS NULL
            WHERE {$where}
            ORDER BY a.appt_date ASC, a.appt_time ASC
            LIMIT 500";
    $st = $conn->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    json_ok_data(['data' => $rows, 'total' => count($rows)]);
}

/* ════════ today ════════ */
if ($action === 'today') {
    $today = date('Y-m-d');
    $st = $conn->prepare("
        SELECT a.*,
               CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) full_name,
               r.contact_no
        FROM appointments a
        INNER JOIN residents r ON r.id=a.resident_id AND r.deleted_at IS NULL
        WHERE a.appt_date=? AND a.status != 'cancelled'
        ORDER BY a.appt_time ASC
    ");
    $st->bind_param('s', $today);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    json_ok_data(['data' => $rows, 'total' => count($rows), 'date' => $today]);
}

/* ════════ upcoming ════════ */
if ($action === 'upcoming') {
    $from = date('Y-m-d');
    $to   = date('Y-m-d', strtotime('+7 days'));
    $st = $conn->prepare("
        SELECT a.*,
               CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) full_name
        FROM appointments a
        INNER JOIN residents r ON r.id=a.resident_id AND r.deleted_at IS NULL
        WHERE a.appt_date BETWEEN ? AND ? AND a.status='scheduled'
        ORDER BY a.appt_date ASC, a.appt_time ASC
        LIMIT 20
    ");
    $st->bind_param('ss', $from, $to);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    json_ok_data(['data' => $rows]);
}

/* ════════ calendar ════════ */
if ($action === 'calendar') {
    $month = $_GET['month'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
    $from = $month . '-01';
    $to   = date('Y-m-t', strtotime($from));
    $st = $conn->prepare("
        SELECT appt_date, status, COUNT(*) cnt
        FROM appointments
        WHERE appt_date BETWEEN ? AND ?
        GROUP BY appt_date, status
        ORDER BY appt_date
    ");
    $st->bind_param('ss', $from, $to);
    $st->execute();
    $raw = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $cal = [];
    foreach ($raw as $r) {
        $d = $r['appt_date'];
        if (!isset($cal[$d])) $cal[$d] = ['total'=>0,'scheduled'=>0,'completed'=>0,'cancelled'=>0,'no_show'=>0];
        $cal[$d]['total']          += (int)$r['cnt'];
        $cal[$d][$r['status']]     += (int)$r['cnt'];
    }
    json_ok_data(['calendar' => $cal, 'month' => $month]);
}

/* ════════ get ════════ */
if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) json_err('Invalid id');
    $st = $conn->prepare("
        SELECT a.*,
               CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) full_name,
               r.contact_no, r.birthdate, r.gender, r.address
        FROM appointments a
        INNER JOIN residents r ON r.id=a.resident_id AND r.deleted_at IS NULL
        WHERE a.id=? LIMIT 1
    ");
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!$row) json_err('Not found', 404);
    json_ok_data(['data' => $row]);
}

/* ════════ save (create / update) ════════ */
if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Invalid method', 405);

    $id         = (int)($_POST['id'] ?? 0);
    $rid        = (int)($_POST['resident_id'] ?? 0);
    $date       = trim($_POST['appt_date'] ?? '');
    $time       = trim($_POST['appt_time'] ?? '');
    $type       = trim($_POST['appt_type'] ?? 'general');
    $purpose    = trim($_POST['purpose'] ?? '');
    $worker     = p($_POST['health_worker'] ?? null);
    $status     = trim($_POST['status'] ?? 'scheduled');
    $notes      = p($_POST['notes'] ?? null);
    $userId     = (int)($_SESSION['user_id'] ?? 0);

    if ($rid <= 0)     json_err('Resident is required');
    if (!$date)        json_err('Date is required');
    if (!$time)        json_err('Time is required');
    if ($purpose==='') json_err('Purpose is required');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_err('Invalid date format');

    $validTypes   = ['general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','dental','other'];
    $validStatus  = ['scheduled','completed','cancelled','no_show'];
    if (!in_array($type,   $validTypes,  true)) $type   = 'general';
    if (!in_array($status, $validStatus, true)) $status = 'scheduled';

    if ($id > 0) {
        /* update */
        $st = $conn->prepare("
            UPDATE appointments SET
                resident_id=?, appt_date=?, appt_time=?, appt_type=?,
                purpose=?, health_worker=?, status=?, notes=?, updated_by=?
            WHERE id=? LIMIT 1
        ");
        $st->bind_param('isssssssii', $rid,$date,$time,$type,$purpose,$worker,$status,$notes,$userId,$id);
        if (!$st->execute()) json_err('Update failed: '.$st->error, 500);
        json_ok_data(['id' => $id], 'Appointment updated.');
    } else {
        /* insert */
        $code = apptCode($conn);
        $st = $conn->prepare("
            INSERT INTO appointments
                (appt_code,resident_id,appt_date,appt_time,appt_type,
                 purpose,health_worker,status,notes,created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        // s=appt_code, i=resident_id, s*7=date/time/type/purpose/worker/status/notes, i=created_by
        $st->bind_param('sisssssssi', $code,$rid,$date,$time,$type,$purpose,$worker,$status,$notes,$userId);
        if (!$st->execute()) json_err('Insert failed: '.$st->error, 500);
        json_ok_data(['id' => (int)$conn->insert_id, 'code' => $code], 'Appointment created.');
    }
}

/* ════════ update_status ════════ */
if ($action === 'update_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Invalid method', 405);
    $id     = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $valid  = ['scheduled','completed','cancelled','no_show'];
    if ($id <= 0 || !in_array($status, $valid, true)) json_err('Invalid params');
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $st = $conn->prepare("UPDATE appointments SET status=?, updated_by=? WHERE id=? LIMIT 1");
    $st->bind_param('sii', $status, $userId, $id);
    if (!$st->execute()) json_err('Update failed');
    json_ok_data(['id'=>$id,'status'=>$status], 'Status updated.');
}

json_err('Unknown action', 404);
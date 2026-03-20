<?php
/**
 * Health Records API — UPDATED
 * Added action=list_subtypes to return actual sub_type values from DB
 * This replaces the static SUBTYPE_OPTIONS in the JS frontend.
 *
 * Replaces / patches: public/hcnurse/health-records/api/health_records_api.php
 * (Only the top section changes — add the list_subtypes action before existing code)
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../includes/meta_helper.php';

function safe_program(string $type): string {
    $allowed = ['immunization','maternal','family_planning','prenatal','postnatal','child_nutrition'];
    return in_array($type, $allowed, true) ? $type : 'maternal';
}

function compute_date_range(string $period, string $month): array {
    $today     = date('Y-m-d');
    $currentYM = date('Y-m');

    if ($period === 'monthly') {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = $currentYM;
        $from = $month . '-01';
        $to   = date('Y-m-t', strtotime($from));
        return [$from, $to, $month];
    }
    if ($period === 'daily')  return [$today, $today, null];
    if ($period === 'weekly') {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        return [$monday, $sunday, null];
    }
    // all → current month
    $from = $currentYM . '-01';
    $to   = date('Y-m-t', strtotime($from));
    return [$from, $to, null];
}

function build_preview(array $meta): string {
    $parts = [];
    if (!empty($meta['sub_type']) && $meta['sub_type'] !== 'all')
        $parts[] = ucfirst(str_replace('_', ' ', (string)$meta['sub_type']));
    if (!empty($meta['status']))        $parts[] = (string)$meta['status'];
    if (!empty($meta['time']))          $parts[] = (string)$meta['time'];
    if (!empty($meta['health_worker'])) $parts[] = (string)$meta['health_worker'];
    return implode(' • ', $parts);
}

function row_with_meta(array $row): array {
    $meta = meta_decode($row['notes'] ?? '');
    $meta = meta_normalize($meta);
    $row['meta'] = $meta;
    $row['details_preview'] = build_preview($meta);
    return $row;
}

$action = $_GET['action'] ?? 'list';
$type   = safe_program($_GET['type'] ?? 'maternal');
$id     = (int)($_GET['id'] ?? 0);
$period = $_GET['period'] ?? 'all';
$month  = $_GET['month']  ?? date('Y-m');
$search = trim($_GET['search'] ?? '');
$sub    = trim($_GET['sub']    ?? 'all');

/* ════════════════════════════════════════
   NEW: list_subtypes — dynamic from DB
   Returns actual distinct sub_type values
   used in consultations for this program type.
════════════════════════════════════════ */
if ($action === 'list_subtypes') {
    $rows = $conn->query(
        "SELECT DISTINCT notes FROM consultations ORDER BY id DESC LIMIT 2000"
    );

    $subtypes = ['all'];
    $seen     = ['all' => true];

    if ($rows) while ($row = $rows->fetch_assoc()) {
        $meta    = meta_normalize(meta_decode($row['notes'] ?? ''));
        $program = $meta['program'] ?? '';
        $st      = trim($meta['sub_type'] ?? '');

        if ($program !== $type) continue;
        if ($st === '' || $st === 'all' || isset($seen[$st])) continue;

        $seen[$st] = true;
        $subtypes[] = $st;
    }

    json_ok(['subtypes' => $subtypes, 'type' => $type]);
}

/* GET SINGLE */
if ($action === 'get') {
    if ($id <= 0) json_err('Invalid id');

    $sql = "SELECT c.id, c.resident_id, c.complaint, c.diagnosis, c.treatment, c.notes, c.consultation_date,
                   CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
            FROM consultations c
            LEFT JOIN residents r ON r.id = c.resident_id
            WHERE c.id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) json_err('SQL prepare failed', 500);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_err('Record not found', 404);

    $row = row_with_meta($row);
    $program = $row['meta']['program'] ?? '';
    if ($program !== $type) json_err('Record does not match requested type', 404);

    json_ok(['data' => $row]);
}

/* UPDATE */
if ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Invalid method', 405);

    $id   = (int)($_POST['id'] ?? 0);
    $type = safe_program($_GET['type'] ?? ($_POST['type'] ?? 'maternal'));
    if ($id <= 0) json_err('Invalid id');

    /* Fetch existing to preserve program */
    $stmt = $conn->prepare("SELECT id, resident_id, complaint, diagnosis, treatment, notes, consultation_date FROM consultations WHERE id = ? LIMIT 1");
    if (!$stmt) json_err('SQL error', 500);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if (!$existing) json_err('Record not found', 404);

    $oldMeta = meta_normalize(meta_decode($existing['notes'] ?? ''));
    $oldProgram = $oldMeta['program'] ?? '';
    if ($oldProgram !== '' && $oldProgram !== $type) json_err('Record does not match requested type', 404);

    $resident_id       = (int)($_POST['resident_id']       ?? $existing['resident_id']);
    $complaint         = trim($_POST['complaint']          ?? $existing['complaint']);
    $diagnosis         = trim($_POST['diagnosis']          ?? $existing['diagnosis']);
    $treatment         = trim($_POST['treatment']          ?? $existing['treatment']);
    $consultation_date = trim($_POST['consultation_date']  ?? $existing['consultation_date']);

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $consultation_date)) {
        [$mm,$dd,$yyyy] = explode('/', $consultation_date);
        $consultation_date = sprintf('%04d-%02d-%02d', (int)$yyyy, (int)$mm, (int)$dd);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $consultation_date)) json_err('Invalid date format.');

    $time          = trim($_POST['consultation_time'] ?? ($oldMeta['time'] ?? ''));
    $health_worker = trim($_POST['health_worker']     ?? ($oldMeta['health_worker'] ?? ''));
    $status        = trim($_POST['status']            ?? ($oldMeta['status'] ?? 'Completed'));
    $remarks       = trim($_POST['remarks']           ?? ($oldMeta['remarks'] ?? ''));
    $sub_type      = trim($_POST['sub_type']          ?? ($oldMeta['sub_type'] ?? 'all'));

    /* ALWAYS preserve program */
    $newMeta = $oldMeta;
    $newMeta['program']       = $type;   /* locked to the page type */
    $newMeta['sub_type']      = $sub_type;
    $newMeta['status']        = $status;
    $newMeta['time']          = $time;
    $newMeta['health_worker'] = $health_worker;
    $newMeta['remarks']       = $remarks;
    $notes = meta_encode($newMeta);

    if ($resident_id <= 0) json_err('Resident is required.');
    if ($complaint === '')  json_err('Chief complaint is required.');

    $stmt = $conn->prepare("UPDATE consultations SET resident_id=?, complaint=?, diagnosis=?, treatment=?, notes=?, consultation_date=? WHERE id=? LIMIT 1");
    if (!$stmt) json_err('SQL error', 500);
    $stmt->bind_param("isssssi", $resident_id, $complaint, $diagnosis, $treatment, $notes, $consultation_date, $id);
    if (!$stmt->execute()) json_err('Failed to update: '.$stmt->error, 500);

    json_ok(['message' => 'Record updated.', 'id' => $id]);
}

/* LIST */
[$from, $to, $monthOut] = compute_date_range($period, $month);

$sql = "SELECT c.id, c.resident_id, c.complaint, c.diagnosis, c.treatment, c.notes, c.consultation_date,
               CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
        FROM consultations c
        LEFT JOIN residents r ON r.id = c.resident_id
        WHERE c.consultation_date BETWEEN ? AND ?";
$params = [$from, $to]; $typesBind = "ss";

if ($search !== '') {
    $sql .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ?)";
    $like = "%{$search}%"; $params[] = $like; $params[] = $like; $params[] = $like; $typesBind .= "sss";
}
$sql .= " ORDER BY c.consultation_date DESC, c.id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) json_err('SQL error', 500);
$stmt->bind_param($typesBind, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $row = row_with_meta($row);
    $program = $row['meta']['program'] ?? '';
    if ($program !== $type) continue;
    if ($sub !== '' && $sub !== 'all') {
        if (($row['meta']['sub_type'] ?? '') !== $sub) continue;
    }
    $data[] = $row;
}

json_ok([
    'filters' => ['type'=>$type,'period'=>$period,'month'=>$monthOut,'from'=>$from,'to'=>$to,'search'=>$search,'sub'=>$sub],
    'total'   => count($data),
    'data'    => $data
]);
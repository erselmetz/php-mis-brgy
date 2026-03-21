<?php
/**
 * Health Records API
 * Replaces: public/hcnurse/health-records/api/health_records_api.php
 *
 * FIXES:
 * 1. program value ALWAYS preserved on update (never lost on edit/complete)
 * 2. list_subtypes action queries DB instead of static array
 * 3. update action explicitly re-reads program from old meta before saving
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

$ALLOWED_PROGRAMS = ['immunization','maternal','family_planning','prenatal','postnatal','child_nutrition'];

function safe_program(string $t, array $allowed): string {
    return in_array($t, $allowed, true) ? $t : 'maternal';
}

function compute_date_range(string $period, string $month): array {
    $today = date('Y-m-d');
    $ym    = date('Y-m');
    if ($period === 'monthly') {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = $ym;
        $from = $month . '-01';
        $to   = date('Y-m-t', strtotime($from));
        return [$from, $to, $month];
    }
    if ($period === 'daily')  return [$today, $today, null];
    if ($period === 'weekly') {
        $mon = date('Y-m-d', strtotime('monday this week'));
        $sun = date('Y-m-d', strtotime('sunday this week'));
        $ms  = $ym.'-01'; $me = date('Y-m-t', strtotime($ms));
        return [max($mon,$ms), min($sun,$me), null];
    }
    $from = $ym.'-01'; $to = date('Y-m-t', strtotime($from));
    return [$from, $to, null];
}

function build_preview(array $meta): string {
    $p = [];
    if (!empty($meta['sub_type']) && $meta['sub_type'] !== 'all') $p[] = ucfirst(str_replace('_',' ',(string)$meta['sub_type']));
    if (!empty($meta['status']))        $p[] = (string)$meta['status'];
    if (!empty($meta['time']))          $p[] = (string)$meta['time'];
    if (!empty($meta['health_worker'])) $p[] = (string)$meta['health_worker'];
    return implode(' • ', $p);
}

function row_with_meta(array $row): array {
    $meta = meta_decode($row['notes'] ?? '');
    $meta = meta_normalize($meta);
    $row['meta']            = $meta;
    $row['details_preview'] = build_preview($meta);
    return $row;
}

$action = $_GET['action'] ?? 'list';
$type   = safe_program($_GET['type'] ?? 'maternal', $GLOBALS['ALLOWED_PROGRAMS']);
$period = $_GET['period'] ?? 'all';
$month  = $_GET['month']  ?? date('Y-m');
$search = trim($_GET['search'] ?? '');
$sub    = trim($_GET['sub']    ?? 'all');
$id     = (int)($_GET['id']    ?? 0);

/* ════════════════════════════════════
   list_subtypes — queries DB for real
   distinct sub_type values for a type
════════════════════════════════════ */
if ($action === 'list_subtypes') {
    // scan consultations.notes JSON for sub_type values matching this program
    $stmt = $conn->prepare("
        SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(notes,'$.sub_type')) AS sub_type
        FROM consultations
        WHERE notes LIKE CONCAT('%\"program\":\"', ?, '\"%')
          AND JSON_UNQUOTE(JSON_EXTRACT(notes,'$.sub_type')) IS NOT NULL
          AND JSON_UNQUOTE(JSON_EXTRACT(notes,'$.sub_type')) != 'null'
          AND JSON_UNQUOTE(JSON_EXTRACT(notes,'$.sub_type')) != ''
        ORDER BY sub_type ASC
        LIMIT 50
    ");
    $stmt->bind_param('s', $type);
    $stmt->execute();
    $res  = $stmt->get_result();
    $subs = [['value'=>'all','label'=>'All']];
    while ($r = $res->fetch_assoc()) {
        $v = $r['sub_type'] ?? '';
        if ($v && $v !== 'all') {
            $subs[] = ['value'=>$v, 'label'=>ucwords(str_replace('_',' ',$v))];
        }
    }
    json_ok(['subtypes' => $subs]);
}

/* ════════════════════════════════════
   GET single record
════════════════════════════════════ */
if ($action === 'get') {
    if ($id <= 0) json_err('Invalid id');
    $stmt = $conn->prepare("
        SELECT c.*, CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) AS resident_name
        FROM consultations c
        LEFT JOIN residents r ON r.id = c.resident_id
        WHERE c.id = ? LIMIT 1
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_err('Record not found', 404);

    $row   = row_with_meta($row);
    $prog  = $row['meta']['program'] ?? '';
    if ($prog !== $type) json_err('Record does not match requested type', 404);

    json_ok(['data' => $row]);
}

/* ════════════════════════════════════
   UPDATE — CRITICAL FIX:
   always read program from existing
   notes; never let it become empty
════════════════════════════════════ */
if ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Invalid method', 405);
    if ($id <= 0) json_err('Invalid id');

    // 1. Fetch existing row first
    $stmt = $conn->prepare("SELECT * FROM consultations WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if (!$existing) json_err('Record not found', 404);

    // 2. Decode old meta — program MUST be preserved
    $oldMeta    = meta_normalize(meta_decode($existing['notes'] ?? ''));
    $oldProgram = $oldMeta['program'] ?? '';

    // 3. Safety: if old program doesn't match page type, reject
    if ($oldProgram !== '' && $oldProgram !== $type) {
        json_err('Record type mismatch', 404);
    }

    // 4. Collect form fields (fallback to existing if not sent)
    $resident_id = (int)($_POST['resident_id'] ?? $existing['resident_id']);
    $complaint   = trim($_POST['complaint']   ?? $existing['complaint']);
    $diagnosis   = trim($_POST['diagnosis']   ?? $existing['diagnosis']);
    $treatment   = trim($_POST['treatment']   ?? $existing['treatment']);

    $consultation_date = trim($_POST['consultation_date'] ?? $existing['consultation_date']);
    // convert mm/dd/yyyy → yyyy-mm-dd
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $consultation_date)) {
        [$mm,$dd,$yyyy] = explode('/', $consultation_date);
        $consultation_date = sprintf('%04d-%02d-%02d', (int)$yyyy, (int)$mm, (int)$dd);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $consultation_date)) json_err('Invalid date format');

    // 5. Meta — start from old, overlay submitted fields
    $newMeta = $oldMeta;
    // ALWAYS lock program to the page type (page type = source of truth)
    $newMeta['program']       = $type;
    $newMeta['sub_type']      = trim($_POST['sub_type']      ?? $oldMeta['sub_type']      ?? 'all');
    $newMeta['status']        = trim($_POST['status']        ?? $oldMeta['status']        ?? 'Completed');
    $newMeta['time']          = trim($_POST['consultation_time'] ?? $oldMeta['time']       ?? '');
    $newMeta['health_worker'] = trim($_POST['health_worker'] ?? $oldMeta['health_worker'] ?? '');
    $newMeta['remarks']       = trim($_POST['remarks']       ?? $oldMeta['remarks']       ?? '');

    $notes = meta_encode($newMeta);

    if ($resident_id <= 0) json_err('Resident is required');
    if ($complaint === '')  json_err('Chief complaint is required');

    $stmt = $conn->prepare("
        UPDATE consultations
        SET resident_id=?, complaint=?, diagnosis=?, treatment=?, notes=?, consultation_date=?
        WHERE id=? LIMIT 1
    ");
    $stmt->bind_param('isssssi', $resident_id, $complaint, $diagnosis, $treatment, $notes, $consultation_date, $id);
    if (!$stmt->execute()) json_err('Update failed: '.$stmt->error, 500);

    json_ok(['message'=>'Record updated.', 'id'=>$id]);
}

/* ════════════════════════════════════
   LIST records
════════════════════════════════════ */
[$from, $to, $monthOut] = compute_date_range($period, $month);

$sql    = "SELECT c.*, CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) AS resident_name
           FROM consultations c
           LEFT JOIN residents r ON r.id=c.resident_id
           WHERE c.consultation_date BETWEEN ? AND ?";
$params = [$from, $to];
$types  = 'ss';

if ($search !== '') {
    $sql   .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ?)";
    $like   = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
$sql .= ' ORDER BY c.consultation_date DESC, c.id DESC';

$stmt = $conn->prepare($sql);
if (!$stmt) json_err('SQL error: '.$conn->error, 500);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res  = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $row  = row_with_meta($row);
    $prog = $row['meta']['program'] ?? '';
    if ($prog !== $type) continue;
    if ($sub !== '' && $sub !== 'all' && ($row['meta']['sub_type'] ?? '') !== $sub) continue;
    $data[] = $row;
}

json_ok([
    'filters' => ['type'=>$type,'period'=>$period,'month'=>$monthOut,'from'=>$from,'to'=>$to,'search'=>$search,'sub'=>$sub],
    'total'   => count($data),
    'data'    => $data,
]);
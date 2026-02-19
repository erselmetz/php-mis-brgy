<?php
// /hcnurse/api/health_records_api.php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

// Inputs
$type   = $_GET['type'] ?? 'maternal';
$period = $_GET['period'] ?? 'all';         // all|daily|weekly|monthly
$month  = $_GET['month'] ?? date('Y-m');    // YYYY-MM (used if monthly)
$search = trim($_GET['search'] ?? '');
$sub    = trim($_GET['sub'] ?? 'all');

// Allowed programs
$allowedTypes = ['immunization', 'maternal', 'family_planning', 'prenatal', 'postnatal', 'child_nutrition'];
if (!in_array($type, $allowedTypes, true)) $type = 'maternal';

// --- Date range rules (your twist)
$today = date('Y-m-d');
$currentYM = date('Y-m');

if ($period === 'monthly') {
  // selected month
  if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = $currentYM;
  $from = $month . '-01';
  $to   = date('Y-m-t', strtotime($from));
} elseif ($period === 'daily') {
  // today only (current month context)
  $from = $today;
  $to   = $today;
} elseif ($period === 'weekly') {
  // current week but clipped to current month boundaries
  // week starts Monday
  $monday = date('Y-m-d', strtotime('monday this week'));
  $sunday = date('Y-m-d', strtotime('sunday this week'));

  $monthStart = $currentYM . '-01';
  $monthEnd   = date('Y-m-t', strtotime($monthStart));

  // clip
  $from = ($monday < $monthStart) ? $monthStart : $monday;
  $to   = ($sunday > $monthEnd) ? $monthEnd : $sunday;
} else {
  // "all time" = whole current month (your twist)
  $from = $currentYM . '-01';
  $to   = date('Y-m-t', strtotime($from));
}

// Build SQL (date filter first; search by resident name in SQL)
$sql = "
  SELECT
    c.id,
    c.resident_id,
    c.complaint,
    c.diagnosis,
    c.treatment,
    c.notes,
    c.consultation_date,
    CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
  FROM consultations c
  LEFT JOIN residents r ON r.id = c.resident_id
  WHERE c.consultation_date BETWEEN ? AND ?
";

$params = [$from, $to];
$typesBind = "ss";

if ($search !== '') {
  $sql .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ?)";
  $like = "%{$search}%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $typesBind .= "sss";
}

$sql .= " ORDER BY c.consultation_date DESC, c.id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'SQL prepare failed: ' . $conn->error]);
  exit;
}
$stmt->bind_param($typesBind, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// Filter by program/sub_type in PHP using meta
$data = [];
while ($row = $res->fetch_assoc()) {
  $meta = meta_decode($row['notes'] ?? '');
  $meta = meta_normalize($meta);

  $program = $meta['program'] ?? '';
  if ($program !== $type) continue;

  if ($sub !== '' && $sub !== 'all') {
    $metaSub = $meta['sub_type'] ?? '';
    if ($metaSub !== $sub) continue;
  }

  // friendly preview
  $row['meta'] = $meta;
  $previewParts = [];

  if (!empty($meta['sub_type']) && $meta['sub_type'] !== 'all') {
    $previewParts[] = ucfirst(str_replace('_', ' ', $meta['sub_type']));
  }

  if (!empty($meta['status'])) {
    $previewParts[] = $meta['status'];
  }

  if (!empty($meta['time'])) {
    $previewParts[] = $meta['time'];
  }

  if (!empty($meta['health_worker'])) {
    $previewParts[] = $meta['health_worker'];
  }

  $row['details_preview'] = implode(' • ', $previewParts);


  $data[] = $row;
}

echo json_encode([
  'status' => 'ok',
  'filters' => [
    'type' => $type,
    'period' => $period,
    'month' => ($period === 'monthly') ? $month : null,
    'from' => $from,
    'to' => $to,
    'search' => $search,
    'sub' => $sub,
  ],
  'total' => count($data),
  'data' => $data
]);

<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

function fullNameFromRow(array $r): string {
  $parts = [trim($r['first_name'] ?? ''), trim($r['middle_name'] ?? ''), trim($r['last_name'] ?? ''), trim($r['suffix'] ?? '')];
  return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) respond(false, 'Invalid id');

$sql = "
  SELECT
    c.*,
    r.first_name, r.middle_name, r.last_name, r.suffix, r.birthdate
  FROM consultations c
  INNER JOIN residents r ON r.id = c.resident_id
  WHERE c.id = ? AND r.deleted_at IS NULL
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) respond(false, 'Not found');

$extras = meta_decode($row['notes'] ?? '');

respond(true, 'OK', [
  'data' => [
    'id' => (int)$row['id'],
    'resident_id' => (int)$row['resident_id'],
    'fullname' => fullNameFromRow($row),
    'consultation_date' => $row['consultation_date'] ?? '',
    'complaint' => $row['complaint'] ?? '',
    'diagnosis' => $row['diagnosis'] ?? '',
    'treatment' => $row['treatment'] ?? '',
    'notes' => $row['notes'] ?? '',
    'time' => $extras['time'] ?? '',
    'health_worker' => $extras['health_worker'] ?? '',
    'status' => $extras['status'] ?? '',
    'remarks' => $extras['remarks'] ?? ''
  ]
]);

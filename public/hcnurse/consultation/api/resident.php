<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

$term = trim($_GET['term'] ?? '');
$like = "%" . $term . "%";

$sql = "
  SELECT id, first_name, middle_name, last_name, suffix
  FROM residents
  WHERE deleted_at IS NULL
    AND (
      first_name LIKE ?
      OR middle_name LIKE ?
      OR last_name LIKE ?
      OR CONCAT(first_name,' ',last_name) LIKE ?
      OR CONCAT(first_name,' ',middle_name,' ',last_name) LIKE ?
    )
  ORDER BY last_name ASC, first_name ASC
  LIMIT 20
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $like, $like, $like, $like, $like);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
  $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
    $r['first_name'] ?? '',
    $r['middle_name'] ?? '',
    $r['last_name'] ?? '',
    $r['suffix'] ?? ''
  ]))));

  $out[] = [
    'label' => $name,
    'value' => $name,
    'id'    => (int)$r['id'],
  ];
}

echo json_encode($out);

<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();
/* =========================
   AJAX: RESIDENT SEARCH
========================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'residents') {
  header('Content-Type: application/json; charset=utf-8');

  $q = trim($_GET['q'] ?? '');
  $limit = (int)($_GET['limit'] ?? 30);
  if ($limit < 1 || $limit > 100) $limit = 30;

  // common columns
  $idCol = 'id';
  $firstCol = 'first_name';
  $midCol = 'middle_name';
  $lastCol = 'last_name';

  // If your table uses different columns, adjust here.
  // This assumes: residents(first_name,middle_name,last_name) OR resident(first_name,middle_name,last_name)

  $like = '%' . $q . '%';
  $stmt = $conn->prepare("
    SELECT {$idCol} AS id,
           {$firstCol} AS first_name,
           {$midCol} AS middle_name,
           {$lastCol} AS last_name
    FROM residents
    WHERE CONCAT_WS(' ', {$firstCol}, {$midCol}, {$lastCol}) LIKE ?
    ORDER BY {$lastCol} ASC, {$firstCol} ASC
    LIMIT {$limit}
  ");
  $stmt->bind_param("s", $like);
  $stmt->execute();
  $res = $stmt->get_result();

  $rows = [];
  while ($r = $res->fetch_assoc()) {
    $full = trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
    $rows[] = [
      'id' => (int)$r['id'],
      'name' => $full !== '' ? $full : ('Resident #' . (int)$r['id']),
    ];
  }
  echo json_encode(['success' => true, 'residents' => $rows]);
  exit;
}
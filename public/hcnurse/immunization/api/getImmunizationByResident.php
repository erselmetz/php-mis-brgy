<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();
/* =========================
   AJAX: GET IMMUNIZATIONS BY RESIDENT
========================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'immunizations') {
  header('Content-Type: application/json; charset=utf-8');

  $resident_id = (int)($_GET['resident_id'] ?? 0);
  if ($resident_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resident.']);
    exit;
  }

  $stmt = $conn->prepare("
    SELECT id, vaccine_name, dose, date_given, next_schedule, remarks
    FROM immunizations
    WHERE resident_id = ?
    ORDER BY date_given DESC, id DESC
  ");
  $stmt->bind_param("i", $resident_id);
  $stmt->execute();
  $res = $stmt->get_result();

  $rows = [];
  while ($r = $res->fetch_assoc()) {
    // simple UI status
    $status = 'Up to Date';
    if (!empty($r['next_schedule'])) {
      $status = (strtotime($r['next_schedule']) >= strtotime(date('Y-m-d'))) ? 'Up to Date' : 'Due/Overdue';
    }
    $rows[] = [
      'id' => (int)$r['id'],
      'vaccine' => $r['vaccine_name'],
      'dose' => $r['dose'],
      'date_given' => $r['date_given'],
      'next_schedule' => $r['next_schedule'],
      'status' => $status,
      'remarks' => $r['remarks'],
    ];
  }

  echo json_encode(['success' => true, 'rows' => $rows]);
  exit;
}
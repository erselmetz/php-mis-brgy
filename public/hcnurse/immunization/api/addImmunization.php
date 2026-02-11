<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();
/* =========================
   AJAX: ADD IMMUNIZATION
========================= */
if (isset($_POST['action']) && $_POST['action'] === 'add_immunization') {
  header('Content-Type: application/json; charset=utf-8');

  $resident_id = (int)($_POST['resident_id'] ?? 0);
  $vaccine_name = trim($_POST['vaccine_name'] ?? '');
  $dose = trim($_POST['dose'] ?? '');
  $date_given = trim($_POST['date_given'] ?? '');
  $next_schedule = trim($_POST['next_schedule'] ?? '');
  $remarks = trim($_POST['remarks'] ?? '');

  if ($resident_id <= 0 || $vaccine_name === '' || $date_given === '') {
    echo json_encode(['success' => false, 'message' => 'Please select resident, vaccine type, and date administered.']);
    exit;
  }

  // Convert from mm/dd/yyyy -> Y-m-d (datepicker)
  $dg = DateTime::createFromFormat('m/d/Y', $date_given);
  if (!$dg) {
    echo json_encode(['success' => false, 'message' => 'Invalid Date Administered format.']);
    exit;
  }
  $date_given_sql = $dg->format('Y-m-d');

  $next_sql = null;
  if ($next_schedule !== '') {
    $ns = DateTime::createFromFormat('m/d/Y', $next_schedule);
    if (!$ns) {
      echo json_encode(['success' => false, 'message' => 'Invalid Next Due Date format.']);
      exit;
    }
    $next_sql = $ns->format('Y-m-d');
  }

  $stmt = $conn->prepare("
    INSERT INTO immunizations (resident_id, vaccine_name, dose, date_given, next_schedule, remarks)
    VALUES (?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("isssss", $resident_id, $vaccine_name, $dose, $date_given_sql, $next_sql, $remarks);

  if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error]);
    exit;
  }

  echo json_encode(['success' => true, 'message' => 'Immunization added successfully.']);
  exit;
}
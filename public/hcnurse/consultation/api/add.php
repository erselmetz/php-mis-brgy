<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

function respond($ok, $msg, $extra = [])
{
  echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
  exit;
}

function packNotes(string $time, string $worker, string $status, string $remarks): string
{
  $parts = [];
  if ($time !== '') $parts[] = "Time: {$time}";
  if ($worker !== '') $parts[] = "Health Worker: {$worker}";
  if ($status !== '') $parts[] = "Status: {$status}";
  if ($remarks !== '') $parts[] = "Remarks: {$remarks}";
  return implode(" | ", $parts);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request');

$resident_id = (int)($_POST['resident_id'] ?? 0);
$consultation_date = trim($_POST['consultation_date'] ?? '');

// convert date if mm/dd/yyyy
if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $consultation_date)) {
  [$mm, $dd, $yyyy] = explode('/', $consultation_date);
  $consultation_date = sprintf('%04d-%02d-%02d', (int)$yyyy, (int)$mm, (int)$dd);
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $consultation_date)) {
  respond(false, 'Invalid date format.');
}

$complaint = trim($_POST['complaint'] ?? '');
$diagnosis = trim($_POST['diagnosis'] ?? '');
$treatment = trim($_POST['treatment'] ?? '');

$time = trim($_POST['consultation_time'] ?? '');
$health_worker = trim($_POST['health_worker'] ?? '');
$status = trim($_POST['status'] ?? 'Completed');
$remarks = trim($_POST['remarks'] ?? '');

if ($resident_id <= 0) respond(false, 'Resident is required.');
if ($consultation_date === '') respond(false, 'Date is required.');
if ($complaint === '') respond(false, 'Chief complaint is required.');

$notes = packNotes($time, $health_worker, $status, $remarks);

$sql = "INSERT INTO consultations (resident_id, complaint, diagnosis, treatment, notes, consultation_date)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssss", $resident_id, $complaint, $diagnosis, $treatment, $notes, $consultation_date);
$stmt->execute();

respond(true, 'Consultation added.', ['id' => (int)$conn->insert_id]);

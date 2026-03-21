<?php
/**
 * Consultation Add API
 * Replaces: public/hcnurse/consultation/api/add.php
 *
 * BUG FIXES:
 * 1. program is ALWAYS written to notes JSON (even when status=Completed)
 * 2. respond() helper defined inline as fallback if not in app.php
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

// Fallback if respond() is not defined in app.php
if (!function_exists('respond')) {
    function respond(bool $ok, string $msg, array $data = []): never {
        echo json_encode(['success' => $ok, 'message' => $msg] + ($data ? ['data' => $data] : []));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request');

$resident_id = (int)($_POST['resident_id'] ?? 0);
$date        = trim($_POST['consultation_date'] ?? '');

// Convert mm/dd/yyyy → yyyy-mm-dd
if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
    [$mm, $dd, $yyyy] = explode('/', $date);
    $date = sprintf('%04d-%02d-%02d', (int)$yyyy, (int)$mm, (int)$dd);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) respond(false, 'Invalid date format. Expected mm/dd/yyyy.');

$complaint = trim($_POST['complaint']          ?? '');
$diagnosis = trim($_POST['diagnosis']          ?? '');
$treatment = trim($_POST['treatment']          ?? '');
$program   = trim($_POST['consultation_type']  ?? '');
$sub_type  = trim($_POST['sub_type']           ?? 'all');
$time      = trim($_POST['consultation_time']  ?? '');
$worker    = trim($_POST['health_worker']      ?? '');
$status    = trim($_POST['status']             ?? 'Completed');
$remarks   = trim($_POST['remarks']            ?? '');

$allowed = ['immunization', 'maternal', 'family_planning', 'prenatal', 'postnatal', 'child_nutrition'];

if ($resident_id <= 0)                     respond(false, 'Please select a resident.');
if ($date === '')                           respond(false, 'Date is required.');
if ($complaint === '')                      respond(false, 'Chief complaint is required.');
if (!in_array($program, $allowed, true))    respond(false, 'Invalid consultation type selected.');

// CRITICAL: program ALWAYS stored in notes — never lost on status change
$notes = meta_encode([
    'program'       => $program,
    'sub_type'      => $sub_type ?: 'all',
    'status'        => $status,
    'time'          => $time,
    'health_worker' => $worker,
    'remarks'       => $remarks,
]);

$stmt = $conn->prepare("
    INSERT INTO consultations
        (resident_id, complaint, diagnosis, treatment, notes, consultation_date)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('isssss', $resident_id, $complaint, $diagnosis, $treatment, $notes, $date);

if (!$stmt->execute()) {
    respond(false, 'Database error: ' . $stmt->error);
}

respond(true, 'Consultation added successfully.', ['id' => (int)$conn->insert_id]);
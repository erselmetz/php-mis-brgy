<?php
/**
 * Consultation Edit API
 * Replaces: public/hcnurse/consultation/api/edit.php
 *
 * FIX: reads existing notes to preserve program before writing updated meta
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request');

$id          = (int)($_POST['id']          ?? 0);
$resident_id = (int)($_POST['resident_id'] ?? 0);
$date        = trim($_POST['consultation_date'] ?? '');

// convert mm/dd/yyyy
if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
    [$mm,$dd,$yyyy] = explode('/', $date);
    $date = sprintf('%04d-%02d-%02d', (int)$yyyy, (int)$mm, (int)$dd);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) respond(false, 'Invalid date format.');

$complaint = trim($_POST['complaint']         ?? '');
$diagnosis = trim($_POST['diagnosis']         ?? '');
$treatment = trim($_POST['treatment']         ?? '');
$time      = trim($_POST['consultation_time'] ?? '');
$worker    = trim($_POST['health_worker']     ?? '');
$status    = trim($_POST['status']            ?? 'Completed');
$remarks   = trim($_POST['remarks']           ?? '');

if ($id <= 0)          respond(false, 'Invalid consultation id.');
if ($resident_id <= 0) respond(false, 'Resident is required.');
if ($complaint === '')  respond(false, 'Chief complaint is required.');

// CRITICAL: fetch existing notes to preserve program
$existing = $conn->query("SELECT notes FROM consultations WHERE id = {$id} LIMIT 1")->fetch_assoc();
$oldMeta  = meta_normalize(meta_decode($existing['notes'] ?? ''));
$program  = $oldMeta['program'] ?? '';   // ← always re-read from DB

// Build new meta — program is locked to what was stored, never overwritten
$notes = meta_encode([
    'program'      => $program,   // ← preserved
    'sub_type'     => trim($_POST['sub_type'] ?? $oldMeta['sub_type'] ?? 'all'),
    'status'       => $status,
    'time'         => $time,
    'health_worker'=> $worker,
    'remarks'      => $remarks,
]);

$stmt = $conn->prepare("
    UPDATE consultations
    SET resident_id=?, complaint=?, diagnosis=?, treatment=?, notes=?, consultation_date=?
    WHERE id=? LIMIT 1
");
$stmt->bind_param('isssssi', $resident_id, $complaint, $diagnosis, $treatment, $notes, $date, $id);
$stmt->execute();

respond(true, 'Consultation updated.');
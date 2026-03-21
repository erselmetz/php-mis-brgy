<?php
/**
 * Consultation Edit API
 * Replaces: public/hcnurse/consultation/api/edit.php
 *
 * BUG FIXES:
 * 1. Always reads existing notes first → preserves program field
 * 2. respond() fallback defined inline
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('respond')) {
    function respond(bool $ok, string $msg, array $data = []): never {
        echo json_encode(['success' => $ok, 'message' => $msg] + ($data ? ['data' => $data] : []));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request');

$id          = (int)($_POST['id']             ?? 0);
$resident_id = (int)($_POST['resident_id']    ?? 0);
$date        = trim($_POST['consultation_date'] ?? '');

// Convert mm/dd/yyyy → yyyy-mm-dd
if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
    [$mm, $dd, $yyyy] = explode('/', $date);
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

// CRITICAL: Read existing notes first to preserve program
$existing = $conn->query("SELECT notes FROM consultations WHERE id = " . (int)$id . " LIMIT 1")->fetch_assoc();
if (!$existing) respond(false, 'Consultation not found.');

$oldMeta = meta_normalize(meta_decode($existing['notes'] ?? ''));
$program = $oldMeta['program'] ?? ''; // preserved — never overwritten

$notes = meta_encode([
    'program'       => $program,  // always locked to original value
    'sub_type'      => trim($_POST['sub_type'] ?? $oldMeta['sub_type'] ?? 'all'),
    'status'        => $status,
    'time'          => $time,
    'health_worker' => $worker,
    'remarks'       => $remarks,
]);

$stmt = $conn->prepare("
    UPDATE consultations
    SET resident_id = ?, complaint = ?, diagnosis = ?, treatment = ?,
        notes = ?, consultation_date = ?
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('isssssi', $resident_id, $complaint, $diagnosis, $treatment, $notes, $date, $id);

if (!$stmt->execute()) respond(false, 'Update failed: ' . $stmt->error);

respond(true, 'Consultation updated successfully.');
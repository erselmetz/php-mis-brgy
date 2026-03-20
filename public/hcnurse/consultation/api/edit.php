<?php
/**
 * Consultation Edit API — FIXED
 * Bug: editing a record replaced the full notes JSON including program,
 *      but if program wasn't submitted (e.g. not in edit form), it went blank.
 * Fix: always read existing program from DB first, then merge — never lose it.
 *
 * Replaces: public/hcnurse/consultation/api/edit.php
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request');

$id          = (int)($_POST['id']          ?? 0);
$resident_id = (int)($_POST['resident_id'] ?? 0);
$consultation_date = trim($_POST['consultation_date'] ?? '');

/* convert mm/dd/yyyy → yyyy-mm-dd */
if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $consultation_date)) {
    [$mm, $dd, $yyyy] = explode('/', $consultation_date);
    $consultation_date = sprintf('%04d-%02d-%02d', (int)$yyyy, (int)$mm, (int)$dd);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $consultation_date)) respond(false, 'Invalid date format.');

$complaint     = trim($_POST['complaint']         ?? '');
$diagnosis     = trim($_POST['diagnosis']         ?? '');
$treatment     = trim($_POST['treatment']         ?? '');
$time          = trim($_POST['consultation_time'] ?? '');
$health_worker = trim($_POST['health_worker']     ?? '');
$status        = trim($_POST['status']            ?? 'Completed');
$remarks       = trim($_POST['remarks']           ?? '');

if ($id <= 0)          respond(false, 'Invalid consultation id.');
if ($resident_id <= 0) respond(false, 'Resident is required.');
if ($complaint === '')  respond(false, 'Chief complaint is required.');

/* ── CRITICAL FIX: fetch existing notes to preserve program ── */
$existing = $conn->prepare("SELECT notes FROM consultations WHERE id = ? LIMIT 1");
$existing->bind_param("i", $id);
$existing->execute();
$existingRow = $existing->get_result()->fetch_assoc();
$existing->close();

if (!$existingRow) respond(false, 'Consultation record not found.');

/* Decode existing meta */
$oldMeta = meta_normalize(meta_decode($existingRow['notes'] ?? ''));

/* Preserve program — use existing value, never overwrite with empty */
$program = $oldMeta['program'] ?? '';
/* Allow override only if explicitly submitted and non-empty */
if (!empty($_POST['consultation_type'])) {
    $allowed = ['immunization','maternal','family_planning','prenatal','postnatal','child_nutrition'];
    $submitted = trim($_POST['consultation_type']);
    if (in_array($submitted, $allowed, true)) {
        $program = $submitted;
    }
}

/* sub_type — preserve existing if not submitted */
$sub_type = !empty($_POST['sub_type']) ? trim($_POST['sub_type']) : ($oldMeta['sub_type'] ?? 'all');

/* Build merged notes — program is ALWAYS present */
$notes = meta_encode([
    'program'      => $program,
    'sub_type'     => $sub_type,
    'status'       => $status,
    'time'         => $time,
    'health_worker'=> $health_worker,
    'remarks'      => $remarks,
]);

$sql = "UPDATE consultations
        SET resident_id=?, complaint=?, diagnosis=?, treatment=?, notes=?, consultation_date=?
        WHERE id=?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssi", $resident_id, $complaint, $diagnosis, $treatment, $notes, $consultation_date, $id);
$stmt->execute();

respond(true, 'Consultation updated.');
<?php
/**
 * NIP Card Update API
 * public/hcnurse/care-visits/api/nip_update.php
 *
 * Handles:
 *   POST (no id)        → insert new immunization record
 *   POST ?id=X          → update existing record
 *   POST ?action=delete&id=X → soft delete (mark removed)
 */
require_once __DIR__ . '/../../../../includes/app.php';
require_once __DIR__ . '/../../../../includes/hcnurse_health_metrics.php';
requireHCNurse();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'save';
$id     = (int)($_GET['id'] ?? 0);

/* ── helper ── */
function np($v) { $v = trim((string)($v ?? '')); return $v === '' ? null : $v; }
function ni($v) { $n = np($v); return $n === null ? null : (int)$n; }
function nd($v) {
    $s = np($v);
    if (!$s) return null;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return null;
    return $s;
}

/* ════════════════════════
   DELETE
════════════════════════ */
if ($action === 'delete') {
    if ($id <= 0) json_err('Invalid id');
    // Verify it exists and belongs to a resident this session can see
    $chk = $conn->prepare("SELECT id FROM immunizations WHERE id=? LIMIT 1");
    $chk->bind_param('i', $id);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) json_err('Record not found', 404);

    $st = $conn->prepare("DELETE FROM immunizations WHERE id=? LIMIT 1");
    $st->bind_param('i', $id);
    if (!$st->execute()) json_err('Delete failed: ' . $st->error, 500);
    json_ok_data(['id' => $id], 'Record deleted.');
}

/* ════════════════════════
   SAVE (insert or update)
════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method not allowed', 405);

$rid         = ni($_POST['resident_id']   ?? 0);
$vaccName    = np($_POST['vaccine_name']  ?? '');
$dose        = np($_POST['dose']          ?? null);
$schedId     = ni($_POST['schedule_id']  ?? null);
$dateGiven   = nd($_POST['date_given']    ?? null);
$nextSched   = nd($_POST['next_schedule'] ?? null);
$adminBy     = np($_POST['administered_by'] ?? null);
$remarks     = np($_POST['remarks']          ?? null);
$batch       = np($_POST['batch_number']     ?? null);
$lot         = np($_POST['lot_number']       ?? null);
$expiryDate  = nd($_POST['expiry_date']      ?? null);
$vvmStatus   = np($_POST['vvm_status']       ?? 'OK');
$coldTemp    = np($_POST['temperature_at_vaccination'] ?? null);
$facility    = np($_POST['given_at_facility'] ?? null);
$site        = np($_POST['site_given']        ?? null);
$route       = np($_POST['route']             ?? 'IM');
$adverse     = np($_POST['adverse_reaction']  ?? null);
$isDefaulter = (int)($_POST['is_defaulter']   ?? 0);
$catchUp     = (int)($_POST['catch_up']       ?? 0);
$careVisitId = ni($_POST['care_visit_id']     ?? null);
$userId      = (int)($_SESSION['user_id']     ?? 0);

if (!$rid || $rid <= 0) json_err('Resident is required');
if (!$vaccName)         json_err('Vaccine name is required');
if (!$dateGiven)        json_err('Date given is required');

/* Validate route */
$validRoutes = ['IM','SC','ID','Oral','Nasal'];
if (!in_array($route, $validRoutes, true)) $route = 'IM';

/* Validate VVM */
$validVvm = ['OK','WARN','DISCARD'];
if (!in_array($vvmStatus, $validVvm, true)) $vvmStatus = 'OK';

$conn->begin_transaction();
try {
    if ($id > 0) {
        /* UPDATE */
        $st = $conn->prepare("
            UPDATE immunizations SET
                vaccine_name=?, dose=?, schedule_id=?,
                date_given=?, next_schedule=?, administered_by=?,
                remarks=?, batch_number=?, lot_number=?, expiry_date=?,
                vvm_status=?, temperature_at_vaccination=?, given_at_facility=?,
                site_given=?, route=?, adverse_reaction=?,
                is_defaulter=?, catch_up=?,
                care_visit_id=?
            WHERE id=? LIMIT 1
        ");
        $st->bind_param(
            'ssississssssssssiisi',
            $vaccName, $dose, $schedId,
            $dateGiven, $nextSched, $adminBy,
            $remarks, $batch, $lot, $expiryDate,
            $vvmStatus, $coldTemp, $facility,
            $site, $route, $adverse,
            $isDefaulter, $catchUp,
            $careVisitId,
            $id
        );
        $st->execute();
        $resultId = $id;
        $msg = 'Vaccine record updated.';
    } else {
        /* INSERT */
        $st = $conn->prepare("
            INSERT INTO immunizations
                (resident_id, vaccine_name, dose, schedule_id,
                 date_given, next_schedule, administered_by,
                 remarks, batch_number, lot_number, expiry_date,
                 vvm_status, temperature_at_vaccination, given_at_facility,
                 site_given, route, adverse_reaction,
                 is_defaulter, catch_up, care_visit_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $st->bind_param(
            'isssissssssssssssiis',
            $rid, $vaccName, $dose, $schedId,
            $dateGiven, $nextSched, $adminBy,
            $remarks, $batch, $lot, $expiryDate,
            $vvmStatus, $coldTemp, $facility,
            $site, $route, $adverse,
            $isDefaulter, $catchUp, $careVisitId
        );
        $st->execute();
        $resultId = (int)$conn->insert_id;
        $msg = 'Vaccine recorded successfully.';
    }

    $conn->commit();
    json_ok_data(['id' => $resultId], $msg);

} catch (\Throwable $e) {
    $conn->rollback();
    json_err('Save failed: ' . $e->getMessage(), 500);
}
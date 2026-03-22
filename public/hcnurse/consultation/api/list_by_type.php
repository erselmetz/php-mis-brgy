<?php
/**
 * Consultation List by Type API
 * public/hcnurse/consultation/api/list_by_type.php
 *
 * Returns consultations for a given resident + consult_type.
 * Used by the care-visits page to show consultation records alongside
 * care_visits records in a unified visit history.
 */
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json; charset=utf-8');

$type = trim($_GET['type'] ?? 'general');
$rid  = (int)($_GET['resident_id'] ?? 0);
$from = $_GET['from'] ?? '2000-01-01';
$to   = $_GET['to']   ?? date('Y-m-d');

$allowed = ['general','maternal','family_planning','prenatal','postnatal','child_nutrition','immunization','other'];
if (!in_array($type, $allowed, true)) json_err('Invalid type');

$params = [$type, $from, $to];
$types  = 'sss';
$where  = "c.consult_type = ? AND c.consultation_date BETWEEN ? AND ?";

if ($rid > 0) {
    $where   .= " AND c.resident_id = ?";
    $params[] = $rid;
    $types   .= 'i';
}

$sql = "
    SELECT
        c.id,
        c.resident_id,
        c.consultation_date,
        c.consult_type,
        c.consult_status,
        c.health_worker,
        c.complaint,
        c.diagnosis,
        c.risk_level,
        c.bp_systolic,
        c.bp_diastolic,
        c.temp_celsius,
        c.weight_kg,
        c.height_cm,
        CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
    FROM consultations c
    INNER JOIN residents r ON r.id = c.resident_id AND r.deleted_at IS NULL
    WHERE {$where}
    ORDER BY c.consultation_date DESC, c.id DESC
    LIMIT 200
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res  = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) $rows[] = $row;

json_ok_data($rows);
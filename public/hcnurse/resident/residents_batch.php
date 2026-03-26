<?php
/**
 * Batch fetch resident details by IDs
 * public/hcnurse/resident/residents_batch.php
 *
 * GET ?ids[]=1&ids[]=2&ids[]=3
 * Returns: { residents: [{ id, first_name, middle_name, last_name, suffix, birthdate, gender }] }
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();
header('Content-Type: application/json; charset=utf-8');

$rawIds = $_GET['ids'] ?? [];
if (!is_array($rawIds)) $rawIds = [$rawIds];

$ids = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
if (empty($ids)) {
    json_ok_data(['residents' => []]);
}

// Limit to 200 ids for safety
$ids = array_slice($ids, 0, 200);

$ph = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$st = $conn->prepare("
    SELECT id, first_name, middle_name, last_name, suffix, birthdate, gender, address
    FROM residents
    WHERE id IN ($ph) AND deleted_at IS NULL
    ORDER BY last_name ASC, first_name ASC
");
$st->bind_param($types, ...$ids);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

json_ok_data(['residents' => $rows]);
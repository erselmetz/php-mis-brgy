<?php
require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing resident ID']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT r.*,
           h.household_no,
           h.address as household_address,
           CONCAT_WS(' ', hr.first_name, hr.middle_name, hr.last_name, hr.suffix) as household_head
    FROM residents r
    LEFT JOIN households h ON r.household_id = h.id
    LEFT JOIN residents hr ON h.head_id = hr.id
    WHERE r.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Add formatted household display name
    if ($row['household_id']) {
        $row['household_display'] = $row['household_no'] . ' - ' . $row['household_head'];
    } else {
        $row['household_display'] = '';
    }
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Resident not found']);
}

$stmt->close();

/**
 * IMPORTANT: Do not close $conn here
 * It's a shared connection managed by db.php
 * Closing it would break other operations that use the same connection
 */

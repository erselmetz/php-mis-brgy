<?php
require_once __DIR__ . '/../../../includes/app.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing blotter ID']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT b.*, u.name as created_by_name 
    FROM blotter b 
    LEFT JOIN users u ON b.created_by = u.id 
    WHERE b.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Blotter case not found']);
}

$stmt->close();

/**
 * IMPORTANT: Do not close $conn here
 * It's a shared connection managed by db.php
 * Closing it would break other operations that use the same connection
 */

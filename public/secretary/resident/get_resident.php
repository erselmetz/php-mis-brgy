<?php
require_once __DIR__ . '/../../../includes/app.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing resident ID']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
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

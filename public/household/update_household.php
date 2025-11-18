<?php
require_once '../../includes/app.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$fields = [
    'household_no', 'head_name', 'address'
];

$data = [];
foreach ($fields as $field) {
    $data[$field] = $_POST[$field] ?? null;
}

// Validate required fields
if (empty($data['household_no']) || empty($data['head_name']) || empty($data['address'])) {
    echo json_encode(['success' => false, 'message' => 'Household number, head name, and address are required']);
    exit;
}

// Check for duplicate household number (excluding current record)
$check = $conn->prepare("SELECT id FROM households WHERE household_no = ? AND id != ?");
$check->bind_param("si", $data['household_no'], $id);
$check->execute();
$result = $check->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Household number already exists']);
    $check->close();
    exit;
}
$check->close();

// Update total_members based on actual resident count
$countStmt = $conn->prepare("SELECT COUNT(*) as count FROM residents WHERE household_id = ?");
$countStmt->bind_param("i", $id);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$total_members = $countRow['count'];
$countStmt->close();

$sql = "UPDATE households SET
    household_no = ?, head_name = ?, address = ?, total_members = ?
    WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'sssii',
    $data['household_no'],
    $data['head_name'],
    $data['address'],
    $total_members,
    $id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Household updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();


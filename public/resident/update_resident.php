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
    'household_id', 'first_name', 'middle_name', 'last_name', 'suffix',
    'gender', 'birthdate', 'birthplace', 'civil_status', 'religion',
    'occupation', 'citizenship', 'contact_no', 'address', 'voter_status',
    'disability_status', 'remarks'
];

$data = [];
foreach ($fields as $field) {
    $data[$field] = $_POST[$field] ?? null;
}

$sql = "UPDATE residents SET
    household_id = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
    gender = ?, birthdate = ?, birthplace = ?, civil_status = ?, religion = ?,
    occupation = ?, citizenship = ?, contact_no = ?, address = ?, voter_status = ?,
    disability_status = ?, remarks = ?
    WHERE id = ?";

if (empty($data['household_id']) || !is_numeric($data['household_id'])) {
    $data['household_id'] = null;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'issssssssssssssssi',
    $data['household_id'],
    $data['first_name'],
    $data['middle_name'],
    $data['last_name'],
    $data['suffix'],
    $data['gender'],
    $data['birthdate'],
    $data['birthplace'],
    $data['civil_status'],
    $data['religion'],
    $data['occupation'],
    $data['citizenship'],
    $data['contact_no'],
    $data['address'],
    $data['voter_status'],
    $data['disability_status'],
    $data['remarks'],
    $id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Resident updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();

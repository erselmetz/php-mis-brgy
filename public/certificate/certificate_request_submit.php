<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access
header('Content-Type: application/json');

$response = [];

$issued_by = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resident_id = intval($_POST['resident_id']);
    $certificate_type = trim($_POST['certificate_type']);
    $purpose = trim($_POST['purpose']);

    if (empty($resident_id) || empty($certificate_type)) {
        $response = ['status' => 'error', 'message' => 'Please fill in all required fields.'];
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO certificate_request (resident_id, issued_by, certificate_type, purpose) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $resident_id, $issued_by, $certificate_type, $purpose);

    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Certificate request submitted successfully.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Database error: ' . $stmt->error];
    }

    echo json_encode($response);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

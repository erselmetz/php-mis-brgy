<?php
/**
 * Add Blotter AJAX Endpoint
 * Handles new blotter case creation via AJAX.
 * Returns JSON response.
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$complainant_name    = sanitizeString($_POST['complainant_name']    ?? '', false);
$complainant_address = sanitizeString($_POST['complainant_address'] ?? '');
$complainant_contact = sanitizeString($_POST['complainant_contact'] ?? '');
$respondent_name     = sanitizeString($_POST['respondent_name']     ?? '', false);
$respondent_address  = sanitizeString($_POST['respondent_address']  ?? '');
$respondent_contact  = sanitizeString($_POST['respondent_contact']  ?? '');
$incident_date       = $_POST['incident_date'] ?? '';
$incident_time       = $_POST['incident_time'] ?? '';
$incident_location   = sanitizeString($_POST['incident_location']   ?? '', false);
$incident_description= sanitizeString($_POST['incident_description']?? '', false);
$status              = $_POST['status'] ?? 'pending';

// Validate required fields
if (empty($complainant_name) || empty($respondent_name) || empty($incident_date) || empty($incident_location) || empty($incident_description)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!validateDateFormat($incident_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid incident date format.']);
    exit;
}

$allowed = ['pending', 'under_investigation', 'resolved', 'dismissed'];
if (!in_array($status, $allowed)) $status = 'pending';

// Generate case number
$year    = date('Y');
$stmt    = $conn->prepare("SELECT COUNT(*) as count FROM blotter WHERE case_number LIKE ?");
$pattern = "BLT-$year-%";
$stmt->bind_param("s", $pattern);
$stmt->execute();
$row     = $stmt->get_result()->fetch_assoc();
$count   = ($row['count'] ?? 0) + 1;
$case_number = "BLT-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
$stmt->close();

// Insert
$stmt = $conn->prepare("
    INSERT INTO blotter (
        case_number, complainant_name, complainant_address, complainant_contact,
        respondent_name, respondent_address, respondent_contact,
        incident_date, incident_time, incident_location, incident_description,
        status, created_by
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
");
$created_by = $_SESSION['user_id'];
$stmt->bind_param("ssssssssssssi",
    $case_number, $complainant_name, $complainant_address, $complainant_contact,
    $respondent_name, $respondent_address, $respondent_contact,
    $incident_date, $incident_time, $incident_location, $incident_description,
    $status, $created_by
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Case recorded successfully.', 'case_number' => $case_number]);
} else {
    error_log('Blotter Insert Error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Error recording case. Please try again.']);
}
$stmt->close();
<?php
/**
 * Update Blotter API Endpoint
 * 
 * Handles AJAX requests to update existing blotter records.
 * Validates input data and uses prepared statements for security.
 * Returns JSON response for frontend handling.
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
    exit;
}

// Validate and sanitize blotter ID
$id = sanitizeInt($_POST['id'] ?? 0, 1);
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing blotter ID.']);
    exit;
}

// Define allowed fields to prevent mass assignment vulnerabilities
$fields = [
    'complainant_name', 'complainant_address', 'complainant_contact',
    'respondent_name', 'respondent_address', 'respondent_contact',
    'incident_date', 'incident_time', 'incident_location', 'incident_description',
    'status', 'resolution', 'resolved_date'
];

// Collect and sanitize POST data
$data = [];
foreach ($fields as $field) {
    if ($field === 'incident_date' || $field === 'resolved_date') {
        $data[$field] = !empty($_POST[$field]) ? $_POST[$field] : null;
    } else {
        $data[$field] = sanitizeString($_POST[$field] ?? null);
    }
}

// Validate required fields
if (empty($data['complainant_name']) || empty($data['respondent_name']) || 
    empty($data['incident_date']) || empty($data['incident_location']) || 
    empty($data['incident_description'])) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit;
}

// Validate date formats
if (!empty($data['incident_date']) && !validateDateFormat($data['incident_date'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid incident date format. Use YYYY-MM-DD format.']);
    exit;
}

if (!empty($data['resolved_date']) && !validateDateFormat($data['resolved_date'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid resolved date format. Use YYYY-MM-DD format.']);
    exit;
}

// Validate status against allowed values
$allowedStatuses = ['pending', 'under_investigation', 'resolved', 'dismissed'];
if (!empty($data['status']) && !in_array($data['status'], $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Set resolved_date to null if status is not resolved
if ($data['status'] !== 'resolved') {
    $data['resolved_date'] = null;
}

// Get current blotter data to compare changes
$currentStmt = $conn->prepare("SELECT case_number, status FROM blotter WHERE id = ?");
$currentStmt->bind_param("i", $id);
$currentStmt->execute();
$currentResult = $currentStmt->get_result();
$currentData = $currentResult->fetch_assoc();
$currentStmt->close();

$oldStatus = $currentData['status'] ?? null;
$caseNumber = $currentData['case_number'] ?? '';

/**
 * Update blotter record using prepared statement
 * Prepared statements prevent SQL injection attacks
 */
$sql = "UPDATE blotter SET
complainant_name = ?, complainant_address = ?, complainant_contact = ?,
respondent_name = ?, respondent_address = ?, respondent_contact = ?,
incident_date = ?, incident_time = ?, incident_location = ?, incident_description = ?,
status = ?, resolution = ?, resolved_date = ?
WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log('Blotter Update Error - Query preparation failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    exit;
}

/**
 * Bind parameters in the correct order
 * Parameter types: i = integer, s = string
 * Order must match the SET placeholders in the SQL query
 */
$stmt->bind_param(
    'sssssssssssssi',
    $data['complainant_name'],
    $data['complainant_address'],
    $data['complainant_contact'],
    $data['respondent_name'],
    $data['respondent_address'],
    $data['respondent_contact'],
    $data['incident_date'],
    $data['incident_time'],
    $data['incident_location'],
    $data['incident_description'],
    $data['status'],
    $data['resolution'],
    $data['resolved_date'],
    $id
);

// Execute query and handle result
if ($stmt->execute()) {
    // Log history if status changed
    if ($oldStatus && $oldStatus !== $data['status']) {
        $userName = $_SESSION['name'] ?? 'Unknown';
        $historySql = "INSERT INTO blotter_history (blotter_id, case_number, action_type, old_status, new_status, user_id, user_name, notes) VALUES (?, ?, 'status_changed', ?, ?, ?, ?, ?)";
        $historyStmt = $conn->prepare($historySql);
        $notes = "Status changed from " . ucfirst(str_replace('_', ' ', $oldStatus)) . " to " . ucfirst(str_replace('_', ' ', $data['status']));
        $historyStmt->bind_param("isssiss", $id, $caseNumber, $oldStatus, $data['status'], $_SESSION['user_id'], $userName, $notes);
        $historyStmt->execute();
        $historyStmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Blotter case updated successfully']);
} else {
    // Log error for debugging but don't expose database details to user
    error_log('Blotter Update Error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Update failed. Please try again.']);
}

$stmt->close();

/**
 * IMPORTANT: Do not close $conn here
 * It's a shared connection managed by db.php
 * Closing it would break other operations that use the same connection
 */

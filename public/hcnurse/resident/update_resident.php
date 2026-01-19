<?php
/**
 * Update Resident API Endpoint
 * 
 * Handles AJAX requests to update existing resident records.
 * Validates input data and uses prepared statements for security.
 * Returns JSON response for frontend handling.
 */

require_once '../../includes/app.php';
requireHCNurse();

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Validate and sanitize resident ID
$id = sanitizeInt($_POST['id'] ?? 0, 1);
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing resident ID.']);
    exit;
}

/**
 * Define allowed fields to prevent mass assignment vulnerabilities
 * Only these fields will be processed from POST data
 */
$fields = [
    'household_id', 'first_name', 'middle_name', 'last_name', 'suffix',
    'gender', 'birthdate', 'birthplace', 'civil_status', 'religion',
    'occupation', 'citizenship', 'contact_no', 'address', 'voter_status',
    'disability_status', 'remarks'
];

// Collect and sanitize POST data
$data = [];
foreach ($fields as $field) {
    if ($field === 'household_id') {
        // Special handling for household_id - can be null
        $data[$field] = !empty($_POST[$field]) ? sanitizeInt($_POST[$field], 1) : null;
    } else {
        $data[$field] = sanitizeString($_POST[$field] ?? null);
    }
}

// Validate required fields
if (empty($data['first_name']) || empty($data['last_name'])) {
    echo json_encode(['success' => false, 'message' => 'First and last name are required.']);
    exit;
}

// Validate birthdate format if provided
if (!empty($data['birthdate']) && !validateDateFormat($data['birthdate'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid birthdate format. Use YYYY-MM-DD format.']);
    exit;
}

// Validate phone number format if provided
if (!empty($data['contact_no']) && !validatePhilippinePhone($data['contact_no'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid contact number format. Use 09XXXXXXXXX format.']);
    exit;
}

// Validate household_id if provided - check if household exists
if (!empty($data['household_id'])) {
    $householdCheckQuery = "SELECT id FROM households WHERE id = ?";
    $householdStmt = $conn->prepare($householdCheckQuery);
    $householdStmt->bind_param('i', $data['household_id']);
    $householdStmt->execute();
    $householdResult = $householdStmt->get_result();

    if ($householdResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected household does not exist.']);
        exit;
    }
    $householdStmt->close();
}

/**
 * Update resident record using prepared statement
 * Prepared statements prevent SQL injection attacks
 */
$sql = "UPDATE residents SET
household_id = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
gender = ?, birthdate = ?, birthplace = ?, civil_status = ?, religion = ?,
occupation = ?, citizenship = ?, contact_no = ?, address = ?, voter_status = ?,
disability_status = ?, remarks = ?
WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log('Resident Update Error - Query preparation failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    exit;
}

/**
 * Bind parameters in the correct order
 * Parameter types: i = integer, s = string
 * Order must match the SET placeholders in the SQL query
 */
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

// Execute query and handle result
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Resident updated successfully']);
} else {
    // Log error for debugging but don't expose database details to user
    error_log('Resident Update Error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Update failed. Please try again.']);
}

$stmt->close();

/**
 * IMPORTANT: Do not close $conn here
 * It's a shared connection managed by db.php
 * Closing it would break other operations that use the same connection
 */

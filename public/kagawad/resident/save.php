<?php
/**
 * Save Resident API Endpoint
 * 
 * Handles AJAX requests to save new resident records.
 * Validates input data and uses prepared statements for security.
 * Returns JSON response for frontend handling.
 */

require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    /**
     * Define allowed fields to prevent mass assignment vulnerabilities
     * Only these fields will be processed from POST data
     */
    $fields = [
        'household_id', 'birthdate', 'first_name', 'middle_name', 'last_name', 'suffix',
        'gender', 'birthplace', 'civil_status', 'religion', 'occupation', 'citizenship',
        'contact_no', 'address', 'voter_status', 'remarks'
    ];

    // Collect and sanitize POST data
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = isset($_POST[$f]) ? trim($_POST[$f]) : null;
    }

    /**
     * Input Validation
     * Validate required fields and data formats before database operations
     */
    
    // Required fields validation
    if (empty($data['first_name']) || empty($data['last_name'])) {
        echo json_encode(['status' => 'error', 'message' => 'First and last name are required.']);
        exit;
    }

    // Birthdate format validation (YYYY-MM-DD)
    if (!empty($data['birthdate']) && !validateDateFormat($data['birthdate'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid birthdate format. Use YYYY-MM-DD format.']);
        exit;
    }

    // Philippine phone number validation (09XXXXXXXXX)
    if (!empty($data['contact_no']) && !validatePhilippinePhone($data['contact_no'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid contact number format. Use 09XXXXXXXXX format.']);
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
            echo json_encode(['status' => 'error', 'message' => 'Selected household does not exist.']);
            exit;
        }
        $householdStmt->close();
    }

    /**
     * Insert new resident record using prepared statements
     * Prepared statements prevent SQL injection attacks by separating SQL structure from data
     */
    $stmt = $conn->prepare("
        INSERT INTO residents (
            household_id, birthdate, first_name, middle_name, last_name, suffix,
            gender, birthplace, civil_status, religion, occupation, citizenship,
            contact_no, address, voter_status, remarks, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ");

    if ($stmt === false) {
        error_log('Resident Save Error - Query preparation failed: ' . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
        exit;
    }

    /**
     * Bind parameters in the correct order
     * Parameter types: i = integer, s = string
     * Order must match the VALUES placeholders in the SQL query
     */
    $householdId = !empty($data['household_id']) ? (int)$data['household_id'] : null;
    $stmt->bind_param(
        "isssssssssssssss",
        $householdId,
        $data['birthdate'],
        $data['first_name'],
        $data['middle_name'],
        $data['last_name'],
        $data['suffix'],
        $data['gender'],
        $data['birthplace'],
        $data['civil_status'],
        $data['religion'],
        $data['occupation'],
        $data['citizenship'],
        $data['contact_no'],
        $data['address'],
        $data['voter_status'],
        $data['remarks']
    );

    // Execute query and handle result
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Resident saved successfully.']);
    } else {
        // Log detailed error for debugging (not exposed to user)
        error_log('Resident Save Error: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred. Please try again.']);
    }

    $stmt->close();

} catch (Exception $e) {
    /**
     * Catch any unexpected errors
     * Log full error details for debugging but show generic message to user
     * This prevents exposing sensitive system information
     */
    error_log('Resident Save Exception: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while saving resident. Please try again.']);
}

<?php
/**
 * Certificate Request Submission API
 * 
 * Handles AJAX requests to submit new certificate requests.
 * Validates input and uses prepared statements for security.
 * Returns JSON response for frontend handling.
 */

require_once '../../includes/app.php';
requireHCNurse(); // Only HC Nurse can access
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Get current user ID (the person issuing the certificate)
    $issued_by = $_SESSION['user_id'] ?? null;
    
    if (empty($issued_by)) {
        echo json_encode(['status' => 'error', 'message' => 'User session expired. Please login again.']);
        exit;
    }

    // Sanitize and validate input
    $resident_id = sanitizeInt($_POST['resident_id'] ?? 0, 1); // Must be positive integer
    $certificate_type = sanitizeString($_POST['certificate_type'] ?? '', false);
    $purpose = sanitizeString($_POST['purpose'] ?? '', false);

    // Validate required fields
    if (empty($resident_id) || empty($certificate_type)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }

    /**
     * Validate certificate type against allowed values
     * This prevents invalid certificate types from being stored
     */
    $allowedTypes = ['Barangay Clearance', 'Indigency Certificate', 'Residency Certificate'];
    if (!in_array($certificate_type, $allowedTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid certificate type.']);
        exit;
    }

    /**
     * Insert certificate request using prepared statement
     * This prevents SQL injection attacks
     */
    $stmt = $conn->prepare("INSERT INTO certificate_request (resident_id, issued_by, certificate_type, purpose) VALUES (?, ?, ?, ?)");
    
    if ($stmt === false) {
        error_log('Certificate Request Error - Query preparation failed: ' . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
        exit;
    }
    
    $stmt->bind_param("iiss", $resident_id, $issued_by, $certificate_type, $purpose);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Certificate request submitted successfully.']);
    } else {
        // Log error for debugging but don't expose to user
        error_log('Certificate Request Error: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred. Please try again.']);
    }
    
    $stmt->close();

} catch (Exception $e) {
    // Log full error details for debugging
    error_log('Certificate Request Exception: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
}

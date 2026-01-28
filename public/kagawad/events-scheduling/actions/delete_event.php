<?php
/**
 * Delete Event API
 * Handles deletion of events
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireKagawad();

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => 'Unknown error occurred.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $response['message'] = 'Invalid event ID.';
    echo json_encode($response);
    exit;
}

try {
    // Check if event exists
    $checkStmt = $conn->prepare("SELECT id, title FROM events WHERE id = ?");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $response['message'] = 'Event not found.';
        echo json_encode($response);
        exit;
    }
    
    // Delete event
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Event deleted successfully.';
    } else {
        $response['message'] = 'Database error: ' . $conn->error;
        error_log('Delete event error: ' . $conn->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log('Delete event error: ' . $e->getMessage());
    $response['message'] = 'Failed to delete event.';
}

echo json_encode($response);


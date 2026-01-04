<?php
/**
 * Update Event API
 * Handles updating existing events
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireAdmin();

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
$title = sanitizeString($_POST['title'] ?? '');
$description = sanitizeString($_POST['description'] ?? '');
$event_date = $_POST['event_date'] ?? '';
$event_time = $_POST['event_time'] ?? '';
$location = sanitizeString($_POST['location'] ?? '');
$priority = sanitizeString($_POST['priority'] ?? 'normal');
$status = sanitizeString($_POST['status'] ?? 'scheduled');

if ($id <= 0) {
    $response['message'] = 'Invalid event ID.';
    echo json_encode($response);
    exit;
}

if (empty($title) || empty($event_date)) {
    $response['message'] = 'Title and Event Date are required.';
    echo json_encode($response);
    exit;
}

// Validate date format
if (!validateDateFormat($event_date)) {
    $response['message'] = 'Invalid date format.';
    echo json_encode($response);
    exit;
}

// Validate priority
if (!in_array($priority, ['normal', 'important', 'urgent'])) {
    $priority = 'normal';
}

// Validate status
if (!in_array($status, ['scheduled', 'completed', 'cancelled'])) {
    $status = 'scheduled';
}

try {
    // Check if event exists
    $checkStmt = $conn->prepare("SELECT id FROM events WHERE id = ?");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $response['message'] = 'Event not found.';
        echo json_encode($response);
        exit;
    }
    
    // Update event
    $sql = "UPDATE events SET 
            title = ?, description = ?, event_date = ?, event_time = ?, 
            location = ?, priority = ?, status = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sssssssi',
        $title,
        $description,
        $event_date,
        $event_time,
        $location,
        $priority,
        $status,
        $id
    );
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Event updated successfully.';
    } else {
        $response['message'] = 'Database error: ' . $conn->error;
        error_log('Update event error: ' . $conn->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log('Update event error: ' . $e->getMessage());
    $response['message'] = 'Failed to update event.';
}

echo json_encode($response);


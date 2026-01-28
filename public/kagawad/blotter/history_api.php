<?php
require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

header('Content-Type: application/json');

$blotterId = isset($_GET['blotter_id']) ? (int)$_GET['blotter_id'] : 0;
$caseNumber = sanitizeString($_GET['case_number'] ?? '');

if ($blotterId > 0) {
    // Get history for specific blotter case
    $stmt = $conn->prepare("
        SELECT h.*, u.name as user_name
        FROM blotter_history h
        LEFT JOIN users u ON h.user_id = u.id
        WHERE h.blotter_id = ?
        ORDER BY h.created_at DESC
        LIMIT 100
    ");
    $stmt->bind_param('i', $blotterId);
} else if (!empty($caseNumber)) {
    // Get history by case number
    $stmt = $conn->prepare("
        SELECT h.*, u.name as user_name
        FROM blotter_history h
        LEFT JOIN users u ON h.user_id = u.id
        WHERE h.case_number = ?
        ORDER BY h.created_at DESC
        LIMIT 100
    ");
    $stmt->bind_param('s', $caseNumber);
} else {
    // Get all history
    $stmt = $conn->prepare("
        SELECT h.*, u.name as user_name
        FROM blotter_history h
        LEFT JOIN users u ON h.user_id = u.id
        ORDER BY h.created_at DESC
        LIMIT 100
    ");
}

$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $statusColors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'under_investigation' => 'bg-blue-100 text-blue-800',
        'resolved' => 'bg-green-100 text-green-800',
        'dismissed' => 'bg-gray-100 text-gray-800'
    ];
    
    $oldStatusColor = $row['old_status'] ? ($statusColors[$row['old_status']] ?? 'bg-gray-100 text-gray-800') : '';
    $newStatusColor = $row['new_status'] ? ($statusColors[$row['new_status']] ?? 'bg-gray-100 text-gray-800') : '';
    
    $history[] = [
        'id' => $row['id'],
        'case_number' => $row['case_number'],
        'action_type' => $row['action_type'],
        'old_status' => $row['old_status'],
        'new_status' => $row['new_status'],
        'old_status_display' => $row['old_status'] ? ucfirst(str_replace('_', ' ', $row['old_status'])) : '',
        'new_status_display' => $row['new_status'] ? ucfirst(str_replace('_', ' ', $row['new_status'])) : '',
        'old_status_color' => $oldStatusColor,
        'new_status_color' => $newStatusColor,
        'user_name' => $row['user_name'] ?? 'Unknown',
        'notes' => $row['notes'],
        'created_at' => $row['created_at']
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'history' => $history,
    'total' => count($history)
]);

/**
 * IMPORTANT: Do not close $conn here
 * It's a shared connection managed by db.php
 * Closing it would break other operations that use the same connection
 */

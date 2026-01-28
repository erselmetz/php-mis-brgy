<?php
/**
 * Fetch Events API
 * Returns all events for the calendar and event list
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireKagawad();

header('Content-Type: application/json; charset=utf-8');

try {
    // Check if events table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'events'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        // Table doesn't exist, return empty array
        echo json_encode([
            'status' => 'ok',
            'data' => [],
            'message' => 'Events table not found. Please run schema/create_events_scheduling.php'
        ]);
        exit;
    }
    
    $search = sanitizeString($_GET['search'] ?? '');
    $month = (int)($_GET['month'] ?? date('n'));
    $year = (int)($_GET['year'] ?? date('Y'));
    $status = sanitizeString($_GET['status'] ?? 'scheduled');
    
    $where = [];
    $params = [];
    $types = '';
    
    // Filter by status (scheduled, completed, cancelled)
    if ($status === 'all') {
        // Show all statuses
    } else {
        $where[] = "e.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // Filter by month/year
    if ($month > 0 && $year > 0) {
        $where[] = "YEAR(event_date) = ? AND MONTH(event_date) = ?";
        $params[] = $year;
        $params[] = $month;
        $types .= 'ii';
    }
    
    // Search filter
    if (!empty($search)) {
        $where[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT e.*, u.name as created_by_name 
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            {$whereClause}
            ORDER BY event_date ASC, event_time ASC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'status' => 'ok',
        'data' => $events
    ]);
    
} catch (mysqli_sql_exception $e) {
    error_log('Fetch events SQL error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => []
    ]);
} catch (Exception $e) {
    error_log('Fetch events error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch events: ' . $e->getMessage(),
        'data' => []
    ]);
}


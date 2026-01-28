<?php
/**
 * Fetch Event History API
 * Returns completed and cancelled events
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireKagawad();

header('Content-Type: application/json; charset=utf-8');

try {
    $search = sanitizeString($_GET['search'] ?? '');
    $limit  = (int)($_GET['limit'] ?? 50);

    $where  = ["e.status IN ('completed', 'cancelled')"];
    $params = [];
    $types  = '';

    // Search filter
    if (!empty($search)) {
        $where[] = "(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    $sql = "
        SELECT 
            e.*,
            u.name AS created_by_name
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        {$whereClause}
        ORDER BY e.event_date DESC, e.created_at DESC
        LIMIT ?
    ";

    $params[] = $limit;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    $stmt->close();

    echo json_encode([
        'status' => 'ok',
        'data'   => $events
    ]);

} catch (Exception $e) {
    error_log('Fetch event history error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to fetch event history',
        'data'    => []
    ]);
}

<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

try {
    $search = sanitizeString($_GET['search'] ?? '');
    $officerId = isset($_GET['officer_id']) ? (int)$_GET['officer_id'] : 0;

    // Check if term_history table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'term_history'");
    if ($tableCheck->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'history' => [],
            'total' => 0,
            'message' => 'Term history table does not exist. Please run database migrations.'
        ]);
        exit;
    }

    $query = "
        SELECT th.*, 
               o.position as current_position,
               u.name as officer_user_name,
               r.first_name, r.middle_name, r.last_name,
               CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as resident_name
        FROM term_history th
        LEFT JOIN officers o ON th.officer_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN residents r ON o.resident_id = r.id
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if ($officerId > 0) {
        $query .= " AND th.officer_id = ?";
        $params[] = $officerId;
        $types .= 'i';
    }

    if (!empty($search)) {
        $query .= " AND (
            u.name LIKE ? OR 
            o.position LIKE ? OR
            CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) LIKE ?
        )";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    $query .= " ORDER BY th.created_at DESC LIMIT 200";

    $stmt = $conn->prepare($query);
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

    $history = [];
    while ($row = $result->fetch_assoc()) {
        // Format term period
        $termPeriod = '';
        if ($row['old_term_start'] || $row['new_term_start']) {
            $oldTerm = $row['old_term_start'] ? date('M d, Y', strtotime($row['old_term_start'])) : 'N/A';
            $newTerm = $row['new_term_start'] ? date('M d, Y', strtotime($row['new_term_start'])) : 'N/A';
            if ($row['old_term_start'] != $row['new_term_start']) {
                $termPeriod = "{$oldTerm} → {$newTerm}";
            } else {
                $termPeriod = $newTerm;
            }
        }
        
        // Format action display
        $actionDisplay = ucfirst(str_replace('_', ' ', $row['action_type']));
        
        // Status colors
        $statusColors = [
            'Active' => 'bg-green-100 text-green-800',
            'Inactive' => 'bg-gray-100 text-gray-800'
        ];
        $oldStatusColor = $row['old_status'] ? ($statusColors[$row['old_status']] ?? 'bg-gray-100 text-gray-800') : '';
        $newStatusColor = $row['new_status'] ? ($statusColors[$row['new_status']] ?? 'bg-gray-100 text-gray-800') : '';
        
        $history[] = [
            'id' => $row['id'],
            'officer_id' => $row['officer_id'],
            'officer_name' => $row['officer_user_name'] ?? 'N/A',
            'resident_name' => $row['resident_name'] ?? 'Not a resident',
            'position' => $row['current_position'] ?? $row['new_position'] ?? $row['old_position'] ?? 'N/A',
            'action_type' => $row['action_type'],
            'action_display' => $actionDisplay,
            'old_position' => $row['old_position'],
            'new_position' => $row['new_position'],
            'old_term_start' => $row['old_term_start'],
            'new_term_start' => $row['new_term_start'],
            'old_term_end' => $row['old_term_end'],
            'new_term_end' => $row['new_term_end'],
            'term_period' => $termPeriod,
            'old_status' => $row['old_status'],
            'new_status' => $row['new_status'],
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
} catch (Exception $e) {
    error_log('Term History API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading term history: ' . $e->getMessage(),
        'history' => [],
        'total' => 0
    ]);
}
?>

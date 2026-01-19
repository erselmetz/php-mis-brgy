<?php
require_once '../../../includes/app.php';
requireKagawad(); // Only Kagawad can access

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get residents that can be selected as heads of household
    // Include residents who don't have a household or have minimal household info
    $stmt = $conn->prepare("
        SELECT
            r.id,
            r.first_name,
            r.middle_name,
            r.last_name,
            r.suffix,
            r.address,
            r.birthdate,
            TIMESTAMPDIFF(YEAR, r.birthdate, CURDATE()) as age
        FROM residents r
        WHERE r.deleted_at IS NULL
        ORDER BY r.last_name, r.first_name
        LIMIT ?
    ");

    $limit = (int)($_GET['limit'] ?? 1000);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $residents = [];
    while ($row = $result->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name'] . ' ' . ($row['suffix'] ?? ''));
        $residents[] = [
            'id' => $row['id'],
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'],
            'last_name' => $row['last_name'],
            'suffix' => $row['suffix'],
            'full_name' => $fullName,
            'address' => $row['address'],
            'age' => $row['age']
        ];
    }

    echo json_encode([
        'success' => true,
        'residents' => $residents
    ]);

} catch (Exception $e) {
    error_log('Get Residents for Head API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

/**
 * IMPORTANT: Do not close $conn here
 * It's a shared connection managed by db.php
 * Closing it would break other operations that use the same connection
 */
?>
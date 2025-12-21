<?php
require_once __DIR__ . '/../../../includes/app.php';
requireLogin();
header('Content-Type: application/json');

$role = $_SESSION['role'] ?? '';

// Only allow certain roles to query blotter summary
$allowedRoles = ['admin', 'tanod', 'secretary'];
if (!in_array($role, $allowedRoles)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$start = trim($_GET['start_date'] ?? '');
$end = trim($_GET['end_date'] ?? '');

// Validate dates (YYYY-MM-DD)
$startParam = null;
$endParam = null;
$hasRange = false;
if ($start && $end) {
    $startDt = DateTime::createFromFormat('Y-m-d', $start);
    $endDt = DateTime::createFromFormat('Y-m-d', $end);
    if ($startDt && $endDt) {
        $hasRange = true;
        // Normalize times to cover full days
        $startParam = $startDt->format('Y-m-d') . ' 00:00:00';
        $endParam = $endDt->format('Y-m-d') . ' 23:59:59';
    }
}

try {
    if ($hasRange) {
        $sql = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) AS under_investigation_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed_count
        FROM blotter
        WHERE (incident_date BETWEEN ? AND ?)
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) throw new Exception($conn->error);
        $stmt->bind_param('ss', $startParam, $endParam);
    } else {
        // No range provided: count all
        $sql = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) AS under_investigation_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed_count
        FROM blotter";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) throw new Exception($conn->error);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) throw new Exception($stmt->error);

    $row = $res->fetch_assoc();
    $data = [
        'total' => (int)($row['total'] ?? 0),
        'pending' => (int)($row['pending_count'] ?? 0),
        'under_investigation' => (int)($row['under_investigation_count'] ?? 0),
        'resolved' => (int)($row['resolved_count'] ?? 0),
        'dismissed' => (int)($row['dismissed_count'] ?? 0)
    ];

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
} catch (Exception $e) {
    error_log('Blotter summary error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch blotter summary']);
    exit;
}

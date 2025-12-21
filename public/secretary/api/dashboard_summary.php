<?php
require_once __DIR__ . '/../../../includes/app.php';
requireLogin();
header('Content-Type: application/json');

$role = $_SESSION['role'] ?? '';
// Allow appropriate roles
$allowedRoles = ['admin', 'staff', 'tanod', 'secretary'];
if (!in_array($role, $allowedRoles)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$start = trim($_GET['start_date'] ?? '');
$end = trim($_GET['end_date'] ?? '');

$hasRange = false;
$startParam = null;
$endParam = null;
if ($start && $end) {
    $startDt = DateTime::createFromFormat('Y-m-d', $start);
    $endDt = DateTime::createFromFormat('Y-m-d', $end);
    if ($startDt && $endDt) {
        $hasRange = true;
        $startParam = $startDt->format('Y-m-d') . ' 00:00:00';
        $endParam = $endDt->format('Y-m-d') . ' 23:59:59';
    }
}

$response = ['status' => 'error', 'data' => []];
try {
    // Population summary (from residents)
    if ($hasRange) {
        $sqlPop = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) AS male_count,
            SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) AS female_count,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 THEN 1 ELSE 0 END) AS senior_count,
            SUM(CASE WHEN disability_status = 'Yes' THEN 1 ELSE 0 END) AS pwd_count,
            SUM(CASE WHEN voter_status = 'Yes' THEN 1 ELSE 0 END) AS voter_registered_count,
            SUM(CASE WHEN voter_status = 'No' THEN 1 ELSE 0 END) AS voter_unregistered_count
        FROM residents
        WHERE (created_at BETWEEN ? AND ?)
        ";
        $stmtPop = $conn->prepare($sqlPop);
        if ($stmtPop === false) throw new Exception($conn->error);
        $stmtPop->bind_param('ss', $startParam, $endParam);
    } else {
        $sqlPop = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) AS male_count,
            SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) AS female_count,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 THEN 1 ELSE 0 END) AS senior_count,
            SUM(CASE WHEN disability_status = 'Yes' THEN 1 ELSE 0 END) AS pwd_count,
            SUM(CASE WHEN voter_status = 'Yes' THEN 1 ELSE 0 END) AS voter_registered_count,
            SUM(CASE WHEN voter_status = 'No' THEN 1 ELSE 0 END) AS voter_unregistered_count
        FROM residents";
        $stmtPop = $conn->prepare($sqlPop);
        if ($stmtPop === false) throw new Exception($conn->error);
    }

    $stmtPop->execute();
    $resPop = $stmtPop->get_result();
    if ($resPop === false) throw new Exception($stmtPop->error);
    $rowPop = $resPop->fetch_assoc();
    $population = [
        'total' => (int)($rowPop['total'] ?? 0),
        'male' => (int)($rowPop['male_count'] ?? 0),
        'female' => (int)($rowPop['female_count'] ?? 0),
        'senior' => (int)($rowPop['senior_count'] ?? 0),
        'pwd' => (int)($rowPop['pwd_count'] ?? 0),
        'voter_registered' => (int)($rowPop['voter_registered_count'] ?? 0),
        'voter_unregistered' => (int)($rowPop['voter_unregistered_count'] ?? 0)
    ];
    $stmtPop->close();

    // Blotter summary
    if ($hasRange) {
        $sqlBlot = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) AS under_investigation_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed_count
        FROM blotter
        WHERE (incident_date BETWEEN ? AND ?)
        ";
        $stmtBlot = $conn->prepare($sqlBlot);
        if ($stmtBlot === false) throw new Exception($conn->error);
        $stmtBlot->bind_param('ss', $startParam, $endParam);
    } else {
        $sqlBlot = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) AS under_investigation_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed_count
        FROM blotter";
        $stmtBlot = $conn->prepare($sqlBlot);
        if ($stmtBlot === false) throw new Exception($conn->error);
    }

    $stmtBlot->execute();
    $resBlot = $stmtBlot->get_result();
    if ($resBlot === false) throw new Exception($stmtBlot->error);
    $rowBlot = $resBlot->fetch_assoc();
    $blotter = [
        'total' => (int)($rowBlot['total'] ?? 0),
        'pending' => (int)($rowBlot['pending_count'] ?? 0),
        'under_investigation' => (int)($rowBlot['under_investigation_count'] ?? 0),
        'resolved' => (int)($rowBlot['resolved_count'] ?? 0),
        'dismissed' => (int)($rowBlot['dismissed_count'] ?? 0)
    ];
    $stmtBlot->close();

    $response = ['status' => 'success', 'data' => ['population' => $population, 'blotter' => $blotter]];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log('Dashboard summary error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch dashboard summary']);
    exit;
}

<?php
/**
 * Dashboard API
 * Returns date-filtered stats as JSON for the secretary dashboard.
 * 
 * GET params:
 *   date_from  — start date (Y-m-d), defaults to first day of current month
 *   date_to    — end date   (Y-m-d), defaults to today
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

// ── Date range ────────────────────────────────────────────────────────────────
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Sanitize — fallback to current month if invalid
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-d');

// ── Helper ────────────────────────────────────────────────────────────────────
function q(mysqli $conn, string $sql, array $params = []): ?array
{
    if (empty($params)) {
        $res = $conn->query($sql);
        return $res ? $res->fetch_assoc() : null;
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param(...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function qAll(mysqli $conn, string $sql, array $params = []): array
{
    if (empty($params)) {
        $res = $conn->query($sql);
        if (!$res) return [];
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    return $rows;
}

// ── 1. POPULATION (all-time, no date filter) ──────────────────────────────────
$pop = q($conn, "
    SELECT
        COUNT(*) total,
        SUM(gender='Male') male,
        SUM(gender='Female') female,
        SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE())>=60) senior,
        SUM(disability_status='Yes') pwd,
        SUM(voter_status='Yes') voter_registered,
        SUM(voter_status='No') voter_unregistered
    FROM residents WHERE deleted_at IS NULL
") ?? [];

// New residents within date range
$newResidents = q($conn, "
    SELECT COUNT(*) cnt FROM residents
    WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN ? AND ?
", ['ss', $dateFrom, $dateTo])['cnt'] ?? 0;

// ── 2. CERTIFICATES (date-filtered) ──────────────────────────────────────────
$certs = q($conn, "
    SELECT
        COUNT(*) total,
        SUM(status='pending')  pending,
        SUM(status='approved') approved,
        SUM(status='printed')  printed,
        SUM(certificate_type='Barangay Clearance')   clearance,
        SUM(certificate_type='Indigency Certificate') indigency,
        SUM(certificate_type='Residency Certificate') residency
    FROM certificate_request
    WHERE DATE(created_at) BETWEEN ? AND ?
", ['ss', $dateFrom, $dateTo]) ?? [];

// Certificates by day (for line chart)
$certsByDay = qAll($conn, "
    SELECT DATE(created_at) as day, COUNT(*) cnt
    FROM certificate_request
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY day ASC
", ['ss', $dateFrom, $dateTo]);

// ── 3. BLOTTER (date-filtered) ────────────────────────────────────────────────
$blotter = q($conn, "
    SELECT
        COUNT(*) total,
        SUM(status='pending')              pending,
        SUM(status='under_investigation')  under_investigation,
        SUM(status='resolved')             resolved,
        SUM(status='dismissed')            dismissed
    FROM blotter
    WHERE DATE(created_at) BETWEEN ? AND ?
", ['ss', $dateFrom, $dateTo]) ?? [];

// All-time blotter (for overall status chart)
$blotterAll = q($conn, "
    SELECT
        SUM(status='pending')              pending,
        SUM(status='under_investigation')  under_investigation,
        SUM(status='resolved')             resolved,
        SUM(status='dismissed')            dismissed
    FROM blotter WHERE archived_at IS NULL
") ?? [];

// ── 4. OFFICERS ───────────────────────────────────────────────────────────────
$officers = q($conn, "
    SELECT
        COUNT(*) total,
        SUM(status='Active') active,
        SUM(status='Inactive') inactive
    FROM officers WHERE archived_at IS NULL
") ?? [];

// Officers with terms expiring in next 60 days
$expiringOfficers = qAll($conn, "
    SELECT o.position, o.term_end, u.name
    FROM officers o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.archived_at IS NULL
      AND o.term_end IS NOT NULL
      AND o.term_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY o.term_end ASC
");

// ── 5. INVENTORY ──────────────────────────────────────────────────────────────
$inventory = q($conn, "
    SELECT COUNT(*) total FROM inventory
") ?? [];

// Low stock — quantity <= 5
$lowStockItems = qAll($conn, "
    SELECT name, quantity, unit
    FROM inventory
    WHERE quantity IS NOT NULL AND quantity <= 5
    ORDER BY quantity ASC
    LIMIT 5
");

// ── 6. EVENTS (upcoming, no date filter) ─────────────────────────────────────
$upcomingEvents = qAll($conn, "
    SELECT title, event_date, event_time, location
    FROM events
    WHERE status = 'scheduled' AND event_date >= CURDATE()
    ORDER BY event_date ASC
    LIMIT 5
");

// ── 7. MEDICINES ──────────────────────────────────────────────────────────────
$lowMedicines = qAll($conn, "
    SELECT name, quantity, unit
    FROM medicines
    WHERE quantity IS NOT NULL AND quantity <= 10
    ORDER BY quantity ASC
    LIMIT 5
");

// Medicine dispenses in date range
$dispenses = q($conn, "
    SELECT COUNT(*) total, COALESCE(SUM(quantity),0) qty
    FROM medicine_dispense
    WHERE DATE(dispense_date) BETWEEN ? AND ?
", ['ss', $dateFrom, $dateTo]) ?? ['total' => 0, 'qty' => 0];

// ── 8. CONSULTATIONS ──────────────────────────────────────────────────────────
$consultations = q($conn, "
    SELECT COUNT(*) total
    FROM consultations
    WHERE DATE(consultation_date) BETWEEN ? AND ?
", ['ss', $dateFrom, $dateTo]) ?? ['total' => 0];

// ── 9. LAST BACKUP ────────────────────────────────────────────────────────────
$lastBackup = q($conn, "
    SELECT created_at, file_size FROM backups
    ORDER BY created_at DESC LIMIT 1
");

// ── Response ──────────────────────────────────────────────────────────────────
echo json_encode([
    'date_from'        => $dateFrom,
    'date_to'          => $dateTo,
    'population'       => $pop,
    'new_residents'    => (int)$newResidents,
    'certificates'     => $certs,
    'certs_by_day'     => $certsByDay,
    'blotter'          => $blotter,
    'blotter_all'      => $blotterAll,
    'officers'         => $officers,
    'expiring_officers'=> $expiringOfficers,
    'inventory'        => $inventory,
    'low_stock_items'  => $lowStockItems,
    'upcoming_events'  => $upcomingEvents,
    'low_medicines'    => $lowMedicines,
    'dispenses'        => $dispenses,
    'consultations'    => $consultations,
    'last_backup'      => $lastBackup,
]);
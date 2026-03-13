<?php
/**
 * File Reports Stats API
 *
 * Returns live record counts and last-updated timestamps
 * for each report category shown on the profile page.
 *
 * Access : Secretary only
 * Method : GET
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

/**
 * Helper: run a COUNT + MAX(updated_at / created_at) query
 * and return [ 'count' => int, 'last_updated' => string|null ]
 */
function tableStats(mysqli $conn, string $table, string $dateCol = 'updated_at'): array
{
    // Fallback to created_at if updated_at doesn't exist
    $cols   = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$dateCol'");
    $col    = ($cols && $cols->num_rows > 0) ? $dateCol : 'created_at';

    $result = $conn->query("SELECT COUNT(*) AS cnt, MAX(`$col`) AS last_dt FROM `$table`");
    if (!$result) {
        return ['count' => 0, 'last_updated' => null];
    }
    $row = $result->fetch_assoc();
    return [
        'count'        => (int) ($row['cnt'] ?? 0),
        'last_updated' => $row['last_dt'] ?? null,
    ];
}

/**
 * Human-friendly "time ago" label
 */
function timeAgo(?string $datetime): string
{
    if (!$datetime) return 'No records';
    $diff = time() - strtotime($datetime);
    if ($diff < 60)        return 'Just now';
    if ($diff < 3600)      return floor($diff / 60) . ' min ago';
    if ($diff < 86400)     return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800)    return floor($diff / 86400) . ' day(s) ago';
    return date('m/d/Y', strtotime($datetime));
}

// ── Residents (exclude archived) ───────────────────────────────────────────────
$resResult = $conn->query(
    "SELECT COUNT(*) AS cnt, MAX(COALESCE(updated_at, created_at)) AS last_dt
     FROM residents
     WHERE archived_at IS NULL OR archived_at = '0000-00-00 00:00:00'"
);
$resRow     = $resResult ? $resResult->fetch_assoc() : ['cnt' => 0, 'last_dt' => null];
$residents  = ['count' => (int)$resRow['cnt'], 'last_updated' => $resRow['last_dt']];

// ── Officers / Officials ────────────────────────────────────────────────────────
$offResult  = $conn->query(
    "SELECT COUNT(*) AS cnt, MAX(COALESCE(updated_at, created_at)) AS last_dt
     FROM officers
     WHERE archived_at IS NULL OR archived_at = '0000-00-00 00:00:00'"
);
$offRow     = $offResult ? $offResult->fetch_assoc() : ['cnt' => 0, 'last_dt' => null];
$officers   = ['count' => (int)$offRow['cnt'], 'last_updated' => $offRow['last_dt']];

// ── Blotter ─────────────────────────────────────────────────────────────────────
$blResult   = $conn->query(
    "SELECT COUNT(*) AS cnt, MAX(COALESCE(updated_at, created_at)) AS last_dt
     FROM blotter
     WHERE archived_at IS NULL OR archived_at = '0000-00-00 00:00:00'"
);
$blRow      = $blResult ? $blResult->fetch_assoc() : ['cnt' => 0, 'last_dt' => null];
$blotter    = ['count' => (int)$blRow['cnt'], 'last_updated' => $blRow['last_dt']];

// ── Inventory ───────────────────────────────────────────────────────────────────
$invStats   = tableStats($conn, 'inventory');

// ── Build response ──────────────────────────────────────────────────────────────
$reports = [
    [
        'key'          => 'residents',
        'label'        => 'Residence List',
        'count'        => $residents['count'],
        'last_updated' => timeAgo($residents['last_updated']),
        'print_url'    => '/secretary/profile/file_reports_print.php?report=residents',
    ],
    [
        'key'          => 'officers',
        'label'        => 'Officials & Staff',
        'count'        => $officers['count'],
        'last_updated' => timeAgo($officers['last_updated']),
        'print_url'    => '/secretary/profile/file_reports_print.php?report=officers',
    ],
    [
        'key'          => 'blotter',
        'label'        => 'Blotter',
        'count'        => $blotter['count'],
        'last_updated' => timeAgo($blotter['last_updated']),
        'print_url'    => '/secretary/profile/file_reports_print.php?report=blotter',
    ],
    [
        'key'          => 'inventory',
        'label'        => 'Inventory',
        'count'        => $invStats['count'],
        'last_updated' => timeAgo($invStats['last_updated']),
        'print_url'    => '/secretary/profile/file_reports_print.php?report=inventory',
    ],
];

echo json_encode(['status' => 'ok', 'data' => $reports]);
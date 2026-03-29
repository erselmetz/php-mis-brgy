<?php
/**
 * File Reports Stats API
 *
 * Returns live record counts and last-updated timestamps.
 * Uses UNIX_TIMESTAMP() so PHP never has to parse a datetime string —
 * no timezone mismatch possible.
 *
 * Access : Secretary only
 * Method : GET
 */

require_once __DIR__ . '/../../includes/app.php';
requireKagawad();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

/**
 * Convert a unix timestamp (int) to a human-readable relative label.
 * Receives an integer directly from MySQL UNIX_TIMESTAMP() —
 * no strtotime() parsing, no timezone mismatch possible.
 */
function timeAgoUnix(?int $unix): string
{
    if (!$unix) return 'No records';
    $diff = time() - $unix;
    if ($diff < 0)      return 'Just now';   // clock skew safety
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60)    . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600)  . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day(s) ago';
    return date('M d, Y', $unix);
}

/**
 * Single query per table — returns count + latest unix timestamp.
 *
 * GREATEST(MAX(created_at), MAX(updated_at)) captures BOTH:
 *   - newly inserted rows  → MAX(created_at) bumps
 *   - recently edited rows → MAX(updated_at) bumps
 *
 * UNIX_TIMESTAMP() converts to integer inside MySQL so PHP
 * never has to call strtotime() on a datetime string.
 */
function reportStats(mysqli $conn, string $table, string $whereClause = ''): array
{
    $where = $whereClause ? "WHERE $whereClause" : '';

    $sql = "
        SELECT
            COUNT(*) AS cnt,
            UNIX_TIMESTAMP(
                GREATEST(
                    COALESCE(MAX(created_at), FROM_UNIXTIME(0)),
                    COALESCE(MAX(updated_at), FROM_UNIXTIME(0))
                )
            ) AS last_unix
        FROM `{$table}`
        {$where}
    ";

    $result = $conn->query($sql);
    if (!$result) {
        error_log("file_reports.php error on {$table}: " . $conn->error);
        return ['count' => 0, 'last_unix' => null];
    }

    $row = $result->fetch_assoc();
    return [
        'count'     => (int)($row['cnt']      ?? 0),
        'last_unix' => (int)($row['last_unix'] ?? 0) ?: null,
    ];
}

// ── Fetch stats ──────────────────────────────────────────────────────────────
$residents = reportStats($conn, 'residents', 'deleted_at IS NULL');
$officers  = reportStats($conn, 'officers',  'archived_at IS NULL');
$blotter   = reportStats($conn, 'blotter',   'archived_at IS NULL');
$inventory = reportStats($conn, 'inventory');

// ── Build response ───────────────────────────────────────────────────────────
$reports = [
    [
        'key'          => 'residents',
        'label'        => 'Residence List',
        'count'        => $residents['count'],
        'last_updated' => timeAgoUnix($residents['last_unix']),
        'print_url'    => '/kagawad/profile/file_reports_print.php?report=residents',
    ],
    [
        'key'          => 'officers',
        'label'        => 'Officials & Staff',
        'count'        => $officers['count'],
        'last_updated' => timeAgoUnix($officers['last_unix']),
        'print_url'    => '/kagawad/profile/file_reports_print.php?report=officers',
    ],
    [
        'key'          => 'blotter',
        'label'        => 'Blotter',
        'count'        => $blotter['count'],
        'last_updated' => timeAgoUnix($blotter['last_unix']),
        'print_url'    => '/kagawad/profile/file_reports_print.php?report=blotter',
    ],
    [
        'key'          => 'inventory',
        'label'        => 'Inventory',
        'count'        => $inventory['count'],
        'last_updated' => timeAgoUnix($inventory['last_unix']),
        'print_url'    => '/kagawad/profile/file_reports_print.php?report=inventory',
    ],
];

echo json_encode(['status' => 'ok', 'data' => $reports]);
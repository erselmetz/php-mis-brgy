<?php
/**
 * File Reports Stats API
 *
 * Returns live record counts and last-updated timestamps
 * for each report category shown on the profile page.
 *
 * Table archive column reference:
 *   residents  → deleted_at  (soft-delete, NULL = active)
 *   officers   → archived_at (NULL = active)
 *   blotter    → archived_at (NULL = active)
 *   inventory  → no archive column, all rows counted
 *
 * Access : Secretary only
 * Method : GET
 */

require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

header('Content-Type: application/json');

/**
 * Human-friendly "time ago" label
 */
function timeAgo(?string $datetime): string
{
    if (!$datetime) return 'No records';
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day(s) ago';
    return date('m/d/Y', strtotime($datetime));
}

/**
 * Safe query helper — returns ['count'=>int, 'last_updated'=>string|null]
 * Catches query errors and returns zeros instead of breaking the whole response.
 */
function safeCount(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if (!$result) {
        error_log('file_reports.php query error: ' . $conn->error . ' | SQL: ' . $sql);
        return ['count' => 0, 'last_updated' => null];
    }
    $row = $result->fetch_assoc();
    return [
        'count'        => (int)($row['cnt'] ?? 0),
        'last_updated' => $row['last_dt'] ?? null,
    ];
}

// ── Residents ─────────────────────────────────────────────────────────────────
// Uses `deleted_at` for soft-delete (NOT archived_at)
$residents = safeCount($conn,
    "SELECT COUNT(*) AS cnt,
            MAX(COALESCE(updated_at, created_at)) AS last_dt
     FROM residents
     WHERE deleted_at IS NULL"
);

// ── Officers / Officials & Staff ──────────────────────────────────────────────
// Uses `archived_at` (added by add_archived_at_to_officers migration)
$officers = safeCount($conn,
    "SELECT COUNT(*) AS cnt,
            MAX(COALESCE(updated_at, created_at)) AS last_dt
     FROM officers
     WHERE archived_at IS NULL"
);

// ── Blotter ───────────────────────────────────────────────────────────────────
// Uses `archived_at` (added by add_archived_at_to_blotter migration)
$blotter = safeCount($conn,
    "SELECT COUNT(*) AS cnt,
            MAX(COALESCE(updated_at, created_at)) AS last_dt
     FROM blotter
     WHERE archived_at IS NULL"
);

// ── Inventory ─────────────────────────────────────────────────────────────────
// No soft-delete column — count all rows
$inventory = safeCount($conn,
    "SELECT COUNT(*) AS cnt,
            MAX(COALESCE(updated_at, created_at)) AS last_dt
     FROM inventory"
);

// ── Build response ────────────────────────────────────────────────────────────
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
        'count'        => $inventory['count'],
        'last_updated' => timeAgo($inventory['last_updated']),
        'print_url'    => '/secretary/profile/file_reports_print.php?report=inventory',
    ],
];

echo json_encode(['status' => 'ok', 'data' => $reports]);
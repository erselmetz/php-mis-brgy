<?php
/**
 * File Reports Print Page
 *
 * Generates a clean, printable HTML report for the requested data set.
 * Opens in a new tab and triggers window.print() automatically.
 *
 * Table archive column reference:
 *   residents  → deleted_at  (soft-delete, NULL = active)
 *   officers   → archived_at (NULL = active)
 *   blotter    → archived_at (NULL = active)
 *   inventory  → no archive column
 *
 * Query params:
 *   ?report=residents | officers | blotter | inventory
 *
 * Access: Secretary only
 */

require_once __DIR__ . '/../../includes/app.php';
requireSecretary();

$allowed = ['residents', 'officers', 'blotter', 'inventory'];
$report  = $_GET['report'] ?? '';

if (!in_array($report, $allowed)) {
    http_response_code(400);
    exit('Invalid report type.');
}

$generated = date('F d, Y h:i A');
$rows      = [];
$title     = '';
$headers   = [];

// ── Fetch data per report type ─────────────────────────────────────────────────

if ($report === 'residents') {
    $title   = 'Residence List Report';
    $headers = ['#', 'Full Name', 'Gender', 'Birthdate', 'Civil Status', 'Address', 'Contact No.', 'Voter Status'];

    // residents uses deleted_at (NOT archived_at)
    $res = $conn->query(
        "SELECT id,
                CONCAT(first_name, ' ', COALESCE(NULLIF(middle_name,''), ''), ' ', last_name) AS full_name,
                gender, birthdate, civil_status, address, contact_no, voter_status
         FROM residents
         WHERE deleted_at IS NULL
         ORDER BY last_name, first_name"
    );
    if ($res) {
        $i = 1;
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                $i++,
                trim($row['full_name']),
                $row['gender']       ?? '—',
                $row['birthdate']    ? date('m/d/Y', strtotime($row['birthdate'])) : '—',
                $row['civil_status'] ?? '—',
                $row['address']      ?? '—',
                $row['contact_no']   ?? '—',
                $row['voter_status'] ?? '—',
            ];
        }
    }

} elseif ($report === 'officers') {
    $title   = 'Officials & Staff Report';
    $headers = ['#', 'Name', 'Position', 'Term Start', 'Term End', 'Status'];

    // officers uses archived_at
    $res = $conn->query(
        "SELECT o.position, o.term_start, o.term_end, o.status,
                CONCAT(r.first_name, ' ', COALESCE(NULLIF(r.middle_name,''), ''), ' ', r.last_name) AS full_name
         FROM officers o
         LEFT JOIN residents r ON o.resident_id = r.id
         WHERE o.archived_at IS NULL
         ORDER BY o.position"
    );
    if ($res) {
        $i = 1;
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                $i++,
                trim($row['full_name'] ?? 'N/A'),
                $row['position']   ?? '—',
                $row['term_start'] ? date('m/d/Y', strtotime($row['term_start'])) : '—',
                $row['term_end']   ? date('m/d/Y', strtotime($row['term_end']))   : '—',
                $row['status']     ?? '—',
            ];
        }
    }

} elseif ($report === 'blotter') {
    $title   = 'Blotter Report';
    $headers = ['#', 'Case No.', 'Complainant', 'Respondent', 'Incident Date', 'Location', 'Status'];

    // blotter uses archived_at
    $res = $conn->query(
        "SELECT case_number, complainant_name, respondent_name,
                incident_date, incident_location, status
         FROM blotter
         WHERE archived_at IS NULL
         ORDER BY incident_date DESC"
    );
    if ($res) {
        $i = 1;
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                $i++,
                $row['case_number']       ?? '—',
                $row['complainant_name']  ?? '—',
                $row['respondent_name']   ?? '—',
                $row['incident_date']     ? date('m/d/Y', strtotime($row['incident_date'])) : '—',
                $row['incident_location'] ?? '—',
                ucfirst(str_replace('_', ' ', $row['status'] ?? '—')),
            ];
        }
    }

} elseif ($report === 'inventory') {
    $title   = 'Inventory Report';
    $headers = ['#', 'Asset Code', 'Item Name', 'Category', 'Quantity', 'Condition', 'Location', 'Status'];

    // inventory has no soft-delete — fetch all
    $res = $conn->query(
        "SELECT asset_code, name, category, quantity, cond, location,
                COALESCE(status, 'available') AS status
         FROM inventory
         ORDER BY name"
    );
    if ($res) {
        $i = 1;
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                $i++,
                $row['asset_code'] ?? '—',
                $row['name']       ?? '—',
                $row['category']   ?? '—',
                $row['quantity']   ?? '0',
                $row['cond']       ?? '—',
                $row['location']   ?? '—',
                ucfirst($row['status'] ?? '—'),
            ];
        }
    }
}

$totalRows = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?> - MIS Barangay</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #111; padding: 20px; }

    .print-controls {
      position: fixed; top: 16px; right: 16px;
      background: white; border: 1px solid #ccc;
      border-radius: 8px; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.15);
      z-index: 999;
    }
    .print-controls button {
      background: #446c3e; color: white;
      border: none; padding: 8px 18px;
      border-radius: 6px; cursor: pointer; font-size: 13px;
    }
    .print-controls button:hover { background: #355630; }

    .header { margin-bottom: 16px; border-bottom: 2px solid #446c3e; padding-bottom: 10px; }
    .header h2 { font-size: 18px; color: #446c3e; }
    .header .meta { font-size: 11px; color: #666; margin-top: 4px; }

    .summary-pill {
      display: inline-block; padding: 3px 10px;
      background: #f0f4ef; border-radius: 999px;
      font-size: 11px; margin-right: 6px; margin-bottom: 10px;
    }

    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #446c3e; color: white; padding: 7px 8px; text-align: left; font-size: 11px; }
    td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; vertical-align: top; }
    tr:nth-child(even) td { background: #f9fafb; }

    .no-records { padding: 20px; text-align: center; color: #999; }

    @media print {
      .print-controls { display: none; }
      body { padding: 10px; }
    }
  </style>
</head>
<body>

  <div class="print-controls">
    <button onclick="window.print()">🖨 Print / Save PDF</button>
  </div>

  <div class="header">
    <h2>MIS Barangay — <?= htmlspecialchars($title) ?></h2>
    <div class="meta">
      Generated: <?= htmlspecialchars($generated) ?>
      &nbsp;|&nbsp;
      Prepared by: <?= htmlspecialchars($_SESSION['name'] ?? 'Secretary') ?>
    </div>
  </div>

  <div>
    <span class="summary-pill">Total Records: <?= $totalRows ?></span>
  </div>

  <?php if ($totalRows === 0): ?>
    <p class="no-records">No records found.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <?php foreach ($headers as $h): ?>
            <th><?= htmlspecialchars($h) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($row as $cell): ?>
              <td><?= htmlspecialchars((string)$cell) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <script>
    if (window.opener) {
      window.addEventListener('load', () => window.print());
    }
  </script>
</body>
</html>
<?php
/**
 * Tanod Duty Schedule - Print Sheet
 */
require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

$filterDate = $_GET['filter_date'] ?? '';
$filterShift = $_GET['filter_shift'] ?? '';

$where = [];
$params = [];
$types = '';

if (!empty($filterDate)) {
    $where[] = "duty_date = ?";
    $params[] = $filterDate;
    $types .= 's';
}
if (!empty($filterShift)) {
    $where[] = "shift = ?";
    $params[] = $filterShift;
    $types .= 's';
}

$whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT t.*, u.name AS created_by_name FROM tanod_duty_schedule t
        LEFT JOIN users u ON t.created_by = u.id
        {$whereClause}
        ORDER BY t.duty_date ASC, FIELD(t.shift,'morning','afternoon','night')";

$stmt = $conn->prepare($sql);
if (!empty($params))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$title = 'Tanod Duty Schedule';
if ($filterDate)
    $title .= ' — ' . date('F j, Y', strtotime($filterDate));
if ($filterShift)
    $title .= ' (' . ucfirst($filterShift) . ' Shift)';

$shiftLabels = ['morning' => '☀️ Morning (6AM–2PM)', 'afternoon' => '🌤️ Afternoon (2PM–10PM)', 'night' => '🌙 Night (10PM–6AM)'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
        }

        .page {
            padding: 20px 28px;
        }

        .header {
            text-align: center;
            margin-bottom: 16px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header p {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 10px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #1e40af;
            color: #fff;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }

        td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }

        tr:nth-child(even) td {
            background: #f9fafb;
        }

        .footer {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }

        .sig-line {
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
            padding-top: 4px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="no-print" style="text-align:right; margin-bottom:12px;">
            <button onclick="window.print()"
                style="padding:6px 14px;background:#1e40af;color:#fff;border:none;border-radius:4px;cursor:pointer;">🖨️
                Print</button>
        </div>

        <div class="header">
            <h1>Barangay Bombongan</h1>
            <p>Tanod Duty Schedule</p>
            <?php if ($filterDate): ?>
                <p><?= date('F j, Y', strtotime($filterDate)) ?></p><?php endif; ?>
        </div>

        <div class="meta">
            <span>Generated: <?= date('F j, Y g:i A') ?></span>
            <span>Total Records: <?= count($rows) ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Duty Code</th>
                    <th>Tanod Name</th>
                    <th>Date</th>
                    <th>Shift</th>
                    <th>Post / Location</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:12px;color:#888;">No records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['duty_code']) ?></td>
                            <td><?= htmlspecialchars($row['tanod_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($row['duty_date'])) ?></td>
                            <td><?= $shiftLabels[$row['shift']] ?? ucfirst($row['shift']) ?></td>
                            <td><?= htmlspecialchars($row['post_location'] ?: '—') ?></td>
                            <td><?= ucfirst($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['notes'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <div>
                <div class="sig-line">Prepared by</div>
            </div>
            <div>
                <div class="sig-line">Barangay Captain</div>
            </div>
        </div>
    </div>
</body>

</html>
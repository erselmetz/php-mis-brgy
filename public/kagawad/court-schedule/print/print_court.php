<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireKagawad();

$filterDate = $_GET['filter_date'] ?? '';
$filterFac = $_GET['filter_facility'] ?? '';

$where = [];
$params = [];
$types = '';
if (!empty($filterDate)) {
    $where[] = "reservation_date = ?";
    $params[] = $filterDate;
    $types .= 's';
}
if (!empty($filterFac)) {
    $where[] = "facility = ?";
    $params[] = $filterFac;
    $types .= 's';
}

$wc = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT c.*, u.name AS created_by_name FROM court_schedule c LEFT JOIN users u ON c.created_by=u.id {$wc} ORDER BY c.reservation_date ASC, c.time_start ASC";
$stmt = $conn->prepare($sql);
if (!empty($params))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$facLabels = ['basketball_court' => 'Basketball Court', 'multipurpose_area' => 'Multipurpose Area', 'gym' => 'Gym'];
$title = 'Court / Facility Schedule';
if ($filterDate)
    $title .= ' — ' . date('F j, Y', strtotime($filterDate));
if ($filterFac)
    $title .= ' (' . ($facLabels[$filterFac] ?? $filterFac) . ')';
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
            background: #15803d;
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
            .no-print {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="no-print" style="text-align:right;margin-bottom:12px;">
            <button onclick="window.print()"
                style="padding:6px 14px;background:#15803d;color:#fff;border:none;border-radius:4px;cursor:pointer;">🖨️
                Print</button>
        </div>
        <div class="header">
            <h1>Barangay Bombongan</h1>
            <p>Court / Facility Reservation Schedule</p>
            <?php if ($filterDate): ?>
                <p><?= date('F j, Y', strtotime($filterDate)) ?></p><?php endif; ?>
        </div>
        <div class="meta">
            <span>Generated: <?= date('F j, Y g:i A') ?></span>
            <span>Total: <?= count($rows) ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Facility</th>
                    <th>Borrower</th>
                    <th>Organization</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:12px;color:#888;">No records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r):
                        $fmt = function ($t) {
                            if (!$t)
                                return '';
                            [$h, $m] = explode(':', $t);
                            $hr = (int) $h;
                            $ap = $hr >= 12 ? 'PM' : 'AM';
                            return ($hr % 12 ?: 12) . ':' . $m . ' ' . $ap; };
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($r['reservation_code']) ?></td>
                            <td><?= $facLabels[$r['facility']] ?? $r['facility'] ?></td>
                            <td><?= htmlspecialchars($r['borrower_name']) ?></td>
                            <td><?= htmlspecialchars($r['organization'] ?: '—') ?></td>
                            <td><?= date('M j, Y', strtotime($r['reservation_date'])) ?></td>
                            <td><?= $fmt($r['time_start']) ?> – <?= $fmt($r['time_end']) ?></td>
                            <td><?= htmlspecialchars($r['purpose']) ?></td>
                            <td><?= ucfirst($r['status']) ?></td>
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
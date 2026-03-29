<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

$filterDate = $_GET['filter_date'] ?? '';
$filterStat = $_GET['filter_status'] ?? '';

$where = [];
$params = [];
$types = '';
if (!empty($filterDate)) {
    $where[] = "patrol_date = ?";
    $params[] = $filterDate;
    $types .= 's';
}
if (!empty($filterStat)) {
    $where[] = "status = ?";
    $params[] = $filterStat;
    $types .= 's';
}

$wc = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT p.*, u.name AS created_by_name FROM patrol_schedule p LEFT JOIN users u ON p.created_by=u.id {$wc} ORDER BY p.patrol_date ASC, p.time_start ASC";
$stmt = $conn->prepare($sql);
if (!empty($params))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$title = 'Mobile Patrol / Roving Schedule';
if ($filterDate)
    $title .= ' — ' . date('F j, Y', strtotime($filterDate));
if ($filterStat)
    $title .= ' (' . ucfirst($filterStat) . ')';
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
            font-size: 11px;
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
            font-size: 15px;
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
            margin-top: 8px;
        }

        th {
            background: #b45309;
            color: #fff;
            padding: 5px 7px;
            text-align: left;
            font-size: 10px;
        }

        td {
            padding: 4px 7px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
            vertical-align: top;
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
                style="padding:6px 14px;background:#b45309;color:#fff;border:none;border-radius:4px;cursor:pointer;">🖨️
                Print</button>
        </div>
        <div class="header">
            <h1>Barangay Bombongan</h1>
            <p>Mobile Patrol / Roving Tanod Schedule</p>
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
                    <th>Code</th>
                    <th>Team</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Patrol Route</th>
                    <th>Area Covered</th>
                    <th>Members</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center;padding:10px;color:#888;">No records found.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    foreach ($rows as $i => $r):
                        $fmt = function ($t) {
                            if (!$t)
                                return '';
                            [$h, $m] = explode(':', $t);
                            $hr = (int) $h;
                            $ap = $hr >= 12 ? 'PM' : 'AM';
                            return ($hr % 12 ?: 12) . ':' . $m . ' ' . $ap; };
                        $type = $r['is_weekly'] ? 'Weekly (' . ($days[$r['week_day']] ?? '') . ')' : 'One-time';
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($r['patrol_code']) ?></td>
                            <td><?= htmlspecialchars($r['team_name']) ?></td>
                            <td><?= $r['patrol_date'] ? date('M j, Y', strtotime($r['patrol_date'])) : '—' ?></td>
                            <td><?= $fmt($r['time_start']) ?> – <?= $fmt($r['time_end']) ?></td>
                            <td><?= htmlspecialchars($r['patrol_route'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($r['area_covered'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($r['tanod_members'] ?: '—') ?></td>
                            <td><?= $type ?></td>
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
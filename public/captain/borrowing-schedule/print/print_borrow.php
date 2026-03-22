<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireCaptain();

$filterStat = $_GET['filter_status'] ?? '';
$filterDate = $_GET['filter_date'] ?? '';

$where = [];
$params = [];
$types = '';
if (!empty($filterStat)) {
    $where[] = "b.status = ?";
    $params[] = $filterStat;
    $types .= 's';
}
if (!empty($filterDate)) {
    $where[] = "b.borrow_date = ?";
    $params[] = $filterDate;
    $types .= 's';
}

$wc = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT b.*, u.name AS created_by_name FROM borrowing_schedule b LEFT JOIN users u ON b.created_by=u.id {$wc} ORDER BY b.borrow_date DESC";
$stmt = $conn->prepare($sql);
if (!empty($params))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$title = 'Equipment Borrowing Schedule';
if ($filterStat)
    $title .= ' — ' . ucfirst($filterStat);
if ($filterDate)
    $title .= ' (' . date('F j, Y', strtotime($filterDate)) . ')';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?= htmlspecialchars($title) ?>
    </title>
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
            background: #7c3aed;
            color: #fff;
            padding: 5px 7px;
            text-align: left;
            font-size: 10px;
        }

        td {
            padding: 4px 7px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
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
                style="padding:6px 14px;background:#7c3aed;color:#fff;border:none;border-radius:4px;cursor:pointer;">🖨️
                Print</button>
        </div>
        <div class="header">
            <h1>Barangay Bombongan</h1>
            <p>Equipment / Items Borrowing Schedule</p>
        </div>
        <div class="meta">
            <span>Generated:
                <?= date('F j, Y g:i A') ?>
            </span>
            <span>Total Records:
                <?= count($rows) ?>
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Borrower</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Borrow Date</th>
                    <th>Return Date</th>
                    <th>Actual Return</th>
                    <th>Status</th>
                    <th>Condition Out</th>
                    <th>Condition In</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="11" style="text-align:center;padding:10px;color:#888;">No records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td>
                                <?= $i + 1 ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['borrow_code']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['borrower_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['item_name']) ?>
                            </td>
                            <td>
                                <?= $r['quantity'] ?>
                            </td>
                            <td>
                                <?= $r['borrow_date'] ? date('M j, Y', strtotime($r['borrow_date'])) : '—' ?>
                            </td>
                            <td>
                                <?= $r['return_date'] ? date('M j, Y', strtotime($r['return_date'])) : '—' ?>
                            </td>
                            <td>
                                <?= $r['actual_return'] ? date('M j, Y', strtotime($r['actual_return'])) : '—' ?>
                            </td>
                            <td>
                                <?= ucfirst($r['status']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['condition_out'] ?: '—') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['condition_in'] ?: '—') ?>
                            </td>
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
<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse(); // Only HC Nurse can access
// reports/medicine_inventory_print.php

$conn->set_charset('utf8mb4');

$search = trim($_GET['search'] ?? '');
$category_id = (int) ($_GET['category_id'] ?? 0);
$status = strtoupper(trim($_GET['status'] ?? 'ALL'));
$exp_days = (int) ($_GET['exp_days'] ?? 30);
if ($exp_days < 1)
    $exp_days = 30;
if ($exp_days > 365)
    $exp_days = 365;

$today = date('Y-m-d');
$expLimit = date('Y-m-d', strtotime("+{$exp_days} days"));

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(m.name LIKE CONCAT('%', ?, '%') OR m.description LIKE CONCAT('%', ?, '%'))";
    $types .= "ss";
    $params[] = $search;
    $params[] = $search;
}
if ($category_id > 0) {
    $where[] = "m.category_id = ?";
    $types .= "i";
    $params[] = $category_id;
}
if ($status === 'OUT_OF_STOCK')
    $where[] = "m.stock_qty = 0";
elseif ($status === 'CRITICAL')
    $where[] = "(m.stock_qty > 0 AND m.stock_qty <= m.reorder_level)";
elseif ($status === 'OK')
    $where[] = "(m.stock_qty > m.reorder_level)";
elseif ($status === 'EXPIRING_SOON') {
    $where[] = "(m.expiration_date IS NOT NULL AND m.expiration_date BETWEEN ? AND ?)";
    $types .= "ss";
    $params[] = $today;
    $params[] = $expLimit;
}

$whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "
  SELECT
    m.name,
    mc.name AS category_name,
    m.unit,
    m.stock_qty,
    m.reorder_level,
    m.expiration_date,
    CASE
      WHEN m.stock_qty = 0 THEN 'OUT OF STOCK'
      WHEN m.stock_qty <= m.reorder_level THEN 'CRITICAL'
      ELSE 'OK'
    END AS stock_status,
    CASE
      WHEN m.expiration_date IS NOT NULL AND m.expiration_date BETWEEN ? AND ? THEN 'YES'
      ELSE 'NO'
    END AS expiring_soon
  FROM medicines m
  LEFT JOIN medicine_categories mc ON mc.id = m.category_id
  $whereSql
  ORDER BY
    (m.stock_qty = 0) DESC,
    (m.stock_qty <= m.reorder_level) DESC,
    m.name ASC
";

$finalTypes = "ss" . $types;
$finalParams = array_merge([$today, $expLimit], $params);

$stmt = $conn->prepare($sql);
$stmt->bind_param($finalTypes, ...$finalParams);
$stmt->execute();
$res = $stmt->get_result();

$title = "HC Nurse - Medicine Inventory Report";
$subtitle = "Generated: " . date('F d, Y h:i A') . " | Expiring Soon: {$exp_days} days";
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111;
        }

        .header {
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 16px;
        }

        .header .sub {
            color: #555;
            font-size: 11px;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .tag {
            padding: 2px 6px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 11px;
            display: inline-block;
        }

        .ok {
            background: #e7f9ed;
            color: #166534;
        }

        .critical {
            background: #fff7ed;
            color: #9a3412;
        }

        .oos {
            background: #fee2e2;
            color: #991b1b;
        }

        .yes {
            color: #b91c1c;
            font-weight: bold;
        }

        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="header">
        <h2><?= htmlspecialchars($title) ?></h2>
        <div class="sub"><?= htmlspecialchars($subtitle) ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:24%;">Medicine</th>
                <th style="width:14%;">Category</th>
                <th style="width:8%;">Stock</th>
                <th style="width:10%;">Reorder Level</th>
                <th style="width:10%;">Unit</th>
                <th style="width:12%;">Expiration</th>
                <th style="width:12%;">Status</th>
                <th style="width:10%;">Expiring Soon</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $res->fetch_assoc()): ?>
                <?php
                $status = $row['stock_status'];
                $cls = 'ok';
                if ($status === 'CRITICAL')
                    $cls = 'critical';
                if ($status === 'OUT OF STOCK')
                    $cls = 'oos';
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                    <td><?= (int) $row['stock_qty'] ?></td>
                    <td><?= (int) $row['reorder_level'] ?></td>
                    <td><?= htmlspecialchars($row['unit'] ?? 'pcs') ?></td>
                    <td><?= htmlspecialchars($row['expiration_date'] ?? '-') ?></td>
                    <td><span class="tag <?= $cls ?>"><?= htmlspecialchars($row['stock_status']) ?></span></td>
                    <td class="<?= ($row['expiring_soon'] === 'YES') ? 'yes' : '' ?>">
                        <?= htmlspecialchars($row['expiring_soon']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>

</html>
<?php
$stmt->close();
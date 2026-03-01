<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type = $_GET['type'] ?? 'maternal';
$period = $_GET['period'] ?? 'all';
$month = $_GET['month'] ?? date('Y-m');
$search = trim($_GET['search'] ?? '');
$sub = trim($_GET['sub'] ?? 'all');

$allowed = ['immunization', 'maternal', 'family_planning', 'prenatal', 'postnatal', 'child_nutrition'];
if (!in_array($type, $allowed, true))
    $type = 'maternal';

/* ---- SAME DATE LOGIC ---- */
function compute_range($period, $month)
{
    $today = date('Y-m-d');
    $currentYM = date('Y-m');

    if ($period === 'monthly') {
        if (!preg_match('/^\d{4}-\d{2}$/', $month))
            $month = $currentYM;
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        return [$from, $to];
    }

    if ($period === 'daily') {
        return [$today, $today];
    }

    if ($period === 'weekly') {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        return [$monday, $sunday];
    }

    $from = $currentYM . '-01';
    $to = date('Y-m-t', strtotime($from));
    return [$from, $to];
}

list($from, $to) = compute_range($period, $month);

/* ---- FETCH DATA ---- */
$sql = "
SELECT
  c.id,
  c.complaint,
  c.notes,
  c.consultation_date,
  CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
FROM consultations c
LEFT JOIN residents r ON r.id = c.resident_id
WHERE c.consultation_date BETWEEN ? AND ?
ORDER BY c.consultation_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {
    $meta = meta_normalize(meta_decode($row['notes'] ?? ''));

    if (($meta['program'] ?? '') !== $type)
        continue;
    if ($sub !== 'all' && ($meta['sub_type'] ?? '') !== $sub)
        continue;

    $row['meta'] = $meta;
    $data[] = $row;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Print Records</title>
    <style>
        body {
            font-family: Arial;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        th {
            background: #eee;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <h2>Health Records -
        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?>
    </h2>
    <p>Period:
        <?= htmlspecialchars($period); ?> | Date Range:
        <?= $from; ?> to
        <?= $to; ?>
    </p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Resident</th>
                <th>Sub Type</th>
                <th>Status</th>
                <th>Complaint</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($row['consultation_date']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['resident_name']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['meta']['sub_type'] ?? '-'); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['meta']['status'] ?? '-'); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['complaint']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        window.onload = function () {
            window.print();
        }
    </script>

</body>

</html>
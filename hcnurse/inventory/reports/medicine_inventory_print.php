<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

date_default_timezone_set('Asia/Manila');

$conn->set_charset('utf8mb4');

$search      = trim($_GET['search'] ?? '');
$category_id = (int)($_GET['category_id'] ?? 0);
$status      = strtoupper(trim($_GET['status'] ?? 'ALL'));
$exp_days    = (int)($_GET['exp_days'] ?? 30);
if ($exp_days < 1) $exp_days = 30;
if ($exp_days > 365) $exp_days = 365;

$exp_only = isset($_GET['exp_only']) && (int)$_GET['exp_only'] === 1;

$today    = date('Y-m-d');
$expLimit = date('Y-m-d', strtotime("+{$exp_days} days"));

$where  = [];
$params = [];
$types  = '';

/* =========================
   FILTERS
========================= */
if ($search !== '') {
  $where[] = "(m.name LIKE CONCAT('%', ?, '%') OR m.description LIKE CONCAT('%', ?, '%'))";
  $types  .= "ss";
  $params[] = $search;
  $params[] = $search;
}

if ($category_id > 0) {
  $where[] = "m.category_id = ?";
  $types  .= "i";
  $params[] = $category_id;
}

if ($status === 'OUT_OF_STOCK') {
  $where[] = "m.stock_qty = 0";
} elseif ($status === 'CRITICAL') {
  $where[] = "(m.stock_qty > 0 AND m.stock_qty <= m.reorder_level)";
} elseif ($status === 'OK') {
  $where[] = "(m.stock_qty > m.reorder_level)";
} elseif ($status === 'EXPIRING_SOON') {
  $where[] = "(m.expiration_date IS NOT NULL AND m.expiration_date BETWEEN ? AND ?)";
  $types  .= "ss";
  $params[] = $today;
  $params[] = $expLimit;
}

// Exp-only works with ANY status (AND condition)
if ($exp_only) {
  $where[] = "(m.expiration_date IS NOT NULL AND m.expiration_date BETWEEN ? AND ?)";
  $types  .= "ss";
  $params[] = $today;
  $params[] = $expLimit;
}

$whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";

/* =========================
   MAIN QUERY
   NOTE: first 2 params are for computed expiring_soon column
========================= */
$sql = "
  SELECT
    m.name,
    mc.name AS category_name,
    m.unit,
    m.stock_qty,
    m.reorder_level,
    m.expiration_date,
    CASE
      WHEN m.stock_qty = 0 THEN 'OUT_OF_STOCK'
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

$finalTypes  = "ss" . $types;
$finalParams = array_merge([$today, $expLimit], $params);

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param($finalTypes, ...$finalParams);
$stmt->execute();
$res = $stmt->get_result();

/* =========================
   CATEGORY LABEL (for filter display)
========================= */
$categoryLabel = 'All';
if ($category_id > 0) {
  $cs = $conn->prepare("SELECT name FROM medicine_categories WHERE id = ?");
  $cs->bind_param("i", $category_id);
  $cs->execute();
  $cr = $cs->get_result()->fetch_assoc();
  $categoryLabel = $cr['name'] ?? 'Unknown';
  $cs->close();
}

/* =========================
   SUMMARY COUNTS (from the fetched rows)
========================= */
$summary = [
  'total' => 0,
  'out_of_stock' => 0,
  'critical' => 0,
  'ok_items' => 0,
  'expiring_soon' => 0
];

$rowsCache = [];
while ($row = $res->fetch_assoc()) {
  $rowsCache[] = $row;
  $summary['total']++;

  $st = $row['stock_status'] ?? '';
  if ($st === 'OUT_OF_STOCK') $summary['out_of_stock']++;
  elseif ($st === 'CRITICAL') $summary['critical']++;
  elseif ($st === 'OK') $summary['ok_items']++;

  if (($row['expiring_soon'] ?? 'NO') === 'YES') $summary['expiring_soon']++;
}

$title = "HC Nurse - Medicine Inventory Report";
$generated = date('F d, Y h:i A');

$filterParts = [];
$filterParts[] = "Category: {$categoryLabel}";
$filterParts[] = "Status: " . ($status ?: 'ALL');
$filterParts[] = "Expiring window: {$exp_days} days";
$filterParts[] = "Exp-only: " . ($exp_only ? 'YES' : 'NO');
if ($search !== '') $filterParts[] = "Search: {$search}";
$filterText = implode(" | ", $filterParts);

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title) ?></title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
    .header { margin-bottom: 10px; }
    .header h2 { margin: 0; font-size: 16px; }
    .header .sub { color: #555; font-size: 11px; margin-top: 4px; }
    .summary { margin: 8px 0 12px; font-size: 11px; }
    .pill { display:inline-block; padding:2px 8px; border-radius:999px; background:#f3f4f6; margin-right:6px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
    th { background: #f3f4f6; text-align: left; }
    .tag { padding: 2px 6px; border-radius: 6px; font-weight: bold; font-size: 11px; display:inline-block; }
    .ok { background:#e7f9ed; color:#166534; }
    .critical { background:#fff7ed; color:#9a3412; }
    .oos { background:#fee2e2; color:#991b1b; }
    .yes { color:#b91c1c; font-weight:bold; }
    @media print { body { margin: 0; } }
  </style>
</head>

<body onload="window.print()">
  <div class="header">
    <h2><?= htmlspecialchars($title) ?></h2>
    <div class="sub">Generated: <?= htmlspecialchars($generated) ?></div>
    <div class="sub"><?= htmlspecialchars($filterText) ?></div>

    <div class="summary">
      <span class="pill">Total: <?= (int)$summary['total'] ?></span>
      <span class="pill">Out of Stock: <?= (int)$summary['out_of_stock'] ?></span>
      <span class="pill">Critical: <?= (int)$summary['critical'] ?></span>
      <span class="pill">OK: <?= (int)$summary['ok_items'] ?></span>
      <span class="pill">Expiring Soon: <?= (int)$summary['expiring_soon'] ?></span>
    </div>
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
      <?php if (count($rowsCache) === 0): ?>
        <tr><td colspan="8" style="padding:10px;color:#666;">No records found for the selected filters.</td></tr>
      <?php else: ?>
        <?php foreach ($rowsCache as $row): ?>
          <?php
            $st = $row['stock_status'] ?? 'OK';
            $cls = 'ok';
            if ($st === 'CRITICAL') $cls = 'critical';
            if ($st === 'OUT_OF_STOCK') $cls = 'oos';
          ?>
          <tr>
            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
            <td><?= (int)($row['stock_qty'] ?? 0) ?></td>
            <td><?= (int)($row['reorder_level'] ?? 0) ?></td>
            <td><?= htmlspecialchars($row['unit'] ?? 'pcs') ?></td>
            <td><?= htmlspecialchars($row['expiration_date'] ?? '-') ?></td>
            <td><span class="tag <?= $cls ?>"><?= htmlspecialchars($st) ?></span></td>
            <td class="<?= (($row['expiring_soon'] ?? 'NO') === 'YES') ? 'yes' : '' ?>">
              <?= htmlspecialchars($row['expiring_soon'] ?? 'NO') ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
<?php
$stmt->close();
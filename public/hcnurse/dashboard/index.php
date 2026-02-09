<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$role = $_SESSION['role'] ?? '';

/* =========================
   DB HELPERS
========================= */
function tableExists(mysqli $conn, string $table): bool
{
  $table = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '{$table}'");
  return $res && $res->num_rows > 0;
}

function fetchStats(mysqli $conn, string $sql, array $defaults): array
{
  $res = $conn->query($sql);
  if (!$res) {
    error_log('Dashboard SQL Error: ' . $conn->error . ' | SQL: ' . $sql);
    return $defaults;
  }

  $row = $res->fetch_assoc() ?: [];
  foreach ($defaults as $key => $value) {
    $defaults[$key] = (int)($row[$key] ?? 0);
  }
  return $defaults;
}

/* =========================
   PATIENT STATS (residents)
========================= */
$patients = fetchStats($conn, "
  SELECT
    COUNT(*) total,
    SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) < 1) infant,
    SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) BETWEEN 1 AND 12) child,
    SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) BETWEEN 13 AND 59) adult,
    SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) >= 60) senior
  FROM residents
", [
  'total' => 0,
  'infant' => 0,
  'child' => 0,
  'adult' => 0,
  'senior' => 0
]);

/* =========================
   CONSULTATION STATS
========================= */
$consultations = ['total' => 0, 'today' => 0, 'this_month' => 0];
if (tableExists($conn, 'consultations')) {
  $consultations = fetchStats($conn, "
    SELECT
      COUNT(*) total,
      SUM(consultation_date = CURDATE()) today,
      SUM(DATE_FORMAT(consultation_date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')) this_month
    FROM consultations
  ", [
    'total' => 0,
    'today' => 0,
    'this_month' => 0
  ]);
}

/* =========================
   IMMUNIZATION STATS
========================= */
$immunization = ['total' => 0, 'ytd' => 0, 'this_month' => 0, 'upcoming' => 0];
if (tableExists($conn, 'immunizations')) {
  $immunization = fetchStats($conn, "
    SELECT
      COUNT(*) total,
      SUM(YEAR(date_given) = YEAR(CURDATE())) ytd,
      SUM(DATE_FORMAT(date_given,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')) this_month,
      SUM(next_schedule IS NOT NULL AND next_schedule >= CURDATE()) upcoming
    FROM immunizations
  ", [
    'total' => 0,
    'ytd' => 0,
    'this_month' => 0,
    'upcoming' => 0
  ]);
}

/* =========================
   INVENTORY STATS
========================= */
$inventory = ['items' => 0, 'low_stock' => 0, 'ok_stock' => 0];
if (tableExists($conn, 'medicines')) {
  $inventory = fetchStats($conn, "
    SELECT
      COUNT(*) items,
      SUM(stock_qty <= reorder_level) low_stock,
      SUM(stock_qty > reorder_level) ok_stock
    FROM medicines
  ", [
    'items' => 0,
    'low_stock' => 0,
    'ok_stock' => 0
  ]);
}

/* =========================
   HEALTH METRICS (THIS MONTH)
========================= */
$metrics = ['this_month' => 0];
if (tableExists($conn, 'health_metrics')) {
  $metrics = fetchStats($conn, "
    SELECT
      SUM(DATE_FORMAT(recorded_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')) this_month
    FROM health_metrics
  ", [
    'this_month' => 0
  ]);
}

/* =========================
   DISPENSED THIS MONTH
========================= */
$dispensed = ['this_month' => 0];
if (tableExists($conn, 'medicine_dispense')) {
  $dispensed = fetchStats($conn, "
    SELECT
      COALESCE(SUM(quantity),0) this_month
    FROM medicine_dispense
    WHERE DATE_FORMAT(dispense_date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')
  ", [
    'this_month' => 0
  ]);
}

/* =========================
   VACCINATION TREND (LAST 6 MONTHS)
========================= */
$trendLabels = [];
$trendData   = [];

if (tableExists($conn, 'immunizations')) {
  $trendRes = $conn->query("
    SELECT
      DATE_FORMAT(date_given,'%b') m,
      COUNT(*) c,
      DATE_FORMAT(date_given,'%Y-%m') ym
    FROM immunizations
    WHERE date_given >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym, m
    ORDER BY ym ASC
  ");

  if ($trendRes) {
    while ($r = $trendRes->fetch_assoc()) {
      $trendLabels[] = $r['m'];
      $trendData[]   = (int)$r['c'];
    }
  }
}

if (count($trendLabels) === 0) {
  // keep a sane empty UI
  $trendLabels = ["-", "-", "-", "-", "-", "-"];
  $trendData   = [0, 0, 0, 0, 0, 0];
}

/* =========================
   MEDICINE LIST (RIGHT PANEL)
========================= */
$medicineRows = [];
if (tableExists($conn, 'medicines')) {
  $medRes = $conn->query("
    SELECT name, stock_qty, reorder_level
    FROM medicines
    ORDER BY (stock_qty <= reorder_level) DESC, name ASC
    LIMIT 8
  ");

  if ($medRes) {
    while ($r = $medRes->fetch_assoc()) {
      $qty = (int)$r['stock_qty'];
      $re  = (int)$r['reorder_level'];

      $status = 'Good';
      if ($qty > $re && $qty <= ($re * 2)) $status = 'Average';
      if ($qty <= $re) $status = 'Low Stock';

      $den = max($re * 2, 1);
      $pct = max(0, min(100, (int)round(($qty / $den) * 100)));

      $medicineRows[] = [
        'name' => $r['name'],
        'pct' => $pct,
        'status' => $status
      ];
    }
  }
}

if (count($medicineRows) === 0) {
  $medicineRows = [
    ['name' => 'No medicines yet', 'pct' => 0, 'status' => 'Good'],
  ];
}

/* =========================
   TOTAL MEDICINES QTY
========================= */
$medicineTotals = ['total_qty' => 0, 'low_stock' => 0];
if (tableExists($conn, 'medicines')) {
  $medicineTotals = fetchStats($conn, "
    SELECT
      COALESCE(SUM(stock_qty),0) total_qty,
      SUM(stock_qty <= reorder_level) low_stock
    FROM medicines
  ", [
    'total_qty' => 0,
    'low_stock' => 0
  ]);
}

$vaccinatedPct = ($patients['total'] > 0)
  ? (int) round(($immunization['ytd'] / $patients['total']) * 100)
  : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php loadAllStyles(); ?>
  <?= loadAsset('node_js', 'chart.js/dist/chart.umd.min.js') ?>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
  <?php include '../layout/navbar.php'; ?>

  <div class="flex h-full">
    <?php include '../layout/sidebar.php'; ?>

    <main class="flex-1 p-6 overflow-y-auto h-screen pb-24">
      <h2 class="text-2xl font-semibold mb-6">Dashboard</h2>

      <!-- TOP STAT CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

        <div class="bg-white border rounded-xl p-4 shadow-sm flex items-start justify-between">
          <div>
            <div class="text-sm text-gray-600 font-semibold">Total Patients</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($patients['total']) ?></div>
            <div class="text-xs text-gray-500 mt-1">* includes all residents</div>
          </div>
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
            <span class="material-icons text-green-700 text-base">person</span>
          </div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm flex items-start justify-between">
          <div>
            <div class="text-sm text-gray-600 font-semibold">Consultations</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($consultations['this_month']) ?></div>
            <div class="text-xs text-gray-500 mt-1">This month (<?= number_format($consultations['today']) ?> today)</div>
          </div>
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
            <span class="material-icons text-blue-700 text-base">assignment</span>
          </div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm flex items-start justify-between">
          <div>
            <div class="text-sm text-gray-600 font-semibold">Vaccinated (YTD)</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($immunization['ytd']) ?></div>
            <div class="text-xs text-gray-500 mt-1"><?= $vaccinatedPct ?>% of target population</div>
          </div>
          <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
            <span class="material-icons text-purple-700 text-base">trending_up</span>
          </div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm flex items-start justify-between">
          <div>
            <div class="text-sm text-gray-600 font-semibold">Total Medicines</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($medicineTotals['total_qty']) ?></div>
            <div class="text-xs text-gray-500 mt-1">⚠ <?= (int)$medicineTotals['low_stock'] ?> items Low Stock</div>
          </div>
          <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
            <span class="material-icons text-red-700 text-base">remove_circle</span>
          </div>
        </div>

      </div>

      <!-- CHARTS ROW (THIS is what makes it feel “dynamic”) -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

        <!-- LEFT: 2x2 donut charts -->
        <div class="bg-white border rounded-xl p-5 shadow-sm xl:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <div class="text-sm font-semibold text-gray-700">Quick Overview</div>
            <div class="text-xs text-gray-500">
              Metrics this month: <?= number_format($metrics['this_month']) ?> • Dispensed: <?= number_format($dispensed['this_month']) ?>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="border rounded-xl p-4">
              <div class="text-xs font-semibold text-gray-600 mb-2">Patients (Age Group)</div>
              <div style="height:220px;">
                <canvas id="patientsChart"></canvas>
              </div>
            </div>

            <div class="border rounded-xl p-4">
              <div class="text-xs font-semibold text-gray-600 mb-2">Consultations (Today)</div>
              <div style="height:220px;">
                <canvas id="consultTodayChart"></canvas>
              </div>
            </div>

            <div class="border rounded-xl p-4">
              <div class="text-xs font-semibold text-gray-600 mb-2">Consultations (This Month)</div>
              <div style="height:220px;">
                <canvas id="consultMonthChart"></canvas>
              </div>
            </div>

            <div class="border rounded-xl p-4">
              <div class="text-xs font-semibold text-gray-600 mb-2">Immunizations (YTD)</div>
              <div class="flex items-center justify-between">
                <div class="text-xs text-gray-500">Upcoming: <?= number_format($immunization['upcoming']) ?></div>
              </div>
              <div style="height:220px;">
                <canvas id="immunChart"></canvas>
              </div>
            </div>
          </div>

          <div class="mt-4 border rounded-xl p-4">
            <div class="text-xs font-semibold text-gray-600 mb-2">Medicine Inventory (OK vs Low)</div>
            <div style="height:220px;">
              <canvas id="inventoryChart"></canvas>
            </div>
          </div>
        </div>

        <!-- RIGHT: Medicine Inventory Status -->
        <div class="bg-white border rounded-xl p-5 shadow-sm">
          <div class="text-sm font-semibold text-gray-700 mb-4">Medicine Inventory Status</div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-gray-500">
                  <th class="text-left py-2 pr-2 font-semibold">Item Name</th>
                  <th class="text-left py-2 pr-2 font-semibold">Stock Level</th>
                  <th class="text-left py-2 font-semibold">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($medicineRows as $m): ?>
                  <?php
                  $status = strtolower($m['status']);
                  $bar = 'bg-green-600';
                  $txt = 'text-gray-700';

                  if ($status === 'average') $bar = 'bg-orange-500';
                  if ($status === 'low stock') $bar = 'bg-red-500';
                  ?>
                  <tr class="border-t border-gray-100">
                    <td class="py-3 pr-2 text-gray-800"><?= htmlspecialchars($m['name']) ?></td>
                    <td class="py-3 pr-2">
                      <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="<?= $bar ?> h-2 rounded-full" style="width: <?= (int)$m['pct'] ?>%"></div>
                      </div>
                    </td>
                    <td class="py-3 font-semibold <?= $txt ?>"><?= htmlspecialchars($m['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- Vaccination Trends -->
      <div class="bg-white border rounded-xl p-5 shadow-sm">
        <div class="text-sm font-semibold text-gray-700 mb-4">Vaccination Trends (Last 6 Months)</div>
        <div style="height:360px;">
          <canvas id="trendChart"></canvas>
        </div>
      </div>

    </main>
  </div>

  <?php loadAllScripts(); ?>

  <script>
    function doughnut(id, labels, data, colors) {
      const el = document.getElementById(id);
      if (!el) return;
      return new Chart(el, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors }] },
        options: {
          cutout: '65%',
          maintainAspectRatio: false,
          responsive: true
        }
      });
    }

    function bar(id, labels, data, colors) {
      const el = document.getElementById(id);
      if (!el) return;
      return new Chart(el, {
        type: 'bar',
        data: { labels, datasets: [{ data, backgroundColor: colors }] },
        options: {
          maintainAspectRatio: false,
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true } }
        }
      });
    }

    $(window).on('load', function () {
      $("body").show();

      requestAnimationFrame(() => {
        Chart.defaults.plugins.legend.position = 'bottom';
        Chart.defaults.plugins.legend.labels.boxWidth = 12;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;

        const patients = <?= json_encode($patients) ?>;
        const consultations = <?= json_encode($consultations) ?>;
        const immunization = <?= json_encode($immunization) ?>;
        const inventory = <?= json_encode($inventory) ?>;
        const trendLabels = <?= json_encode($trendLabels) ?>;
        const trendData = <?= json_encode($trendData) ?>;

        const themeSecondary =
          getComputedStyle(document.documentElement).getPropertyValue('--theme-secondary').trim() || '#446c3e';

        const othersAge = Math.max(0, patients.total - patients.adult - patients.senior);

        doughnut(
          'patientsChart',
          ['ADULT', 'SENIOR', 'OTHERS'],
          [patients.adult, patients.senior, othersAge],
          [themeSecondary, '#f28e2b', '#bdc3c7']
        );

        doughnut(
          'consultTodayChart',
          ['TODAY', 'NOT TODAY'],
          [consultations.today, Math.max(0, consultations.total - consultations.today)],
          ['#5b4bb7', '#f2d7c2']
        );

        doughnut(
          'consultMonthChart',
          ['THIS MONTH', 'OTHERS'],
          [consultations.this_month, Math.max(0, consultations.total - consultations.this_month)],
          ['#6ab04c', '#5d4037']
        );

        doughnut(
          'immunChart',
          ['YTD', 'OTHERS'],
          [immunization.ytd, Math.max(0, immunization.total - immunization.ytd)],
          ['#9bb13c', '#d66bff']
        );

        bar(
          'inventoryChart',
          ['OK STOCK', 'LOW STOCK'],
          [inventory.ok_stock, inventory.low_stock],
          ['#76b7b2', '#ef4444']
        );

        const el = document.getElementById('trendChart');
        if (el) {
          new Chart(el, {
            type: 'bar',
            data: { labels: trendLabels, datasets: [{ data: trendData }] },
            options: {
              maintainAspectRatio: false,
              responsive: true,
              plugins: { legend: { display: false } },
              scales: { y: { beginAtZero: true } }
            }
          });
        }
      });
    });
  </script>
</body>
</html>

<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

$role = $_SESSION['role'] ?? '';

/* =========================
   DB HELPER
========================= */
function fetchStats(mysqli $conn, string $sql, array $defaults): array
{
  $res = $conn->query($sql);

  if (!$res) {
    error_log('Dashboard SQL Error: ' . $conn->error);
    return $defaults;
  }

  $row = $res->fetch_assoc() ?: [];

  foreach ($defaults as $key => $value) {
    $defaults[$key] = (int)($row[$key] ?? 0);
  }

  return $defaults;
}


/* =========================
   POPULATION STATS
========================= */
$population = fetchStats($conn, "
    SELECT
        COUNT(*) total,
        SUM(gender='Male') male,
        SUM(gender='Female') female,
        SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE())>=60) senior,
        SUM(disability_status='Yes') pwd,
        SUM(voter_status='Yes') voter_registered,
        SUM(voter_status='No') voter_unregistered
    FROM residents
", [
  'total' => 0,
  'male' => 0,
  'female' => 0,
  'senior' => 0,
  'pwd' => 0,
  'voter_registered' => 0,
  'voter_unregistered' => 0
]);

/* =========================
   BLOTTER STATS
========================= */
$blotter = fetchStats($conn, "
    SELECT
        COUNT(*) total,
        SUM(status='pending') pending,
        SUM(status='under_investigation') under_investigation,
        SUM(status='resolved') resolved,
        SUM(status='dismissed') dismissed
    FROM blotter
", [
  'total' => 0,
  'pending' => 0,
  'under_investigation' => 0,
  'resolved' => 0,
  'dismissed' => 0
]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php loadAllStyles(); ?>
  <?= loadAsset('node_js', 'chart.js/dist/chart.umd.min.js') ?>
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display: none;">
  <?php include '../layout/navbar.php'; ?>

  <div class="flex h-full">
    <?php include '../layout/sidebar.php'; ?>

    <main class="flex-1 p-6 overflow-y-auto h-screen pb-24">
      <h2 class="text-2xl font-semibold mb-6">Dashboard</h2>

      <!-- CHARTS -->
      <div class="bg-white p-6 rounded-xl shadow-sm border">
        <h3 class="text-lg font-semibold mb-4">Visual Overview</h3>
        <!-- DASHBOARD GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

          <!-- POPULATION -->
          <div class="bg-gray-100 rounded-2xl p-5 shadow-md">
            <h3 class="text-sm font-semibold mb-3">POPULATION</h3>

            <div class="flex items-center gap-6">
              <!-- LEFT STATS -->
              <div class="space-y-2 w-1/3">
                <div class="bg-gray-400 text-white rounded-md px-3 py-1 text-sm flex justify-between">
                  <span>TOTAL</span><span><?= $population['total'] ?></span>
                </div>
                <div class="bg-gray-400 text-white rounded-md px-3 py-1 text-sm flex justify-between">
                  <span>MALE</span><span><?= $population['male'] ?></span>
                </div>
                <div class="bg-gray-400 text-white rounded-md px-3 py-1 text-sm flex justify-between">
                  <span>FEMALE</span><span><?= $population['female'] ?></span>
                </div>
              </div>

              <!-- CHART -->
              <div class="w-2/3 h-40">
                <canvas id="genderChart"></canvas>
              </div>
            </div>
          </div>

          <!-- PWD & SENIOR -->
          <div class="bg-gray-100 rounded-2xl p-5 shadow-md">
            <h3 class="text-sm font-semibold mb-3">PWD & SENIOR</h3>

            <div class="flex items-center gap-4">
              <div class="space-y-2 w-1/4">
                <div class="bg-gray-400 text-white rounded-md px-3 py-1 text-sm flex justify-between">
                  <span>PWD</span><span><?= $population['pwd'] ?></span>
                </div>
                <div class="bg-gray-400 text-white rounded-md px-3 py-1 text-sm flex justify-between">
                  <span>SENIOR</span><span><?= $population['senior'] ?></span>
                </div>
              </div>

              <div class="w-1/3 h-40">
                <canvas id="pwdChart"></canvas>
              </div>

              <div class="w-1/3 h-40">
                <canvas id="seniorChart"></canvas>
              </div>
            </div>
          </div>

          <!-- VOTERS -->
          <div class="bg-gray-100 rounded-2xl p-5 shadow-md">
            <h3 class="text-sm font-semibold mb-3">VOTERS</h3>

            <div class="flex items-center gap-6">
              <div class="space-y-2 w-1/3">
                <div class="bg-gray-400 text-white rounded-md px-3 py-1 text-sm flex justify-between">
                  <span>VOTERS</span><span><?= $population['voter_registered'] ?></span>
                </div>
                <div class="bg-gray-400 text-white rounded-md px-3 py-1 text-sm flex justify-between">
                  <span>NON VOTERS</span><span><?= $population['voter_unregistered'] ?></span>
                </div>
              </div>

              <div class="w-2/3 h-40">
                <canvas id="voterChart"></canvas>
              </div>
            </div>
          </div>

          <!-- BLOTTER -->
          <div class="bg-gray-100 rounded-2xl p-5 shadow-md">
            <h3 class="text-sm font-semibold mb-3">BLOTTER</h3>
            <div class="h-44">
              <canvas id="blotterChart"></canvas>
            </div>
          </div>

        </div>

      </div>

    </main>
  </div>

  <?php loadAllScripts(); ?>

  <script>
    $(document).ready(function() {
      // ✅ Show body after assets load
      $("body").show();
    });
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    const population = <?= json_encode($population) ?>;
    const blotter = <?= json_encode($blotter) ?>;

    function doughnut(id, labels, data, colors) {
      new Chart(document.getElementById(id), {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: colors
          }]
        },
        options: {
          cutout: '65%',
          maintainAspectRatio: false
        }
      });
    }

    function bar(id, labels, data, colors) {
      new Chart(document.getElementById(id), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: colors
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }

    /* RENDER */
    const themeSecondary = getComputedStyle(document.documentElement).getPropertyValue('--theme-secondary').trim() || '#446c3e';

    doughnut('genderChart',
      ['MALE', 'FEMALE'],
      [population.male, population.female],
      [themeSecondary, '#f28e2b']
    );

    doughnut('pwdChart',
      ['PWD', 'NON-PWD'],
      [population.pwd, population.total - population.pwd],
      ['#5b4bb7', '#f2d7c2']
    );

    doughnut('seniorChart',
      ['SENIOR', 'NON-SENIOR'],
      [population.senior, population.total - population.senior],
      ['#6ab04c', '#5d4037']
    );

    doughnut('voterChart',
      ['VOTERS', 'NON VOTERS'],
      [population.voter_registered, population.voter_unregistered],
      ['#9bb13c', '#d66bff']
    );

    bar('blotterChart',
      ['Pending', 'Under Investigation', 'Resolved', 'Dismissed'],
      [blotter.pending, blotter.under_investigation, blotter.resolved, blotter.dismissed],
      ['#bdc3c7', '#f0932b', '#bdc3c7', '#76b7b2']
    );
  </script>

</body>

</html>
<?php
require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php loadAllStyles(); ?>
  <?= loadAsset('node_js', 'chart.js/dist/chart.umd.min.js') ?>
  <style>
    .stat-card {
      background: white;
      border-radius: 14px;
      padding: 18px 20px;
      box-shadow: 0 1px 4px rgba(0,0,0,.08);
      border: 1px solid #e5e7eb;
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .stat-card .icon {
      width: 46px; height: 46px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; flex-shrink: 0;
    }
    .stat-card .val   { font-size: 26px; font-weight: 700; line-height: 1; }
    .stat-card .label { font-size: 11px; color: #6b7280; margin-top: 2px; text-transform: uppercase; letter-spacing: .5px; }
    .stat-card .sub   { font-size: 11px; color: #9ca3af; margin-top: 1px; }

    .section-card {
      background: white;
      border-radius: 14px;
      padding: 20px;
      box-shadow: 0 1px 4px rgba(0,0,0,.08);
      border: 1px solid #e5e7eb;
    }
    .section-title { font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 14px; text-transform: uppercase; letter-spacing: .5px; }

    .alert-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 8px 10px; border-radius: 8px; margin-bottom: 6px; font-size: 12px;
    }

    .badge {
      display: inline-flex; align-items: center; justify-content: center;
      padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }

    #loadingOverlay {
      position: absolute; inset: 0; background: rgba(255,255,255,.6);
      display: flex; align-items: center; justify-content: center;
      border-radius: 14px; z-index: 10;
    }
  </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
  <?php include '../layout/navbar.php'; ?>
  <div class="flex h-full">
    <?php include '../layout/sidebar.php'; ?>

    <main class="flex-1 p-6 overflow-y-auto h-screen pb-24">

      <!-- ── Header + Date Filter ───────────────────────────────────────── -->
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-semibold text-gray-800">Dashboard</h2>
          <p class="text-sm text-gray-500" id="dateRangeLabel">Loading…</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Quick presets -->
          <div class="flex gap-1">
            <button class="preset-btn px-3 py-1.5 text-xs rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium" data-preset="today">Today</button>
            <button class="preset-btn px-3 py-1.5 text-xs rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium" data-preset="this_month">This Month</button>
            <button class="preset-btn px-3 py-1.5 text-xs rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium" data-preset="last_month">Last Month</button>
            <button class="preset-btn px-3 py-1.5 text-xs rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium" data-preset="last_30">Last 30 Days</button>
            <button class="preset-btn px-3 py-1.5 text-xs rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium" data-preset="this_year">This Year</button>
          </div>

          <!-- Custom range -->
          <div class="flex items-center gap-2">
            <input type="date" id="dateFrom" class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-theme-primary focus:outline-none">
            <span class="text-gray-400 text-xs">to</span>
            <input type="date" id="dateTo" class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-theme-primary focus:outline-none">
            <button id="applyFilter" class="px-4 py-1.5 text-xs bg-theme-primary text-white rounded-lg font-semibold hover-theme-darker transition">Apply</button>
          </div>
        </div>
      </div>

      <!-- ── Stat Cards (date-sensitive) ──────────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6" id="statCards">

        <div class="stat-card col-span-1">
          <div class="icon" style="background:#dbeafe;">📜</div>
          <div>
            <div class="val" id="sc-cert-total">—</div>
            <div class="label">Certificates</div>
            <div class="sub" id="sc-cert-pending">— pending</div>
          </div>
        </div>

        <div class="stat-card col-span-1">
          <div class="icon" style="background:#fef3c7;">⚖️</div>
          <div>
            <div class="val" id="sc-blotter-total">—</div>
            <div class="label">Blotter Cases</div>
            <div class="sub" id="sc-blotter-pending">— pending</div>
          </div>
        </div>

        <div class="stat-card col-span-1">
          <div class="icon" style="background:#dcfce7;">👥</div>
          <div>
            <div class="val" id="sc-residents-total">—</div>
            <div class="label">Residents</div>
            <div class="sub" id="sc-residents-new">— new this period</div>
          </div>
        </div>

        <div class="stat-card col-span-1">
          <div class="icon" style="background:#f3e8ff;">🏛️</div>
          <div>
            <div class="val" id="sc-officers-total">—</div>
            <div class="label">Officers</div>
            <div class="sub" id="sc-officers-expiring">— expiring soon</div>
          </div>
        </div>

        <div class="stat-card col-span-1">
          <div class="icon" style="background:#fce7f3;">💊</div>
          <div>
            <div class="val" id="sc-dispense-total">—</div>
            <div class="label">Dispenses</div>
            <div class="sub" id="sc-consult-total">— consultations</div>
          </div>
        </div>

        <div class="stat-card col-span-1">
          <div class="icon" style="background:#e0f2fe;">🗄️</div>
          <div>
            <div class="val" id="sc-backup-days">—</div>
            <div class="label">Last Backup</div>
            <div class="sub" id="sc-backup-date">—</div>
          </div>
        </div>

      </div>

      <!-- ── Main Grid ──────────────────────────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        <!-- Certificates Line Chart (2/3 width) -->
        <div class="section-card lg:col-span-2 relative">
          <div class="section-title">📜 Certificate Requests — Daily Trend</div>
          <div style="height:200px;">
            <canvas id="certLineChart"></canvas>
          </div>
        </div>

        <!-- Certificate Breakdown Doughnut (1/3 width) -->
        <div class="section-card relative">
          <div class="section-title">Certificate Breakdown</div>
          <div style="height:160px;">
            <canvas id="certTypeChart"></canvas>
          </div>
          <div class="mt-3 space-y-1" id="certTypeList"></div>
        </div>

      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        <!-- Population (static, no date filter) -->
        <div class="section-card">
          <div class="section-title">👥 Population Overview</div>
          <div style="height:160px;">
            <canvas id="genderChart"></canvas>
          </div>
          <div class="grid grid-cols-2 gap-2 mt-3 text-xs" id="popStats"></div>
        </div>

        <!-- Blotter Bar Chart -->
        <div class="section-card relative">
          <div class="section-title">⚖️ Blotter — Overall Status</div>
          <div style="height:200px;">
            <canvas id="blotterChart"></canvas>
          </div>
        </div>

        <!-- Alerts Column -->
        <div class="space-y-4">

          <!-- Expiring Officers -->
          <div class="section-card">
            <div class="section-title">🏛️ Expiring Officer Terms <span class="text-gray-400 font-normal normal-case">(next 60 days)</span></div>
            <div id="expiringList" class="space-y-1 text-xs text-gray-500">Loading…</div>
          </div>

          <!-- Upcoming Events -->
          <div class="section-card">
            <div class="section-title">📅 Upcoming Events</div>
            <div id="eventsList" class="space-y-1 text-xs text-gray-500">Loading…</div>
          </div>

        </div>

      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Low Stock Inventory -->
        <div class="section-card">
          <div class="section-title">📦 Low Stock — Inventory <span class="text-gray-400 font-normal normal-case">(qty ≤ 5)</span></div>
          <div id="lowStockList" class="space-y-1 text-xs text-gray-500">Loading…</div>
        </div>

        <!-- Low Stock Medicines -->
        <div class="section-card">
          <div class="section-title">💊 Low Stock — Medicines <span class="text-gray-400 font-normal normal-case">(qty ≤ 10)</span></div>
          <div id="lowMedList" class="space-y-1 text-xs text-gray-500">Loading…</div>
        </div>

      </div>

    </main>
  </div>

  <?php loadAllScripts(); ?>
  <script>
  $(function () {
    $('body').show();

    // ── Chart instances (so we can destroy & redraw) ──────────────────────
    let certLineChart = null, certTypeChart = null,
        genderChart   = null, blotterChart  = null;

    // ── Theme color ───────────────────────────────────────────────────────
    const THEME = getComputedStyle(document.documentElement)
                    .getPropertyValue('--theme-secondary').trim() || '#446c3e';

    // ── Date helpers ──────────────────────────────────────────────────────
    function today()     { return new Date().toISOString().slice(0,10); }
    function firstOfMonth(d = new Date()) {
      return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-01';
    }
    function lastOfMonth(d = new Date()) {
      const last = new Date(d.getFullYear(), d.getMonth()+1, 0);
      return last.toISOString().slice(0,10);
    }
    function addDays(dateStr, n) {
      const d = new Date(dateStr);
      d.setDate(d.getDate() + n);
      return d.toISOString().slice(0,10);
    }
    function fmtDate(str) {
      if (!str) return '—';
      return new Date(str + 'T00:00:00').toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
    }
    function diffDays(from, to) {
      return Math.round((new Date(to) - new Date(from)) / 86400000);
    }

    // ── Preset buttons ────────────────────────────────────────────────────
    const presets = {
      today      : () => [today(), today()],
      this_month : () => [firstOfMonth(), today()],
      last_month : () => {
        const d = new Date(); d.setMonth(d.getMonth()-1);
        return [firstOfMonth(d), lastOfMonth(d)];
      },
      last_30    : () => [addDays(today(), -30), today()],
      this_year  : () => [new Date().getFullYear() + '-01-01', today()],
    };

    $('.preset-btn').on('click', function () {
      const [from, to] = presets[$(this).data('preset')]();
      $('#dateFrom').val(from);
      $('#dateTo').val(to);
      load(from, to);
    });

    $('#applyFilter').on('click', function () {
      load($('#dateFrom').val(), $('#dateTo').val());
    });

    // ── Destroy & recreate chart helper ───────────────────────────────────
    function mkChart(ref, id, config) {
      if (ref) ref.destroy();
      return new Chart(document.getElementById(id), config);
    }

    // ── Main load function ────────────────────────────────────────────────
    function load(from, to) {
      $.getJSON('dashboard_api.php', { date_from: from, date_to: to }, function (d) {
        renderStatCards(d);
        renderCertLineChart(d);
        renderCertTypeChart(d);
        renderPopulationChart(d);
        renderBlotterChart(d);
        renderAlerts(d);
        renderLists(d);
        $('#dateRangeLabel').text(fmtDate(from) + '  →  ' + fmtDate(to));
      }).fail(function () {
        $('#dateRangeLabel').text('Failed to load data.');
      });
    }

    // ── Stat Cards ────────────────────────────────────────────────────────
    function renderStatCards(d) {
      const c  = d.certificates   || {};
      const b  = d.blotter        || {};
      const p  = d.population     || {};
      const o  = d.officers       || {};
      const di = d.dispenses      || {};
      const co = d.consultations  || {};
      const bk = d.last_backup;

      $('#sc-cert-total').text(c.total    ?? 0);
      $('#sc-cert-pending').text((c.pending ?? 0) + ' pending');
      $('#sc-blotter-total').text(b.total  ?? 0);
      $('#sc-blotter-pending').text((b.pending ?? 0) + ' pending');
      $('#sc-residents-total').text(p.total ?? 0);
      $('#sc-residents-new').text((d.new_residents ?? 0) + ' new this period');
      $('#sc-officers-total').text(o.active ?? 0);
      $('#sc-officers-expiring').text((d.expiring_officers?.length ?? 0) + ' expiring soon');
      $('#sc-dispense-total').text(di.total ?? 0);
      $('#sc-consult-total').text((co.total ?? 0) + ' consultations');

      if (bk) {
        const days = diffDays(bk.created_at.slice(0,10), today());
        const daysText = days === 0 ? 'Today' : days + 'd ago';
        $('#sc-backup-days').text(daysText);
        $('#sc-backup-date').text(fmtDate(bk.created_at.slice(0,10)));
        $('#sc-backup-days').css('color', days > 7 ? '#ef4444' : '#16a34a');
      } else {
        $('#sc-backup-days').text('None');
        $('#sc-backup-date').text('No backup found');
        $('#sc-backup-days').css('color', '#ef4444');
      }
    }

    // ── Certificate Line Chart ────────────────────────────────────────────
    function renderCertLineChart(d) {
      const days   = (d.certs_by_day || []).map(r => r.day);
      const counts = (d.certs_by_day || []).map(r => parseInt(r.cnt));

      certLineChart = mkChart(certLineChart, 'certLineChart', {
        type: 'line',
        data: {
          labels: days.length ? days : ['No data'],
          datasets: [{
            label: 'Requests',
            data: counts.length ? counts : [0],
            borderColor: THEME,
            backgroundColor: THEME + '22',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: THEME,
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { ticks: { font: { size: 10 }, maxTicksLimit: 10 } },
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
          }
        }
      });
    }

    // ── Certificate Type Doughnut ─────────────────────────────────────────
    function renderCertTypeChart(d) {
      const c = d.certificates || {};
      const vals   = [c.clearance??0, c.indigency??0, c.residency??0];
      const labels = ['Clearance', 'Indigency', 'Residency'];
      const colors = ['#3b82f6', '#f59e0b', '#10b981'];

      certTypeChart = mkChart(certTypeChart, 'certTypeChart', {
        type: 'doughnut',
        data: { labels, datasets: [{ data: vals, backgroundColor: colors }] },
        options: {
          cutout: '65%',
          maintainAspectRatio: false,
          plugins: { legend: { display: false } }
        }
      });

      // Mini legend below
      const html = labels.map((l, i) =>
        `<div style="display:flex;justify-content:space-between;font-size:11px;padding:2px 0;">
          <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${colors[i]};margin-right:5px;"></span>${l}</span>
          <strong>${vals[i]}</strong>
        </div>`
      ).join('');
      $('#certTypeList').html(html);
    }

    // ── Population Doughnut ───────────────────────────────────────────────
    function renderPopulationChart(d) {
      const p = d.population || {};

      genderChart = mkChart(genderChart, 'genderChart', {
        type: 'doughnut',
        data: {
          labels: ['Male', 'Female'],
          datasets: [{ data: [p.male??0, p.female??0], backgroundColor: [THEME, '#f59e0b'] }]
        },
        options: { cutout: '60%', maintainAspectRatio: false }
      });

      $('#popStats').html(`
        <div class="bg-gray-50 rounded-lg px-3 py-2 flex justify-between"><span>Total</span><strong>${p.total??0}</strong></div>
        <div class="bg-gray-50 rounded-lg px-3 py-2 flex justify-between"><span>Senior</span><strong>${p.senior??0}</strong></div>
        <div class="bg-gray-50 rounded-lg px-3 py-2 flex justify-between"><span>PWD</span><strong>${p.pwd??0}</strong></div>
        <div class="bg-gray-50 rounded-lg px-3 py-2 flex justify-between"><span>Voters</span><strong>${p.voter_registered??0}</strong></div>
      `);
    }

    // ── Blotter Bar Chart ─────────────────────────────────────────────────
    function renderBlotterChart(d) {
      const b = d.blotter_all || {};
      blotterChart = mkChart(blotterChart, 'blotterChart', {
        type: 'bar',
        data: {
          labels: ['Pending', 'Under Inv.', 'Resolved', 'Dismissed'],
          datasets: [{
            data: [b.pending??0, b.under_investigation??0, b.resolved??0, b.dismissed??0],
            backgroundColor: ['#fbbf24','#60a5fa','#34d399','#9ca3af'],
            borderRadius: 6,
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
      });
    }

    // ── Alerts ────────────────────────────────────────────────────────────
    function renderAlerts(d) {
      // Expiring officers
      const exp = d.expiring_officers || [];
      if (exp.length === 0) {
        $('#expiringList').html('<p class="text-gray-400">No expiring terms in next 60 days.</p>');
      } else {
        $('#expiringList').html(exp.map(o => {
          const days = diffDays(today(), o.term_end);
          const color = days <= 14 ? '#ef4444' : '#f59e0b';
          return `<div class="alert-row" style="background:#fafafa;">
            <span><strong>${o.name ?? '—'}</strong><br>${o.position}</span>
            <span style="color:${color};font-weight:700;">${days}d</span>
          </div>`;
        }).join(''));
      }

      // Upcoming events
      const ev = d.upcoming_events || [];
      if (ev.length === 0) {
        $('#eventsList').html('<p class="text-gray-400">No upcoming events.</p>');
      } else {
        $('#eventsList').html(ev.map(e =>
          `<div class="alert-row" style="background:#fafafa;">
            <span><strong>${e.title}</strong><br>${e.location ?? ''}</span>
            <span style="color:#6b7280;">${fmtDate(e.event_date)}</span>
          </div>`
        ).join(''));
      }
    }

    // ── Low Stock Lists ───────────────────────────────────────────────────
    function renderLists(d) {
      const ls = d.low_stock_items || [];
      if (ls.length === 0) {
        $('#lowStockList').html('<p class="text-gray-400">No low stock items.</p>');
      } else {
        $('#lowStockList').html(ls.map(i =>
          `<div class="alert-row" style="background:#fef2f2;">
            <span>${i.name}</span>
            <span style="color:#ef4444;font-weight:700;">${i.quantity} ${i.unit??''}</span>
          </div>`
        ).join(''));
      }

      const lm = d.low_medicines || [];
      if (lm.length === 0) {
        $('#lowMedList').html('<p class="text-gray-400">No low stock medicines.</p>');
      } else {
        $('#lowMedList').html(lm.map(m =>
          `<div class="alert-row" style="background:#fef2f2;">
            <span>${m.name}</span>
            <span style="color:#ef4444;font-weight:700;">${m.quantity} ${m.unit??''}</span>
          </div>`
        ).join(''));
      }
    }

    // ── Init: default to this month ───────────────────────────────────────
    const initFrom = firstOfMonth();
    const initTo   = today();
    $('#dateFrom').val(initFrom);
    $('#dateTo').val(initTo);

    // Highlight "This Month" preset as active on load
    $('[data-preset="this_month"]')
      .addClass('bg-theme-primary text-white border-theme-primary')
      .removeClass('bg-white');

    // Remove active class when another preset is clicked
    $('.preset-btn').on('click', function () {
      $('.preset-btn').removeClass('bg-theme-primary text-white border-theme-primary').addClass('bg-white');
      $(this).addClass('bg-theme-primary text-white border-theme-primary').removeClass('bg-white');
    });

    load(initFrom, initTo);
  });
  </script>
</body>
</html>
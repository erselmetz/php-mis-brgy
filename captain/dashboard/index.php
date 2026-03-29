<?php
require_once __DIR__ . '/../../includes/app.php';
requireCaptain();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllStyles(); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const population = <?= json_encode($population) ?>;
        const blotter = <?= json_encode($blotter) ?>;
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
    /* ═══════════════════════════════════════════════════════
       DESIGN TOKENS — Government Document Aesthetic
       ═══════════════════════════════════════════════════════ */
    :root {
        --paper:       #fdfcf9;
        --paper-lt:    #f9f7f3;
        --paper-dk:    #f0ede6;
        --ink:         #1a1a1a;
        --ink-muted:   #5a5a5a;
        --ink-faint:   #a0a0a0;
        --rule:        #d8d4cc;
        --rule-dk:     #b8b4ac;
        --bg:          #edeae4;
        --accent:      var(--theme-primary, #2d5a27);
        --accent-lt:   color-mix(in srgb, var(--accent) 8%, white);
        --accent-md:   color-mix(in srgb, var(--accent) 18%, white);
        --accent-dk:   color-mix(in srgb, var(--accent) 65%, black);

        /* Status palette */
        --s-warn-bg:  #fef9ec; --s-warn-fg:  #7a5700;
        --s-ok-bg:    #edfaf3; --s-ok-fg:    #1a5c35;
        --s-info-bg:  #edf3fa; --s-info-fg:  #1a3a5c;
        --s-err-bg:   #fdeeed; --s-err-fg:   #7a1f1a;
        --s-neu-bg:   #f3f1ec; --s-neu-fg:   #5a5a5a;

        /* Typography */
        --f-serif:  'Source Serif 4', Georgia, serif;
        --f-sans:   'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:   'Source Code Pro', 'Courier New', monospace;

        --shadow-card: 0 1px 2px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
    }

    /* ═══════════════════════════════════════════════════════
       BASE
       ═══════════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select { font-family: var(--f-sans); }

    .dash-page {
        background: var(--bg);
        min-height: 100%;
        padding: 0 0 56px;
    }

    /* ═══════════════════════════════════════════════════════
       DOCUMENT HEADER (replaces generic "Dashboard" h2)
       ═══════════════════════════════════════════════════════ */
    .doc-header {
        background: var(--paper);
        border-bottom: 1px solid var(--rule);
        padding: 20px 28px 0;
    }
    .doc-header-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
        padding-bottom: 16px;
    }
    .doc-title-block {}
    .doc-eyebrow {
        font-size: 8.5px;
        font-weight: 700;
        letter-spacing: 1.8px;
        text-transform: uppercase;
        color: var(--ink-faint);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .doc-eyebrow::before {
        content: '';
        display: inline-block;
        width: 18px; height: 2px;
        background: var(--accent);
    }
    .doc-title {
        font-family: var(--f-serif);
        font-size: 22px;
        font-weight: 700;
        color: var(--ink);
        letter-spacing: -.2px;
        margin-bottom: 3px;
    }
    .doc-subtitle {
        font-size: 12px;
        color: var(--ink-faint);
        font-style: italic;
    }

    /* ── DATE FILTER BAR ── */
    .date-filter-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 14px 28px;
        background: var(--paper-lt);
        border-top: 1px solid var(--rule);
        border-bottom: 3px solid var(--accent);
    }
    .df-label {
        font-size: 8.5px;
        font-weight: 700;
        letter-spacing: 1.3px;
        text-transform: uppercase;
        color: var(--ink-faint);
        white-space: nowrap;
        margin-right: 4px;
    }
    .df-presets {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }
    .preset-btn {
        padding: 5px 12px;
        border: 1.5px solid var(--rule-dk);
        border-radius: 2px;
        background: #fff;
        font-family: var(--f-sans);
        font-size: 11px;
        font-weight: 600;
        color: var(--ink-muted);
        cursor: pointer;
        transition: all .13s;
        letter-spacing: .2px;
    }
    .preset-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
    .preset-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }

    .df-sep {
        width: 1px; height: 24px;
        background: var(--rule);
        flex-shrink: 0;
    }
    .df-custom {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .df-input {
        padding: 5px 10px;
        border: 1.5px solid var(--rule-dk);
        border-radius: 2px;
        font-family: var(--f-sans);
        font-size: 11px;
        color: var(--ink);
        background: #fff;
        outline: none;
        transition: border-color .15s;
    }
    .df-input:focus { border-color: var(--accent); }
    .df-between { font-size: 10px; color: var(--ink-faint); }
    .df-apply {
        padding: 5px 14px;
        background: var(--accent);
        border: none;
        border-radius: 2px;
        font-family: var(--f-sans);
        font-size: 11px;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        letter-spacing: .4px;
        text-transform: uppercase;
        transition: filter .13s;
    }
    .df-apply:hover { filter: brightness(1.1); }

    .df-range-display {
        margin-left: auto;
        font-family: var(--f-mono);
        font-size: 10px;
        color: var(--ink-faint);
        letter-spacing: .3px;
        white-space: nowrap;
    }

    /* ═══════════════════════════════════════════════════════
       LAYOUT GRID
       ═══════════════════════════════════════════════════════ */
    .dash-body { padding: 22px 28px 0; }

    /* ── STAT LEDGER (top row) ── */
    .stat-ledger {
        background: var(--paper);
        border: 1px solid var(--rule);
        border-top: 3px solid var(--accent);
        border-radius: 2px;
        box-shadow: var(--shadow-card);
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        margin-bottom: 20px;
        overflow: hidden;
    }
    @media (max-width: 1100px) { .stat-ledger { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 700px)  { .stat-ledger { grid-template-columns: repeat(2, 1fr); } }

    .stat-cell {
        padding: 18px 20px;
        border-right: 1px solid var(--rule);
        position: relative;
        cursor: default;
        transition: background .12s;
    }
    .stat-cell:last-child { border-right: none; }
    .stat-cell:hover { background: var(--paper-lt); }

    /* accent tick on hover */
    .stat-cell::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 0;
        background: var(--accent);
        transition: width .15s;
    }
    .stat-cell:hover::before { width: 3px; }

    .stat-eyebrow {
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 1.4px;
        text-transform: uppercase;
        color: var(--ink-faint);
        margin-bottom: 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .stat-value {
        font-family: var(--f-mono);
        font-size: 28px;
        font-weight: 600;
        color: var(--ink);
        line-height: 1;
        margin-bottom: 6px;
        letter-spacing: -1px;
    }
    .stat-value.loading { color: var(--rule-dk); }
    .stat-sub {
        font-size: 10.5px;
        color: var(--ink-faint);
        line-height: 1.4;
    }
    .stat-sub strong { color: var(--ink-muted); font-weight: 600; }
    .stat-badge {
        display: inline-block;
        padding: 1px 7px;
        border-radius: 2px;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    /* ═══════════════════════════════════════════════════════
       SECTION CARDS
       ═══════════════════════════════════════════════════════ */
    .dash-grid-2    { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
    .dash-grid-3    { display: grid; grid-template-columns: 2fr 1.2fr 1.2fr; gap: 18px; margin-bottom: 18px; }
    .dash-grid-2b   { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
    @media (max-width: 1000px) {
        .dash-grid-2, .dash-grid-3, .dash-grid-2b { grid-template-columns: 1fr; }
    }

    .doc-card {
        background: var(--paper);
        border: 1px solid var(--rule);
        border-radius: 2px;
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    /* Card header */
    .dc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 18px;
        background: var(--paper-lt);
        border-bottom: 1px solid var(--rule);
    }
    .dc-title {
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 1.3px;
        text-transform: uppercase;
        color: var(--ink-muted);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dc-title::before {
        content: '';
        display: inline-block;
        width: 3px; height: 12px;
        background: var(--accent);
        border-radius: 1px;
    }
    .dc-badge {
        font-family: var(--f-mono);
        font-size: 9px;
        color: var(--ink-faint);
        letter-spacing: .5px;
    }

    /* Card body */
    .dc-body { padding: 18px; }
    .dc-body-flush { padding: 0; }

    /* ═══════════════════════════════════════════════════════
       CHARTS
       ═══════════════════════════════════════════════════════ */
    .chart-wrap {
        position: relative;
        width: 100%;
    }
    .chart-wrap canvas { display: block; }

    /* Chart + legend two-col */
    .chart-legend-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 16px;
        align-items: center;
        padding: 18px;
    }

    /* Donut legend */
    .donut-legend { display: flex; flex-direction: column; gap: 6px; min-width: 120px; }
    .dl-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 11px;
        color: var(--ink-muted);
    }
    .dl-dot {
        width: 8px; height: 8px;
        border-radius: 2px;
        flex-shrink: 0;
    }
    .dl-label { flex: 1; }
    .dl-val {
        font-family: var(--f-mono);
        font-size: 11px;
        font-weight: 600;
        color: var(--ink);
    }

    /* ═══════════════════════════════════════════════════════
       POPULATION STAT GRID
       ═══════════════════════════════════════════════════════ */
    .pop-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        border-top: 1px solid var(--rule);
    }
    .pop-cell {
        padding: 10px 18px;
        border-right: 1px solid var(--rule);
        border-bottom: 1px solid var(--rule);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .pop-cell:nth-child(even) { border-right: none; }
    .pop-lbl { font-size: 11px; color: var(--ink-muted); }
    .pop-val { font-family: var(--f-mono); font-size: 13px; font-weight: 600; color: var(--ink); }

    /* ═══════════════════════════════════════════════════════
       ALERT / LIST ROWS
       ═══════════════════════════════════════════════════════ */
    .alert-list { display: flex; flex-direction: column; }
    .alert-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 18px;
        border-bottom: 1px solid #f0ede8;
        font-size: 12px;
        color: var(--ink);
        transition: background .1s;
    }
    .alert-row:last-child { border-bottom: none; }
    .alert-row:hover { background: var(--paper-lt); }
    .alert-name { font-weight: 500; color: var(--ink); }
    .alert-sub  { font-size: 10.5px; color: var(--ink-faint); margin-top: 1px; }
    .alert-val  {
        font-family: var(--f-mono);
        font-size: 11px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .alert-empty {
        padding: 32px 18px;
        text-align: center;
        font-size: 12px;
        color: var(--ink-faint);
        font-style: italic;
    }

    /* Urgency colors for values */
    .val-ok   { color: var(--s-ok-fg); }
    .val-warn { color: var(--s-warn-fg); }
    .val-err  { color: var(--s-err-fg); }
    .val-info { color: var(--s-info-fg); }
    .val-neu  { color: var(--ink-faint); }

    /* Inline status badge */
    .row-badge {
        display: inline-block;
        padding: 1px 7px;
        border-radius: 2px;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }
    .rb-warn { background: var(--s-warn-bg); color: var(--s-warn-fg); border: 1px solid color-mix(in srgb,var(--s-warn-fg) 20%,transparent); }
    .rb-ok   { background: var(--s-ok-bg);   color: var(--s-ok-fg);   border: 1px solid color-mix(in srgb,var(--s-ok-fg)   20%,transparent); }
    .rb-err  { background: var(--s-err-bg);   color: var(--s-err-fg);  border: 1px solid color-mix(in srgb,var(--s-err-fg)  20%,transparent); }
    .rb-info { background: var(--s-info-bg); color: var(--s-info-fg); border: 1px solid color-mix(in srgb,var(--s-info-fg) 20%,transparent); }
    .rb-neu  { background: var(--s-neu-bg);  color: var(--s-neu-fg);  border: 1px solid color-mix(in srgb,var(--s-neu-fg)  20%,transparent); }

    /* ═══════════════════════════════════════════════════════
       LOW STOCK TABLE
       ═══════════════════════════════════════════════════════ */
    .stock-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .stock-table thead th {
        padding: 8px 16px;
        background: #f5f3ee;
        text-align: left;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: var(--ink-muted);
        border-bottom: 1px solid var(--rule-dk);
    }
    .stock-table tbody tr { border-bottom: 1px solid #f0ede8; transition: background .1s; }
    .stock-table tbody tr:last-child { border-bottom: none; }
    .stock-table tbody tr:hover { background: var(--paper-lt); }
    .stock-table td { padding: 9px 16px; color: var(--ink); vertical-align: middle; }
    .stock-qty {
        font-family: var(--f-mono);
        font-size: 13px;
        font-weight: 600;
        color: var(--s-err-fg);
    }
    .stock-empty { padding: 28px 16px; text-align: center; font-size: 12px; color: var(--ink-faint); font-style: italic; }

    /* ═══════════════════════════════════════════════════════
       BLOTTER MINI BARS (visual instead of plain chart)
       ═══════════════════════════════════════════════════════ */
    .blotter-bars { padding: 18px; display: flex; flex-direction: column; gap: 14px; }
    .bb-row { display: flex; flex-direction: column; gap: 5px; }
    .bb-meta { display: flex; justify-content: space-between; align-items: center; }
    .bb-label { font-size: 11px; color: var(--ink-muted); font-weight: 500; }
    .bb-count { font-family: var(--f-mono); font-size: 12px; font-weight: 600; color: var(--ink); }
    .bb-track { height: 6px; background: #ede9e3; border-radius: 1px; overflow: hidden; }
    .bb-fill  { height: 100%; border-radius: 1px; transition: width .6s ease; width: 0%; }
    .bb-pending  { background: var(--s-warn-fg); }
    .bb-invest   { background: var(--s-info-fg); }
    .bb-resolved { background: var(--s-ok-fg); }
    .bb-dismissed{ background: var(--ink-faint); }

    /* ═══════════════════════════════════════════════════════
       SHIMMER / LOADING
       ═══════════════════════════════════════════════════════ */
    @keyframes shimmer {
        0%   { background-position: -600px 0; }
        100% { background-position:  600px 0; }
    }
    .loading-shimmer {
        background: linear-gradient(90deg, #e8e4de 25%, #dedad4 50%, #e8e4de 75%);
        background-size: 600px 100%;
        animation: shimmer 1.4s infinite;
        border-radius: 3px;
        display: inline-block;
    }

    /* ═══════════════════════════════════════════════════════
       SLIDE-IN ANIMATION
       ═══════════════════════════════════════════════════════ */
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .anim { animation: fadeSlideUp .3s ease both; }
    .anim-d1 { animation-delay: .05s; }
    .anim-d2 { animation-delay: .10s; }
    .anim-d3 { animation-delay: .15s; }
    .anim-d4 { animation-delay: .20s; }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto dash-page">

            <!-- ══ Document Header ══ -->
            <div class="doc-header">
                <div class="doc-header-top">
                    <div class="doc-title-block">
                        <div class="doc-eyebrow">Barangay Management Information System</div>
                        <div class="doc-title">Executive Dashboard</div>
                        <div class="doc-subtitle">Summary statistics and operational overview — Barangay Bombongan, Morong, Rizal</div>
                    </div>
                </div>

                <!-- Date filter bar -->
                <div class="date-filter-bar">
                    <span class="df-label">Period</span>
                    <div class="df-presets">
                        <button class="preset-btn" data-preset="today">Today</button>
                        <button class="preset-btn active" data-preset="this_month">This Month</button>
                        <button class="preset-btn" data-preset="last_month">Last Month</button>
                        <button class="preset-btn" data-preset="last_30">Last 30 Days</button>
                        <button class="preset-btn" data-preset="this_year">This Year</button>
                    </div>
                    <div class="df-sep"></div>
                    <div class="df-custom">
                        <input type="date" class="df-input" id="dateFrom">
                        <span class="df-between">to</span>
                        <input type="date" class="df-input" id="dateTo">
                        <button class="df-apply" id="applyFilter">Apply</button>
                    </div>
                    <div class="df-range-display" id="rangeDisplay">—</div>
                </div>
            </div>

            <!-- ══ Dashboard Body ══ -->
            <div class="dash-body">

                <!-- ── ROW 1: Stat Ledger ── -->
                <div class="stat-ledger anim" style="margin-top:20px;">
                    <div class="stat-cell">
                        <div class="stat-eyebrow">Certificates</div>
                        <div class="stat-value" id="sc-cert">—</div>
                        <div class="stat-sub" id="sc-cert-sub">Loading…</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-eyebrow">Blotter Cases</div>
                        <div class="stat-value" id="sc-blotter">—</div>
                        <div class="stat-sub" id="sc-blotter-sub">Loading…</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-eyebrow">Residents</div>
                        <div class="stat-value" id="sc-residents">—</div>
                        <div class="stat-sub" id="sc-residents-sub">Loading…</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-eyebrow">Active Officers</div>
                        <div class="stat-value" id="sc-officers">—</div>
                        <div class="stat-sub" id="sc-officers-sub">Loading…</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-eyebrow">Dispenses</div>
                        <div class="stat-value" id="sc-dispenses">—</div>
                        <div class="stat-sub" id="sc-dispenses-sub">Loading…</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-eyebrow">Last Backup</div>
                        <div class="stat-value" id="sc-backup">—</div>
                        <div class="stat-sub" id="sc-backup-sub">Loading…</div>
                    </div>
                </div>

                <!-- ── ROW 2: Cert trend + Cert breakdown + Blotter bars ── -->
                <div class="dash-grid-3 anim anim-d1">

                    <!-- Certificate Daily Trend -->
                    <div class="doc-card">
                        <div class="dc-header">
                            <span class="dc-title">Certificate Requests — Daily Trend</span>
                            <span class="dc-badge" id="certChartPeriod">—</span>
                        </div>
                        <div class="dc-body">
                            <div class="chart-wrap" style="height:180px;">
                                <canvas id="certLineChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Cert Type Breakdown -->
                    <div class="doc-card">
                        <div class="dc-header">
                            <span class="dc-title">By Certificate Type</span>
                        </div>
                        <div class="chart-legend-row">
                            <div class="chart-wrap" style="height:130px;">
                                <canvas id="certTypeChart"></canvas>
                            </div>
                            <div class="donut-legend" id="certTypeLegend">
                                <div class="dl-item"><span class="dl-dot" style="background:#1a3a5c;"></span><span class="dl-label">Clearance</span><span class="dl-val" id="dl-bc">—</span></div>
                                <div class="dl-item"><span class="dl-dot" style="background:#7a5700;"></span><span class="dl-label">Indigency</span><span class="dl-val" id="dl-ic">—</span></div>
                                <div class="dl-item"><span class="dl-dot" style="background:#1a5c35;"></span><span class="dl-label">Residency</span><span class="dl-val" id="dl-rc">—</span></div>
                            </div>
                        </div>
                        <!-- Status mini-ledger -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;border-top:1px solid var(--rule);">
                            <div style="padding:9px 14px;border-right:1px solid var(--rule);font-size:11px;display:flex;justify-content:space-between;">
                                <span style="color:var(--ink-faint);">Pending</span>
                                <span class="dl-val" id="cert-stat-pending" style="color:var(--s-warn-fg);">—</span>
                            </div>
                            <div style="padding:9px 14px;font-size:11px;display:flex;justify-content:space-between;">
                                <span style="color:var(--ink-faint);">Approved</span>
                                <span class="dl-val" id="cert-stat-approved" style="color:var(--s-ok-fg);">—</span>
                            </div>
                        </div>
                    </div>

                    <!-- Blotter Status Bars -->
                    <div class="doc-card">
                        <div class="dc-header">
                            <span class="dc-title">Blotter — All-time Status</span>
                            <span class="dc-badge" id="blotterTotal">—</span>
                        </div>
                        <div class="blotter-bars" id="blotterBars">
                            <div class="bb-row">
                                <div class="bb-meta"><span class="bb-label">Pending</span><span class="bb-count" id="bb-pending">—</span></div>
                                <div class="bb-track"><div class="bb-fill bb-pending" id="bb-pending-fill"></div></div>
                            </div>
                            <div class="bb-row">
                                <div class="bb-meta"><span class="bb-label">Under Investigation</span><span class="bb-count" id="bb-invest">—</span></div>
                                <div class="bb-track"><div class="bb-fill bb-invest" id="bb-invest-fill"></div></div>
                            </div>
                            <div class="bb-row">
                                <div class="bb-meta"><span class="bb-label">Resolved</span><span class="bb-count" id="bb-resolved">—</span></div>
                                <div class="bb-track"><div class="bb-fill bb-resolved" id="bb-resolved-fill"></div></div>
                            </div>
                            <div class="bb-row">
                                <div class="bb-meta"><span class="bb-label">Dismissed</span><span class="bb-count" id="bb-dismissed">—</span></div>
                                <div class="bb-track"><div class="bb-fill bb-dismissed" id="bb-dismissed-fill"></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── ROW 3: Population + Expiring Officers + Upcoming Events ── -->
                <div class="dash-grid-3 anim anim-d2">

                    <!-- Population -->
                    <div class="doc-card">
                        <div class="dc-header">
                            <span class="dc-title">Population Overview</span>
                            <span class="dc-badge" id="popTotal">—</span>
                        </div>
                        <div style="padding:16px 18px 10px;">
                            <div class="chart-wrap" style="height:130px;">
                                <canvas id="genderChart"></canvas>
                            </div>
                        </div>
                        <div class="pop-grid" id="popGrid">
                            <div class="pop-cell"><span class="pop-lbl">Male</span><span class="pop-val" id="pop-male">—</span></div>
                            <div class="pop-cell"><span class="pop-lbl">Female</span><span class="pop-val" id="pop-female">—</span></div>
                            <div class="pop-cell"><span class="pop-lbl">Senior (60+)</span><span class="pop-val" id="pop-senior">—</span></div>
                            <div class="pop-cell"><span class="pop-lbl">PWD</span><span class="pop-val" id="pop-pwd">—</span></div>
                            <div class="pop-cell"><span class="pop-lbl">Voters</span><span class="pop-val" id="pop-voters">—</span></div>
                            <div class="pop-cell" style="border-right:none;"><span class="pop-lbl">Unregistered</span><span class="pop-val" id="pop-unreg">—</span></div>
                        </div>
                    </div>

                    <!-- Expiring Officer Terms -->
                    <div class="doc-card">
                        <div class="dc-header">
                            <span class="dc-title">Expiring Officer Terms</span>
                            <span class="dc-badge">Next 60 days</span>
                        </div>
                        <div class="alert-list" id="expiringList">
                            <div class="alert-empty">Loading…</div>
                        </div>
                    </div>

                    <!-- Upcoming Events -->
                    <div class="doc-card">
                        <div class="dc-header">
                            <span class="dc-title">Upcoming Events</span>
                            <span class="dc-badge">Scheduled</span>
                        </div>
                        <div class="alert-list" id="eventsList">
                            <div class="alert-empty">Loading…</div>
                        </div>
                    </div>
                </div>

                <!-- ── ROW 4: Low Stock ── -->
                <div class="dash-grid-2b anim anim-d3">

                    <!-- Low Stock Inventory -->
                    <div class="doc-card">
                        <div class="dc-header">
                            <span class="dc-title">Low Stock — Inventory</span>
                            <span class="dc-badge">Qty ≤ 5</span>
                        </div>
                        <div class="dc-body-flush">
                            <table class="stock-table">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th style="text-align:right;">Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="lowStockBody">
                                    <tr><td colspan="3" class="stock-empty">Loading…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Low Stock Medicines -->
                    <div class="doc-card">
                        <div class="dc-header">
                            <span class="dc-title">Low Stock — Medicines</span>
                            <span class="dc-badge">Qty ≤ 10</span>
                        </div>
                        <div class="dc-body-flush">
                            <table class="stock-table">
                                <thead>
                                    <tr>
                                        <th>Medicine Name</th>
                                        <th>Unit</th>
                                        <th style="text-align:right;">Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="lowMedBody">
                                    <tr><td colspan="3" class="stock-empty">Loading…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- /.dash-body -->
        </main>
    </div>

    <?php loadAllScripts(); ?>

    <script>
    $(function () {
        $('body').show();

        /* ══════════════════════════════════════════
           CHART INSTANCES
        ══════════════════════════════════════════ */
        let certLineChart = null, certTypeChart = null, genderChart = null;

        const ACCENT  = getComputedStyle(document.documentElement)
                            .getPropertyValue('--theme-primary').trim() || '#2d5a27';
        const CHART_DEFAULTS = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: {
                backgroundColor: '#1a1a1a',
                titleFont: { family: "'Source Code Pro', monospace", size: 11 },
                bodyFont:  { family: "'Source Sans 3', sans-serif",  size: 11 },
                padding: 10, cornerRadius: 2
            }}
        };

        /* ══════════════════════════════════════════
           DATE HELPERS
        ══════════════════════════════════════════ */
        const today = () => new Date().toISOString().slice(0,10);
        const firstOfMonth = (d = new Date()) =>
            d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-01';
        const lastOfMonth  = (d = new Date()) => {
            const l = new Date(d.getFullYear(), d.getMonth()+1, 0);
            return l.toISOString().slice(0,10);
        };
        const addDays = (s, n) => { const d=new Date(s); d.setDate(d.getDate()+n); return d.toISOString().slice(0,10); };
        const fmtDate = s => s ? new Date(s+'T00:00:00').toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : '—';
        const diffDays = (a, b) => Math.round((new Date(b)-new Date(a))/86400000);

        const PRESETS = {
            today:      () => [today(), today()],
            this_month: () => [firstOfMonth(), today()],
            last_month: () => { const d=new Date(); d.setMonth(d.getMonth()-1); return [firstOfMonth(d),lastOfMonth(d)]; },
            last_30:    () => [addDays(today(),-30), today()],
            this_year:  () => [new Date().getFullYear()+'-01-01', today()],
        };

        /* Preset buttons */
        $('.preset-btn').on('click', function () {
            const [f, t] = PRESETS[$(this).data('preset')]();
            $('.preset-btn').removeClass('active');
            $(this).addClass('active');
            $('#dateFrom').val(f);
            $('#dateTo').val(t);
            load(f, t);
        });
        $('#applyFilter').on('click', () => {
            $('.preset-btn').removeClass('active');
            load($('#dateFrom').val(), $('#dateTo').val());
        });

        /* ══════════════════════════════════════════
           MAIN LOAD
        ══════════════════════════════════════════ */
        function load(from, to) {
            $('#rangeDisplay').text(fmtDate(from) + '  ›  ' + fmtDate(to));

            $.getJSON('dashboard_api.php', { date_from: from, date_to: to }, d => {
                renderStatLedger(d, from, to);
                renderCertLineChart(d, from, to);
                renderCertTypeChart(d);
                renderBlotterBars(d);
                renderPopulation(d);
                renderExpiringOfficers(d);
                renderEvents(d);
                renderLowStock(d);
            }).fail(() => {
                $('#rangeDisplay').text('⚠ Failed to load data');
            });
        }

        /* ══════════════════════════════════════════
           STAT LEDGER
        ══════════════════════════════════════════ */
        function renderStatLedger(d, from, to) {
            const c  = d.certificates || {};
            const b  = d.blotter || {};
            const p  = d.population || {};
            const o  = d.officers || {};
            const di = d.dispenses || {};
            const co = d.consultations || {};
            const bk = d.last_backup;
            const nr = d.new_residents || 0;

            // Certificates
            $('#sc-cert').text(fmtNum(c.total ?? 0));
            $('#sc-cert-sub').html(`<strong>${c.pending ?? 0}</strong> pending · <strong>${c.approved ?? 0}</strong> approved`);

            // Blotter (period)
            $('#sc-blotter').text(fmtNum(b.total ?? 0));
            $('#sc-blotter-sub').html(`<strong>${b.pending ?? 0}</strong> pending · <strong>${b.resolved ?? 0}</strong> resolved`);

            // Residents (all-time)
            $('#sc-residents').text(fmtNum(p.total ?? 0));
            $('#sc-residents-sub').html(`<strong>+${nr}</strong> new this period`);

            // Officers
            $('#sc-officers').text(fmtNum(o.active ?? 0));
            const exp = d.expiring_officers?.length ?? 0;
            $('#sc-officers-sub').html(exp > 0
                ? `<span style="color:var(--s-warn-fg);">⚠ ${exp} term${exp!==1?'s':''} expiring</span>`
                : 'All terms current');

            // Dispenses
            $('#sc-dispenses').text(fmtNum(di.total ?? 0));
            $('#sc-dispenses-sub').html(`<strong>${co.total ?? 0}</strong> consultations`);

            // Backup
            if (bk) {
                const days = diffDays(bk.created_at.slice(0,10), today());
                const label = days === 0 ? 'Today' : days === 1 ? 'Yesterday' : `${days}d ago`;
                const cls   = days > 7 ? 'val-err' : days > 3 ? 'val-warn' : 'val-ok';
                $('#sc-backup').html(`<span class="${cls}">${label}</span>`);
                $('#sc-backup-sub').text(fmtDate(bk.created_at.slice(0,10)));
            } else {
                $('#sc-backup').html('<span class="val-err">None</span>');
                $('#sc-backup-sub').text('No backup on record');
            }
        }

        /* ══════════════════════════════════════════
           CERT LINE CHART
        ══════════════════════════════════════════ */
        function renderCertLineChart(d, from, to) {
            const days   = (d.certs_by_day || []).map(r => r.day);
            const counts = (d.certs_by_day || []).map(r => parseInt(r.cnt));
            $('#certChartPeriod').text(counts.reduce((a,b)=>a+b,0) + ' total');

            if (certLineChart) certLineChart.destroy();
            certLineChart = new Chart(document.getElementById('certLineChart'), {
                type: 'line',
                data: {
                    labels: days.length ? days : ['No data'],
                    datasets: [{
                        data: counts.length ? counts : [0],
                        borderColor: ACCENT,
                        backgroundColor: ACCENT + '18',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointBackgroundColor: ACCENT,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1.5,
                        borderWidth: 2,
                    }]
                },
                options: {
                    ...CHART_DEFAULTS,
                    scales: {
                        x: {
                            ticks: { font:{family:"'Source Code Pro',monospace",size:9}, color:'#a0a0a0', maxTicksLimit: 8 },
                            grid: { color: '#ede9e3', drawBorder: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { font:{family:"'Source Code Pro',monospace",size:9}, color:'#a0a0a0', stepSize:1 },
                            grid: { color: '#ede9e3', drawBorder: false }
                        }
                    }
                }
            });
        }

        /* ══════════════════════════════════════════
           CERT TYPE DONUT
        ══════════════════════════════════════════ */
        function renderCertTypeChart(d) {
            const c = d.certificates || {};
            const vals   = [c.clearance ?? 0, c.indigency ?? 0, c.residency ?? 0];
            const colors = ['#1a3a5c', '#7a5700', '#1a5c35'];

            $('#dl-bc').text(vals[0]);
            $('#dl-ic').text(vals[1]);
            $('#dl-rc').text(vals[2]);
            $('#cert-stat-pending').text(c.pending ?? 0);
            $('#cert-stat-approved').text(c.approved ?? 0);

            if (certTypeChart) certTypeChart.destroy();
            certTypeChart = new Chart(document.getElementById('certTypeChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Clearance', 'Indigency', 'Residency'],
                    datasets: [{ data: vals, backgroundColor: colors, borderWidth: 2, borderColor: '#fdfcf9' }]
                },
                options: { ...CHART_DEFAULTS, cutout: '68%' }
            });
        }

        /* ══════════════════════════════════════════
           BLOTTER BARS
        ══════════════════════════════════════════ */
        function renderBlotterBars(d) {
            const b = d.blotter_all || {};
            const total = (b.pending??0)+(b.under_investigation??0)+(b.resolved??0)+(b.dismissed??0);
            $('#blotterTotal').text(total + ' cases');

            const items = [
                { id:'pending',   val: b.pending??0 },
                { id:'invest',    val: b.under_investigation??0 },
                { id:'resolved',  val: b.resolved??0 },
                { id:'dismissed', val: b.dismissed??0 },
            ];
            items.forEach(item => {
                $(`#bb-${item.id}`).text(item.val);
                const pct = total > 0 ? Math.round((item.val/total)*100) : 0;
                setTimeout(() => $(`#bb-${item.id}-fill`).css('width', pct + '%'), 100);
            });
        }

        /* ══════════════════════════════════════════
           POPULATION
        ══════════════════════════════════════════ */
        function renderPopulation(d) {
            const p = d.population || {};
            $('#popTotal').text(fmtNum(p.total ?? 0) + ' total');
            $('#pop-male').text(fmtNum(p.male ?? 0));
            $('#pop-female').text(fmtNum(p.female ?? 0));
            $('#pop-senior').text(fmtNum(p.senior ?? 0));
            $('#pop-pwd').text(fmtNum(p.pwd ?? 0));
            $('#pop-voters').text(fmtNum(p.voter_registered ?? 0));
            $('#pop-unreg').text(fmtNum(p.voter_unregistered ?? 0));

            if (genderChart) genderChart.destroy();
            genderChart = new Chart(document.getElementById('genderChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Male', 'Female'],
                    datasets: [{
                        data: [p.male ?? 0, p.female ?? 0],
                        backgroundColor: [ACCENT, '#7a5700'],
                        borderWidth: 2, borderColor: '#fdfcf9'
                    }]
                },
                options: { ...CHART_DEFAULTS, cutout: '65%' }
            });
        }

        /* ══════════════════════════════════════════
           EXPIRING OFFICERS
        ══════════════════════════════════════════ */
        function renderExpiringOfficers(d) {
            const $list = $('#expiringList');
            const items = d.expiring_officers || [];
            if (!items.length) {
                $list.html('<div class="alert-empty">No terms expiring in the next 60 days.</div>');
                return;
            }
            $list.empty();
            items.forEach(o => {
                const days = diffDays(today(), o.term_end);
                const cls  = days <= 14 ? 'val-err' : 'val-warn';
                const badge= days <= 14 ? 'rb-err' : 'rb-warn';
                $list.append(`
                    <div class="alert-row">
                        <div>
                            <div class="alert-name">${esc(o.name ?? '—')}</div>
                            <div class="alert-sub">${esc(o.position)}</div>
                        </div>
                        <div style="text-align:right;">
                            <span class="row-badge ${badge}">${days}d left</span>
                            <div class="alert-sub" style="margin-top:3px;">${fmtDate(o.term_end)}</div>
                        </div>
                    </div>
                `);
            });
        }

        /* ══════════════════════════════════════════
           UPCOMING EVENTS
        ══════════════════════════════════════════ */
        function renderEvents(d) {
            const $list = $('#eventsList');
            const items = d.upcoming_events || [];
            if (!items.length) {
                $list.html('<div class="alert-empty">No upcoming events scheduled.</div>');
                return;
            }
            $list.empty();
            items.forEach(e => {
                const days = diffDays(today(), e.event_date);
                const badge= days === 0 ? 'rb-err' : days <= 3 ? 'rb-warn' : 'rb-info';
                const dstr = days === 0 ? 'Today' : days === 1 ? 'Tomorrow' : `In ${days}d`;
                $list.append(`
                    <div class="alert-row">
                        <div>
                            <div class="alert-name">${esc(e.title)}</div>
                            <div class="alert-sub">${e.location ? '📍 '+esc(e.location) : fmtDate(e.event_date)}</div>
                        </div>
                        <div style="text-align:right;">
                            <span class="row-badge ${badge}">${dstr}</span>
                            <div class="alert-sub" style="margin-top:3px;">${fmtDate(e.event_date)}</div>
                        </div>
                    </div>
                `);
            });
        }

        /* ══════════════════════════════════════════
           LOW STOCK TABLES
        ══════════════════════════════════════════ */
        function renderLowStock(d) {
            // Inventory
            const $inv = $('#lowStockBody');
            const inv  = d.low_stock_items || [];
            if (!inv.length) {
                $inv.html('<tr><td colspan="3" class="stock-empty">No low-stock items — all quantities sufficient.</td></tr>');
            } else {
                $inv.empty();
                inv.forEach(i => {
                    $inv.append(`
                        <tr>
                            <td>${esc(i.name)}</td>
                            <td style="color:var(--ink-faint);font-size:11px;">${esc(i.category || '—')}</td>
                            <td style="text-align:right;"><span class="stock-qty">${i.quantity}</span></td>
                        </tr>
                    `);
                });
            }

            // Medicines
            const $med = $('#lowMedBody');
            const med  = d.low_medicines || [];
            if (!med.length) {
                $med.html('<tr><td colspan="3" class="stock-empty">No low-stock medicines — all quantities sufficient.</td></tr>');
            } else {
                $med.empty();
                med.forEach(m => {
                    $med.append(`
                        <tr>
                            <td>${esc(m.name)}</td>
                            <td style="color:var(--ink-faint);font-size:11px;">${esc(m.unit || '—')}</td>
                            <td style="text-align:right;"><span class="stock-qty">${m.quantity}</span></td>
                        </tr>
                    `);
                });
            }
        }

        /* ══════════════════════════════════════════
           UTILITIES
        ══════════════════════════════════════════ */
        function fmtNum(n) {
            return Number(n).toLocaleString('en-PH');
        }
        function esc(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }

        /* ══════════════════════════════════════════
           BOOT
        ══════════════════════════════════════════ */
        const initFrom = firstOfMonth();
        const initTo   = today();
        $('#dateFrom').val(initFrom);
        $('#dateTo').val(initTo);
        load(initFrom, initTo);
    });
    </script>
</body>
</html>
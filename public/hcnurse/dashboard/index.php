<?php
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

/* ═══════════════════════════════════════════
   DB HELPERS
═══════════════════════════════════════════ */
function tableExists(mysqli $conn, string $t): bool {
    $t   = $conn->real_escape_string($t);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
}
function fetchRow(mysqli $conn, string $sql, array $defaults = []): array {
    $res = $conn->query($sql);
    if (!$res) { error_log('HC Dashboard: '.$conn->error); return $defaults; }
    $row = $res->fetch_assoc() ?: [];
    foreach ($defaults as $k => $v) $defaults[$k] = (int)($row[$k] ?? 0);
    return $defaults;
}

/* ═══════════════════════════════════════════
   STATIC COUNTS
═══════════════════════════════════════════ */
$patients = fetchRow($conn,
    "SELECT COUNT(*) total,
            SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) < 1)              infant,
            SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) BETWEEN 1 AND 12) child,
            SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) BETWEEN 13 AND 59) adult,
            SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) >= 60)             senior
     FROM residents WHERE deleted_at IS NULL",
    ['total'=>0,'infant'=>0,'child'=>0,'adult'=>0,'senior'=>0]
);

$inventory = ['items'=>0,'low_stock'=>0,'ok_stock'=>0,'total_qty'=>0];
if (tableExists($conn,'medicines'))
    $inventory = fetchRow($conn,
        "SELECT COUNT(*) items,
                SUM(stock_qty <= reorder_level)  low_stock,
                SUM(stock_qty > reorder_level)   ok_stock,
                COALESCE(SUM(stock_qty),0)       total_qty
         FROM medicines",
        ['items'=>0,'low_stock'=>0,'ok_stock'=>0,'total_qty'=>0]
    );

/* ─── Medicine rows for right panel ─── */
$medicineRows = [];
if (tableExists($conn,'medicines')) {
    $mr = $conn->query(
        "SELECT name, stock_qty, reorder_level
         FROM medicines
         ORDER BY (stock_qty <= reorder_level) DESC, name ASC LIMIT 10"
    );
    if ($mr) while ($r = $mr->fetch_assoc()) {
        $qty    = (int)$r['stock_qty'];
        $re     = max((int)$r['reorder_level'], 1);
        $status = $qty <= 0 ? 'Out of Stock' : ($qty <= $re ? 'Low Stock' : ($qty <= $re*2 ? 'Average' : 'Good'));
        $pct    = max(0, min(100, (int)round(($qty / ($re*2)) * 100)));
        $medicineRows[] = [
            'name'   => $r['name'],
            'qty'    => $qty,
            'pct'    => $pct,
            'status' => $status,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — HC Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllStyles(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --paper:      #fdfcf9;
        --paper-lt:   #f9f7f3;
        --paper-dk:   #f0ede6;
        --ink:        #1a1a1a;
        --ink-muted:  #5a5a5a;
        --ink-faint:  #a0a0a0;
        --rule:       #d8d4cc;
        --rule-dk:    #b8b4ac;
        --bg:         #edeae4;
        --accent:     var(--theme-primary, #2d5a27);
        --accent-lt:  color-mix(in srgb, var(--accent) 8%, white);
        --ok-bg:      #edfaf3; --ok-fg:     #1a5c35;
        --warn-bg:    #fef9ec; --warn-fg:   #7a5700;
        --danger-bg:  #fdeeed; --danger-fg: #7a1f1a;
        --neu-bg:     #f3f1ec; --neu-fg:    #5a5a5a;
        --blue:       #2563eb; --blue-lt:   #eff6ff;
        --purple:     #7c3aed; --purple-lt: #f5f3ff;
        --teal:       #0d9488; --teal-lt:   #f0fdfa;
        --amber:      #d97706; --amber-lt:  #fffbeb;
        --rose:       #e11d48; --rose-lt:   #fff1f2;
        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 14px rgba(0,0,0,.05);
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body, input, button, select { font-family:var(--f-sans); }
    .hcd-page { background:var(--bg); min-height:100%; padding-bottom:56px; }

    /* ── Doc header ── */
    .doc-header { background:var(--paper); border-bottom:1px solid var(--rule); }
    .doc-header-inner {
        padding:20px 28px 0;
        display:flex; align-items:flex-end;
        justify-content:space-between; gap:20px; flex-wrap:wrap;
    }
    .doc-eyebrow {
        font-size:8.5px; font-weight:700; letter-spacing:1.8px;
        text-transform:uppercase; color:var(--ink-faint);
        display:flex; align-items:center; gap:8px; margin-bottom:5px;
    }
    .doc-eyebrow::before { content:''; width:18px; height:2px; background:var(--accent); display:inline-block; }
    .doc-title {
        font-family:var(--f-serif); font-size:22px; font-weight:700;
        color:var(--ink); letter-spacing:-.3px; margin-bottom:3px;
        display:flex; align-items:baseline; gap:0; flex-wrap:wrap;
    }
    .doc-sub { font-size:12px; color:var(--ink-faint); font-style:italic; }

    /* Period pill */
    #periodPill {
        display:inline-flex; align-items:center; gap:6px;
        padding:3px 10px; border-radius:2px;
        background:var(--accent-lt);
        border:1px solid color-mix(in srgb,var(--accent) 20%,transparent);
        font-family:var(--f-mono); font-size:9px; color:var(--accent);
        letter-spacing:.4px; margin-left:12px; vertical-align:middle;
    }

    /* Period filter bar */
    .period-bar {
        display:flex; align-items:center; gap:6px;
        padding:12px 0 0; flex-wrap:wrap;
    }
    .period-lbl {
        font-size:8.5px; font-weight:700; letter-spacing:1.2px;
        text-transform:uppercase; color:var(--ink-faint); margin-right:4px;
    }
    .pb-btn {
        padding:5px 13px; border-radius:2px;
        border:1.5px solid var(--rule-dk); background:#fff;
        font-size:10.5px; font-weight:700; letter-spacing:.3px;
        text-transform:uppercase; color:var(--ink-muted); cursor:pointer;
        transition:all .12s; font-family:var(--f-sans);
    }
    .pb-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-lt); }
    .pb-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }
    .pb-sep { width:1px; height:20px; background:var(--rule); margin:0 4px; }
    .pb-range { display:flex; align-items:center; gap:6px; }
    .pb-date {
        padding:5px 10px; border:1.5px solid var(--rule-dk); border-radius:2px;
        font-size:12px; font-family:var(--f-sans); color:var(--ink);
        background:#fff; outline:none; transition:border-color .14s;
    }
    .pb-date:focus { border-color:var(--accent); }
    .pb-apply {
        padding:5px 14px; border-radius:2px;
        background:var(--accent); border:1.5px solid var(--accent);
        color:#fff; font-size:10.5px; font-weight:700;
        letter-spacing:.4px; text-transform:uppercase;
        cursor:pointer; font-family:var(--f-sans); transition:filter .12s;
    }
    .pb-apply:hover { filter:brightness(1.1); }
    .doc-accent-bar { height:3px; margin-top:12px; background:linear-gradient(to right, var(--accent), transparent); }

    /* ── Stat ledger ── */
    .stat-ledger {
        display:grid; grid-template-columns:repeat(5,1fr);
        margin:22px 28px 0;
        background:var(--paper);
        border:1px solid var(--rule); border-radius:2px;
        box-shadow:var(--shadow); overflow:hidden;
    }
    .sl-cell {
        padding:16px 18px; border-right:1px solid var(--rule);
        position:relative; transition:background .12s;
    }
    .sl-cell:last-child { border-right:none; }
    .sl-cell::after { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
    .sl-0::after { background:var(--accent); }
    .sl-1::after { background:var(--blue); }
    .sl-2::after { background:var(--purple); }
    .sl-3::after { background:var(--teal); }
    .sl-4::after { background:var(--rose); }
    .sl-cell:hover { background:var(--paper-lt); }
    .sl-eyebrow { font-size:8px; font-weight:700; letter-spacing:1.4px; text-transform:uppercase; color:var(--ink-faint); margin-bottom:8px; }
    .sl-val { font-family:var(--f-mono); font-size:30px; font-weight:600; line-height:1; margin-bottom:4px; letter-spacing:-1px; color:var(--ink); }
    .sl-sub { font-size:10.5px; color:var(--ink-faint); line-height:1.5; }
    .sl-sub strong { color:var(--ink-muted); font-weight:600; }
    .sl-skel {
        height:30px; width:70px; border-radius:2px;
        background:linear-gradient(90deg,#f0ede8 25%,#e8e5e0 50%,#f0ede8 75%);
        background-size:300px 100%; animation:shimmer 1.2s infinite;
    }
    @keyframes shimmer { 0%{background-position:-300px 0} 100%{background-position:300px 0} }

    /* ── Body grid ── */
    .hcd-grid { display:grid; grid-template-columns:1fr 300px; gap:18px; margin:18px 28px 0; align-items:start; }
    .hcd-left  { display:flex; flex-direction:column; gap:18px; }
    @media (max-width:1100px) { .hcd-grid { grid-template-columns:1fr; } }

    /* ── Card ── */
    .hc-card { background:var(--paper); border:1px solid var(--rule); border-radius:2px; box-shadow:var(--shadow); overflow:hidden; }
    .hc-card-head {
        padding:10px 18px; border-bottom:1px solid var(--rule); background:var(--paper-lt);
        display:flex; align-items:center; justify-content:space-between;
    }
    .hc-card-title {
        font-size:8.5px; font-weight:700; letter-spacing:1.4px;
        text-transform:uppercase; color:var(--ink-muted);
        display:flex; align-items:center; gap:8px;
    }
    .hc-card-title::before { content:''; display:inline-block; width:3px; height:13px; background:var(--accent); border-radius:1px; flex-shrink:0; }
    .hc-card-meta { font-family:var(--f-mono); font-size:9.5px; color:var(--ink-faint); letter-spacing:.3px; }
    .hc-card-body { padding:18px; }

    /* ── Care grid ── */
    .care-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:16px; }
    .care-cell { padding:12px 14px; border-radius:2px; border:1px solid var(--rule); background:var(--paper-lt); position:relative; overflow:hidden; }
    .care-cell::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
    .cc-maternal::before   { background:var(--rose); }
    .cc-fp::before         { background:var(--blue); }
    .cc-prenatal::before   { background:var(--amber); }
    .cc-postnatal::before  { background:var(--teal); }
    .cc-nutrition::before  { background:var(--accent); }
    .cc-immune::before     { background:var(--purple); }
    .care-val { font-family:var(--f-mono); font-size:22px; font-weight:600; line-height:1; margin-bottom:3px; letter-spacing:-.5px; }
    .cc-maternal  .care-val { color:var(--rose); }
    .cc-fp        .care-val { color:var(--blue); }
    .cc-prenatal  .care-val { color:var(--amber); }
    .cc-postnatal .care-val { color:var(--teal); }
    .cc-nutrition .care-val { color:var(--accent); }
    .cc-immune    .care-val { color:var(--purple); }
    .care-lbl { font-size:8.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:var(--ink-faint); }

    /* ── Charts ── */
    .chart-h180 { position:relative; height:180px; }
    .chart-h220 { position:relative; height:220px; }

    /* ── Medicine panel ── */
    .med-row { display:flex; align-items:center; gap:12px; padding:10px 18px; border-bottom:1px solid #f0ede8; transition:background .1s; }
    .med-row:last-of-type { border-bottom:none; }
    .med-row:hover { background:var(--paper-lt); }
    .med-info { flex:1; min-width:0; }
    .med-name { font-size:12px; font-weight:600; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:5px; }
    .med-track { height:4px; background:var(--rule); border-radius:2px; }
    .med-fill  { height:100%; border-radius:2px; transition:width .4s; }
    .mf-good { background:var(--ok-fg); }
    .mf-avg  { background:var(--amber); }
    .mf-low  { background:var(--rose); }
    .mf-oos  { background:var(--ink-faint); }
    .med-badge { font-size:9px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; padding:2px 7px; border-radius:2px; white-space:nowrap; flex-shrink:0; }
    .mb-good { background:var(--ok-bg);     color:var(--ok-fg); }
    .mb-avg  { background:var(--warn-bg);   color:var(--warn-fg); }
    .mb-low  { background:var(--danger-bg); color:var(--danger-fg); }
    .mb-oos  { background:var(--neu-bg);    color:var(--neu-fg); }

    /* inv summary strip */
    .inv-strip { display:grid; grid-template-columns:repeat(3,1fr); border-top:1px solid var(--rule); background:var(--paper-lt); }
    .inv-strip-cell { padding:11px 14px; text-align:center; border-right:1px solid var(--rule); }
    .inv-strip-cell:last-child { border-right:none; }
    .inv-strip-val { font-family:var(--f-mono); font-size:20px; font-weight:600; color:var(--ink); line-height:1; margin-bottom:3px; }
    .inv-strip-lbl { font-size:7.5px; font-weight:700; letter-spacing:1.1px; text-transform:uppercase; color:var(--ink-faint); }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto hcd-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Health Center</div>
                        <div class="doc-title">
                            Health Center Dashboard
                            <span id="periodPill">THIS MONTH</span>
                        </div>
                        <div class="doc-sub"><?= date('l, d F Y') ?> &mdash; <?= htmlspecialchars($_SESSION['name'] ?? 'HC Nurse') ?></div>

                        <!-- Period filter bar -->
                        <div class="period-bar">
                            <span class="period-lbl">Period</span>
                            <button class="pb-btn" data-preset="today">Today</button>
                            <button class="pb-btn active" data-preset="this_month">This Month</button>
                            <button class="pb-btn" data-preset="last_30">Last 30 Days</button>
                            <button class="pb-btn" data-preset="this_year">This Year</button>
                            <button class="pb-btn" data-preset="all_time">All Time</button>
                            <div class="pb-sep"></div>
                            <div class="pb-range">
                                <input type="date" class="pb-date" id="pbFrom">
                                <span style="font-size:11px;color:var(--ink-faint);">to</span>
                                <input type="date" class="pb-date" id="pbTo">
                                <button class="pb-apply" id="pbApply">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="doc-accent-bar"></div>
            </div>

            <!-- ── Stat Ledger ── -->
            <div class="stat-ledger">
                <div class="sl-cell sl-0">
                    <div class="sl-eyebrow">Consultations</div>
                    <div class="sl-val" id="stConsult"><span class="sl-skel"></span></div>
                    <div class="sl-sub" id="stConsultSub">&nbsp;</div>
                </div>
                <div class="sl-cell sl-1">
                    <div class="sl-eyebrow">Immunizations</div>
                    <div class="sl-val" id="stImmune" style="color:var(--blue);"><span class="sl-skel"></span></div>
                    <div class="sl-sub" id="stImmuneSub">&nbsp;</div>
                </div>
                <div class="sl-cell sl-2">
                    <div class="sl-eyebrow">Care Visits</div>
                    <div class="sl-val" id="stVisits" style="color:var(--purple);"><span class="sl-skel"></span></div>
                    <div class="sl-sub" id="stVisitsSub">&nbsp;</div>
                </div>
                <div class="sl-cell sl-3">
                    <div class="sl-eyebrow">Dispensed</div>
                    <div class="sl-val" id="stDispense" style="color:var(--teal);"><span class="sl-skel"></span></div>
                    <div class="sl-sub" id="stDispenseSub">&nbsp;</div>
                </div>
                <div class="sl-cell sl-4">
                    <div class="sl-eyebrow">Low Stock</div>
                    <div class="sl-val" id="stLowStock" style="color:var(--rose);">
                        <?= $inventory['low_stock'] ?>
                    </div>
                    <div class="sl-sub">
                        of <strong><?= $inventory['items'] ?></strong> medicines
                    </div>
                </div>
            </div>

            <!-- ── Body Grid ── -->
            <div class="hcd-grid">

                <!-- LEFT -->
                <div class="hcd-left">

                    <!-- Care Programs -->
                    <div class="hc-card">
                        <div class="hc-card-head">
                            <span class="hc-card-title">Care Programs Overview</span>
                            <span class="hc-card-meta" id="careCardMeta">—</span>
                        </div>
                        <div class="hc-card-body">
                            <div class="care-grid">
                                <div class="care-cell cc-maternal">
                                    <div class="care-val" id="cvMaternal">—</div>
                                    <div class="care-lbl">Maternal</div>
                                </div>
                                <div class="care-cell cc-fp">
                                    <div class="care-val" id="cvFP">—</div>
                                    <div class="care-lbl">Family Planning</div>
                                </div>
                                <div class="care-cell cc-prenatal">
                                    <div class="care-val" id="cvPrenatal">—</div>
                                    <div class="care-lbl">Prenatal</div>
                                </div>
                                <div class="care-cell cc-postnatal">
                                    <div class="care-val" id="cvPostnatal">—</div>
                                    <div class="care-lbl">Postnatal</div>
                                </div>
                                <div class="care-cell cc-nutrition">
                                    <div class="care-val" id="cvNutrition">—</div>
                                    <div class="care-lbl">Child Nutrition</div>
                                </div>
                                <div class="care-cell cc-immune">
                                    <div class="care-val" id="cvImmune">—</div>
                                    <div class="care-lbl">Immunization</div>
                                </div>
                            </div>
                            <div class="chart-h180">
                                <canvas id="careChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Consultation Trend -->
                    <div class="hc-card">
                        <div class="hc-card-head">
                            <span class="hc-card-title">Consultation Daily Trend</span>
                            <span class="hc-card-meta" id="trendMeta">—</span>
                        </div>
                        <div class="hc-card-body">
                            <div class="chart-h220"><canvas id="trendChart"></canvas></div>
                        </div>
                    </div>

                    <!-- Donuts row -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                        <div class="hc-card">
                            <div class="hc-card-head"><span class="hc-card-title">Patients by Age</span></div>
                            <div class="hc-card-body"><div class="chart-h180"><canvas id="patientChart"></canvas></div></div>
                        </div>
                        <div class="hc-card">
                            <div class="hc-card-head"><span class="hc-card-title">Medicine Stock Status</span></div>
                            <div class="hc-card-body"><div class="chart-h180"><canvas id="stockChart"></canvas></div></div>
                        </div>
                    </div>

                </div><!-- /left -->

                <!-- RIGHT: Medicine panel -->
                <div class="hc-card" style="position:sticky;top:0;">
                    <div class="hc-card-head">
                        <span class="hc-card-title">Medicine Inventory</span>
                    </div>

                    <?php foreach ($medicineRows as $m):
                        $sl = strtolower(str_replace([' ','_'],'',$m['status']));
                        $fillCls  = match($sl){ 'good'=>'mf-good','average'=>'mf-avg','lowstock'=>'mf-low', default=>'mf-oos' };
                        $badgeCls = match($sl){ 'good'=>'mb-good','average'=>'mb-avg','lowstock'=>'mb-low', default=>'mb-oos' };
                    ?>
                    <div class="med-row">
                        <div class="med-info">
                            <div class="med-name"><?= htmlspecialchars($m['name']) ?></div>
                            <div class="med-track">
                                <div class="med-fill <?= $fillCls ?>" style="width:<?= $m['pct'] ?>%;"></div>
                            </div>
                        </div>
                        <span class="med-badge <?= $badgeCls ?>"><?= htmlspecialchars($m['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$medicineRows): ?>
                        <div style="padding:24px 18px;text-align:center;color:var(--ink-faint);font-size:12px;font-style:italic;">No medicines in inventory.</div>
                    <?php endif; ?>

                    <div class="inv-strip">
                        <div class="inv-strip-cell">
                            <div class="inv-strip-val"><?= $inventory['items'] ?></div>
                            <div class="inv-strip-lbl">Items</div>
                        </div>
                        <div class="inv-strip-cell">
                            <div class="inv-strip-val" style="color:var(--ok-fg);"><?= $inventory['ok_stock'] ?></div>
                            <div class="inv-strip-lbl">OK Stock</div>
                        </div>
                        <div class="inv-strip-cell">
                            <div class="inv-strip-val" style="color:<?= $inventory['low_stock'] > 0 ? 'var(--rose)' : 'var(--ink-faint)' ?>;">
                                <?= $inventory['low_stock'] ?>
                            </div>
                            <div class="inv-strip-lbl">Low / Out</div>
                        </div>
                    </div>
                </div>

            </div><!-- /hcd-grid -->
        </main>
    </div>

    <?php loadAllScripts(); ?>
    <script>
    $(function () {
        $('body').show();

        const ACCENT  = getComputedStyle(document.documentElement).getPropertyValue('--theme-primary').trim()  || '#2d5a27';
        const ACCENT2 = getComputedStyle(document.documentElement).getPropertyValue('--theme-secondary').trim() || '#446c3e';

        Chart.defaults.plugins.legend.position        = 'bottom';
        Chart.defaults.plugins.legend.labels.boxWidth = 10;
        Chart.defaults.plugins.legend.labels.padding  = 10;
        Chart.defaults.plugins.legend.labels.font     = { size:10, family:"'Source Sans 3', sans-serif" };
        Chart.defaults.plugins.legend.labels.usePointStyle = true;

        /* ── Date helpers ── */
        const today = () => new Date().toISOString().slice(0,10);
        const fmt   = d => d ? new Date(d+'T00:00:00').toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : '—';

        const presets = {
            today:      () => { const t=today(); return [t,t]; },
            this_month: () => {
                const n=new Date();
                return [n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-01', today()];
            },
            last_30: () => {
                const d=new Date(); d.setDate(d.getDate()-30);
                return [d.toISOString().slice(0,10), today()];
            },
            this_year: () => [new Date().getFullYear()+'-01-01', today()],
            all_time:  () => ['2000-01-01', today()],
        };

        const PRESET_LABELS = {
            today:'TODAY', this_month:'THIS MONTH',
            last_30:'LAST 30 DAYS', this_year:'THIS YEAR', all_time:'ALL TIME'
        };

        /* ── Chart instances ── */
        let careInst=null, trendInst=null, patientInst=null, stockInst=null;

        function mkChart(inst, id, cfg) {
            if (inst) inst.destroy();
            const el = document.getElementById(id);
            if (!el) return null;
            return new Chart(el, cfg);
        }

        /* ── Render ── */
        function renderAll(d) {
            $('#stConsult').text(d.consultations ?? 0);
            $('#stConsultSub').html(`Today: <strong>${d.today_consult ?? 0}</strong>`);
            $('#stImmune').text(d.immunizations ?? 0);
            $('#stImmuneSub').html(`Upcoming: <strong>${d.upcoming_immune ?? 0}</strong>`);
            $('#stVisits').text(d.care_visits ?? 0);
            $('#stVisitsSub').html(`Programs: <strong>${d.active_programs ?? 0}</strong>`);
            $('#stDispense').text(d.dispensed_qty ?? 0);
            $('#stDispenseSub').html(`Txns: <strong>${d.dispensed_txn ?? 0}</strong>`);

            const cv = d.care_by_type || {};
            $('#cvMaternal').text(cv.maternal ?? 0);
            $('#cvFP').text(cv.family_planning ?? 0);
            $('#cvPrenatal').text(cv.prenatal ?? 0);
            $('#cvPostnatal').text(cv.postnatal ?? 0);
            $('#cvNutrition').text(cv.child_nutrition ?? 0);
            $('#cvImmune').text(d.immunizations ?? 0);

            $('#careCardMeta').text(fmt(d.date_from) + ' → ' + fmt(d.date_to));
            $('#trendMeta').text(fmt(d.date_from) + ' → ' + fmt(d.date_to));

            careInst = mkChart(careInst, 'careChart', {
                type:'bar',
                data:{
                    labels:['Maternal','Fam. Planning','Prenatal','Postnatal','Child Nutrition','Immunization'],
                    datasets:[{
                        data:[cv.maternal??0,cv.family_planning??0,cv.prenatal??0,cv.postnatal??0,cv.child_nutrition??0,d.immunizations??0],
                        backgroundColor:['#fda4af','#93c5fd','#fcd34d','#5eead4',ACCENT2+'bb','#c4b5fd'],
                        borderRadius:3,
                    }]
                },
                options:{
                    maintainAspectRatio:false, responsive:true,
                    plugins:{ legend:{ display:false } },
                    scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1, font:{size:10} } }, x:{ ticks:{ font:{size:9} } } }
                }
            });

            const days   = (d.consult_by_day||[]).map(r=>r.day);
            const counts = (d.consult_by_day||[]).map(r=>+r.cnt);
            trendInst = mkChart(trendInst, 'trendChart', {
                type:'line',
                data:{
                    labels: days.length ? days : ['No data'],
                    datasets:[{
                        label:'Consultations',
                        data: counts.length ? counts : [0],
                        borderColor:ACCENT, backgroundColor:ACCENT+'22',
                        fill:true, tension:.4,
                        pointRadius:3, pointBackgroundColor:ACCENT, borderWidth:2,
                    }]
                },
                options:{
                    maintainAspectRatio:false, responsive:true,
                    plugins:{ legend:{ display:false } },
                    scales:{ x:{ ticks:{ font:{size:9}, maxTicksLimit:12 } }, y:{ beginAtZero:true, ticks:{ stepSize:1, font:{size:10} } } }
                }
            });
        }

        function renderStaticCharts() {
            const p   = <?= json_encode($patients) ?>;
            const inv = <?= json_encode($inventory) ?>;
            patientInst = mkChart(patientInst,'patientChart',{
                type:'doughnut',
                data:{ labels:['Adult','Senior','Child','Infant'], datasets:[{ data:[p.adult,p.senior,p.child,p.infant], backgroundColor:[ACCENT,'#f59e0b','#3b82f6','#a78bfa'], borderWidth:1.5, borderColor:'#fff' }] },
                options:{ cutout:'68%', maintainAspectRatio:false, responsive:true }
            });
            stockInst = mkChart(stockInst,'stockChart',{
                type:'doughnut',
                data:{ labels:['OK Stock','Low / Out'], datasets:[{ data:[inv.ok_stock,inv.low_stock], backgroundColor:['#1a5c35','#e11d48'], borderWidth:1.5, borderColor:'#fff' }] },
                options:{ cutout:'68%', maintainAspectRatio:false, responsive:true }
            });
        }

        /* ── API call ── */
        function load(from, to, preset) {
            $('#periodPill').text(preset ? (PRESET_LABELS[preset]||preset.toUpperCase()) : (fmt(from)+' → '+fmt(to)));
            ['#stConsult','#stImmune','#stVisits','#stDispense'].forEach(id=>$(id).html('<span class="sl-skel"></span>'));
            $.getJSON('dashboard_api.php',{ date_from:from, date_to:to }, renderAll)
             .fail(()=>{ ['#stConsult','#stImmune','#stVisits','#stDispense'].forEach(id=>$(id).text('—')); });
        }

        /* ── Preset buttons ── */
        $('.pb-btn').on('click', function(){
            const key = $(this).data('preset');
            const [f,t] = presets[key]();
            $('.pb-btn').removeClass('active'); $(this).addClass('active');
            $('#pbFrom').val(f); $('#pbTo').val(t);
            load(f, t, key);
        });

        $('#pbApply').on('click', function(){
            const f=$('#pbFrom').val(), t=$('#pbTo').val();
            if (!f||!t) return;
            $('.pb-btn').removeClass('active');
            load(f, t, null);
        });

        const [initFrom, initTo] = presets.this_month();
        $('#pbFrom').val(initFrom); $('#pbTo').val(initTo);
        load(initFrom, initTo, 'this_month');
        renderStaticCharts();
    });
    </script>
</body>
</html>
<?php
/**
 * HC Nurse Dashboard
 * Replaces: public/hcnurse/dashboard/index.php
 *
 * FIXES:
 * 1. compact() bug → direct array build (no undefined variable)
 * 2. Age groups from actual birthdate computation (Infant/Child/Teen/Adult/Senior)
 * 3. Care-program cells are clickable links → /hcnurse/health-records/?type=xxx
 * 4. Period filter bar for dynamic stat reload via dashboard_api.php
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

function tblOk(mysqli $c, string $t): bool {
    $t = $c->real_escape_string($t);
    return (bool)$c->query("SHOW TABLES LIKE '{$t}'")?->num_rows;
}
function safeRow(mysqli $c, string $sql, array $def): array {
    $r = $c->query($sql);
    if (!$r) return $def;
    $row = $r->fetch_assoc() ?: [];
    foreach ($def as $k=>$v) $def[$k] = (int)($row[$k] ?? 0);
    return $def;
}

/* ── patients by real birthdate ── */
$patients = safeRow($conn,
    "SELECT COUNT(*) total,
        SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) < 1)              AS infant,
        SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) BETWEEN 1 AND 12) AS child,
        SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) BETWEEN 13 AND 17) AS teen,
        SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) BETWEEN 18 AND 59) AS adult,
        SUM(TIMESTAMPDIFF(YEAR,birthdate,CURDATE()) >= 60)            AS senior
     FROM residents WHERE deleted_at IS NULL",
    ['total'=>0,'infant'=>0,'child'=>0,'teen'=>0,'adult'=>0,'senior'=>0]
);

/* ── inventory ── */
$inventory = ['items'=>0,'low_stock'=>0,'ok_stock'=>0];
if (tblOk($conn,'medicines'))
    $inventory = safeRow($conn,
        "SELECT COUNT(*) items,
                SUM(stock_qty <= reorder_level) low_stock,
                SUM(stock_qty > reorder_level)  ok_stock
         FROM medicines",
        ['items'=>0,'low_stock'=>0,'ok_stock'=>0]
    );

/* ── medicine panel (right sidebar) ── */
$medicineRows = [];
if (tblOk($conn,'medicines')) {
    $mr = $conn->query(
        "SELECT name, stock_qty, reorder_level FROM medicines
         ORDER BY (stock_qty <= reorder_level) DESC, name ASC LIMIT 10"
    );
    if ($mr) while ($r = $mr->fetch_assoc()) {
        $qty  = (int)$r['stock_qty'];
        $re   = max((int)$r['reorder_level'], 1);
        $status = $qty <= 0 ? 'Out of Stock' : ($qty <= $re ? 'Low Stock' : ($qty <= $re*2 ? 'Average' : 'Good'));
        $pct    = max(0, min(100, (int)round($qty / ($re*2) * 100)));
        $medicineRows[] = ['name'=>$r['name'], 'qty'=>$qty, 'pct'=>$pct, 'status'=>$status];
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
    :root{
        --paper:#fdfcf9;--paper-lt:#f9f7f3;--ink:#1a1a1a;--ink-muted:#5a5a5a;--ink-faint:#a0a0a0;
        --rule:#d8d4cc;--rule-dk:#b8b4ac;--bg:#edeae4;
        --accent:var(--theme-primary,#2d5a27);--accent-lt:color-mix(in srgb,var(--accent) 8%,white);
        --ok-fg:#1a5c35;--ok-bg:#edfaf3;--danger-fg:#7a1f1a;--danger-bg:#fdeeed;
        --blue:#2563eb;--blue-lt:#eff6ff;--purple:#7c3aed;--purple-lt:#f5f3ff;
        --teal:#0d9488;--teal-lt:#f0fdfa;--amber:#d97706;--amber-lt:#fffbeb;
        --rose:#e11d48;--rose-lt:#fff1f2;
        --f-serif:'Source Serif 4',Georgia,serif;
        --f-sans:'Source Sans 3','Segoe UI',sans-serif;
        --f-mono:'Source Code Pro','Courier New',monospace;
        --shadow:0 1px 2px rgba(0,0,0,.07),0 3px 14px rgba(0,0,0,.05);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body,input,button,select{font-family:var(--f-sans);}
    .hcd{background:var(--bg);min-height:100%;padding-bottom:56px;}

    /* doc header */
    .dh{background:var(--paper);border-bottom:1px solid var(--rule);}
    .dhi{padding:20px 28px 0;display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;}
    .ey{font-size:8.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--ink-faint);display:flex;align-items:center;gap:8px;margin-bottom:5px;}
    .ey::before{content:'';width:18px;height:2px;background:var(--accent);display:inline-block;}
    .dt{font-family:var(--f-serif);font-size:22px;font-weight:700;color:var(--ink);letter-spacing:-.3px;margin-bottom:3px;display:flex;align-items:baseline;gap:0;flex-wrap:wrap;}
    .ds{font-size:12px;color:var(--ink-faint);font-style:italic;}
    #periodPill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:2px;background:var(--accent-lt);border:1px solid color-mix(in srgb,var(--accent) 20%,transparent);font-family:var(--f-mono);font-size:9px;color:var(--accent);letter-spacing:.4px;margin-left:12px;vertical-align:middle;}
    .pb{display:flex;align-items:center;gap:6px;padding:12px 0 0;flex-wrap:wrap;}
    .pblbl{font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-faint);margin-right:4px;}
    .pbb{padding:5px 13px;border-radius:2px;border:1.5px solid var(--rule-dk);background:#fff;font-size:10.5px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;color:var(--ink-muted);cursor:pointer;transition:all .12s;font-family:var(--f-sans);}
    .pbb:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-lt);}
    .pbb.active{background:var(--accent);border-color:var(--accent);color:#fff;}
    .pbsep{width:1px;height:20px;background:var(--rule);margin:0 4px;}
    .pbd{padding:5px 10px;border:1.5px solid var(--rule-dk);border-radius:2px;font-size:12px;font-family:var(--f-sans);color:var(--ink);background:#fff;outline:none;transition:border-color .14s;}
    .pbd:focus{border-color:var(--accent);}
    .pba{padding:5px 14px;border-radius:2px;background:var(--accent);border:1.5px solid var(--accent);color:#fff;font-size:10.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;font-family:var(--f-sans);}
    .dab{height:3px;margin-top:12px;background:linear-gradient(to right,var(--accent),transparent);}

    /* stat ledger */
    .sl{display:grid;grid-template-columns:repeat(5,1fr);margin:22px 28px 0;background:var(--paper);border:1px solid var(--rule);border-radius:2px;box-shadow:var(--shadow);overflow:hidden;}
    .sc{padding:16px 18px;border-right:1px solid var(--rule);position:relative;transition:background .12s;}
    .sc:last-child{border-right:none;}
    .sc::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
    .sc0::after{background:var(--accent);}
    .sc1::after{background:var(--blue);}
    .sc2::after{background:var(--purple);}
    .sc3::after{background:var(--teal);}
    .sc4::after{background:var(--rose);}
    .sc:hover{background:var(--paper-lt);}
    .se{font-size:8px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:8px;}
    .sv{font-family:var(--f-mono);font-size:30px;font-weight:600;line-height:1;margin-bottom:4px;letter-spacing:-1px;color:var(--ink);}
    .ss{font-size:10.5px;color:var(--ink-faint);line-height:1.5;}
    .ss strong{color:var(--ink-muted);font-weight:600;}
    .skel{height:30px;width:70px;border-radius:2px;background:linear-gradient(90deg,#f0ede8 25%,#e8e5e0 50%,#f0ede8 75%);background-size:300px 100%;animation:shimmer 1.2s infinite;}
    @keyframes shimmer{0%{background-position:-300px 0}100%{background-position:300px 0}}

    /* grid */
    .hcg{display:grid;grid-template-columns:1fr 300px;gap:18px;margin:18px 28px 0;align-items:start;}
    .hcl{display:flex;flex-direction:column;gap:18px;}
    @media(max-width:1100px){.hcg{grid-template-columns:1fr;}}

    /* card */
    .hcc{background:var(--paper);border:1px solid var(--rule);border-radius:2px;box-shadow:var(--shadow);overflow:hidden;}
    .hcch{padding:10px 18px;border-bottom:1px solid var(--rule);background:var(--paper-lt);display:flex;align-items:center;justify-content:space-between;}
    .hcct{font-size:8.5px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--ink-muted);display:flex;align-items:center;gap:8px;}
    .hcct::before{content:'';display:inline-block;width:3px;height:13px;background:var(--accent);border-radius:1px;flex-shrink:0;}
    .hccm{font-family:var(--f-mono);font-size:9.5px;color:var(--ink-faint);letter-spacing:.3px;}
    .hccb{padding:18px;}

    /* care grid — all cells are anchor links */
    .cg{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;}
    .cc{padding:12px 14px;border-radius:2px;border:1px solid var(--rule);background:var(--paper-lt);position:relative;overflow:hidden;text-decoration:none;display:block;transition:box-shadow .14s,border-color .14s;cursor:pointer;}
    .cc:hover{box-shadow:0 2px 8px rgba(0,0,0,.1);border-color:var(--rule-dk);}
    .cc::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
    .cc-mat::before{background:var(--rose);}
    .cc-fp::before{background:var(--blue);}
    .cc-pre::before{background:var(--amber);}
    .cc-pos::before{background:var(--teal);}
    .cc-nut::before{background:var(--accent);}
    .cc-imm::before{background:var(--purple);}
    .cv{font-family:var(--f-mono);font-size:22px;font-weight:600;line-height:1;margin-bottom:3px;letter-spacing:-.5px;}
    .cc-mat .cv{color:var(--rose);}
    .cc-fp  .cv{color:var(--blue);}
    .cc-pre .cv{color:var(--amber);}
    .cc-pos .cv{color:var(--teal);}
    .cc-nut .cv{color:var(--accent);}
    .cc-imm .cv{color:var(--purple);}
    .cl{font-size:8.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-faint);}

    /* charts */
    .ch180{position:relative;height:180px;}
    .ch220{position:relative;height:220px;}

    /* medicine panel */
    .mr{display:flex;align-items:center;gap:12px;padding:10px 18px;border-bottom:1px solid #f0ede8;transition:background .1s;}
    .mr:last-of-type{border-bottom:none;}
    .mr:hover{background:var(--paper-lt);}
    .mi{flex:1;min-width:0;}
    .mn{font-size:12px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px;}
    .mt{height:4px;background:var(--rule);border-radius:2px;}
    .mf{height:100%;border-radius:2px;transition:width .4s;}
    .mf-good{background:var(--ok-fg);}
    .mf-avg{background:var(--amber);}
    .mf-low{background:var(--rose);}
    .mf-oos{background:var(--ink-faint);}
    .mb{font-size:9px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;padding:2px 7px;border-radius:2px;white-space:nowrap;flex-shrink:0;}
    .mb-good{background:var(--ok-bg);color:var(--ok-fg);}
    .mb-avg{background:var(--amber-lt);color:var(--amber);}
    .mb-low{background:var(--danger-bg);color:var(--danger-fg);}
    .mb-oos{background:#f3f1ec;color:#5a5a5a;}
    .is{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--rule);background:var(--paper-lt);}
    .isc{padding:11px 14px;text-align:center;border-right:1px solid var(--rule);}
    .isc:last-child{border-right:none;}
    .isv{font-family:var(--f-mono);font-size:20px;font-weight:600;color:var(--ink);line-height:1;margin-bottom:3px;}
    .isl{font-size:7.5px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:var(--ink-faint);}
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>
        <main class="flex-1 h-screen overflow-y-auto hcd">

            <!-- Doc Header -->
            <div class="dh">
                <div class="dhi">
                    <div>
                        <div class="ey">Barangay Bombongan — Health Center</div>
                        <div class="dt">Health Center Dashboard <span id="periodPill">THIS MONTH</span></div>
                        <div class="ds"><?= date('l, d F Y') ?> — <?= htmlspecialchars($_SESSION['name']??'HC Nurse') ?></div>
                        <div class="pb">
                            <span class="pblbl">Period</span>
                            <button class="pbb" data-p="today">Today</button>
                            <button class="pbb active" data-p="this_month">This Month</button>
                            <button class="pbb" data-p="last_30">Last 30 Days</button>
                            <button class="pbb" data-p="this_year">This Year</button>
                            <button class="pbb" data-p="all_time">All Time</button>
                            <div class="pbsep"></div>
                            <input type="date" class="pbd" id="pbFrom">
                            <span style="font-size:11px;color:var(--ink-faint);">to</span>
                            <input type="date" class="pbd" id="pbTo">
                            <button class="pba" id="pbApply">Apply</button>
                        </div>
                    </div>
                </div>
                <div class="dab"></div>
            </div>

            <!-- Stat Ledger -->
            <div class="sl">
                <div class="sc sc0"><div class="se">Consultations</div><div class="sv" id="stC"><span class="skel"></span></div><div class="ss" id="stCs">&nbsp;</div></div>
                <div class="sc sc1"><div class="se">Immunizations</div><div class="sv" id="stI" style="color:var(--blue);"><span class="skel"></span></div><div class="ss" id="stIs">&nbsp;</div></div>
                <div class="sc sc2"><div class="se">Care Visits</div><div class="sv" id="stV" style="color:var(--purple);"><span class="skel"></span></div><div class="ss" id="stVs">&nbsp;</div></div>
                <div class="sc sc3"><div class="se">Dispensed</div><div class="sv" id="stD" style="color:var(--teal);"><span class="skel"></span></div><div class="ss" id="stDs">&nbsp;</div></div>
                <div class="sc sc4"><div class="se">Low Stock</div>
                    <div class="sv" style="color:var(--rose);"><?= $inventory['low_stock'] ?></div>
                    <div class="ss">of <strong><?= $inventory['items'] ?></strong> medicines</div>
                </div>
            </div>

            <!-- Body Grid -->
            <div class="hcg">
                <div class="hcl">

                    <!-- Care Programs (clickable) -->
                    <div class="hcc">
                        <div class="hcch"><span class="hcct">Care Programs</span><span class="hccm" id="careM">—</span></div>
                        <div class="hccb">
                            <div class="cg">
                                <a href="/hcnurse/health-records/?type=maternal"        class="cc cc-mat"><div class="cv" id="cvM">—</div><div class="cl">Maternal</div></a>
                                <a href="/hcnurse/health-records/?type=family_planning" class="cc cc-fp" ><div class="cv" id="cvFP">—</div><div class="cl">Family Planning</div></a>
                                <a href="/hcnurse/health-records/?type=prenatal"        class="cc cc-pre"><div class="cv" id="cvPre">—</div><div class="cl">Prenatal</div></a>
                                <a href="/hcnurse/health-records/?type=postnatal"       class="cc cc-pos"><div class="cv" id="cvPos">—</div><div class="cl">Postnatal</div></a>
                                <a href="/hcnurse/health-records/?type=child_nutrition" class="cc cc-nut"><div class="cv" id="cvNut">—</div><div class="cl">Child Nutrition</div></a>
                                <a href="/hcnurse/health-records/?type=immunization"    class="cc cc-imm"><div class="cv" id="cvImm">—</div><div class="cl">Immunization</div></a>
                            </div>
                            <div class="ch180"><canvas id="careChart"></canvas></div>
                        </div>
                    </div>

                    <!-- Trend -->
                    <div class="hcc">
                        <div class="hcch"><span class="hcct">Consultation Daily Trend</span><span class="hccm" id="trendM">—</span></div>
                        <div class="hccb"><div class="ch220"><canvas id="trendChart"></canvas></div></div>
                    </div>

                    <!-- Donuts -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                        <div class="hcc">
                            <div class="hcch"><span class="hcct">Patients by Age</span></div>
                            <div class="hccb"><div class="ch180"><canvas id="patientChart"></canvas></div></div>
                        </div>
                        <div class="hcc">
                            <div class="hcch"><span class="hcct">Medicine Stock</span></div>
                            <div class="hccb"><div class="ch180"><canvas id="stockChart"></canvas></div></div>
                        </div>
                    </div>

                </div><!-- /left -->

                <!-- Right: medicine panel -->
                <div class="hcc" style="position:sticky;top:0;">
                    <div class="hcch"><span class="hcct">Medicine Inventory</span></div>
                    <?php foreach ($medicineRows as $m):
                        $sl = strtolower(str_replace([' ','_'],'',$m['status']));
                        $fc = match($sl){ 'good'=>'mf-good','average'=>'mf-avg','lowstock'=>'mf-low', default=>'mf-oos' };
                        $bc = match($sl){ 'good'=>'mb-good','average'=>'mb-avg','lowstock'=>'mb-low', default=>'mb-oos' };
                    ?>
                    <div class="mr">
                        <div class="mi">
                            <div class="mn"><?= htmlspecialchars($m['name']) ?></div>
                            <div class="mt"><div class="mf <?= $fc ?>" style="width:<?= $m['pct'] ?>%;"></div></div>
                        </div>
                        <span class="mb <?= $bc ?>"><?= htmlspecialchars($m['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$medicineRows): ?>
                        <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:12px;font-style:italic;">No medicines in inventory.</div>
                    <?php endif; ?>
                    <div class="is">
                        <div class="isc"><div class="isv"><?= $inventory['items'] ?></div><div class="isl">Items</div></div>
                        <div class="isc"><div class="isv" style="color:var(--ok-fg);"><?= $inventory['ok_stock'] ?></div><div class="isl">OK</div></div>
                        <div class="isc"><div class="isv" style="color:<?= $inventory['low_stock']>0?'var(--rose)':'var(--ink-faint)' ?>;"><?= $inventory['low_stock'] ?></div><div class="isl">Low</div></div>
                    </div>
                </div>

            </div><!-- /hcg -->
        </main>
    </div>

    <?php loadAllScripts(); ?>
    <script>
    $(function(){
        $('body').show();
        const AC = getComputedStyle(document.documentElement).getPropertyValue('--theme-primary').trim()||'#2d5a27';
        const AC2= getComputedStyle(document.documentElement).getPropertyValue('--theme-secondary').trim()||'#446c3e';
        Chart.defaults.plugins.legend.position='bottom';
        Chart.defaults.plugins.legend.labels.boxWidth=10;
        Chart.defaults.plugins.legend.labels.font={size:10,family:"'Source Sans 3',sans-serif"};
        Chart.defaults.plugins.legend.labels.usePointStyle=true;

        const fmt = d=>d?new Date(d+'T00:00:00').toLocaleDateString('en-PH',{month:'short',day:'numeric'}):'—';
        const today=()=>new Date().toISOString().slice(0,10);
        const presets={
            today:()=>{const t=today();return[t,t];},
            this_month:()=>{const n=new Date();return[n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-01',today()];},
            last_30:()=>{const d=new Date();d.setDate(d.getDate()-30);return[d.toISOString().slice(0,10),today()];},
            this_year:()=>[new Date().getFullYear()+'-01-01',today()],
            all_time:()=>['2000-01-01',today()],
        };
        const LABELS={today:'TODAY',this_month:'THIS MONTH',last_30:'LAST 30 DAYS',this_year:'THIS YEAR',all_time:'ALL TIME'};

        let careInst=null,trendInst=null,patInst=null,stockInst=null;
        function mkChart(inst,id,cfg){
            if(inst)inst.destroy();
            const el=document.getElementById(id);
            if(!el)return null;
            return new Chart(el,cfg);
        }

        function render(d){
            $('#stC').text(d.consultations??0);
            $('#stCs').html(`Today: <strong>${d.today_consult??0}</strong>`);
            $('#stI').text(d.immunizations??0);
            $('#stIs').html(`Upcoming: <strong>${d.upcoming_immune??0}</strong>`);
            $('#stV').text(d.care_visits??0);
            $('#stVs').html(`Programs: <strong>${d.active_programs??0}</strong>`);
            $('#stD').text(d.dispensed_qty??0);
            $('#stDs').html(`Txns: <strong>${d.dispensed_txn??0}</strong>`);

            const cv=d.care_by_type||{};
            $('#cvM').text(cv.maternal??0);
            $('#cvFP').text(cv.family_planning??0);
            $('#cvPre').text(cv.prenatal??0);
            $('#cvPos').text(cv.postnatal??0);
            $('#cvNut').text(cv.child_nutrition??0);
            $('#cvImm').text(cv.immunization??0);
            $('#careM').text(fmt(d.date_from)+' → '+fmt(d.date_to));
            $('#trendM').text(fmt(d.date_from)+' → '+fmt(d.date_to));

            careInst=mkChart(careInst,'careChart',{
                type:'bar',
                data:{
                    labels:['Maternal','Fam.Plan','Prenatal','Postnatal','Nutrition','Immunization'],
                    datasets:[{data:[cv.maternal??0,cv.family_planning??0,cv.prenatal??0,cv.postnatal??0,cv.child_nutrition??0,cv.immunization??0],
                        backgroundColor:['#fda4af','#93c5fd','#fcd34d','#5eead4',AC2+'bb','#c4b5fd'],borderRadius:3}]
                },
                options:{maintainAspectRatio:false,responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,font:{size:10}}},x:{ticks:{font:{size:9}}}}}
            });

            const days=(d.consult_by_day||[]).map(r=>r.day);
            const cnts=(d.consult_by_day||[]).map(r=>+r.cnt);
            trendInst=mkChart(trendInst,'trendChart',{
                type:'line',
                data:{labels:days.length?days:['No data'],datasets:[{label:'Consultations',data:cnts.length?cnts:[0],borderColor:AC,backgroundColor:AC+'22',fill:true,tension:.4,pointRadius:3,pointBackgroundColor:AC,borderWidth:2}]},
                options:{maintainAspectRatio:false,responsive:true,plugins:{legend:{display:false}},scales:{x:{ticks:{font:{size:9},maxTicksLimit:12}},y:{beginAtZero:true,ticks:{stepSize:1,font:{size:10}}}}}
            });
        }

        function renderStatic(){
            // age groups — computed from real birthdate (Infant/Child/Teen/Adult/Senior)
            const p=<?= json_encode($patients) ?>;
            const inv=<?= json_encode($inventory) ?>;
            patInst=mkChart(patInst,'patientChart',{
                type:'doughnut',
                data:{
                    labels:['Adult','Senior','Teen','Child','Infant'],
                    datasets:[{data:[p.adult,p.senior,p.teen,p.child,p.infant],backgroundColor:[AC,'#f59e0b','#3b82f6','#a78bfa','#fb7185'],borderWidth:1.5,borderColor:'#fff'}]
                },
                options:{cutout:'68%',maintainAspectRatio:false,responsive:true}
            });
            stockInst=mkChart(stockInst,'stockChart',{
                type:'doughnut',
                data:{labels:['OK Stock','Low / Out'],datasets:[{data:[inv.ok_stock,inv.low_stock],backgroundColor:['#1a5c35','#e11d48'],borderWidth:1.5,borderColor:'#fff'}]},
                options:{cutout:'68%',maintainAspectRatio:false,responsive:true}
            });
        }

        function load(from,to,preset){
            $('#periodPill').text(preset?(LABELS[preset]||preset.toUpperCase()):(fmt(from)+' → '+fmt(to)));
            ['#stC','#stI','#stV','#stD'].forEach(id=>$(id).html('<span class="skel"></span>'));
            $.getJSON('dashboard_api.php',{date_from:from,date_to:to},render)
             .fail(()=>['#stC','#stI','#stV','#stD'].forEach(id=>$(id).text('—')));
        }

        $('.pbb').on('click',function(){
            const key=$(this).data('p');
            const [f,t]=presets[key]();
            $('.pbb').removeClass('active');$(this).addClass('active');
            $('#pbFrom').val(f);$('#pbTo').val(t);
            load(f,t,key);
        });
        $('#pbApply').on('click',function(){
            const f=$('#pbFrom').val(),t=$('#pbTo').val();
            if(!f||!t)return;
            $('.pbb').removeClass('active');
            load(f,t,null);
        });

        const[f0,t0]=presets.this_month();
        $('#pbFrom').val(f0);$('#pbTo').val(t0);
        load(f0,t0,'this_month');
        renderStatic();
    });
    </script>
</body>
</html>
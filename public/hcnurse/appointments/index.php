<?php
/**
 * Appointments — Main Page
 * public/hcnurse/appointments/index.php
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointments — MIS Barangay</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php loadAllAssets(); ?>
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
    --p:#fdfcf9;--plt:#f9f7f3;--pdk:#f0ede6;
    --ink:#1a1a1a;--muted:#5a5a5a;--faint:#a0a0a0;
    --rule:#d8d4cc;--rule-dk:#b8b4ac;--bg:#edeae4;
    --acc:var(--theme-primary,#2d5a27);
    --acc-lt:color-mix(in srgb,var(--acc) 8%,white);
    --acc-md:color-mix(in srgb,var(--acc) 15%,white);
    --ok-bg:#edfaf3;--ok-fg:#1a5c35;
    --warn-bg:#fef9ec;--warn-fg:#7a5700;
    --danger-bg:#fdeeed;--danger-fg:#7a1f1a;
    --info-bg:#edf3fa;--info-fg:#1a3a5c;
    --f-s:'Source Serif 4',Georgia,serif;
    --f-n:'Source Sans 3','Segoe UI',sans-serif;
    --f-m:'Source Code Pro','Courier New',monospace;
    --sh:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.05);
    --sh-md:0 2px 8px rgba(0,0,0,.09),0 8px 32px rgba(0,0,0,.07);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:var(--f-n);}
.page{background:var(--bg);min-height:100%;padding-bottom:56px;}

/* ── header ── */
.hdr{background:var(--p);border-bottom:1px solid var(--rule);}
.hdr-inner{padding:18px 28px;display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.eyebrow{font-size:8.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:5px;}
.eyebrow::before{content:'';width:18px;height:2px;background:var(--acc);display:inline-block;}
.page-title{font-family:var(--f-s);font-size:21px;font-weight:700;color:var(--ink);margin-bottom:3px;}
.page-sub{font-size:12px;color:var(--faint);font-style:italic;}
.accent-bar{height:3px;background:linear-gradient(90deg,var(--acc),transparent);}

/* ── toolbar ── */
.toolbar{background:var(--plt);border-bottom:1px solid var(--rule);padding:10px 28px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.tb-search{padding:7px 11px 7px 34px;border:1.5px solid var(--rule-dk);border-radius:3px;font-size:12.5px;color:var(--ink);background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23a0a0a0' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center;outline:none;width:230px;transition:border-color .15s,box-shadow .15s;}
.tb-search:focus{border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-lt);}
.tb-search::placeholder{color:var(--faint);font-size:12px;}
.tb-select{padding:7px 10px;border:1.5px solid var(--rule-dk);border-radius:3px;font-size:12px;background:#fff;color:var(--ink);outline:none;cursor:pointer;transition:border-color .15s;}
.tb-select:focus{border-color:var(--acc);}
.tb-sep{flex:1;}
.tb-chip{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:20px;background:var(--p);border:1px solid var(--rule);font-family:var(--f-m);font-size:10.5px;font-weight:600;color:var(--muted);white-space:nowrap;}
.tb-chip-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}

/* ── layout ── */
.body{display:grid;grid-template-columns:272px 1fr;gap:16px;margin:16px 28px 0;}
@media(max-width:1080px){.body{grid-template-columns:1fr;}}

/* ── card ── */
.card{background:var(--p);border:1px solid var(--rule);border-radius:4px;box-shadow:var(--sh);overflow:hidden;}
.card-head{padding:10px 15px;border-bottom:1px solid var(--rule);background:var(--plt);display:flex;align-items:center;justify-content:space-between;gap:8px;}
.card-title{font-size:8.5px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:7px;}
.card-title::before{content:'';width:3px;height:11px;background:var(--acc);border-radius:2px;flex-shrink:0;}

/* ── mini calendar ── */
.cal-nav{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--rule);}
.cal-month{font-family:var(--f-s);font-size:13.5px;font-weight:600;color:var(--ink);}
.cal-nav-btn{background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:3px;color:var(--muted);font-size:14px;transition:all .12s;line-height:1;}
.cal-nav-btn:hover{background:var(--acc-lt);color:var(--acc);}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);}
.cal-dow{padding:5px 0;text-align:center;font-size:8.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--faint);background:var(--plt);border-bottom:1px solid var(--rule);}
.cal-day{background:var(--p);padding:4px 2px 3px;text-align:center;cursor:pointer;transition:background .1s;position:relative;border-right:1px solid #f0ede8;border-bottom:1px solid #f0ede8;display:flex;flex-direction:column;align-items:center;gap:2px;min-height:34px;}
.cal-day:nth-child(7n){border-right:none;}
.cal-day:hover{background:var(--acc-lt);}
.cal-day.today .cal-dn{background:var(--acc);color:#fff;border-radius:50%;width:21px;height:21px;display:inline-flex;align-items:center;justify-content:center;}
.cal-day.selected{background:var(--acc-md);}
.cal-day.other-month .cal-dn{color:var(--faint);}
.cal-dn{font-size:11.5px;font-weight:500;color:var(--ink);line-height:1;}
.cal-dots{display:flex;gap:2px;justify-content:center;min-height:4px;}
.cal-dot{width:4px;height:4px;border-radius:50%;}
.dot-scheduled{background:var(--acc);}
.dot-completed{background:var(--ok-fg);}
.dot-no_show{background:var(--danger-fg);}
.cal-legend{padding:7px 12px;border-top:1px solid var(--rule);display:flex;gap:10px;font-size:9.5px;color:var(--faint);}
.cal-legend span{display:flex;align-items:center;gap:4px;}

/* ── today panel ── */
.today-list{max-height:360px;overflow-y:auto;}
.today-item{padding:9px 14px;border-bottom:1px solid #f4f1ec;display:flex;align-items:center;gap:10px;transition:background .1s;}
.today-item:last-child{border-bottom:none;}
.today-item:hover{background:var(--plt);}
.ti-time{font-family:var(--f-m);font-size:10.5px;font-weight:700;color:var(--acc);min-width:42px;}
.ti-info{flex:1;min-width:0;}
.ti-name{font-weight:600;font-size:12.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ti-purpose{font-size:10.5px;color:var(--faint);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ti-actions{display:flex;gap:4px;flex-shrink:0;}

/* ══════════════════════════════════════════
   CUSTOM TABLE — the main attraction
══════════════════════════════════════════ */
.appt-table-wrap{display:flex;flex-direction:column;height:100%;}

/* Controls bar inside card */
.tbl-controls{padding:10px 14px;border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between;gap:10px;background:var(--plt);}
.tbl-info{font-family:var(--f-m);font-size:9.5px;color:var(--faint);letter-spacing:.3px;}
.tbl-pg{display:flex;align-items:center;gap:4px;}
.pg-btn{width:26px;height:26px;border-radius:3px;border:1.5px solid var(--rule-dk);background:#fff;color:var(--muted);font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .13s;font-family:var(--f-n);}
.pg-btn:hover:not(:disabled){border-color:var(--acc);color:var(--acc);}
.pg-btn:disabled{opacity:.35;cursor:not-allowed;}
.pg-btn.active{background:var(--acc);border-color:var(--acc);color:#fff;}
.pg-ellipsis{font-size:11px;color:var(--faint);padding:0 2px;}

/* Table itself */
.appt-tbl{width:100%;border-collapse:collapse;}
.appt-tbl thead tr{background:var(--plt);border-bottom:2px solid var(--rule-dk);}
.appt-tbl thead th{
    padding:10px 14px;
    text-align:left;
    font-size:8px;
    font-weight:700;
    letter-spacing:1.3px;
    text-transform:uppercase;
    color:var(--faint);
    white-space:nowrap;
    cursor:pointer;
    user-select:none;
    position:relative;
    transition:color .12s;
}
.appt-tbl thead th:hover{color:var(--ink);}
.appt-tbl thead th.sort-asc,
.appt-tbl thead th.sort-desc{color:var(--acc);}
.sort-icon{display:inline-block;margin-left:5px;opacity:.4;font-size:9px;transition:opacity .12s;}
.appt-tbl thead th.sort-asc .sort-icon,
.appt-tbl thead th.sort-desc .sort-icon{opacity:1;}
.appt-tbl thead th:last-child{cursor:default;}

/* Rows */
.appt-tbl tbody tr{
    border-bottom:1px solid #f0ede8;
    transition:background .1s;
    animation:rowIn .18s ease both;
}
@keyframes rowIn{from{opacity:0;transform:translateY(3px);}to{opacity:1;transform:none;}}
.appt-tbl tbody tr:hover{background:var(--acc-lt);}
.appt-tbl tbody tr:last-child{border-bottom:none;}
.appt-tbl td{padding:11px 14px;vertical-align:middle;font-size:13px;color:var(--ink);}

/* Column: code */
.col-code{font-family:var(--f-m);font-size:10px;color:var(--acc);font-weight:600;letter-spacing:.5px;}

/* Column: date+time */
.col-date-main{font-family:var(--f-m);font-size:11.5px;color:var(--ink);font-weight:600;}
.col-date-sub{font-size:10px;color:var(--faint);margin-top:1px;}

/* Column: patient */
.col-patient-name{font-weight:600;font-size:13px;color:var(--ink);}
.col-patient-contact{font-size:10.5px;color:var(--faint);margin-top:1px;font-family:var(--f-m);}

/* Column: type badge */
.type-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border:1px solid transparent;white-space:nowrap;}
.type-pill::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}

/* Column: purpose */
.col-purpose{font-size:12px;color:var(--muted);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

/* Column: worker */
.col-worker{font-size:11.5px;color:var(--muted);}

/* Status badges */
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;border:1px solid transparent;white-space:nowrap;}
.status-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}
.b-scheduled{background:#edf3fa;color:#1a3a5c;border-color:#bfdbfe80;}
.b-completed{background:var(--ok-bg);color:var(--ok-fg);border-color:#a7f3d080;}
.b-cancelled{background:#f3f1ec;color:#6a6a6a;border-color:#d8d4cc80;}
.b-no_show{background:var(--danger-bg);color:var(--danger-fg);border-color:#fca5a580;}

/* Column: actions */
.action-group{display:flex;align-items:center;gap:4px;}
.act-btn{display:inline-flex;align-items:center;justify-content:center;height:28px;padding:0 10px;border-radius:3px;font-family:var(--f-n);font-size:10px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .13s;white-space:nowrap;gap:4px;}
.act-view{background:#fff;border-color:var(--rule-dk);color:var(--muted);}
.act-view:hover{border-color:var(--acc);color:var(--acc);background:var(--acc-lt);}
.act-edit{background:#fff;border-color:var(--rule-dk);color:var(--muted);}
.act-edit:hover{border-color:var(--acc);color:var(--acc);background:var(--acc-lt);}
.act-done{background:var(--ok-bg);border-color:#a7f3d080;color:var(--ok-fg);width:28px;padding:0;}
.act-done:hover{background:var(--ok-fg);color:#fff;border-color:var(--ok-fg);}
.act-ns{background:var(--danger-bg);border-color:#fca5a580;color:var(--danger-fg);width:28px;padding:0;}
.act-ns:hover{background:var(--danger-fg);color:#fff;border-color:var(--danger-fg);}
.act-divider{width:1px;height:16px;background:var(--rule-dk);margin:0 2px;flex-shrink:0;}

/* empty state */
.tbl-empty{padding:56px 20px;text-align:center;}
.tbl-empty-icon{font-size:28px;margin-bottom:10px;opacity:.25;}
.tbl-empty-text{font-size:13px;color:var(--faint);font-style:italic;}

/* skeleton loader */
.skeleton{display:inline-block;height:12px;border-radius:3px;background:linear-gradient(90deg,#f0ede8 25%,#e8e4de 50%,#f0ede8 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;}
@keyframes shimmer{0%{background-position:200% 0;}100%{background-position:-200% 0;}}

/* ── status badge helper (also used in today panel) ── */
.badge-sm{display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:20px;font-size:8.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;border:1px solid transparent;white-space:nowrap;}
.badge-sm::before{content:'';width:4px;height:4px;border-radius:50%;background:currentColor;flex-shrink:0;}

/* ── btn ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 15px;border-radius:3px;font-family:var(--f-n);font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .13s;white-space:nowrap;}
.btn-acc{background:var(--acc);border-color:var(--acc);color:#fff;}
.btn-acc:hover{filter:brightness(1.08);}
.btn-ghost{background:#fff;border-color:var(--rule-dk);color:var(--muted);}
.btn-ghost:hover{border-color:var(--acc);color:var(--acc);}

/* ── form ── */
.fg{margin-bottom:13px;}
.fg-lbl{display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:5px;}
.req{color:var(--danger-fg);}
.fg-in,.fg-sel,.fg-ta{width:100%;padding:9px 12px;border:1.5px solid var(--rule-dk);border-radius:3px;font-family:var(--f-n);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .14s,box-shadow .14s;}
.fg-in:focus,.fg-sel:focus,.fg-ta:focus{border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-lt);}
.fg-in::placeholder{color:var(--faint);font-style:italic;font-size:12px;}
.fg-ta{resize:vertical;min-height:66px;}
.fg-hint{font-size:10px;color:var(--faint);margin-top:3px;}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.form-section{padding:14px 18px 0;border-top:1px solid var(--rule);}
.form-section:first-child{border-top:none;padding-top:18px;}
.fs-lbl{font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:12px;}
.fs-lbl::after{content:'';flex:1;height:1px;background:var(--rule);}
.fs-body{padding-bottom:14px;}
.submit-bar{padding:11px 16px;border-top:1px solid var(--rule);background:var(--plt);display:flex;justify-content:flex-end;gap:8px;}

/* ── modal ── */
.ui-dialog{border:1px solid var(--rule-dk)!important;border-radius:4px!important;box-shadow:var(--sh-md)!important;padding:0!important;font-family:var(--f-n)!important;}
.ui-dialog-titlebar{background:var(--acc)!important;border:none!important;padding:11px 15px!important;border-radius:4px 4px 0 0!important;}
.ui-dialog-title{font-family:var(--f-n)!important;font-size:11px!important;font-weight:700!important;letter-spacing:1px!important;text-transform:uppercase!important;color:#fff!important;}
.ui-dialog-titlebar-close{background:rgba(255,255,255,.15)!important;border:1px solid rgba(255,255,255,.25)!important;border-radius:3px!important;color:#fff!important;width:24px!important;height:24px!important;top:50%!important;transform:translateY(-50%)!important;}
.ui-dialog-content{padding:0!important;}
.ui-autocomplete{z-index:9999!important;max-height:200px;overflow-y:auto;border-radius:3px!important;border:1.5px solid var(--rule-dk)!important;box-shadow:var(--sh-md)!important;}
.ui-menu-item-wrapper{padding:8px 13px!important;font-size:13px!important;}
.ui-state-active{background:var(--acc-lt)!important;color:var(--ink)!important;}
</style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
<?php include_once '../layout/navbar.php'; ?>
<div class="flex h-full" style="background:var(--bg);">
    <?php include_once '../layout/sidebar.php'; ?>
    <main class="flex-1 h-screen overflow-y-auto page">

        <!-- ── Header ── -->
        <div class="hdr">
            <div class="hdr-inner">
                <div>
                    <div class="eyebrow">Barangay Bombongan — Health Center</div>
                    <div class="page-title">Appointments</div>
                    <div class="page-sub">Schedule, track, and manage patient appointments</div>
                </div>
                <div style="display:flex;gap:7px;">
                    <button class="btn btn-ghost" id="btnPrint">↗ Print</button>
                    <button class="btn btn-acc"   id="btnAdd">+ New Appointment</button>
                </div>
            </div>
        </div>
        <div class="accent-bar"></div>

        <!-- ── Toolbar ── -->
        <div class="toolbar">
            <input type="text" id="tbSearch" class="tb-search" placeholder="Search patient or purpose…">
            <select id="tbStatus" class="tb-select">
                <option value="all">All statuses</option>
                <option value="scheduled">Scheduled</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="no_show">No Show</option>
            </select>
            <select id="tbType" class="tb-select">
                <option value="all">All types</option>
                <option value="general">General</option>
                <option value="maternal">Maternal</option>
                <option value="family_planning">Family Planning</option>
                <option value="prenatal">Prenatal</option>
                <option value="postnatal">Postnatal</option>
                <option value="child_nutrition">Child Nutrition</option>
                <option value="immunization">Immunization</option>
                <option value="dental">Dental</option>
                <option value="other">Other</option>
            </select>
            <input type="date" id="tbFrom" class="tb-select" style="font-size:12px;">
            <span style="font-size:10px;color:var(--faint);padding:0 2px;">to</span>
            <input type="date" id="tbTo" class="tb-select" style="font-size:12px;">
            <div class="tb-sep"></div>
            <div class="tb-chip">
                <span class="tb-chip-dot dot-scheduled"></span>
                <span id="cntScheduled">—</span> scheduled
            </div>
            <div class="tb-chip">
                <span class="tb-chip-dot dot-completed"></span>
                <span id="cntToday">—</span> today
            </div>
        </div>

        <div class="body">

            <!-- ── LEFT: calendar + today ── -->
            <div style="display:flex;flex-direction:column;gap:14px;">

                <!-- mini calendar -->
                <div class="card">
                    <div class="cal-nav">
                        <button class="cal-nav-btn" id="calPrev">‹</button>
                        <div class="cal-month" id="calTitle">—</div>
                        <button class="cal-nav-btn" id="calNext">›</button>
                    </div>
                    <div class="cal-grid" id="calDOW">
                        <?php foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $d): ?>
                        <div class="cal-dow"><?= $d ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cal-grid" id="calDays"></div>
                    <div class="cal-legend">
                        <span><span class="cal-dot dot-scheduled" style="display:inline-block;width:7px;height:7px;border-radius:50%;"></span>Scheduled</span>
                        <span><span class="cal-dot dot-completed" style="display:inline-block;width:7px;height:7px;border-radius:50%;"></span>Completed</span>
                        <span><span class="cal-dot dot-no_show"   style="display:inline-block;width:7px;height:7px;border-radius:50%;"></span>No show</span>
                    </div>
                </div>

                <!-- today's appointments -->
                <div class="card">
                    <div class="card-head">
                        <div class="card-title">Today's Appointments</div>
                        <span style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);"><?= date('d M Y') ?></span>
                    </div>
                    <div class="today-list" id="todayList">
                        <div style="padding:18px;text-align:center;color:var(--faint);font-size:12px;">Loading…</div>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT: custom table ── -->
            <div class="card appt-table-wrap">
                <div class="card-head">
                    <div class="card-title">All Appointments</div>
                    <span id="listCount" style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);">—</span>
                </div>

                <!-- table scrollable area -->
                <div style="overflow-x:auto;">
                    <table class="appt-tbl" id="apptTable">
                        <thead>
                            <tr>
                                <th data-col="appt_code"   data-dir="asc">Code <span class="sort-icon">↕</span></th>
                                <th data-col="appt_date"   data-dir="asc" class="sort-asc">Date / Time <span class="sort-icon">↑</span></th>
                                <th data-col="full_name"   data-dir="asc">Patient <span class="sort-icon">↕</span></th>
                                <th data-col="appt_type"   data-dir="asc">Type <span class="sort-icon">↕</span></th>
                                <th data-col="purpose"     data-dir="asc">Purpose <span class="sort-icon">↕</span></th>
                                <th data-col="health_worker" data-dir="asc">Worker <span class="sort-icon">↕</span></th>
                                <th data-col="status"      data-dir="asc">Status <span class="sort-icon">↕</span></th>
                                <th style="width:140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="apptTbody">
                            <!-- skeleton rows on first load -->
                            <?php for($i=0;$i<6;$i++): ?>
                            <tr>
                                <?php for($j=0;$j<8;$j++): ?>
                                <td><span class="skeleton" style="width:<?= [60,80,120,70,140,80,70,90][$j] ?>px;"></span></td>
                                <?php endfor; ?>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <!-- pagination bar -->
                <div class="tbl-controls" id="tblControls">
                    <span class="tbl-info" id="tblInfo">—</span>
                    <div class="tbl-pg" id="tblPagination"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- ── ADD / EDIT MODAL ── -->
<div id="apptModal" title="New Appointment" class="hidden">
<form id="apptForm" style="max-height:74vh;overflow-y:auto;">
    <input type="hidden" name="id"          id="af_id">
    <input type="hidden" name="resident_id" id="af_rid">

    <div class="form-section" style="border-top:none;padding-top:18px;">
        <div class="fs-lbl">Patient</div>
        <div class="fs-body">
            <div class="fg">
                <label class="fg-lbl">Resident / Patient <span class="req">*</span></label>
                <input type="text" id="af_name" class="fg-in" placeholder="Type to search…" autocomplete="off">
                <div class="fg-hint">Select from the dropdown — required</div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="fs-lbl">Schedule</div>
        <div class="fs-body">
            <div class="g3">
                <div class="fg">
                    <label class="fg-lbl">Date <span class="req">*</span></label>
                    <input type="date" name="appt_date" id="af_date" class="fg-in">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Time <span class="req">*</span></label>
                    <input type="time" name="appt_time" id="af_time" class="fg-in">
                </div>
                <div class="fg">
                    <label class="fg-lbl">Status</label>
                    <select name="status" id="af_status" class="fg-sel">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="fs-lbl">Details</div>
        <div class="fs-body">
            <div class="g2">
                <div class="fg">
                    <label class="fg-lbl">Appointment Type <span class="req">*</span></label>
                    <select name="appt_type" class="fg-sel">
                        <option value="general">General</option>
                        <option value="maternal">Maternal</option>
                        <option value="family_planning">Family Planning</option>
                        <option value="prenatal">Prenatal</option>
                        <option value="postnatal">Postnatal</option>
                        <option value="child_nutrition">Child Nutrition</option>
                        <option value="immunization">Immunization</option>
                        <option value="dental">Dental</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fg-lbl">Health Worker</label>
                    <input type="text" name="health_worker" id="af_worker" class="fg-in"
                           value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" autocomplete="off">
                </div>
            </div>
            <div class="fg">
                <label class="fg-lbl">Purpose / Reason for Visit <span class="req">*</span></label>
                <input type="text" name="purpose" class="fg-in"
                       placeholder="e.g. Follow-up check, BCG vaccination, Prenatal Visit 1…" autocomplete="off">
            </div>
            <div class="fg">
                <label class="fg-lbl">Notes</label>
                <textarea name="notes" class="fg-ta" placeholder="Additional instructions, preparations needed…"></textarea>
            </div>
        </div>
    </div>

    <div class="submit-bar">
        <button type="button" class="btn btn-ghost" onclick="$('#apptModal').dialog('close')">Cancel</button>
        <button type="submit" class="btn btn-acc" id="afSubmit">Save Appointment</button>
    </div>
</form>
</div>

<!-- ── VIEW MODAL ── -->
<div id="viewModal" title="Appointment Detail" class="hidden">
    <div id="viewBody" style="padding:18px;max-height:70vh;overflow-y:auto;"></div>
</div>

<script>
const API = 'api/appointments_api.php';

/* ── State ── */
let allRows    = [];          // full dataset from server
let filtered   = [];          // after client-side search
let sortCol    = 'appt_date';
let sortDir    = 'asc';
let currentPage = 1;
const PAGE_SIZE = 20;

let calYear  = new Date().getFullYear();
let calMonth = new Date().getMonth();
let calData  = {};

$(function(){
    $('body').show();

    function esc(s){ const d=document.createElement('div'); d.textContent=String(s??''); return d.innerHTML; }

    /* ── Type palette ── */
    const typeStyle = {
        general:        {c:'#5a5a5a', bg:'#f3f1ec'},
        maternal:       {c:'#9f1239', bg:'#fff1f2'},
        family_planning:{c:'#1e40af', bg:'#eff6ff'},
        prenatal:       {c:'#92400e', bg:'#fffbeb'},
        postnatal:      {c:'#134e4a', bg:'#f0fdfa'},
        child_nutrition:{c:'#14532d', bg:'#f0fdf4'},
        immunization:   {c:'#4c1d95', bg:'#f5f3ff'},
        dental:         {c:'#0c444e', bg:'#e0f5f8'},
        other:          {c:'#5a5a5a', bg:'#f3f1ec'},
    };
    function typePill(t){
        const s = typeStyle[t] || typeStyle.general;
        const lbl = (t||'general').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
        return `<span class="type-pill" style="background:${s.bg};color:${s.c};border-color:${s.c}30;">${esc(lbl)}</span>`;
    }

    /* ── Status badge ── */
    const statusCls = {scheduled:'b-scheduled',completed:'b-completed',cancelled:'b-cancelled',no_show:'b-no_show'};
    const statusLbl = {scheduled:'Scheduled',completed:'Completed',cancelled:'Cancelled',no_show:'No Show'};
    function statusBadge(s, cls='status-badge'){
        return `<span class="${cls} ${statusCls[s]||'b-scheduled'}">${esc(statusLbl[s]||s)}</span>`;
    }

    /* ── Format date ── */
    function fmtDate(d){
        if(!d) return '—';
        const [y,m,day] = d.split('-');
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return `${parseInt(day)} ${months[parseInt(m)-1]} ${y}`;
    }

    /* ════════════════════════════
       CUSTOM TABLE RENDERER
    ════════════════════════════ */
    function renderTable(){
        const tbody = document.getElementById('apptTbody');

        if(!filtered.length){
            tbody.innerHTML = `<tr><td colspan="8" style="padding:0;">
                <div class="tbl-empty">
                    <div class="tbl-empty-icon">📋</div>
                    <div class="tbl-empty-text">No appointments found for the selected filters.</div>
                </div></td></tr>`;
            renderPagination(0);
            return;
        }

        const start = (currentPage-1) * PAGE_SIZE;
        const page  = filtered.slice(start, start + PAGE_SIZE);

        let html = '';
        page.forEach((r, i) => {
            const time = r.appt_time ? r.appt_time.substring(0,5) : '';
            const isScheduled = r.status === 'scheduled';

            const actionBtns = `
                <div class="action-group">
                    <button class="act-btn act-view viewBtn" data-id="${r.id}" title="View details">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        View
                    </button>
                    <button class="act-btn act-edit editBtn" data-id="${r.id}" title="Edit">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit
                    </button>
                    ${isScheduled ? `
                    <span class="act-divider"></span>
                    <button class="act-btn act-done doneBtn" data-id="${r.id}" title="Mark completed">✓</button>
                    <button class="act-btn act-ns nsBtn"   data-id="${r.id}" title="Mark no-show">✕</button>
                    ` : ''}
                </div>`;

            html += `<tr style="animation-delay:${i*18}ms">
                <td><span class="col-code">${esc(r.appt_code||'—')}</span></td>
                <td>
                    <div class="col-date-main">${esc(fmtDate(r.appt_date))}</div>
                    ${time ? `<div class="col-date-sub">${esc(time)}</div>` : ''}
                </td>
                <td>
                    <div class="col-patient-name">${esc(r.full_name||'—')}</div>
                    ${r.contact_no ? `<div class="col-patient-contact">${esc(r.contact_no)}</div>` : ''}
                </td>
                <td>${typePill(r.appt_type)}</td>
                <td><span class="col-purpose" title="${esc(r.purpose||'')}">${esc((r.purpose||'—').substring(0,55))}${(r.purpose||'').length>55?'…':''}</span></td>
                <td><span class="col-worker">${esc(r.health_worker||'—')}</span></td>
                <td>${statusBadge(r.status)}</td>
                <td>${actionBtns}</td>
            </tr>`;
        });

        tbody.innerHTML = html;
        renderPagination(filtered.length);
        $('#tblInfo').text(`${start+1}–${Math.min(start+PAGE_SIZE, filtered.length)} of ${filtered.length} record${filtered.length===1?'':'s'}`);
    }

    function renderPagination(total){
        const pages = Math.ceil(total / PAGE_SIZE);
        if(pages <= 1){ $('#tblPagination').html(''); return; }

        let html = `<button class="pg-btn" id="pgPrev" ${currentPage===1?'disabled':''}>‹</button>`;

        // smart page numbers
        const range = [];
        for(let p=1; p<=pages; p++){
            if(p===1||p===pages||Math.abs(p-currentPage)<=1) range.push(p);
            else if(range[range.length-1]!=='…') range.push('…');
        }
        range.forEach(p=>{
            if(p==='…') html+=`<span class="pg-ellipsis">…</span>`;
            else html+=`<button class="pg-btn${p===currentPage?' active':''}" data-page="${p}">${p}</button>`;
        });

        html += `<button class="pg-btn" id="pgNext" ${currentPage===pages?'disabled':''}>›</button>`;
        $('#tblPagination').html(html);
    }

    /* Pagination events */
    $(document).on('click','#pgPrev',function(){ if(currentPage>1){ currentPage--; renderTable(); }});
    $(document).on('click','#pgNext',function(){
        const pages=Math.ceil(filtered.length/PAGE_SIZE);
        if(currentPage<pages){ currentPage++; renderTable(); }
    });
    $(document).on('click','.pg-btn[data-page]',function(){
        currentPage=parseInt($(this).data('page')); renderTable();
    });

    /* ── Sort ── */
    function applySort(){
        filtered.sort((a,b)=>{
            let va = a[sortCol]||'', vb = b[sortCol]||'';
            const cmp = String(va).localeCompare(String(vb), undefined, {numeric:true});
            return sortDir==='asc' ? cmp : -cmp;
        });
    }

    $('#apptTable thead th[data-col]').on('click',function(){
        const col = $(this).data('col');
        if(col===sortCol){ sortDir = sortDir==='asc'?'desc':'asc'; }
        else { sortCol=col; sortDir='asc'; }
        // update header classes
        $('#apptTable thead th').removeClass('sort-asc sort-desc');
        $(this).addClass(sortDir==='asc'?'sort-asc':'sort-desc');
        // update sort icons
        $('#apptTable thead th .sort-icon').text('↕');
        $(this).find('.sort-icon').text(sortDir==='asc'?'↑':'↓');
        applySort();
        currentPage=1;
        renderTable();
    });

    /* ── Client filter (search only, server handles status/type/date) ── */
    function clientFilter(){
        const q = $('#tbSearch').val().toLowerCase().trim();
        if(!q){ filtered=[...allRows]; }
        else {
            filtered = allRows.filter(r=>{
                return (r.full_name||'').toLowerCase().includes(q)
                    || (r.purpose||'').toLowerCase().includes(q)
                    || (r.appt_code||'').toLowerCase().includes(q)
                    || (r.health_worker||'').toLowerCase().includes(q);
            });
        }
        applySort();
        currentPage=1;
        renderTable();
    }

    /* ── Load from server ── */
    function loadList(){
        const from=$('#tbFrom').val()||'';
        const to  =$('#tbTo').val()||'';
        const fromFinal=from||(calYear+'-'+String(calMonth+1).padStart(2,'0')+'-01');
        const toFinal  =to  ||(new Date(calYear,calMonth+1,0).toISOString().slice(0,10));

        $.getJSON(API,{action:'list',from:fromFinal,to:toFinal,status:$('#tbStatus').val(),type:$('#tbType').val()},function(res){
            if(res.status!=='ok') return;
            allRows = res.data?.data || [];
            const sched = allRows.filter(r=>r.status==='scheduled').length;
            $('#cntScheduled').text(sched);
            $('#listCount').text(allRows.length+(allRows.length===1?' RECORD':' RECORDS'));
            clientFilter();
        });
    }

    let searchTimer;
    $('#tbSearch').on('input',function(){ clearTimeout(searchTimer); searchTimer=setTimeout(clientFilter,220); });
    $('#tbStatus,#tbType').on('change',loadList);
    $('#tbFrom,#tbTo').on('change',loadList);

    /* ════════════════════════════
       CALENDAR
    ════════════════════════════ */
    function renderCal(){
        const months=['January','February','March','April','May','June','July','August','September','October','November','December'];
        $('#calTitle').text(months[calMonth]+' '+calYear);
        const today=new Date();
        const firstDay=new Date(calYear,calMonth,1).getDay();
        const daysInMonth=new Date(calYear,calMonth+1,0).getDate();
        const daysInPrev=new Date(calYear,calMonth,0).getDate();
        let html='';
        for(let i=firstDay-1;i>=0;i--)
            html+=`<div class="cal-day other-month"><div class="cal-dn">${daysInPrev-i}</div></div>`;
        for(let d=1;d<=daysInMonth;d++){
            const ds=calYear+'-'+String(calMonth+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
            const isToday=(today.getFullYear()===calYear&&today.getMonth()===calMonth&&today.getDate()===d);
            const info=calData[ds]||{};
            let dots='';
            if(info.scheduled) dots+=`<div class="cal-dot dot-scheduled"></div>`;
            if(info.completed) dots+=`<div class="cal-dot dot-completed"></div>`;
            if(info.no_show)   dots+=`<div class="cal-dot dot-no_show"></div>`;
            html+=`<div class="cal-day${isToday?' today':''}" data-date="${ds}">
                <div class="cal-dn">${d}</div>
                <div class="cal-dots">${dots}</div>
            </div>`;
        }
        const remaining=(7-((firstDay+daysInMonth)%7))%7;
        for(let d=1;d<=remaining;d++)
            html+=`<div class="cal-day other-month"><div class="cal-dn">${d}</div></div>`;
        $('#calDays').html(html);
    }

    function loadCalData(){
        const m=calYear+'-'+String(calMonth+1).padStart(2,'0');
        $.getJSON(API,{action:'calendar',month:m},function(res){
            if(res.status!=='ok') return;
            calData=res.data?.calendar||{};
            renderCal();
        });
    }

    $('#calPrev').on('click',function(){ calMonth--; if(calMonth<0){calMonth=11;calYear--;} loadCalData(); loadList(); });
    $('#calNext').on('click',function(){ calMonth++; if(calMonth>11){calMonth=0;calYear++;} loadCalData(); loadList(); });

    $(document).on('click','.cal-day[data-date]',function(){
        $('.cal-day').removeClass('selected');
        $(this).addClass('selected');
        const date=$(this).data('date');
        $('#tbFrom').val(date);
        $('#tbTo').val(date);
        loadList();
    });

    /* ════════════════════════════
       TODAY PANEL
    ════════════════════════════ */
    function loadToday(){
        $.getJSON(API,{action:'today'},function(res){
            if(res.status!=='ok') return;
            const rows=res.data?.data||[];
            $('#cntToday').text(rows.length);
            if(!rows.length){
                $('#todayList').html('<div style="padding:20px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">No appointments today.</div>');
                return;
            }
            let html='';
            rows.forEach(r=>{
                const t=r.appt_time?r.appt_time.substring(0,5):'—';
                html+=`<div class="today-item">
                    <div class="ti-time">${esc(t)}</div>
                    <div class="ti-info">
                        <div class="ti-name">${esc(r.full_name)}</div>
                        <div class="ti-purpose">${esc(r.purpose)}</div>
                    </div>
                    <div class="ti-actions">
                        ${statusBadge(r.status,'badge-sm')}
                        ${r.status==='scheduled'?`<button class="act-btn act-done doneBtn" data-id="${r.id}" style="margin-left:4px;" title="Mark done">✓</button>`:''}
                    </div>
                </div>`;
            });
            $('#todayList').html(html);
        });
    }

    /* ════════════════════════════
       ADD / EDIT MODAL
    ════════════════════════════ */
    $('#apptModal').dialog({autoOpen:false,modal:true,width:680,resizable:false,
        open:function(){ $(this).find(':input:first').blur(); }
    });

    $('#btnAdd').on('click',function(){
        $('#apptForm')[0].reset();
        $('#af_id').val('');
        $('#af_rid').val('');
        $('#af_name').val('');
        $('#af_date').val(new Date().toISOString().slice(0,10));
        $('#af_time').val('08:00');
        $('#af_status').val('scheduled');
        $('#af_worker').val(<?= json_encode($_SESSION['name'] ?? '') ?>);
        $('#apptModal').dialog('option','title','New Appointment').dialog('open');
    });

    /* Autocomplete */
    $('#af_name').autocomplete({
        minLength:1, appendTo:'#apptModal',
        position:{my:'left top',at:'left bottom',collision:'none'},
        source:(req,res)=>$.getJSON('../consultation/api/resident.php',{term:req.term},res),
        select(e,ui){
            e.preventDefault();
            $('#af_rid').val(ui.item.id);
            $('#af_name').val(ui.item.label);
            return false;
        }
    });
    $('#af_name').on('autocompleteopen',()=>$('.ui-autocomplete').css('z-index',9999));
    $('#af_name').on('input',()=>$('#af_rid').val(''));

    /* Submit */
    $('#apptForm').on('submit',function(e){
        e.preventDefault();
        if(!$('#af_rid').val()){ alert('Please select a patient.'); return; }
        const $btn=$('#afSubmit');
        $btn.prop('disabled',true).text('Saving…');
        $.ajax({
            url:API+'?action=save', type:'POST', data:$(this).serialize(), dataType:'json',
            success(res){
                if(res.status!=='ok'){ alert(res.message||'Save failed.'); return; }
                $('#apptModal').dialog('close');
                loadList(); loadToday(); loadCalData();
            },
            error(xhr){ alert('Server error ('+xhr.status+').'); },
            complete(){ $btn.prop('disabled',false).text('Save Appointment'); }
        });
    });

    /* ════════════════════════════
       VIEW MODAL
    ════════════════════════════ */
    $('#viewModal').dialog({autoOpen:false,modal:true,width:520,resizable:false,
        buttons:{'Close':function(){$(this).dialog('close');}}
    });

    $(document).on('click','.viewBtn',function(){
        $.getJSON(API,{action:'get',id:$(this).data('id')},function(res){
            if(res.status!=='ok') return;
            const d=res.data.data||res.data;
            const fields=[
                ['Appointment Code', d.appt_code],
                ['Date',             d.appt_date ? fmtDate(d.appt_date)+' '+(d.appt_time?.substring(0,5)||'') : ''],
                ['Patient',          d.full_name],
                ['Contact',          d.contact_no],
                ['Type',             (d.appt_type||'').replace(/_/g,' ')],
                ['Purpose',          d.purpose],
                ['Health Worker',    d.health_worker],
                ['Status',           d.status],
                ['Notes',            d.notes],
            ];
            let html=`<div style="border-bottom:1px solid var(--rule);padding:12px 18px 14px;background:var(--plt);">
                <div style="font-weight:600;font-size:15px;color:var(--ink);margin-bottom:2px;">${esc(d.full_name||'—')}</div>
                <div style="font-size:10.5px;color:var(--faint);font-family:var(--f-m);">${esc(d.appt_code||'')}</div>
            </div>
            <table style="width:100%;border-collapse:collapse;">`;
            fields.forEach(([l,v])=>{
                if(!v) return;
                html+=`<tr style="border-bottom:1px solid var(--rule);">
                    <td style="padding:9px 18px;font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--faint);white-space:nowrap;width:140px;vertical-align:top;padding-top:11px;">${esc(l)}</td>
                    <td style="padding:9px 18px;font-size:13px;color:var(--ink);">${l==='Status'?statusBadge(v):esc(String(v))}</td>
                </tr>`;
            });
            html+='</table>';
            $('#viewBody').html(html);
            $('#viewModal').dialog('option','title','Appointment Detail').dialog('open');
        });
    });

    /* ════════════════════════════
       EDIT
    ════════════════════════════ */
    $(document).on('click','.editBtn',function(){
        $.getJSON(API,{action:'get',id:$(this).data('id')},function(res){
            if(res.status!=='ok') return;
            const d=res.data?.data||res.data;
            $('#apptForm')[0].reset();
            $('#af_id').val(d.id);
            $('#af_rid').val(d.resident_id);
            $('#af_name').val(d.full_name);
            $('#af_date').val(d.appt_date);
            $('#af_time').val(d.appt_time?.substring(0,5)||'');
            $('#af_status').val(d.status||'scheduled');
            $('#af_worker').val(d.health_worker||'');
            $('[name="appt_type"]').val(d.appt_type||'general');
            $('[name="purpose"]').val(d.purpose||'');
            $('[name="notes"]').val(d.notes||'');
            $('#apptModal').dialog('option','title','Edit — '+d.full_name).dialog('open');
        });
    });

    /* ════════════════════════════
       QUICK STATUS
    ════════════════════════════ */
    function quickStatus(id, status){
        $.post(API+'?action=update_status',{id,status},function(res){
            const r=typeof res==='string'?JSON.parse(res):res;
            if(r.status!=='ok'){ alert(r.message||'Failed.'); return; }
            loadList(); loadToday(); loadCalData();
        });
    }
    $(document).on('click','.doneBtn',function(){ quickStatus($(this).data('id'),'completed'); });
    $(document).on('click','.nsBtn',  function(){ if(confirm('Mark as No Show?')) quickStatus($(this).data('id'),'no_show'); });

    /* ── Print ── */
    $('#btnPrint').on('click',function(){
        const p=new URLSearchParams({
            from:$('#tbFrom').val()||'', to:$('#tbTo').val()||'',
            status:$('#tbStatus').val(), type:$('#tbType').val()
        });
        window.open('print.php?'+p.toString(),'_blank');
    });

    /* ── Boot ── */
    const today=new Date();
    const y=today.getFullYear(), m=today.getMonth()+1;
    $('#tbFrom').val(y+'-'+String(m).padStart(2,'0')+'-01');
    $('#tbTo').val(today.toISOString().slice(0,10));
    loadCalData();
    loadList();
    loadToday();
});
</script>
</body>
</html>
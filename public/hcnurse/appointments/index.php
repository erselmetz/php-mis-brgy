<?php
/**
 * Appointments — Main Page
 * public/hcnurse/appointments/index.php
 *
 * Features:
 * - Mini calendar with appointment dots
 * - Today's appointments panel
 * - Full filterable list
 * - Add / Edit modal
 * - Quick status update (mark completed / no-show)
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
    --ok-bg:#edfaf3;--ok-fg:#1a5c35;
    --warn-bg:#fef9ec;--warn-fg:#7a5700;
    --danger-bg:#fdeeed;--danger-fg:#7a1f1a;
    --info-bg:#edf3fa;--info-fg:#1a3a5c;
    --f-s:'Source Serif 4',Georgia,serif;
    --f-n:'Source Sans 3','Segoe UI',sans-serif;
    --f-m:'Source Code Pro','Courier New',monospace;
    --sh:0 1px 2px rgba(0,0,0,.07),0 3px 14px rgba(0,0,0,.05);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:var(--f-n);}
.page{background:var(--bg);min-height:100%;padding-bottom:56px;}

/* header */
.hdr{background:var(--p);border-bottom:1px solid var(--rule);}
.hdr-inner{padding:18px 28px 18px;display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.eyebrow{font-size:8.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:5px;}
.eyebrow::before{content:'';width:18px;height:2px;background:var(--acc);display:inline-block;}
.page-title{font-family:var(--f-s);font-size:21px;font-weight:700;color:var(--ink);margin-bottom:3px;}
.page-sub{font-size:12px;color:var(--faint);font-style:italic;}
.accent-bar{height:3px;background:linear-gradient(to right,var(--acc),transparent);}

/* toolbar */
.toolbar{background:var(--plt);border-bottom:1px solid var(--rule);padding:10px 28px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.tb-search{padding:6px 11px;border:1.5px solid var(--rule-dk);border-radius:2px;font-size:13px;color:var(--ink);background:#fff;outline:none;width:240px;transition:border-color .13s;}
.tb-search:focus{border-color:var(--acc);}
.tb-search::placeholder{color:var(--faint);font-style:italic;font-size:12px;}
.tb-select{padding:6px 10px;border:1.5px solid var(--rule-dk);border-radius:2px;font-size:12.5px;background:#fff;color:var(--ink);outline:none;cursor:pointer;transition:border-color .13s;}
.tb-select:focus{border-color:var(--acc);}
.tb-sep{flex:1;}
.tb-stat{display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:2px;background:var(--p);border:1px solid var(--rule);font-family:var(--f-m);font-size:11px;font-weight:600;color:var(--muted);}
.tb-stat-dot{width:8px;height:8px;border-radius:50%;}

/* body grid */
.body{display:grid;grid-template-columns:280px 1fr;gap:16px;margin:16px 28px 0;}
@media(max-width:1060px){.body{grid-template-columns:1fr;}}

/* card */
.card{background:var(--p);border:1px solid var(--rule);border-radius:2px;box-shadow:var(--sh);overflow:hidden;}
.card-head{padding:10px 15px;border-bottom:1px solid var(--rule);background:var(--plt);display:flex;align-items:center;justify-content:space-between;gap:8px;}
.card-title{font-size:8.5px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:7px;}
.card-title::before{content:'';width:3px;height:11px;background:var(--acc);border-radius:1px;flex-shrink:0;}

/* mini calendar */
.cal-nav{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--rule);}
.cal-month{font-family:var(--f-s);font-size:14px;font-weight:600;color:var(--ink);}
.cal-nav-btn{background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:2px;color:var(--muted);font-size:14px;transition:background .12s;}
.cal-nav-btn:hover{background:var(--acc-lt);color:var(--acc);}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:var(--rule);margin:0;border-bottom:1px solid var(--rule);}
.cal-dow{padding:5px 0;text-align:center;font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--faint);background:var(--plt);}
.cal-day{background:var(--p);padding:5px 0 4px;text-align:center;cursor:pointer;transition:background .1s;position:relative;min-height:36px;display:flex;flex-direction:column;align-items:center;gap:2px;}
.cal-day:hover{background:var(--acc-lt);}
.cal-day.today .cal-dn{background:var(--acc);color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;}
.cal-day.selected{background:var(--acc-lt);}
.cal-day.other-month .cal-dn{color:var(--faint);}
.cal-dn{font-size:12px;font-weight:500;color:var(--ink);line-height:1;}
.cal-dots{display:flex;gap:2px;justify-content:center;min-height:5px;}
.cal-dot{width:5px;height:5px;border-radius:50%;}
.dot-scheduled{background:var(--acc);}
.dot-completed{background:var(--ok-fg);}
.dot-cancelled{background:var(--faint);}
.dot-no_show{background:var(--danger-fg);}

/* today panel */
.today-list{max-height:380px;overflow-y:auto;}
.today-item{padding:10px 14px;border-bottom:1px solid #f0ede8;display:flex;align-items:center;gap:10px;}
.today-item:last-child{border-bottom:none;}
.ti-time{font-family:var(--f-m);font-size:11px;font-weight:700;color:var(--acc);min-width:48px;}
.ti-info{flex:1;min-width:0;}
.ti-name{font-weight:600;font-size:13px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ti-purpose{font-size:11px;color:var(--muted);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ti-actions{display:flex;gap:4px;flex-shrink:0;}

/* main table */
#apptTable{width:100%!important;border-collapse:collapse;}
#apptTable thead th{padding:9px 13px;background:var(--plt);text-align:left;font-size:8px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--rule-dk);white-space:nowrap;cursor:pointer;user-select:none;}
#apptTable thead th:hover{color:var(--acc);}
#apptTable thead th.sorting_asc::after{content:' ↑';color:var(--acc);}
#apptTable thead th.sorting_desc::after{content:' ↓';color:var(--acc);}
#apptTable tbody tr{border-bottom:1px solid #f0ede8;transition:background .1s;}
#apptTable tbody tr:hover{background:var(--acc-lt);}
#apptTable td{padding:9px 13px;font-size:12.5px;color:var(--ink);vertical-align:middle;}
.tbl-wrap .dataTables_filter,.tbl-wrap .dataTables_length{display:none!important;}
.tbl-wrap .dataTables_info{padding:8px 14px;font-size:10.5px;color:var(--faint);font-family:var(--f-m);border-top:1px solid var(--rule);background:var(--plt);}
.tbl-wrap .dataTables_paginate{padding:8px 14px;border-top:1px solid var(--rule);background:var(--plt);}
.tbl-wrap .paginate_button{display:inline-flex;align-items:center;justify-content:center;min-width:26px;height:24px;padding:0 6px;border:1.5px solid var(--rule-dk)!important;border-radius:2px;font-size:11px;color:var(--muted)!important;background:#fff!important;cursor:pointer;margin:0 2px;transition:all .12s;}
.tbl-wrap .paginate_button:hover{border-color:var(--acc)!important;color:var(--acc)!important;}
.tbl-wrap .paginate_button.current{background:var(--acc)!important;border-color:var(--acc)!important;color:#fff!important;}
.tbl-wrap .paginate_button.disabled{opacity:.35!important;}

/* status badges */
.badge{display:inline-block;padding:2px 8px;border-radius:2px;font-size:8.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;border:1px solid;white-space:nowrap;}
.b-scheduled{background:#edf3fa;color:#1a3a5c;border-color:#bfdbfe;}
.b-completed{background:var(--ok-bg);color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent);}
.b-cancelled{background:#f3f1ec;color:#5a5a5a;border-color:#d8d4cc;}
.b-no_show{background:var(--danger-bg);color:var(--danger-fg);border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent);}

/* type badge */
.type-tag{display:inline-block;padding:2px 7px;border-radius:2px;font-size:8.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;}

/* btn */
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:2px;font-family:var(--f-n);font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .13s;white-space:nowrap;}
.btn-acc{background:var(--acc);border-color:var(--acc);color:#fff;}
.btn-acc:hover{filter:brightness(1.1);}
.btn-ghost{background:#fff;border-color:var(--rule-dk);color:var(--muted);}
.btn-ghost:hover{border-color:var(--acc);color:var(--acc);}
.btn-ok{background:var(--ok-bg);border-color:color-mix(in srgb,var(--ok-fg) 30%,transparent);color:var(--ok-fg);}
.btn-ok:hover{background:var(--ok-fg);color:#fff;}
.btn-danger{background:var(--danger-bg);border-color:color-mix(in srgb,var(--danger-fg) 30%,transparent);color:var(--danger-fg);}
.btn-danger:hover{background:var(--danger-fg);color:#fff;}
.btn-sm{padding:4px 10px;font-size:10px;}

/* form */
.fg{margin-bottom:12px;}
.fg-lbl{display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:5px;}
.fg-lbl .req{color:var(--danger-fg);}
.fg-in,.fg-sel,.fg-ta{width:100%;padding:9px 12px;border:1.5px solid var(--rule-dk);border-radius:2px;font-family:var(--f-n);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .14s,box-shadow .14s;}
.fg-in:focus,.fg-sel:focus,.fg-ta:focus{border-color:var(--acc);box-shadow:0 0 0 3px color-mix(in srgb,var(--acc) 10%,transparent);}
.fg-in::placeholder{color:var(--faint);font-style:italic;font-size:12px;}
.fg-ta{resize:vertical;min-height:66px;}
.fg-hint{font-size:10px;color:var(--faint);margin-top:3px;font-style:italic;}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.form-section{padding:14px 18px 0;border-top:1px solid var(--rule);}
.form-section:first-child{border-top:none;padding-top:18px;}
.fs-lbl{font-size:8px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--faint);display:flex;align-items:center;gap:8px;margin-bottom:12px;}
.fs-lbl::after{content:'';flex:1;height:1px;background:var(--rule);}
.fs-body{padding-bottom:14px;}
.submit-bar{padding:11px 16px;border-top:1px solid var(--rule);background:var(--plt);display:flex;justify-content:flex-end;gap:8px;}

/* modal */
.ui-dialog{border:1px solid var(--rule-dk)!important;border-radius:2px!important;box-shadow:0 8px 48px rgba(0,0,0,.18)!important;padding:0!important;font-family:var(--f-n)!important;}
.ui-dialog-titlebar{background:var(--acc)!important;border:none!important;padding:11px 15px!important;}
.ui-dialog-title{font-family:var(--f-n)!important;font-size:11px!important;font-weight:700!important;letter-spacing:1px!important;text-transform:uppercase!important;color:#fff!important;}
.ui-dialog-titlebar-close{background:rgba(255,255,255,.15)!important;border:1px solid rgba(255,255,255,.25)!important;border-radius:2px!important;color:#fff!important;width:24px!important;height:24px!important;top:50%!important;transform:translateY(-50%)!important;}
.ui-dialog-content{padding:0!important;}
.ui-dialog-buttonpane{background:var(--plt)!important;border-top:1px solid var(--rule)!important;padding:11px 15px!important;margin:0!important;}
.ui-dialog-buttonpane .ui-button{font-family:var(--f-n)!important;font-size:11px!important;font-weight:700!important;letter-spacing:.5px!important;text-transform:uppercase!important;padding:7px 16px!important;border-radius:2px!important;cursor:pointer!important;}
.ui-dialog-buttonpane .ui-button:first-child{background:var(--acc)!important;border:1.5px solid var(--acc)!important;color:#fff!important;}
.ui-dialog-buttonpane .ui-button:not(:first-child){background:#fff!important;border:1.5px solid var(--rule-dk)!important;color:var(--muted)!important;}
.ui-autocomplete{z-index:9999!important;max-height:200px;overflow-y:auto;}
</style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
<?php include_once '../layout/navbar.php'; ?>
<div class="flex h-full" style="background:var(--bg);">
    <?php include_once '../layout/sidebar.php'; ?>
    <main class="flex-1 h-screen overflow-y-auto page">

        <!-- header -->
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

        <!-- toolbar -->
        <div class="toolbar">
            <input type="text"   id="tbSearch"  class="tb-search"  placeholder="Search patient or purpose…">
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
            <span style="font-size:11px;color:var(--faint);">to</span>
            <input type="date" id="tbTo"   class="tb-select" style="font-size:12px;">
            <div class="tb-sep"></div>
            <div class="tb-stat" id="statScheduled">
                <div class="cal-dot dot-scheduled"></div>
                <span id="cntScheduled">—</span> scheduled
            </div>
            <div class="tb-stat" id="statToday">
                <div class="cal-dot dot-completed"></div>
                <span id="cntToday">—</span> today
            </div>
        </div>

        <div class="body">

            <!-- LEFT: calendar + today -->
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
                    <div class="cal-grid" id="calDays" style="background:var(--p);gap:0;border:none;"></div>
                    <div style="padding:8px 14px;font-size:10px;color:var(--faint);border-top:1px solid var(--rule);display:flex;gap:10px;">
                        <span><span class="cal-dot dot-scheduled" style="display:inline-block;vertical-align:middle;margin-right:3px;"></span>Scheduled</span>
                        <span><span class="cal-dot dot-completed" style="display:inline-block;vertical-align:middle;margin-right:3px;"></span>Completed</span>
                        <span><span class="cal-dot dot-no_show"   style="display:inline-block;vertical-align:middle;margin-right:3px;"></span>No show</span>
                    </div>
                </div>

                <!-- today's appointments -->
                <div class="card">
                    <div class="card-head">
                        <div class="card-title">Today's Appointments</div>
                        <span id="todayDate" style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);"><?= date('d M Y') ?></span>
                    </div>
                    <div class="today-list" id="todayList">
                        <div style="padding:16px;text-align:center;color:var(--faint);font-size:12px;">Loading…</div>
                    </div>
                </div>

            </div>

            <!-- RIGHT: main list -->
            <div class="card tbl-wrap">
                <div class="card-head">
                    <div class="card-title">All Appointments</div>
                    <span id="listCount" style="font-family:var(--f-m);font-size:9.5px;color:var(--faint);">—</span>
                </div>
                <table id="apptTable" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date / Time</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Purpose</th>
                            <th>Worker</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="apptTbody"></tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<!-- ADD / EDIT MODAL -->
<div id="apptModal" title="New Appointment" class="hidden">
<form id="apptForm" style="max-height:74vh;overflow-y:auto;">
    <input type="hidden" name="id"          id="af_id">
    <input type="hidden" name="resident_id" id="af_rid">

    <div class="form-section" style="border-top:none;padding-top:18px;">
        <div class="fs-lbl">Patient <div style="flex:1;height:1px;background:var(--rule);"></div></div>
        <div class="fs-body">
            <div class="fg">
                <label class="fg-lbl">Resident / Patient <span class="req">*</span></label>
                <input type="text" id="af_name" class="fg-in"
                       placeholder="Type to search…" autocomplete="off">
                <div class="fg-hint">Select from dropdown — required</div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="fs-lbl">Schedule <div style="flex:1;height:1px;background:var(--rule);"></div></div>
        <div class="fs-body">
            <div class="g3">
                <div class="fg">
                    <label class="fg-lbl">Date <span class="req">*</span></label>
                    <input type="date" name="appt_date" id="af_date" class="fg-in">
                </div>
                <div class="fg">
                    <label class="fg-label fg-lbl">Time <span class="req">*</span></label>
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
        <div class="fs-lbl">Details <div style="flex:1;height:1px;background:var(--rule);"></div></div>
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
        <button type="submit" class="btn btn-acc"   id="afSubmit">Save Appointment</button>
    </div>
</form>
</div>

<!-- VIEW MODAL -->
<div id="viewModal" title="Appointment Detail" class="hidden">
    <div id="viewBody" style="padding:18px;max-height:70vh;overflow-y:auto;"></div>
</div>

<script>
const API = 'api/appointments_api.php';
let calYear = new Date().getFullYear();
let calMonth= new Date().getMonth(); // 0-based
let calData = {};
let apptDT  = null;

$(function(){
    $('body').show();

    function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

    /* ── Status badge ── */
    const statusCls = {scheduled:'b-scheduled',completed:'b-completed',cancelled:'b-cancelled',no_show:'b-no_show'};
    const statusLbl = {scheduled:'Scheduled',completed:'Completed',cancelled:'Cancelled',no_show:'No Show'};
    function sb(s){ return `<span class="badge ${statusCls[s]||'b-scheduled'}">${statusLbl[s]||s}</span>`; }

    /* ── Type color ── */
    const typeColors={general:['#5a5a5a','#f3f1ec'],maternal:['#9f1239','#fff1f2'],family_planning:['#1e40af','#eff6ff'],prenatal:['#92400e','#fffbeb'],postnatal:['#134e4a','#f0fdfa'],child_nutrition:['#14532d','#f0fdf4'],immunization:['#4c1d95','#f5f3ff'],dental:['#0c444e','#e0f5f8'],other:['#5a5a5a','#f3f1ec']};
    function typeBadge(t){
        const [c,bg]=typeColors[t]||typeColors.general;
        return `<span class="type-tag" style="background:${bg};color:${c};border:1px solid ${c}33;">${esc((t||'general').replace(/_/g,' '))}</span>`;
    }

    /* ═══════════════ DataTable ═══════════════ */
    apptDT = $('#apptTable').DataTable({
        data:[],columns:[
            {data:'appt_code',render:d=>`<span style="font-family:var(--f-m);font-size:10px;color:var(--acc);">${esc(d)}</span>`},
            {data:'appt_date',render:(d,t,r)=>`<div style="font-family:var(--f-m);font-size:11px;">${esc(d)}</div><div style="font-size:10.5px;color:var(--faint);">${esc(r.appt_time?.substring(0,5)||'')}</div>`},
            {data:'full_name',render:d=>`<span style="font-weight:600;">${esc(d)}</span>`},
            {data:'appt_type',render:d=>typeBadge(d)},
            {data:'purpose',render:d=>`<span style="font-size:12.5px;color:var(--muted);">${esc((d||'').substring(0,55))}</span>`},
            {data:'health_worker',render:d=>`<span style="font-size:12px;color:var(--muted);">${esc(d||'—')}</span>`},
            {data:'status',render:d=>sb(d)},
            {data:'id',orderable:false,render:(id,t,row)=>`
                <div style="display:flex;gap:4px;">
                    <button class="btn btn-ghost btn-sm viewBtn" data-id="${id}">View</button>
                    <button class="btn btn-ghost btn-sm editBtn" data-id="${id}">Edit</button>
                    ${row.status==='scheduled'?`<button class="btn btn-ok btn-sm doneBtn" data-id="${id}">✓</button><button class="btn btn-danger btn-sm nsBtn" data-id="${id}">✕</button>`:''}
                </div>`
            }
        ],
        pageLength:20,order:[[1,'asc']],dom:'tip',
        language:{info:'_START_–_END_ of _TOTAL_',paginate:{previous:'‹',next:'›'},emptyTable:'No appointments found.'}
    });

    function loadList(){
        const from=$('#tbFrom').val()||'';
        const to  =$('#tbTo').val()||'';
        const fromFinal=from||(calYear+'-'+String(calMonth+1).padStart(2,'0')+'-01');
        const toFinal  =to  ||(new Date(calYear,calMonth+1,0).toISOString().slice(0,10));
        $.getJSON(API,{action:'list',from:fromFinal,to:toFinal,status:$('#tbStatus').val(),type:$('#tbType').val(),search:$('#tbSearch').val()},function(res){
            if(res.status!=='ok') return;
            const rows=res.data?.data||[];
            $('#listCount').text(rows.length+(rows.length===1?' RECORD':' RECORDS'));
            const sched=rows.filter(r=>r.status==='scheduled').length;
            $('#cntScheduled').text(sched);
            apptDT.clear().rows.add(rows).draw();
        });
    }

    let searchTimer;
    $('#tbSearch').on('input',function(){ clearTimeout(searchTimer); searchTimer=setTimeout(loadList,350); });
    $('#tbStatus,#tbType').on('change',loadList);
    $('#tbFrom,#tbTo').on('change',loadList);

    /* ═══════════════ Calendar ═══════════════ */
    function renderCal(){
        const months=['January','February','March','April','May','June','July','August','September','October','November','December'];
        $('#calTitle').text(months[calMonth]+' '+calYear);
        const today=new Date();
        const firstDay=new Date(calYear,calMonth,1).getDay();
        const daysInMonth=new Date(calYear,calMonth+1,0).getDate();
        const daysInPrev=new Date(calYear,calMonth,0).getDate();
        let html='';
        // prev month tail
        for(let i=firstDay-1;i>=0;i--){
            html+=`<div class="cal-day other-month"><div class="cal-dn">${daysInPrev-i}</div></div>`;
        }
        // current month
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
        // next month head
        const remaining=(7-((firstDay+daysInMonth)%7))%7;
        for(let d=1;d<=remaining;d++){
            html+=`<div class="cal-day other-month"><div class="cal-dn">${d}</div></div>`;
        }
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
        const date=$(this).data('date');
        $('#tbFrom').val(date);
        $('#tbTo').val(date);
        loadList();
    });

    /* ═══════════════ Today ═══════════════ */
    function loadToday(){
        $.getJSON(API,{action:'today'},function(res){
            if(res.status!=='ok') return;
            const rows=res.data?.data||[];
            $('#cntToday').text(rows.length);
            if(!rows.length){
                $('#todayList').html('<div style="padding:18px;text-align:center;color:var(--faint);font-size:12px;font-style:italic;">No appointments today.</div>');
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
                        ${sb(r.status)}
                        ${r.status==='scheduled'?`<button class="btn btn-ok btn-sm doneBtn" data-id="${r.id}" style="margin-left:4px;">✓</button>`:''}
                    </div>
                </div>`;
            });
            $('#todayList').html(html);
        });
    }

    /* ═══════════════ Add Modal ═══════════════ */
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

    /* ═══════════════ View ═══════════════ */
    $('#viewModal').dialog({autoOpen:false,modal:true,width:560,resizable:false,
        buttons:{'Close':function(){$(this).dialog('close');}}});

    $(document).on('click','.viewBtn',function(){
        $.getJSON(API,{action:'get',id:$(this).data('id')},function(res){
            if(res.status!=='ok') return;
            const d=res.data.data||res.data;
            const fields=[
                ['Code',d.appt_code],['Date',d.appt_date+' '+d.appt_time?.substring(0,5)],
                ['Patient',d.full_name],['Contact',d.contact_no],
                ['Type',d.appt_type],['Purpose',d.purpose],
                ['Health Worker',d.health_worker],['Status',d.status],
                ['Notes',d.notes],['Follow-up',d.follow_up_date],
            ];
            let html='<table style="width:100%;font-size:13px;border-collapse:collapse;">';
            fields.forEach(([l,v])=>{
                if(!v) return;
                html+=`<tr style="border-bottom:1px solid var(--rule);">
                    <td style="padding:8px 12px;font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--faint);white-space:nowrap;width:130px;">${esc(l)}</td>
                    <td style="padding:8px 12px;color:var(--ink);">${l==='Status'?sb(v):esc(String(v))}</td>
                </tr>`;
            });
            html+='</table>';
            $('#viewBody').html(html);
            $('#viewModal').dialog('option','title','Appointment — '+d.full_name).dialog('open');
        });
    });

    /* ═══════════════ Edit ═══════════════ */
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

    /* ═══════════════ Quick status ═══════════════ */
    function quickStatus(id, status){
        $.post(API+'?action=update_status',{id,status},function(res){
            const r=typeof res==='string'?JSON.parse(res):res;
            if(r.status!=='ok'){ alert(r.message||'Failed.'); return; }
            loadList(); loadToday(); loadCalData();
        });
    }
    $(document).on('click','.doneBtn',function(){ quickStatus($(this).data('id'),'completed'); });
    $(document).on('click','.nsBtn',  function(){ if(confirm('Mark as No Show?')) quickStatus($(this).data('id'),'no_show'); });

    /* Print */
    $('#btnPrint').on('click',function(){
        const p=new URLSearchParams({
            from:$('#tbFrom').val()||'',to:$('#tbTo').val()||'',
            status:$('#tbStatus').val(),type:$('#tbType').val()
        });
        window.open('print.php?'+p.toString(),'_blank');
    });

    /* ═══════════════ Boot ═══════════════ */
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
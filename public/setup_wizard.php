<?php
/**
 * Setup Wizard — Standalone (no app.php / no auth redirect)
 * Replaces: public/setup_wizard.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Read config directly — no includes that redirect
$cfgFile = dirname(__DIR__) . '/config.php';
$DB_HOST = 'localhost'; $DB_USER = 'root'; $DB_PASS = ''; $DB_NAME = 'php_mis_brgy';
if (file_exists($cfgFile)) {
    include_once $cfgFile;
    if (defined('DB_HOST')) $DB_HOST = DB_HOST;
    if (defined('DB_USER')) $DB_USER = DB_USER;
    if (defined('DB_PASS')) $DB_PASS = DB_PASS;
    if (defined('DB_NAME')) $DB_NAME = DB_NAME;
}

// Raw connection — no includes/db.php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = null; $db_ok = false; $db_error = null; $db_version = '';
try {
    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_errno) { $db_error = $conn->connect_error; $conn = null; }
    else { $db_ok = true; $db_version = $conn->server_info; $conn->set_charset('utf8mb4'); }
} catch (Throwable $e) { $db_error = $e->getMessage(); }

$tables_ok = false;
if ($db_ok && $conn) {
    $r = $conn->query("SHOW TABLES LIKE 'users'");
    $tables_ok = ($r && $r->num_rows > 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MIS Barangay — Setup Wizard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{
    --paper:#fdfcf9;--paper-lt:#f9f7f3;--paper-dk:#f0ede6;
    --ink:#1a1a1a;--ink-muted:#5a5a5a;--ink-faint:#a0a0a0;
    --rule:#d8d4cc;--rule-dk:#b8b4ac;
    --accent:#2d5a27;--accent-lt:#edfaf3;
    --ok-bg:#edfaf3;--ok-fg:#1a5c35;
    --warn-bg:#fef9ec;--warn-fg:#7a5700;
    --danger-bg:#fdeeed;--danger-fg:#7a1f1a;
    --info-bg:#edf3fa;--info-fg:#1a3a5c;
    --f-serif:'Source Serif 4',Georgia,serif;
    --f-sans:'Source Sans 3','Segoe UI',sans-serif;
    --f-mono:'Source Code Pro','Courier New',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:var(--f-sans);background:#edeae4;min-height:100vh;}

/* Layout */
.wiz{min-height:100vh;display:grid;grid-template-columns:270px 1fr;}
@media(max-width:800px){.wiz{grid-template-columns:1fr;}.wiz-l{display:none;}}

/* ── Left ── */
.wiz-l{background:var(--accent);display:flex;flex-direction:column;position:relative;overflow:hidden;}
.wiz-l::before{content:'';position:absolute;inset:0;pointer-events:none;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    background-size:180px;}
.wiz-lh{padding:26px 22px 20px;border-bottom:1px solid rgba(255,255,255,.1);position:relative;}
.wiz-icon{font-size:24px;margin-bottom:8px;display:block;}
.wiz-ltitle{font-family:var(--f-serif);font-size:16px;font-weight:700;color:#fff;margin-bottom:2px;}
.wiz-lsub{font-size:10.5px;color:rgba(255,255,255,.48);line-height:1.5;}

.wiz-nav-steps{flex:1;padding:14px 0;}
.ws{display:flex;align-items:flex-start;gap:11px;padding:12px 22px;cursor:pointer;transition:background .12s;position:relative;}
.ws:hover{background:rgba(255,255,255,.06);}
.ws.wa{background:rgba(255,255,255,.1);}
.ws.wa::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:rgba(255,255,255,.7);}
.wsn{width:24px;height:24px;border-radius:50%;border:1.5px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-family:var(--f-mono);font-size:9.5px;font-weight:700;color:rgba(255,255,255,.6);flex-shrink:0;margin-top:1px;transition:all .15s;}
.ws.wd .wsn{background:rgba(255,255,255,.16);border-color:rgba(255,255,255,.4);color:#fff;}
.ws.wa .wsn{background:#fff;border-color:#fff;color:var(--accent);}
.wsi{flex:1;min-width:0;}
.wst{font-size:11.5px;font-weight:600;color:rgba(255,255,255,.88);margin-bottom:1px;}
.wsd{font-size:9.5px;color:rgba(255,255,255,.4);line-height:1.4;}
.wsbadge{flex-shrink:0;padding:2px 6px;border-radius:8px;font-size:8px;font-weight:700;white-space:nowrap;}
.bok{background:rgba(255,255,255,.12);color:rgba(255,255,255,.72);}
.bwarn{background:rgba(255,200,0,.16);color:#fcd34d;}
.berr{background:rgba(255,80,80,.18);color:#fca5a5;}

.wiz-lf{padding:12px 22px;border-top:1px solid rgba(255,255,255,.08);font-family:var(--f-mono);font-size:7.5px;letter-spacing:.8px;text-transform:uppercase;color:rgba(255,255,255,.2);}

/* ── Right ── */
.wiz-r{background:var(--paper);display:flex;flex-direction:column;min-height:100vh;}
.wiz-hd{padding:22px 34px 16px;border-bottom:1px solid var(--rule);background:var(--paper-lt);position:relative;}
.wiz-hd::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(to right,var(--accent),transparent);}
.wiz-hdtitle{font-family:var(--f-serif);font-size:18px;font-weight:700;color:var(--ink);letter-spacing:-.2px;}
.wiz-hdsub{font-size:11.5px;color:var(--ink-faint);margin-top:3px;font-style:italic;}

.wiz-body{flex:1;padding:26px 34px;overflow-y:auto;}

.sp{display:none;}.sp.spa{display:block;}

/* Alerts */
.al{display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border-radius:2px;margin-bottom:18px;border:1px solid;border-left-width:3px;font-size:12.5px;}
.ali{font-size:13px;flex-shrink:0;margin-top:1px;}
.alt{font-weight:700;margin-bottom:1px;font-size:12.5px;}
.alm{font-size:11.5px;opacity:.85;line-height:1.5;}
.al-ok{background:var(--ok-bg);color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent);}
.al-warn{background:var(--warn-bg);color:var(--warn-fg);border-color:color-mix(in srgb,var(--warn-fg) 25%,transparent);}
.al-err{background:var(--danger-bg);color:var(--danger-fg);border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent);}
.al-info{background:var(--info-bg);color:var(--info-fg);border-color:color-mix(in srgb,var(--info-fg) 25%,transparent);}

/* Cards */
.card{border:1px solid var(--rule);border-radius:2px;margin-bottom:16px;overflow:hidden;}
.ch{padding:9px 15px;background:var(--paper-lt);border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between;gap:10px;}
.ct{font-size:8px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;color:var(--ink-muted);display:flex;align-items:center;gap:6px;}
.ct::before{content:'';display:inline-block;width:3px;height:10px;background:var(--accent);border-radius:1px;}
.cn{font-size:9.5px;color:var(--ink-faint);}
.cb{padding:16px 15px;}

/* Status rows */
.sr{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0ede8;gap:12px;}
.sr:last-child{border-bottom:none;}
.srl{font-size:12.5px;color:var(--ink);}
.srv{font-family:var(--f-mono);font-size:11.5px;}
.cok{color:var(--ok-fg);}.cerr{color:var(--danger-fg);}.cwarn{color:var(--warn-fg);}

/* How-to list */
.ht{display:flex;flex-direction:column;gap:9px;}
.hi{display:flex;align-items:flex-start;gap:11px;padding:10px 12px;border-radius:2px;background:var(--paper-lt);border:1px solid var(--rule);}
.hn{width:20px;height:20px;border-radius:50%;background:var(--accent);color:#fff;font-family:var(--f-mono);font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.htm{font-size:12.5px;color:var(--ink-muted);line-height:1.55;}
code{font-family:var(--f-mono);font-size:10.5px;background:var(--paper-dk);padding:1px 5px;border-radius:2px;color:var(--ink);}

/* Config form */
.cfg-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 16px;}
@media(max-width:500px){.cfg-grid{grid-template-columns:1fr;}}
.cfg-grid .fg{min-width:0;}
.fg-lbl{display:block;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:4px;}
.fg-inp{width:100%;padding:8px 11px;border:1.5px solid var(--rule-dk);border-radius:2px;font-family:var(--f-sans);font-size:13px;color:var(--ink);background:#fff;}
.fg-inp:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 15%,transparent);}

/* Table grid */
.tg{display:grid;grid-template-columns:1fr 1fr;gap:3px 14px;}
.ti{display:flex;align-items:center;gap:5px;font-size:10.5px;color:var(--ink-muted);padding:2px 0;}

/* Terminal */
#migOut{
    font-family:var(--f-mono);font-size:11.5px;
    background:#0f0f0f;color:#d0d0d0;
    border-radius:2px;padding:12px 14px;
    min-height:160px;max-height:400px;
    overflow-y:auto;line-height:1.8;
    white-space:pre-wrap;word-break:break-word;
    display:none;margin-top:12px;
    border:1px solid #252525;
}
#migOut .lo{color:#86efac;}
#migOut .le{color:#fca5a5;}
#migOut .lw{color:#fde68a;}
#migOut .li{color:#93c5fd;}
#migOut .lg{color:#6b7280;}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:2px;font-family:var(--f-sans);font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;cursor:pointer;border:1.5px solid;transition:all .13s;white-space:nowrap;text-decoration:none;}
.btn-p{background:var(--accent);border-color:var(--accent);color:#fff;}
.btn-p:hover{filter:brightness(1.08);}
.btn-p:disabled{opacity:.4;cursor:not-allowed;filter:none;}
.btn-g{background:#fff;border-color:var(--rule-dk);color:var(--ink-muted);}
.btn-g:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-lt);}
.btn-sm{padding:5px 12px;font-size:9.5px;}
.brow{display:flex;gap:9px;align-items:center;flex-wrap:wrap;margin-top:12px;}

/* Spinner */
.spr{display:inline-block;width:10px;height:10px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}

/* Bottom nav */
.wiz-foot{padding:16px 34px;border-top:1px solid var(--rule);background:var(--paper-lt);display:flex;align-items:center;justify-content:space-between;gap:12px;}
.wiz-ind{font-family:var(--f-mono);font-size:8.5px;letter-spacing:.5px;color:var(--ink-faint);}

/* Success */
.suc{text-align:center;padding:32px 20px 24px;}
.suc-ico{font-size:48px;margin-bottom:12px;}
.suc-title{font-family:var(--f-serif);font-size:21px;font-weight:700;color:var(--ink);margin-bottom:5px;}
.suc-sub{font-size:13px;color:var(--ink-faint);margin-bottom:22px;line-height:1.6;}
</style>
</head>
<body>
<div class="wiz">

<!-- ════ LEFT ════ -->
<div class="wiz-l">
    <div class="wiz-lh">
        <span class="wiz-icon">🛠</span>
        <div class="wiz-ltitle">Setup Wizard</div>
        <div class="wiz-lsub">Configure MIS Barangay step by step.</div>
    </div>

    <div class="wiz-nav-steps">
        <div class="ws wa" data-step="1">
            <div class="wsn">1</div>
            <div class="wsi"><div class="wst">Database</div><div class="wsd">Check connection</div></div>
            <span class="wsbadge <?= $db_ok?'bok':'berr' ?>" id="b1"><?= $db_ok?'✓ OK':'✗ Error' ?></span>
        </div>
        <div class="ws" data-step="2">
            <div class="wsn">2</div>
            <div class="wsi"><div class="wst">Run Migrations</div><div class="wsd">Create all tables</div></div>
            <span class="wsbadge <?= $tables_ok?'bok':'bwarn' ?>" id="b2"><?= $tables_ok?'✓ Done':'! Pending' ?></span>
        </div>
        <div class="ws" data-step="3">
            <div class="wsn">3</div>
            <div class="wsi"><div class="wst">Default Account</div><div class="wsd">Seed admin user</div></div>
            <span class="wsbadge bwarn" id="b3">! Check</span>
        </div>
        <div class="ws" data-step="4">
            <div class="wsn">4</div>
            <div class="wsi"><div class="wst">Finish</div><div class="wsd">Ready to use</div></div>
            <span class="wsbadge bwarn" id="b4">…</span>
        </div>
    </div>

    <div class="wiz-lf">MIS Barangay Bombongan · <?= date('Y') ?></div>
</div>

<!-- ════ RIGHT ════ -->
<div class="wiz-r">
    <div class="wiz-hd">
        <div class="wiz-hdtitle" id="hdTitle">Step 1 — Database Connection</div>
        <div class="wiz-hdsub"  id="hdSub">Verify that MySQL is running and the database exists</div>
    </div>

    <div class="wiz-body">

        <!-- ── STEP 1 ── -->
        <div class="sp spa" id="p1">

            <?php if (!$db_ok): ?>
            <div class="al al-err">
                <span class="ali">✗</span>
                <div><div class="alt">Database Connection Failed</div>
                <div class="alm"><?= htmlspecialchars($db_error ?? 'Could not connect to MySQL') ?></div></div>
            </div>

            <div class="card">
                <div class="ch"><div class="ct">Quick Setup — Enter Your Database Details</div><span class="cn">No tech skills needed</span></div>
                <div class="cb">
                    <p style="font-size:12px;color:var(--ink-muted);margin-bottom:14px;line-height:1.6;">
                        If you use <strong>Laragon</strong>, XAMPP, or similar: start Apache &amp; MySQL first, then fill the form below. We will create the database for you.
                    </p>
                    <form id="cfgForm" class="cfg-grid">
                        <div class="fg"><label class="fg-lbl">MySQL Host</label><input type="text" name="host" id="cfg_host" class="fg-inp" value="<?= htmlspecialchars($DB_HOST) ?>" placeholder="localhost or 127.0.0.1"></div>
                        <div class="fg"><label class="fg-lbl">Username</label><input type="text" name="user" id="cfg_user" class="fg-inp" value="<?= htmlspecialchars($DB_USER) ?>" placeholder="root"></div>
                        <div class="fg"><label class="fg-lbl">Password</label><input type="password" name="pass" id="cfg_pass" class="fg-inp" value="" placeholder="(usually empty for local)"></div>
                        <div class="fg"><label class="fg-lbl">Database Name</label><input type="text" name="dbname" id="cfg_dbname" class="fg-inp" value="<?= htmlspecialchars($DB_NAME) ?>" placeholder="php_mis_brgy"></div>
                    </form>
                    <div class="brow" style="margin-top:14px;">
                        <button type="button" class="btn btn-p" id="btnSaveConfig"><span class="btn-txt">Save &amp; Install</span></button>
                        <span id="cfgMsg" style="font-size:12px;margin-left:8px;"></span>
                    </div>
                </div>
            </div>

            <details class="card" style="margin-top:12px;">
                <summary class="ch" style="cursor:pointer;user-select:none;"><div class="ct">Manual Setup (if above does not work)</div></summary>
                <div class="cb">
                    <div class="ht">
                        <div class="hi"><div class="hn">1</div><div class="htm">Install <strong>Laragon</strong> or any local server with Apache + MySQL.</div></div>
                        <div class="hi"><div class="hn">2</div><div class="htm">Open Laragon → click <strong>Start All</strong> to start Apache and MySQL.</div></div>
                        <div class="hi"><div class="hn">3</div><div class="htm">
                            Create a database named <code>php_mis_brgy</code> using HeidiSQL or MySQL CLI:<br>
                            <code style="display:block;margin-top:5px;padding:6px 8px;">CREATE DATABASE php_mis_brgy;</code>
                        </div></div>
                        <div class="hi"><div class="hn">4</div><div class="htm">Edit <code>config.php</code> in the project folder with your database details.</div></div>
                    </div>
                </div>
            </details>

            <?php else: ?>
            <div class="al al-ok">
                <span class="ali">✓</span>
                <div><div class="alt">Database Connected Successfully</div>
                <div class="alm">MySQL is running. Click <strong>Next</strong> to install the database tables.</div></div>
            </div>

            <div class="card">
                <div class="ch"><div class="ct">Connection Details</div></div>
                <div class="cb">
                    <div class="sr"><span class="srl">Host</span><span class="srv cok"><?= htmlspecialchars($DB_HOST) ?></span></div>
                    <div class="sr"><span class="srl">Database</span><span class="srv cok"><?= htmlspecialchars($DB_NAME) ?></span></div>
                    <div class="sr"><span class="srl">MySQL Version</span><span class="srv cok"><?= htmlspecialchars($db_version) ?></span></div>
                    <div class="sr"><span class="srl">Tables Installed</span>
                        <span class="srv <?= $tables_ok?'cok':'cwarn' ?>">
                            <?= $tables_ok ? '✓ Yes — found' : '! Not yet — proceed to Step 2' ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── STEP 2 ── -->
        <div class="sp" id="p2">

            <?php if ($tables_ok): ?>
            <div class="al al-ok">
                <span class="ali">✓</span>
                <div><div class="alt">Tables Already Installed</div>
                <div class="alm">Re-running is safe — all scripts use <code>CREATE TABLE IF NOT EXISTS</code>.</div></div>
            </div>
            <?php else: ?>
            <div class="al al-warn">
                <span class="ali">!</span>
                <div><div class="alt">Tables Not Found</div>
                <div class="alm">Click <strong>Install Tables</strong> below to create all required database tables.</div></div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="ch">
                    <div class="ct">Database Tables</div>
                    <span class="cn">Creates users, residents, consultations, etc.</span>
                </div>
                <div class="cb">
                    <p style="font-size:12.5px;color:var(--ink-muted);line-height:1.65;margin-bottom:14px;">
                        Click <strong>Install Tables</strong> to create all required database tables. Safe to run again.
                    </p>
                    <div class="brow" style="margin-top:0;">
                        <button class="btn btn-p" id="btnRun">▶ Install Tables</button>
                        <button class="btn btn-g" id="btnClear" style="display:none;">✕ Clear</button>
                        <span id="migStat" style="font-size:11.5px;"></span>
                    </div>
                    <div id="migOut"></div>
                </div>
            </div>

            <div class="card">
                <div class="ch"><div class="ct">Tables That Will Be Created</div></div>
                <div class="cb">
                    <div class="tg">
                        <?php foreach([
                            'users','residents','households','families',
                            'officers','blotter','events','inventory',
                            'medicines','medicine_categories','medicine_dispense',
                            'consultations','immunizations','health_metrics',
                            'care_visits','certificate_request','backups',
                            'patrol_schedule','tanod_duty_schedule','court_schedule',
                            'borrowing_schedule','blotter_history','term_history',
                            'inventory_audit_trail','inventory_category_list',
                        ] as $t): ?>
                        <div class="ti"><span style="color:var(--ok-fg);font-size:9px;">✓</span><code><?= $t ?></code></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── STEP 3 ── -->
        <div class="sp" id="p3">
            <div class="al al-info">
                <span class="ali">ℹ</span>
                <div><div class="alt">Default Admin Account</div>
                <div class="alm">Created automatically by migrations via <code>schema/create_account.php</code>.</div></div>
            </div>

            <div class="card">
                <div class="ch"><div class="ct">Default Login Credentials</div></div>
                <div class="cb">
                    <div class="sr"><span class="srl">Username</span><span class="srv">Ersel</span></div>
                    <div class="sr"><span class="srl">Password</span><span class="srv">redzone</span></div>
                    <div class="sr"><span class="srl">Role</span><span class="srv cok">Secretary (Developer)</span></div>
                    <div class="sr"><span class="srl">Full Name</span><span class="srv">Ersel Magbanua</span></div>
                </div>
            </div>

            <div class="card">
                <div class="ch"><div class="ct">After First Login</div></div>
                <div class="cb">
                    <div class="ht">
                        <div class="hi"><div class="hn">1</div><div class="htm">Sign in with the credentials above.</div></div>
                        <div class="hi"><div class="hn">2</div><div class="htm">Go to <strong>Officials & Staff</strong> → create accounts for other roles (hcnurse, tanod, captain, kagawad).</div></div>
                        <div class="hi"><div class="hn">3</div><div class="htm">Go to <strong>Profile → Settings</strong> and change the default password.</div></div>
                        <div class="hi"><div class="hn">4</div><div class="htm">Add residents and household data from the dashboard.</div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── STEP 4 ── -->
        <div class="sp" id="p4">
            <div class="suc">
                <div class="suc-ico">🎉</div>
                <div class="suc-title">Setup Complete!</div>
                <div class="suc-sub">MIS Barangay is ready.<br>Sign in with the default account to get started.</div>
                <!-- Login link ONLY on the final step -->
                <a href="/login/" class="btn btn-p" style="font-size:13px;padding:11px 28px;">Go to Login →</a>
            </div>

            <div class="card" style="max-width:480px;margin:0 auto;">
                <div class="ch"><div class="ct">Quick Reference</div></div>
                <div class="cb">
                    <div class="sr"><span class="srl">Login Page</span><span class="srv"><a href="/login/" style="color:var(--accent);">/login/</a></span></div>
                    <div class="sr"><span class="srl">Username</span><span class="srv">Ersel</span></div>
                    <div class="sr"><span class="srl">Password</span><span class="srv">redzone</span></div>
                    <div class="sr"><span class="srl">Database</span>
                        <span class="srv <?= $db_ok?'cok':'cerr' ?>"><?= $db_ok?'✓ Connected':'✗ Not connected' ?></span>
                    </div>
                    <div class="sr"><span class="srl">Tables</span>
                        <span class="srv <?= $tables_ok?'cok':'cwarn' ?>"><?= $tables_ok?'✓ Installed':'! Run migrations (Step 2)' ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /wiz-body -->

    <!-- Bottom nav — no login link here -->
    <div class="wiz-foot">
        <button class="btn btn-g" id="btnPrev" disabled>← Previous</button>
        <span class="wiz-ind" id="wizInd">Step 1 of 4</span>
        <button class="btn btn-p" id="btnNext">Next →</button>
    </div>
</div><!-- /wiz-r -->
</div><!-- /wiz -->

<script>
(function(){
    const TOTAL=4, panels=['p1','p2','p3','p4'];
    let cur=1;
    const meta={
        1:{title:'Step 1 — Database Connection', sub:'Verify that MySQL is running and the database exists'},
        2:{title:'Step 2 — Run Migrations',       sub:'Create all required tables (shortcut for schema\\run.bat)'},
        3:{title:'Step 3 — Default Account',      sub:'Review credentials and post-setup checklist'},
        4:{title:'Step 4 — Finish',               sub:'Setup is complete — click "Go to Login" to sign in'},
    };

    function goto(n){
        if(n<1||n>TOTAL) return;
        cur=n;
        panels.forEach((id,i)=>{
            document.getElementById(id).classList.toggle('spa', i===n-1);
        });
        document.querySelectorAll('.ws').forEach(s=>{
            const sn=parseInt(s.dataset.step);
            s.classList.toggle('wa',sn===n);
            sn<n?s.classList.add('wd'):s.classList.remove('wd');
        });
        document.getElementById('hdTitle').textContent=meta[n].title;
        document.getElementById('hdSub').textContent=meta[n].sub;
        document.getElementById('wizInd').textContent='Step '+n+' of '+TOTAL;
        document.getElementById('btnPrev').disabled=(n===1);
        const nxt=document.getElementById('btnNext');
        nxt.disabled=(n===TOTAL);
        nxt.textContent=(n===TOTAL)?'✓ Done':'Next →';
        window.scrollTo(0,0);
    }

    document.getElementById('btnNext').addEventListener('click',()=>{ if(cur<TOTAL) goto(cur+1); });
    document.getElementById('btnPrev').addEventListener('click',()=>{ if(cur>1) goto(cur-1); });
    document.querySelectorAll('.ws').forEach(s=>s.addEventListener('click',()=>goto(parseInt(s.dataset.step))));

    /* ── Run Migrations — always enabled ── */
    const btnRun=document.getElementById('btnRun');
    const migOut=document.getElementById('migOut');
    const btnClear=document.getElementById('btnClear');
    const migStat=document.getElementById('migStat');

    function line(cls,txt){
        const sp=document.createElement('span');
        if(cls) sp.className=cls;
        sp.textContent=txt;
        migOut.appendChild(sp);
        migOut.appendChild(document.createTextNode('\n'));
    }
    function cls(t){
        const s=t.trim();
        if(s.startsWith('✅')||s.includes('created successfully')||s.includes('added to')||s.startsWith('✓')||s.startsWith('OK')) return 'lo';
        if(s.startsWith('❌')||s.toLowerCase().includes('error')||s.startsWith('✗')||s.startsWith('FAILED')) return 'le';
        if(s.startsWith('⚠')||s.startsWith('ℹ')||s.startsWith('!')) return 'lw';
        if(s.startsWith('📦')||s.startsWith('🔧')||s.startsWith('▶')||s.startsWith('🚀')||s.startsWith('═')||s.startsWith('─')||s.startsWith('Started')||s.startsWith('Completed')||s.startsWith('Time')||s.startsWith('Total')) return 'li';
        if(s==='') return 'lg';
        return '';
    }

    if(btnRun){
        btnRun.addEventListener('click',function(){
            btnRun.disabled=true;
            btnRun.innerHTML='<span class="spr"></span> Running…';
            migOut.style.display='block';
            migOut.innerHTML='';
            if(btnClear) btnClear.style.display='none';
            if(migStat) migStat.textContent='';

            line('li','▶ Connecting to schema_ajax.php…\n');

            fetch('/schema_ajax.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'run=1'
            })
            .then(r=>{
                if(!r.ok) throw new Error('HTTP '+r.status+' — is public/schema_ajax.php deployed?');
                return r.text();
            })
            .then(text=>{
                migOut.innerHTML='';
                text.split('\n').forEach(l=>line(cls(l),l));
                migOut.scrollTop=migOut.scrollHeight;

                const failed=text.includes('❌')||text.includes('FAILED')||text.includes('✗');
                btnRun.disabled=false;
                btnRun.innerHTML='▶ Install Tables';
                if(btnClear) btnClear.style.display='inline-flex';
                if(migStat){
                    migStat.textContent=failed?'⚠ Some errors — see output above':'✓ All migrations done';
                    migStat.style.color=failed?'var(--danger-fg)':'var(--ok-fg)';
                }
                // Update badge live
                const b2=document.getElementById('b2');
                if(b2&&!failed){b2.textContent='✓ Done';b2.className='wsbadge bok'; goto(3);}
            })
            .catch(err=>{
                line('le','✗ '+err.message);
                line('lw','  Make sure public/schema_ajax.php is deployed.');
                btnRun.disabled=false;
                btnRun.innerHTML='▶ Install Tables';
                if(migStat){migStat.textContent='✗ Request failed';migStat.style.color='var(--danger-fg)';}
            });
        });

        if(btnClear){
            btnClear.addEventListener('click',()=>{
                migOut.innerHTML='';migOut.style.display='none';
                btnClear.style.display='none';
                if(migStat) migStat.textContent='';
            });
        }
    }

    try {
        if (sessionStorage.getItem('setup_wizard_after_install') === '1') {
            sessionStorage.removeItem('setup_wizard_after_install');
            goto(2);
        } else {
            goto(1);
        }
    } catch (e) {
        goto(1);
    }

    /* ── Save Config (Step 1 when DB fails) ── */
    const btnSaveCfg = document.getElementById('btnSaveConfig');
    if (btnSaveCfg) {
        btnSaveCfg.addEventListener('click', function (e) {
            e.preventDefault();
            const host = document.getElementById('cfg_host')?.value?.trim() || 'localhost';
            const user = document.getElementById('cfg_user')?.value?.trim() || 'root';
            const pass = document.getElementById('cfg_pass')?.value || '';
            const dbname = document.getElementById('cfg_dbname')?.value?.trim() || 'php_mis_brgy';
            const msgEl = document.getElementById('cfgMsg');
            if (!host || !user || !dbname) {
                if (msgEl) { msgEl.textContent = 'Please fill Host, Username, and Database name.'; msgEl.style.color = 'var(--danger-fg)'; }
                return;
            }
            btnSaveCfg.disabled = true;
            const txt = btnSaveCfg.querySelector('.btn-txt');
            if (txt) txt.textContent = 'Installing…';
            if (msgEl) { msgEl.textContent = ''; msgEl.style.color = ''; }
            const fd = new FormData();
            fd.append('action', 'save_config');
            fd.append('host', host);
            fd.append('user', user);
            fd.append('pass', pass);
            fd.append('dbname', dbname);
            const apiUrl = (window.location.pathname.replace(/[^/]*$/, '') || '/') + 'setup_api.php';
            fetch(apiUrl, { method: 'POST', body: fd })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(text => {
                    let d;
                    try { d = JSON.parse(text); } catch (_) { throw new Error('Invalid response: ' + text.slice(0, 80)); }
                    return d;
                })
                .then(d => {
                    if (d && d.ok) {
                        if (msgEl) { msgEl.textContent = '✓ ' + (d.msg || 'Saved') + ' Reloading…'; msgEl.style.color = 'var(--ok-fg)'; }
                        try { sessionStorage.setItem('setup_wizard_after_install', '1'); } catch (e) {}
                        setTimeout(() => location.reload(), 800);
                    } else {
                        if (msgEl) { msgEl.textContent = (d && d.msg) || 'Failed'; msgEl.style.color = 'var(--danger-fg)'; }
                        btnSaveCfg.disabled = false;
                        if (txt) txt.textContent = 'Save & Install';
                    }
                })
                .catch(err => {
                    if (msgEl) { msgEl.textContent = 'Error: ' + (err.message || 'Request failed'); msgEl.style.color = 'var(--danger-fg)'; }
                    btnSaveCfg.disabled = false;
                    if (txt) txt.textContent = 'Save & Install';
                });
        });
    }
})();
</script>
</body>
</html>
<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate Issuance — MIS Barangay</title>
    <?php loadAllAssets(); echo showDialogReloadScript(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    /* ═══════════════════════════════════════
       TOKENS
    ═══════════════════════════════════════ */
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
        --accent-dk:  color-mix(in srgb, var(--accent) 65%, black);
        --ok-bg:      #edfaf3; --ok-fg:     #1a5c35;
        --warn-bg:    #fef9ec; --warn-fg:   #7a5700;
        --info-bg:    #edf3fa; --info-fg:   #1a3a5c;
        --danger-bg:  #fdeeed; --danger-fg: #7a1f1a;
        --neu-bg:     #f3f1ec; --neu-fg:    #5a5a5a;
        --f-serif:    'Source Serif 4', Georgia, serif;
        --f-sans:     'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:     'Source Code Pro', 'Courier New', monospace;
        --shadow:     0 1px 2px rgba(0,0,0,.07), 0 3px 12px rgba(0,0,0,.04);
        --lift:       0 4px 20px rgba(0,0,0,.12), 0 1px 4px rgba(0,0,0,.08);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body, input, button, select, textarea { font-family: var(--f-sans); }

    .cert-main { background: var(--bg); min-height: 100%; }

    /* ── Document Header (matches dashboard / resident pattern) ── */
    .doc-header { background: var(--paper); border-bottom: 1px solid var(--rule); }
    .doc-header-inner {
        padding: 20px 28px 18px;
        display: flex; align-items: flex-end;
        justify-content: space-between; gap: 20px; flex-wrap: wrap;
    }
    .doc-eyebrow {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.8px;
        text-transform: uppercase; color: var(--ink-faint);
        margin-bottom: 6px; display: flex; align-items: center; gap: 8px;
    }
    .doc-eyebrow::before { content:''; display:inline-block; width:18px; height:2px; background:var(--accent); }
    .doc-title {
        font-family: var(--f-serif); font-size: 22px; font-weight: 700;
        color: var(--ink); letter-spacing: -.2px; margin-bottom: 3px;
    }
    .doc-sub { font-size: 12px; color: var(--ink-faint); font-style: italic; }

    /* ── Search Bar (sits inside doc-header, bottom strip) ── */
    .search-bar {
        background: var(--paper-lt); border-top: 1px solid var(--rule);
        border-bottom: 3px solid var(--accent);
        padding: 14px 28px;
    }
    .search-eyebrow {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.5px;
        text-transform: uppercase; color: var(--ink-faint);
        margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
    }
    .search-eyebrow::before { content:''; display:inline-block; width:16px; height:2px; background:var(--accent); }

    /* .search-wrap — hosts #residentSearch, #searchDD */
    .search-wrap { position: relative; max-width: 560px; }
    .search-wrap input {
        width: 100%; padding: 11px 46px 11px 16px;
        border: 1.5px solid var(--rule-dk); border-radius: 2px;
        font-family: var(--f-sans); font-size: 14px; color: var(--ink);
        background: #fff; outline: none;
        transition: border-color .18s, box-shadow .18s;
    }
    .search-wrap input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .search-wrap input::placeholder { color: var(--ink-faint); font-style: italic; }
    .s-icon {
        position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
        color: var(--ink-faint); font-size: 18px; pointer-events: none; transition: color .18s;
    }
    .search-wrap input:focus ~ .s-icon { color: var(--accent); }

    /* ── Search Dropdown — IDs: #searchDD, #searchDDInner, .dd-row[data-id] ── */
    .search-dd {
        position: absolute; top: calc(100% + 5px); left: 0; right: 0;
        background: #fff; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        box-shadow: var(--lift); overflow: hidden; z-index: 200; display: none;
    }
    .search-dd.open { display: block; }
    .dd-hdr {
        padding: 7px 14px; background: var(--paper-lt);
        border-bottom: 1px solid var(--rule);
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.3px;
        text-transform: uppercase; color: var(--ink-muted);
    }
    .dd-row {
        display: grid; grid-template-columns: 46px 1fr 16px;
        align-items: center; gap: 12px;
        padding: 10px 14px; cursor: pointer;
        border-bottom: 1px solid #f3f0eb; transition: background .1s;
    }
    .dd-row:last-child { border-bottom: none; }
    .dd-row:hover { background: var(--accent-lt); }
    .dd-row:hover .dd-id { border-color: var(--accent); color: var(--accent); }
    .dd-id {
        width: 42px; height: 40px; border: 1.5px solid var(--rule-dk); border-radius: 2px;
        display: flex; align-items: center; justify-content: center;
        font-family: var(--f-mono); font-size: 10px; font-weight: 700;
        color: var(--ink-muted); flex-shrink: 0; transition: all .12s;
    }
    .dd-name { font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 2px; }
    .dd-addr { font-size: 11px; color: var(--ink-faint); }
    .dd-arr  { color: var(--ink-faint); font-size: 13px; }
    .dd-empty { padding: 18px 14px; text-align: center; font-size: 12.5px; color: var(--ink-faint); font-style: italic; }

    /* ── Content Body ── */
    .cert-body { padding: 24px 28px 56px; }

    /* ── Idle / Empty State ── */
    .idle-wrap {
        background: var(--paper); border: 1.5px dashed var(--rule-dk);
        border-radius: 2px; padding: 80px 32px; text-align: center;
        max-width: 580px; margin: 0 auto;
    }
    .idle-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 60px; height: 60px; border-radius: 2px;
        background: var(--paper-dk); font-size: 26px;
        opacity: .35; margin-bottom: 20px;
    }
    .idle-title {
        font-family: var(--f-serif); font-size: 18px; font-weight: 600;
        color: var(--ink); margin-bottom: 8px;
    }
    .idle-rule { width: 32px; height: 2px; background: var(--accent); margin: 10px auto 14px; }
    .idle-sub {
        font-size: 13px; color: var(--ink-faint);
        max-width: 320px; margin: 0 auto; line-height: 1.8;
    }

    /* ── Loading spinner ── */
    @keyframes spin { to { transform: rotate(360deg); } }
    .load-wrap { padding: 56px 0; text-align: center; }
    .load-spin {
        display: inline-block; width: 30px; height: 30px;
        border: 3px solid var(--rule); border-top-color: var(--accent);
        border-radius: 50%; animation: spin .7s linear infinite;
    }
    .load-label { margin-top: 14px; font-size: 12px; color: var(--ink-faint); font-style: italic; }

    /* ── Error State ── */
    .err-wrap { padding: 60px 32px; text-align: center; }
    .err-icon  { font-size: 26px; margin-bottom: 14px; opacity: .4; }
    .err-title { font-family: var(--f-serif); font-size: 16px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
    .err-sub   { font-size: 12px; color: var(--ink-faint); }

    /* dialog override */
    #dialog-message p { padding: 16px 20px; font-size: 13px; color: var(--ink); }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto cert-main">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Office of the Secretary</div>
                        <div class="doc-title">Certificate Issuance</div>
                        <div class="doc-sub">Process and print barangay certificates for residents — clearance, indigency, and residency</div>
                    </div>
                </div>

                <!-- ── Resident Lookup strip ── -->
                <div class="search-bar">
                <div class="search-eyebrow">Resident Lookup</div>
                <div class="search-wrap">
                    <!-- ID preserved: #residentSearch -->
                    <input id="residentSearch" type="text"
                        placeholder="Type a resident name or address to begin…"
                        autocomplete="off" spellcheck="false">
                    <span class="s-icon">⌕</span>
                    <!-- IDs preserved: #searchDD, #searchDDInner -->
                    <div id="searchDD" class="search-dd">
                        <div class="dd-hdr">Search Results</div>
                        <div id="searchDDInner"></div>
                    </div>
                </div>
                </div><!-- /search-bar -->
            </div><!-- /doc-header -->

            <!-- ── Main Content — injected by load_resident_details.php ── -->
            <div class="cert-body">
                <!-- ID preserved: #residentDetails -->
                <div id="residentDetails">
                    <div class="idle-wrap">
                        <div class="idle-icon">📄</div>
                        <div class="idle-title">No Resident Selected</div>
                        <div class="idle-rule"></div>
                        <p class="idle-sub">Use the lookup field above to find a resident by name or address, then process their certificate request.</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- dialog for JS messages — IDs preserved exactly -->
    <div id="dialog-message" title="" style="display:none;"><p id="dialog-text"></p></div>

    <script src="js/index.js"></script>
</body>
</html>
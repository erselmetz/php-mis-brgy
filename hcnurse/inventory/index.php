<?php
require_once __DIR__ . '/../../includes/app.php';
requireHCNurse();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
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
        --info-bg:    #edf3fa; --info-fg:   #1a3a5c;
        --neu-bg:     #f3f1ec; --neu-fg:    #5a5a5a;
        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 14px rgba(0,0,0,.05);
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body, input, button, select, textarea { font-family:var(--f-sans); }
    .inv-page { background:var(--bg); min-height:100%; padding-bottom:56px; }

    /* ── Doc header ── */
    .doc-header { background:var(--paper); border-bottom:1px solid var(--rule); }
    .doc-header-inner {
        padding:20px 28px 18px;
        display:flex; align-items:flex-end; justify-content:space-between; gap:20px; flex-wrap:wrap;
    }
    .doc-eyebrow {
        font-size:8.5px; font-weight:700; letter-spacing:1.8px;
        text-transform:uppercase; color:var(--ink-faint);
        display:flex; align-items:center; gap:8px; margin-bottom:6px;
    }
    .doc-eyebrow::before { content:''; width:18px; height:2px; background:var(--accent); display:inline-block; }
    .doc-title {
        font-family:var(--f-serif); font-size:22px; font-weight:700;
        color:var(--ink); letter-spacing:-.3px; margin-bottom:3px;
    }
    .doc-sub { font-size:12px; color:var(--ink-faint); font-style:italic; }
    .header-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    /* ── Toolbar ── */
    .inv-toolbar {
        background:var(--paper-lt); border-bottom:3px solid var(--accent);
        padding:11px 28px;
        display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    }
    .inv-search {
        padding:7px 12px; border:1.5px solid var(--rule-dk);
        border-radius:2px; font-size:13px; color:var(--ink);
        background:#fff; outline:none; width:240px;
        transition:border-color .15s, box-shadow .15s;
    }
    .inv-search:focus {
        border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
    }
    .inv-search::placeholder { color:var(--ink-faint); font-style:italic; font-size:12px; }
    .toolbar-sep { flex:1; }

    /* ── Buttons ── */
    .btn {
        display:inline-flex; align-items:center; gap:6px;
        padding:7px 16px; border-radius:2px;
        font-family:var(--f-sans); font-size:11.5px; font-weight:700;
        letter-spacing:.4px; text-transform:uppercase;
        cursor:pointer; border:1.5px solid; transition:all .14s; white-space:nowrap;
    }
    .btn-primary { background:var(--accent); border-color:var(--accent); color:#fff; }
    .btn-primary:hover { filter:brightness(1.1); }
    .btn-ghost   { background:#fff; border-color:var(--rule-dk); color:var(--ink-muted); }
    .btn-ghost:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-lt); }
    .btn-sm { padding:5px 11px; font-size:10px; }

    /* ════════════════════════════════
       STAT LEDGER  (4 cells)
    ════════════════════════════════ */
    .stat-ledger {
        display:grid; grid-template-columns:repeat(4,1fr);
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
    .sl-1::after { background:#3b82f6; }
    .sl-2::after { background:#f59e0b; }
    .sl-3::after { background:#e11d48; }
    .sl-cell:hover { background:var(--paper-lt); }
    .sl-eyebrow {
        font-size:8px; font-weight:700; letter-spacing:1.4px;
        text-transform:uppercase; color:var(--ink-faint); margin-bottom:8px;
    }
    .sl-val {
        font-family:var(--f-mono); font-size:30px; font-weight:600;
        line-height:1; margin-bottom:4px; letter-spacing:-1px; color:var(--ink);
    }
    .sl-sub { font-size:10.5px; color:var(--ink-faint); }

    /* ════════════════════════════════
       TABLE
    ════════════════════════════════ */
    .inv-table-wrap {
        margin:18px 28px 0;
        background:var(--paper);
        border:1px solid var(--rule); border-top:3px solid var(--accent);
        border-radius:2px; box-shadow:var(--shadow); overflow:hidden;
    }
    .inv-table-wrap .dataTables_wrapper { padding:0; font-family:var(--f-sans); }
    .inv-table-wrap .dataTables_filter,
    .inv-table-wrap .dataTables_length  { display:none; }
    .inv-table-wrap .dataTables_info {
        padding:10px 18px; font-size:11px; color:var(--ink-faint);
        font-family:var(--f-mono); letter-spacing:.3px;
        border-top:1px solid var(--rule); background:var(--paper-lt);
    }
    .inv-table-wrap .dataTables_paginate {
        padding:10px 18px; border-top:1px solid var(--rule); background:var(--paper-lt);
    }
    .inv-table-wrap .paginate_button {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:30px; height:28px; padding:0 8px;
        border:1.5px solid var(--rule-dk) !important; border-radius:2px;
        font-size:11px; font-weight:600;
        color:var(--ink-muted) !important; background:#fff !important;
        cursor:pointer; margin:0 2px; transition:all .13s;
    }
    .inv-table-wrap .paginate_button:hover   { border-color:var(--accent) !important; color:var(--accent) !important; background:var(--accent-lt) !important; }
    .inv-table-wrap .paginate_button.current { background:var(--accent) !important; border-color:var(--accent) !important; color:#fff !important; }
    .inv-table-wrap .paginate_button.disabled { opacity:.35 !important; }

    #medicineTable { width:100% !important; border-collapse:collapse; }
    #medicineTable thead th {
        padding:10px 14px; background:var(--paper-lt); text-align:left;
        font-size:8.5px; font-weight:700; letter-spacing:1.2px;
        text-transform:uppercase; color:var(--ink-muted);
        border-bottom:1px solid var(--rule-dk); white-space:nowrap;
        cursor:pointer; user-select:none;
    }
    #medicineTable thead th:hover { color:var(--accent); }
    #medicineTable thead th.sorting_asc::after  { content:' ↑'; color:var(--accent); }
    #medicineTable thead th.sorting_desc::after { content:' ↓'; color:var(--accent); }
    #medicineTable tbody tr { border-bottom:1px solid #f0ede8; transition:background .1s; }
    #medicineTable tbody tr:last-child { border-bottom:none; }
    #medicineTable tbody tr:hover { background:var(--accent-lt); }
    #medicineTable td { padding:10px 14px; font-size:12.5px; color:var(--ink); vertical-align:middle; }

    /* Item ID */
    .td-item-id   { font-family:var(--f-mono); font-size:10.5px; color:var(--accent); font-weight:700; letter-spacing:.5px; }
    /* Name */
    .td-item-name { font-weight:600; font-size:13px; margin-bottom:2px; }
    .td-category  { font-size:10.5px; color:var(--ink-faint); font-style:italic; }
    /* Qty */
    .td-qty { font-family:var(--f-mono); font-size:13px; font-weight:600; }
    .qty-critical { color:var(--danger-fg); }
    .qty-warn     { color:#f59e0b; }
    .qty-ok       { color:var(--ok-fg); }
    /* Expiry */
    .td-expiry { font-family:var(--f-mono); font-size:11.5px; color:var(--ink-muted); white-space:nowrap; }
    .exp-danger { color:var(--danger-fg); font-weight:600; }
    .exp-warn   { color:#f59e0b; font-weight:600; }
    /* Status badge */
    .status-stamp {
        display:inline-block; padding:3px 9px; border-radius:2px;
        font-size:9px; font-weight:700; letter-spacing:.5px;
        text-transform:uppercase; border:1px solid;
    }
    .ss-instock   { background:var(--ok-bg);     color:var(--ok-fg);     border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .ss-critical  { background:var(--danger-bg);  color:var(--danger-fg); border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent); }
    .ss-outstock  { background:var(--neu-bg);     color:var(--neu-fg);    border-color:var(--rule); }
    .ss-expired   { background:#1a1a1a;            color:#fff;             border-color:#1a1a1a; }

    /* Row actions */
    .td-actions { display:flex; gap:5px; }
    .act-btn {
        display:inline-flex; align-items:center;
        padding:4px 10px; border-radius:2px; font-size:9.5px;
        font-weight:700; letter-spacing:.4px; text-transform:uppercase;
        cursor:pointer; border:1.5px solid var(--rule-dk);
        font-family:var(--f-sans); transition:all .13s;
        background:#fff; color:var(--ink-muted); white-space:nowrap;
    }
    .act-edit:hover { border-color:var(--accent);    color:var(--accent);    background:var(--accent-lt); }

    /* ════════════════════════════════
       DIALOG OVERRIDES
    ════════════════════════════════ */
    .ui-dialog {
        border:1px solid var(--rule-dk) !important; border-radius:2px !important;
        box-shadow:0 8px 48px rgba(0,0,0,.18) !important;
        padding:0 !important; font-family:var(--f-sans) !important;
    }
    .ui-dialog-titlebar {
        background:var(--accent) !important; border:none !important; padding:12px 16px !important;
    }
    .ui-dialog-title {
        font-family:var(--f-sans) !important; font-size:11px !important; font-weight:700 !important;
        letter-spacing:1px !important; text-transform:uppercase !important; color:#fff !important;
    }
    .ui-dialog-titlebar-close {
        background:rgba(255,255,255,.15) !important; border:1px solid rgba(255,255,255,.25) !important;
        border-radius:2px !important; color:#fff !important;
        width:24px !important; height:24px !important;
        top:50% !important; transform:translateY(-50%) !important;
    }
    .ui-dialog-content { padding:0 !important; }
    .ui-dialog-buttonpane {
        background:var(--paper-lt) !important; border-top:1px solid var(--rule) !important;
        padding:12px 16px !important; margin:0 !important;
    }
    .ui-dialog-buttonpane .ui-button {
        font-family:var(--f-sans) !important; font-size:11px !important; font-weight:700 !important;
        letter-spacing:.5px !important; text-transform:uppercase !important;
        padding:7px 18px !important; border-radius:2px !important; cursor:pointer !important;
    }
    .ui-dialog-buttonpane .ui-button:first-child {
        background:var(--accent) !important; border:1.5px solid var(--accent) !important; color:#fff !important;
    }
    .ui-dialog-buttonpane .ui-button:first-child:hover { filter:brightness(1.1) !important; }
    .ui-dialog-buttonpane .ui-button:not(:first-child) {
        background:#fff !important; border:1.5px solid var(--rule-dk) !important; color:var(--ink-muted) !important;
    }

    /* ════════════════════════════════
       FORM MODALS
    ════════════════════════════════ */
    .modal-scroll { max-height:68vh; overflow-y:auto; }
    .form-section { padding:14px 18px 0; border-top:1px solid var(--rule); }
    .form-section:first-child { border-top:none; padding-top:18px; }
    .form-section-lbl {
        font-size:8px; font-weight:700; letter-spacing:1.6px; text-transform:uppercase;
        color:var(--ink-faint); margin-bottom:12px;
        display:flex; align-items:center; gap:8px;
    }
    .form-section-lbl::after { content:''; flex:1; height:1px; background:var(--rule); }
    .form-section-body { padding-bottom:14px; }
    .fg { margin-bottom:12px; }
    .fg-label {
        display:block; font-size:8.5px; font-weight:700;
        letter-spacing:1.2px; text-transform:uppercase; color:var(--ink-muted); margin-bottom:5px;
    }
    .fg-label .req { color:var(--danger-fg); }
    .fg-input, .fg-select, .fg-textarea {
        width:100%; padding:9px 12px;
        border:1.5px solid var(--rule-dk); border-radius:2px;
        font-family:var(--f-sans); font-size:13px; color:var(--ink);
        background:#fff; outline:none; transition:border-color .15s, box-shadow .15s;
    }
    .fg-input:focus, .fg-select:focus, .fg-textarea:focus {
        border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
    }
    .fg-input::placeholder { color:var(--ink-faint); font-style:italic; font-size:12px; }
    .fg-textarea { resize:vertical; min-height:68px; }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }

    /* ── Report preview dialog ── */
    .rpt-summary {
        padding:10px 16px; background:var(--paper-lt);
        border-bottom:1px solid var(--rule);
        font-family:var(--f-mono); font-size:10px; color:var(--ink-muted);
        letter-spacing:.3px; display:flex; gap:16px; flex-wrap:wrap;
    }
    .rpt-summary span strong { color:var(--ink); }
    .rpt-table-scroll { overflow-y:auto; max-height:420px; }
    .rpt-table { width:100%; border-collapse:collapse; font-size:12px; }
    .rpt-table thead th {
        padding:8px 12px; background:var(--paper-lt); text-align:left;
        font-size:8px; font-weight:700; letter-spacing:1.1px; text-transform:uppercase;
        color:var(--ink-muted); border-bottom:1px solid var(--rule-dk);
        position:sticky; top:0; z-index:1;
    }
    .rpt-table tbody tr { border-bottom:1px solid #f0ede8; transition:background .1s; }
    .rpt-table tbody tr:hover { background:var(--paper-lt); }
    .rpt-table td { padding:8px 12px; vertical-align:middle; }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto inv-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div>
                        <div class="doc-eyebrow">Barangay Bombongan — Health Center</div>
                        <div class="doc-title">Medicine Inventory</div>
                        <div class="doc-sub">Stock levels, expiry tracking, and medicine categories</div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-ghost" id="btnReport">↗ Generate Report</button>
                        <button class="btn btn-ghost" id="btnAddCategory">+ Category</button>
                        <button class="btn btn-primary" id="btnAddMedicine">+ Add Item</button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="inv-toolbar">
                    <input type="text" class="inv-search" id="searchInput" placeholder="Search item name or category…">
                    <div class="toolbar-sep"></div>
                    <span style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);letter-spacing:.5px;" id="itemCount">— ITEMS</span>
                </div>
            </div>

            <!-- ── Stat Ledger ── -->
            <div class="stat-ledger">
                <div class="sl-cell sl-0">
                    <div class="sl-eyebrow">Total Items</div>
                    <div class="sl-val" id="slTotal">—</div>
                    <div class="sl-sub">medicines in stock</div>
                </div>
                <div class="sl-cell sl-1">
                    <div class="sl-eyebrow">In Stock</div>
                    <div class="sl-val" id="slOk" style="color:#3b82f6;">—</div>
                    <div class="sl-sub">above reorder level</div>
                </div>
                <div class="sl-cell sl-2">
                    <div class="sl-eyebrow">Critical / Low</div>
                    <div class="sl-val" id="slCritical" style="color:#f59e0b;">—</div>
                    <div class="sl-sub">at or below reorder</div>
                </div>
                <div class="sl-cell sl-3">
                    <div class="sl-eyebrow">Out / Expired</div>
                    <div class="sl-val" id="slBad" style="color:#e11d48;">—</div>
                    <div class="sl-sub">needs attention</div>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="inv-table-wrap">
                <table id="medicineTable" class="display" style="width:100%;"></table>
            </div>

        </main>
    </div>

    <!-- ════════════════════════════
         ADD / EDIT MEDICINE MODAL
    ════════════════════════════ -->
    <div id="medicineModal" title="Add Medicine" class="hidden">
        <form id="medicineForm" class="modal-scroll">
            <input type="hidden" name="id" id="medId">

            <div class="form-section">
                <div class="form-section-lbl">Item Details</div>
                <div class="form-section-body">
                    <div class="fg">
                        <label class="fg-label">Medicine / Item Name <span class="req">*</span></label>
                        <input type="text" name="name" id="medName" class="fg-input" required autocomplete="off">
                    </div>
                    <div class="form-grid-2">
                        <div class="fg">
                            <label class="fg-label">Category</label>
                            <select name="category_id" id="medCategory" class="fg-select">
                                <option value="">— No Category —</option>
                            </select>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Unit</label>
                            <input type="text" name="unit" id="medUnit" class="fg-input" placeholder="pcs, tabs, bottles…" autocomplete="off">
                        </div>
                    </div>
                    <div class="fg">
                        <label class="fg-label">Description</label>
                        <textarea name="description" id="medDesc" class="fg-textarea" placeholder="Optional notes about this item…"></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-lbl">Stock &amp; Expiry</div>
                <div class="form-section-body">
                    <div class="form-grid-3">
                        <div class="fg">
                            <label class="fg-label">Stock Quantity <span class="req">*</span></label>
                            <input type="number" name="stock_qty" id="medStock" class="fg-input" min="0" required>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Reorder Level <span class="req">*</span></label>
                            <input type="number" name="reorder_level" id="medReorder" class="fg-input" min="0" required>
                        </div>
                        <div class="fg">
                            <label class="fg-label">Expiration Date</label>
                            <input type="text" name="expiration_date" id="medExpiry" class="fg-input" placeholder="yyyy-mm-dd">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;margin-top:4px;" id="medSubmitBtn">
                        + Add Item
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════
         ADD CATEGORY MODAL
    ════════════════════════════ -->
    <div id="categoryModal" title="Add Category" class="hidden">
        <form id="categoryForm" style="padding:18px 20px;">
            <div class="fg">
                <label class="fg-label">Category Name <span class="req">*</span></label>
                <input type="text" name="name" id="catName" class="fg-input" required autocomplete="off"
                       placeholder="e.g. Antibiotics, Vitamins, Analgesics…">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;margin-top:4px;">
                + Add Category
            </button>
        </form>
    </div>

    <!-- ════════════════════════════
         REPORT MODAL
    ════════════════════════════ -->
    <div id="reportModal" title="Generate Medicine Report" class="hidden">
        <form id="reportForm" style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
            <div class="form-grid-2">
                <div class="fg">
                    <label class="fg-label">Category</label>
                    <select name="category_id" id="rptCategory" class="fg-select">
                        <option value="0">All Categories</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fg-label">Stock Status</label>
                    <select name="status" id="rptStatus" class="fg-select">
                        <option value="ALL">All</option>
                        <option value="OUT_OF_STOCK">Out of Stock</option>
                        <option value="CRITICAL">Critical</option>
                        <option value="OK">OK</option>
                        <option value="EXPIRING_SOON">Expiring Soon</option>
                    </select>
                </div>
            </div>
            <div class="fg">
                <label class="fg-label">Expiring within (days)</label>
                <input type="number" name="exp_days" id="rptExpDays" class="fg-input" value="30" min="1" max="365">
            </div>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-ghost" id="rptPreviewBtn" style="flex:1;justify-content:center;">Preview</button>
                <button type="button" class="btn btn-primary" id="rptPrintBtn" style="flex:1;justify-content:center;">↗ Print</button>
            </div>
        </form>
    </div>

    <!-- ════════════════════════════
         REPORT PREVIEW MODAL
    ════════════════════════════ -->
    <div id="reportPreviewModal" title="Report Preview" class="hidden">
        <div class="rpt-summary" id="rptSummaryBar">—</div>
        <div class="rpt-table-scroll">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Reorder</th>
                        <th>Unit</th>
                        <th>Expiration</th>
                        <th>Status</th>
                        <th>Expiring Soon</th>
                    </tr>
                </thead>
                <tbody id="rptTableBody"></tbody>
            </table>
        </div>
    </div>

    <script src="js/medicine_inventory.js"></script>
</body>
</html>
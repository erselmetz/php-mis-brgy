<?php
require_once __DIR__ . '/../../includes/app.php';
requireSecretary();

if (!isset($_GET['id'])) exit;
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();
if (!$resident) exit;

$age = null;
if (!empty($resident['birthdate']))
    $age = (new DateTime($resident['birthdate']))->diff(new DateTime())->y;

$fullName = trim(
    $resident['first_name'] . ' ' .
    (!empty($resident['middle_name']) ? $resident['middle_name'].' ' : '') .
    $resident['last_name'] .
    (!empty($resident['suffix']) ? ', '.$resident['suffix'] : '')
);

$histStmt = $conn->prepare("
    SELECT cr.*, u.name AS issued_by_name
    FROM certificate_request cr
    LEFT JOIN users u ON cr.issued_by = u.id
    WHERE cr.resident_id = ?
    ORDER BY cr.requested_at DESC
");
$histStmt->bind_param("i", $id);
$histStmt->execute();
$histRows = $histStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$certMeta = [
    'Barangay Clearance'    => ['code'=>'BC','label'=>'Barangay Clearance',   'desc'=>'General purpose & employment'],
    'Indigency Certificate' => ['code'=>'IC','label'=>'Indigency Certificate', 'desc'=>'Financial assistance'],
    'Residency Certificate' => ['code'=>'RC','label'=>'Residency Certificate', 'desc'=>'Proof of residency'],
];
$statusConf = [
    'Pending'  => ['label'=>'Pending',  'cls'=>'badge-pending'],
    'Approved' => ['label'=>'Approved', 'cls'=>'badge-approved'],
    'Printed'  => ['label'=>'Printed',  'cls'=>'badge-printed'],
    'Rejected' => ['label'=>'Rejected', 'cls'=>'badge-rejected'],
];
?>
<style>
/* ── variables inherited from index.php ── */

/* Record card */
.rec-card{background:var(--paper);border:1px solid var(--rule);border-top:3px solid var(--accent);border-radius:2px;box-shadow:var(--card-shadow);margin-bottom:18px;}
.rec-header{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:20px;padding:18px 22px;border-bottom:1px solid var(--rule);background:linear-gradient(to right,var(--accent-lt) 0%,var(--paper) 70%);}
.rec-id-block{padding:10px 16px;background:var(--accent);border-radius:2px;text-align:center;flex-shrink:0;}
.rec-id-label{font-size:7.5px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:rgba(255,255,255,.6);margin-bottom:4px;}
.rec-id-num{font-family:var(--font-mono);font-size:19px;font-weight:700;color:#fff;letter-spacing:1px;}
.rec-name{font-family:var(--font-serif);font-size:19px;font-weight:700;color:var(--ink);letter-spacing:.1px;margin-bottom:6px;}
.rec-tags{display:flex;flex-wrap:wrap;gap:5px;}
.rec-tag{display:inline-block;padding:2px 9px;border:1px solid var(--rule-dark);border-radius:2px;font-size:10px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--ink-muted);background:#fff;}
.rec-tag.accent{border-color:var(--accent);color:var(--accent);background:var(--accent-lt);}
.rec-meta{text-align:right;flex-shrink:0;}
.rec-meta-lbl{font-size:9px;color:var(--ink-faint);text-transform:uppercase;letter-spacing:.8px;margin-bottom:2px;}
.rec-meta-val{font-size:12px;font-weight:600;color:var(--ink);}

/* Data grid */
.rec-grid{display:grid;grid-template-columns:repeat(4,1fr);}
@media(max-width:780px){.rec-grid{grid-template-columns:repeat(2,1fr);}}
.rec-cell{padding:12px 20px;border-right:1px solid var(--rule);}
.rec-cell:last-child{border-right:none;}
.rec-cell:nth-child(n+5){border-top:1px solid var(--rule);}
.cell-lbl{font-size:8.5px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:var(--ink-faint);margin-bottom:4px;}
.cell-val{font-size:13px;font-weight:500;color:var(--ink);}

/* Section divider */
.section-div{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.section-div-lbl{font-size:8.5px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--ink-faint);white-space:nowrap;}
.section-div-line{flex:1;height:1px;background:var(--rule);}

/* Workspace */
.workspace{display:grid;grid-template-columns:296px 1fr;gap:18px;}
@media(max-width:860px){.workspace{grid-template-columns:1fr;}}

/* Form panel */
.form-panel{background:var(--paper);border:1px solid var(--rule);border-top:3px solid var(--accent);border-radius:2px;box-shadow:var(--card-shadow);overflow:hidden;}
.panel-hdr{padding:11px 18px;border-bottom:1px solid var(--rule);background:#f7f5f0;display:flex;align-items:center;gap:8px;}
.panel-hdr-icon{font-size:12px;opacity:.65;}
.panel-hdr-lbl{font-size:9.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-muted);}
.form-body{padding:18px;}

/* Cert type cards */
.ct-list{display:flex;flex-direction:column;gap:7px;margin-bottom:16px;}
.cert-type-option{display:grid;grid-template-columns:30px 1fr 16px;align-items:center;gap:10px;padding:11px 13px;border:1.5px solid var(--rule-dark);border-radius:3px;cursor:pointer;background:#fff;transition:border-color .14s,background .14s,box-shadow .14s;}
.cert-type-option:hover{border-color:var(--accent);background:var(--accent-lt);}
.cert-type-option.is-selected{border-color:var(--accent);background:var(--accent-lt);box-shadow:inset 3px 0 0 var(--accent);}
.ct-code-tag{font-family:var(--font-mono);font-size:10.5px;font-weight:700;color:var(--ink-muted);background:#f0ede8;border:1px solid var(--rule);border-radius:2px;padding:3px 5px;text-align:center;transition:all .14s;}
.cert-type-option.is-selected .ct-code-tag{background:var(--accent);border-color:var(--accent);color:#fff;}
.ct-name{font-size:12px;font-weight:600;color:var(--ink);margin-bottom:1px;}
.ct-desc-text{font-size:10px;color:var(--ink-faint);font-style:italic;}
.ct-radio{width:14px;height:14px;border-radius:50%;border:1.5px solid var(--rule-dark);transition:all .14s;flex-shrink:0;}
.cert-type-option.is-selected .ct-radio{border-color:var(--accent);background:var(--accent);box-shadow:inset 0 0 0 3px #fff;}

/* Fields */
.field-grp{margin-bottom:14px;}
.field-lbl{display:block;font-size:8.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:6px;}
.field-inp{width:100%;padding:10px 13px;border:1.5px solid var(--rule-dark);border-radius:3px;font-family:var(--font-sans);font-size:13px;color:var(--ink);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s;}
.field-inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);}
.field-inp::placeholder{color:var(--ink-faint);font-style:italic;font-size:12px;}

/* Form msg */
.form-msg{padding:9px 13px;border-radius:3px;font-size:12px;font-weight:500;margin-bottom:12px;display:none;border-left:3px solid;}
.form-msg[data-type="success"]{background:#edfaf3;color:#1a5c35;border-left-color:#1a5c35;}
.form-msg[data-type="error"]{background:#fdeeed;color:#7a1f1a;border-left-color:#7a1f1a;}

/* Submit btn */
.submit-btn{width:100%;padding:11px 16px;background:var(--accent);color:#fff;border:none;border-radius:3px;font-family:var(--font-sans);font-size:12.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;cursor:pointer;transition:filter .15s,transform .1s;}
.submit-btn:hover{filter:brightness(1.1);}
.submit-btn:active{transform:scale(.98);}
.submit-btn:disabled{opacity:.5;cursor:not-allowed;filter:none;transform:none;}

/* History panel */
.hist-panel{background:var(--paper);border:1px solid var(--rule);border-top:3px solid var(--ink-muted);border-radius:2px;box-shadow:var(--card-shadow);overflow:hidden;}
.hist-panel-hdr{display:flex;align-items:center;justify-content:space-between;}
.hist-count{font-family:var(--font-mono);font-size:9.5px;color:var(--ink-faint);letter-spacing:.5px;}

/* Table */
.hist-tbl{width:100%;border-collapse:collapse;font-size:12.5px;}
.hist-tbl thead th{padding:9px 15px;background:#f7f5f0;text-align:left;font-size:8.5px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:var(--ink-muted);border-bottom:1px solid var(--rule-dark);white-space:nowrap;}
.hist-tbl tbody tr{border-bottom:1px solid #f0ede8;transition:background .1s;}
.hist-tbl tbody tr:last-child{border-bottom:none;}
.hist-tbl tbody tr:hover{background:#faf9f5;}
.hist-tbl td{padding:11px 15px;vertical-align:middle;color:var(--ink);}

/* Cert type col */
.ct-row{display:flex;align-items:center;gap:8px;}
.ct-tag{display:inline-block;font-family:var(--font-mono);font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:2px;letter-spacing:.5px;flex-shrink:0;}
.ct-BC{background:#edf3fa;color:#1a3a5c;border:1px solid #c0d4e8;}
.ct-IC{background:#fef9ec;color:#7a5700;border:1px solid #e8d8a0;}
.ct-RC{background:#edfaf3;color:#1a5c35;border:1px solid #a0d8bc;}
.ct-full{font-size:12px;font-weight:500;color:var(--ink);}

/* Purpose */
.hist-purpose{max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--ink-muted);}

/* Status badges */
.status-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:2px;font-size:9.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;}
.badge-pending {background:#fef9ec;color:#7a5700;border:1px solid color-mix(in srgb,#7a5700 20%,transparent);}
.badge-approved{background:#edfaf3;color:#1a5c35;border:1px solid color-mix(in srgb,#1a5c35 20%,transparent);}
.badge-printed {background:#edf3fa;color:#1a3a5c;border:1px solid color-mix(in srgb,#1a3a5c 20%,transparent);}
.badge-rejected{background:#fdeeed;color:#7a1f1a;border:1px solid color-mix(in srgb,#7a1f1a 20%,transparent);}

/* Date */
.hist-date{font-size:11.5px;color:var(--ink-muted);white-space:nowrap;}
.hist-time{font-size:10px;color:var(--ink-faint);display:block;margin-top:1px;}

/* Row num */
.row-num{font-family:var(--font-mono);font-size:9.5px;color:var(--ink-faint);text-align:right;padding-right:4px;}

/* Action btns */
.act-btn{display:inline-flex;align-items:center;gap:4px;padding:5px 11px;border-radius:2px;font-size:9.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;cursor:pointer;border:1.5px solid;font-family:var(--font-sans);transition:all .14s;white-space:nowrap;}
.btn-print{background:var(--accent);border-color:var(--accent);color:#fff;}
.btn-print:hover{filter:brightness(1.1);}
.btn-reprint{background:#fff;border-color:var(--rule-dark);color:var(--ink-muted);}
.btn-reprint:hover{border-color:var(--ink-muted);color:var(--ink);}

/* Empty */
.hist-empty{padding:52px 24px;text-align:center;}
.hist-empty-icon{font-size:26px;margin-bottom:12px;opacity:.25;}
.hist-empty-txt{font-size:12px;color:var(--ink-faint);font-style:italic;}
</style>

<!-- ── Resident Record Card ── -->
<div class="rec-card su">
    <div class="rec-header">
        <div class="rec-id-block">
            <div class="rec-id-label">Res. No.</div>
            <div class="rec-id-num"><?= str_pad($resident['id'], 4, '0', STR_PAD_LEFT) ?></div>
        </div>
        <div>
            <div class="rec-name"><?= htmlspecialchars($fullName) ?></div>
            <div class="rec-tags">
                <?php if ($age !== null): ?><span class="rec-tag accent"><?= $age ?> years old</span><?php endif; ?>
                <span class="rec-tag"><?= htmlspecialchars($resident['gender'] ?: '—') ?></span>
                <span class="rec-tag"><?= htmlspecialchars($resident['civil_status'] ?: '—') ?></span>
                <?php if ($resident['voter_status']==='Yes'): ?><span class="rec-tag accent">Registered Voter</span><?php endif; ?>
                <?php if ($resident['disability_status']==='Yes'): ?><span class="rec-tag">PWD</span><?php endif; ?>
                <?php if (!empty($resident['religion'])): ?><span class="rec-tag"><?= htmlspecialchars($resident['religion']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="rec-meta">
            <div class="rec-meta-lbl">Date of Birth</div>
            <div class="rec-meta-val"><?= $resident['birthdate'] ? date('F d, Y', strtotime($resident['birthdate'])) : '—' ?></div>
            <div style="margin-top:8px;" class="rec-meta-lbl">Citizenship</div>
            <div class="rec-meta-val"><?= htmlspecialchars($resident['citizenship'] ?: '—') ?></div>
        </div>
    </div>
    <div class="rec-grid">
        <div class="rec-cell"><div class="cell-lbl">Address</div><div class="cell-val"><?= htmlspecialchars($resident['address'] ?: '—') ?></div></div>
        <div class="rec-cell"><div class="cell-lbl">Birthplace</div><div class="cell-val"><?= htmlspecialchars($resident['birthplace'] ?: '—') ?></div></div>
        <div class="rec-cell"><div class="cell-lbl">Contact No.</div><div class="cell-val"><?= htmlspecialchars($resident['contact_no'] ?: '—') ?></div></div>
        <div class="rec-cell"><div class="cell-lbl">Occupation</div><div class="cell-val"><?= htmlspecialchars($resident['occupation'] ?: '—') ?></div></div>
    </div>
</div>

<!-- Section divider -->
<div class="section-div su su1">
    <span class="section-div-lbl">Certificate Operations</span>
    <div class="section-div-line"></div>
</div>

<!-- ── Workspace ── -->
<div class="workspace su su1">

    <!-- Form panel -->
    <div class="form-panel">
        <div class="panel-hdr">
            <span class="panel-hdr-icon">✦</span>
            <span class="panel-hdr-lbl">New Certificate Request</span>
        </div>
        <div class="form-body">
            <form id="certRequestForm">
                <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                <input type="hidden" id="selectedCertType" name="certificate_type">

                <div class="field-lbl" style="margin-bottom:9px;">Select Certificate Type</div>
                <div class="ct-list">
                    <?php foreach ($certMeta as $type => $m): ?>
                    <div class="cert-type-option" data-type="<?= htmlspecialchars($type) ?>">
                        <div class="ct-code-tag"><?= $m['code'] ?></div>
                        <div>
                            <div class="ct-name"><?= htmlspecialchars($m['label']) ?></div>
                            <div class="ct-desc-text"><?= htmlspecialchars($m['desc']) ?></div>
                        </div>
                        <div class="ct-radio"></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="field-grp">
                    <label class="field-lbl" for="certPurposeInput">Purpose of Request</label>
                    <input class="field-inp" type="text" id="certPurposeInput" name="purpose"
                        placeholder="e.g., Employment, Scholarship, Travel…" autocomplete="off">
                </div>

                <div id="formMessage" class="form-msg"></div>

                <button type="submit" class="submit-btn" id="certSubmitBtn">Submit Request</button>
            </form>
        </div>
    </div>

    <!-- History panel -->
    <div class="hist-panel">
        <div class="panel-hdr hist-panel-hdr">
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="panel-hdr-icon">▤</span>
                <span class="panel-hdr-lbl">Request History</span>
            </div>
            <span class="hist-count"><?= count($histRows) ?> RECORD<?= count($histRows)!==1?'S':'' ?></span>
        </div>

        <?php if (count($histRows) > 0): ?>
        <div style="overflow-x:auto;">
            <table class="hist-tbl">
                <thead>
                    <tr>
                        <th style="width:26px;">#</th>
                        <th>Certificate</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Processed By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($histRows as $i => $row):
                    $sc  = $statusConf[$row['status']] ?? ['label'=>$row['status'],'cls'=>'badge-pending'];
                    $cm  = $certMeta[$row['certificate_type']] ?? ['code'=>'??','label'=>$row['certificate_type']];
                    $canPrint   = in_array($row['status'], ['Pending','Approved']);
                    $isReprint  = $row['status'] === 'Printed';
                ?>
                    <tr>
                        <td class="row-num"><?= $i+1 ?></td>
                        <td>
                            <div class="ct-row">
                                <span class="ct-tag ct-<?= $cm['code'] ?>"><?= $cm['code'] ?></span>
                                <span class="ct-full"><?= htmlspecialchars($cm['label']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="hist-purpose" title="<?= htmlspecialchars($row['purpose']) ?>">
                                <?= htmlspecialchars($row['purpose']) ?>
                            </div>
                        </td>
                        <td><span class="status-badge <?= $sc['cls'] ?>"><?= $sc['label'] ?></span></td>
                        <td>
                            <div class="hist-date">
                                <?= date('M d, Y', strtotime($row['requested_at'])) ?>
                                <span class="hist-time"><?= date('h:i A', strtotime($row['requested_at'])) ?></span>
                            </div>
                        </td>
                        <td style="font-size:12px;color:var(--ink-muted);"><?= htmlspecialchars($row['issued_by_name']?:'—') ?></td>
                        <td>
                            <?php if ($canPrint): ?>
                                <button class="act-btn btn-print" onclick="printCertificate(<?= $row['id'] ?>)">⬡ Print</button>
                            <?php elseif ($isReprint): ?>
                                <button class="act-btn btn-reprint" onclick="printCertificate(<?= $row['id'] ?>)">↩ Re-print</button>
                            <?php else: ?>
                                <span style="font-size:11px;color:var(--ink-faint);">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="hist-empty">
            <div class="hist-empty-icon">📄</div>
            <div class="hist-empty-txt">No certificate requests on record for this resident.</div>
        </div>
        <?php endif; ?>
    </div>

</div>
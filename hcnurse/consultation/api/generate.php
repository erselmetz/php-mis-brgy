<?php
/**
 * Consultation Generate / Print
 * Replaces: public/hcnurse/consultation/api/generate.php
 *
 * Redesigned with government-document aesthetic.
 * Program type is always read from meta.program in the notes JSON
 * so it's never lost even if status = Completed.
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$doc         = $_GET['doc']         ?? 'summary';   // summary | report | certificate
$period      = $_GET['period']      ?? 'monthly';
$month       = $_GET['month']       ?? date('Y-m');
$resident_id = (int)($_GET['resident_id'] ?? 0);
$purpose     = trim($_GET['purpose'] ?? '');

if (!in_array($doc, ['summary','report','certificate'], true)) $doc = 'summary';

/* ── Date range ── */
function compute_range(string $period, string $month): array {
    $today = date('Y-m-d');
    if ($period === 'daily')  return [$today, $today, 'Daily — '.date('d F Y')];
    if ($period === 'weekly') {
        $f = date('Y-m-d', strtotime('monday this week'));
        $t = date('Y-m-d', strtotime('sunday this week'));
        return [$f, $t, 'Weekly — '.date('d M', strtotime($f)).' to '.date('d M Y', strtotime($t))];
    }
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
    $f = $month.'-01';
    $t = date('Y-m-t', strtotime($f));
    return [$f, $t, 'Monthly — '.date('F Y', strtotime($f))];
}
[$from, $to, $periodLabel] = compute_range($period, $month);

if ($doc !== 'report' && $resident_id <= 0) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Patient required</title></head><body style="font-family:system-ui,sans-serif;padding:28px;max-width:480px;">';
    echo '<h1 style="font-size:18px;margin:0 0 12px;">Patient required</h1>';
    echo '<p style="color:#444;line-height:1.5;">Patient Summary and Health Certificate need a selected resident. ';
    echo 'Open <strong>Generate Document</strong>, choose the document type, then search and select a patient.</p>';
    echo '<p style="margin-top:20px;"><a href="javascript:history.back()">← Go back</a></p>';
    echo '</body></html>';
    exit;
}

/* ── Fetch ── */
$sql  = "SELECT c.id, c.resident_id, c.complaint, c.diagnosis, c.treatment, c.notes, c.consultation_date,
                CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name,
                r.birthdate, r.address
         FROM consultations c
         LEFT JOIN residents r ON r.id = c.resident_id
         WHERE c.consultation_date BETWEEN ? AND ?";
$params = [$from, $to]; $types = "ss";
if ($doc !== 'report') { $sql .= " AND c.resident_id = ?"; $params[] = $resident_id; $types .= "i"; }
$sql .= " ORDER BY c.consultation_date DESC, c.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $meta = meta_normalize(meta_decode($row['notes'] ?? ''));
    $row['meta'] = $meta;
    $rows[] = $row;
}

$residentName = $rows[0]['resident_name'] ?? '';
$birthdate    = $rows[0]['birthdate']     ?? '';
$address      = $rows[0]['address']       ?? '';

/* age computation from actual birthdate */
$ageStr = '';
if ($birthdate) {
    $bd  = new DateTime($birthdate);
    $age = $bd->diff(new DateTime())->y;
    $ageStr = $age.' years old';
}

/* summary counts */
$total     = count($rows);
$completed = count(array_filter($rows, fn($r) => ($r['meta']['status']??'') === 'Completed'));
$ongoing   = count(array_filter($rows, fn($r) => ($r['meta']['status']??'') === 'Ongoing'));
$other     = $total - $completed - $ongoing;

/* program breakdown (type is preserved in meta.program) */
$byProgram = [];
foreach ($rows as $r) {
    $prog = $r['meta']['program'] ?? 'unknown';
    $byProgram[$prog] = ($byProgram[$prog] ?? 0) + 1;
}

$docTitles = [
    'summary'     => 'Consultation Summary',
    'report'      => 'Consultation Report',
    'certificate' => 'Health Certification',
];
$docTitle = $docTitles[$doc] ?? 'Consultation Document';

function h($s){ return htmlspecialchars((string)($s??''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= h($docTitle) ?></title>
    <?= loadAsset('css','style.css') ?>
    <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Segoe UI',Arial,sans-serif;font-size:11px;color:#111;padding:26px 30px;background:#fff;}

    /* ── Header ── */
    .rpt-header{margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid #2d5a27;}
    .rpt-seal-row{display:flex;align-items:flex-start;gap:16px;}
    .rpt-seal{width:44px;height:44px;border-radius:50%;border:2px solid #2d5a27;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;}
    .rpt-brgy-block{flex:1;}
    .rpt-brgy{font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#2d5a27;margin-bottom:2px;}
    .rpt-republic{font-size:8.5px;color:#777;margin-bottom:6px;}
    .rpt-doc-title{font-size:17px;font-weight:700;color:#1a1a1a;margin-bottom:2px;}
    .rpt-doc-sub{font-size:10px;color:#777;}
    .rpt-meta-row{display:flex;gap:20px;margin-top:10px;font-size:9.5px;color:#555;flex-wrap:wrap;}
    .rpt-meta-row span strong{color:#1a1a1a;}

    /* ── Resident info block (summary/cert) ── */
    .res-block{margin-bottom:16px;padding:12px 16px;background:#f9f7f3;border:1px solid #d8d4cc;border-left:3px solid #2d5a27;}
    .res-block-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px 20px;}
    .res-field-lbl{font-size:8px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#999;margin-bottom:2px;}
    .res-field-val{font-size:12px;font-weight:600;color:#1a1a1a;}

    /* ── Summary strip ── */
    .rpt-summary{display:flex;gap:0;margin-bottom:14px;border:1px solid #e0e0e0;border-radius:2px;overflow:hidden;}
    .rpt-sum-cell{flex:1;padding:10px 14px;text-align:center;border-right:1px solid #e0e0e0;}
    .rpt-sum-cell:last-child{border-right:none;}
    .rpt-sum-val{font-size:20px;font-weight:700;font-family:'Courier New',monospace;color:#1a1a1a;line-height:1;margin-bottom:3px;}
    .rpt-sum-lbl{font-size:8px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#999;}

    /* ── Program breakdown ── */
    .prog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px;}
    .prog-cell{padding:8px 10px;background:#f9f7f3;border:1px solid #e0e0e0;border-radius:2px;text-align:center;}
    .prog-val{font-size:18px;font-weight:700;font-family:'Courier New',monospace;color:#1a1a1a;line-height:1;margin-bottom:2px;}
    .prog-lbl{font-size:8px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#999;}

    /* ── Table ── */
    table{width:100%;border-collapse:collapse;font-size:10.5px;margin-top:4px;}
    thead th{padding:7px 9px;background:#f0ede6;text-align:left;font-size:8px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#555;border-bottom:1.5px solid #2d5a27;}
    tbody tr{border-bottom:1px solid #eee;}
    tbody tr:nth-child(even){background:#fafaf8;}
    tbody td{padding:7px 9px;vertical-align:top;}
    .td-date{font-family:'Courier New',monospace;font-size:10px;white-space:nowrap;color:#555;}
    .td-name{font-weight:600;}
    .td-trunc{max-width:150px;word-wrap:break-word;color:#444;font-size:10px;}
    .prog-tag{font-size:8px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;padding:1px 5px;background:#edf3fa;color:#1a3a5c;border-radius:1px;display:inline-block;}
    .sub-tag{font-size:8px;font-weight:700;text-transform:uppercase;padding:1px 5px;background:#f0ede6;color:#555;border-radius:1px;display:inline-block;}
    .sb{font-size:8px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;padding:1px 5px;border-radius:1px;display:inline-block;}
    .sb-completed{background:#edfaf3;color:#1a5c35;}
    .sb-ongoing{background:#fef9ec;color:#7a5700;}
    .sb-dismissed{background:#f3f1ec;color:#5a5a5a;}

    /* ── Certificate body ── */
    .cert-body{line-height:1.9;font-size:12px;margin:16px 0;}
    .cert-body p{margin-bottom:12px;text-align:justify;}
    .cert-highlight{font-weight:700;text-decoration:underline;}

    /* ── Signatures ── */
    .sigs{display:flex;justify-content:space-between;margin-top:36px;}
    .sig-block{text-align:center;}
    .sig-line{border-top:1px solid #aaa;width:190px;margin:32px auto 4px;}
    .sig-lbl{font-size:8.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#777;}
    .sig-title{font-size:8px;color:#999;margin-top:2px;}

    /* ── Footer ── */
    .rpt-footer{margin-top:18px;padding-top:10px;border-top:1px solid #ddd;display:flex;justify-content:space-between;font-size:8.5px;color:#aaa;}

    </style>
</head>
<body>

<div class="no-print" style="margin-bottom:16px;display:flex;gap:8px;">
    <button onclick="window.print()" style="padding:7px 18px;background:#2d5a27;color:#fff;border:none;border-radius:2px;font-weight:700;font-size:11px;letter-spacing:.5px;text-transform:uppercase;cursor:pointer;">↗ Print</button>
    <button onclick="window.close()" style="padding:7px 18px;background:#fff;color:#555;border:1.5px solid #ccc;border-radius:2px;font-weight:700;font-size:11px;letter-spacing:.5px;text-transform:uppercase;cursor:pointer;">✕ Close</button>
</div>

<!-- Header -->
<div class="rpt-header">
    <div class="rpt-seal-row">
        <div class="rpt-seal">⚕</div>
        <div class="rpt-brgy-block">
            <div class="rpt-republic">Republic of the Philippines</div>
            <div class="rpt-brgy">Barangay Bombongan Health Center</div>
            <div class="rpt-doc-title"><?= h($docTitle) ?></div>
            <div class="rpt-doc-sub"><?= h($periodLabel) ?> · <?= h($from) ?> to <?= h($to) ?></div>
        </div>
    </div>
    <div class="rpt-meta-row">
        <span>Generated: <strong><?= date('d F Y, h:i A') ?></strong></span>
        <?php if ($doc !== 'report'): ?>
        <span>Resident: <strong><?= h($residentName) ?></strong></span>
        <?php endif; ?>
        <span>Total Records: <strong><?= $total ?></strong></span>
    </div>
</div>

<?php if ($doc === 'certificate'): ?>
    <!-- ═══ CERTIFICATE ═══ -->
    <div class="res-block">
        <div class="res-block-grid">
            <div><div class="res-field-lbl">Full Name</div><div class="res-field-val"><?= h($residentName) ?></div></div>
            <div><div class="res-field-lbl">Age</div><div class="res-field-val"><?= h($ageStr) ?></div></div>
            <div><div class="res-field-lbl">Address</div><div class="res-field-val"><?= h($address ?: '—') ?></div></div>
        </div>
    </div>

    <div class="cert-body">
        <p>TO WHOM IT MAY CONCERN:</p>
        <p>
            This is to certify that <span class="cert-highlight"><?= h($residentName) ?></span>,
            <?= $ageStr ? h($ageStr).', ' : '' ?>
            a resident of Barangay Bombongan, has been availing health services
            at the Barangay Health Center and has on record
            <span class="cert-highlight"><?= $total ?></span> consultation visit<?= $total !== 1 ? 's' : '' ?>
            within the period of <strong><?= h($from) ?></strong> to <strong><?= h($to) ?></strong>.
        </p>
        <?php if ($purpose): ?>
        <p>
            This certification is issued upon the request of the above-named individual
            for the purpose of <span class="cert-highlight"><?= h($purpose) ?></span>
            and for whatever legal purpose it may serve.
        </p>
        <?php else: ?>
        <p>
            This certification is issued upon request and for whatever legal purpose it may serve.
        </p>
        <?php endif; ?>
        <p>
            Issued this <strong><?= date('jS') ?></strong> day of
            <strong><?= date('F Y') ?></strong> at the Barangay Bombongan Health Center.
        </p>
    </div>

    <div class="sigs">
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-lbl"><?= h($_SESSION['name'] ?? 'HC Nurse') ?></div>
            <div class="sig-title">Health Center Nurse</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-lbl">Barangay Captain</div>
            <div class="sig-title">Barangay Bombongan</div>
        </div>
    </div>

<?php else: ?>
    <!-- ═══ SUMMARY or REPORT ═══ -->

    <?php if ($doc === 'summary'): ?>
    <!-- Resident info block -->
    <div class="res-block">
        <div class="res-block-grid">
            <div><div class="res-field-lbl">Full Name</div><div class="res-field-val"><?= h($residentName) ?></div></div>
            <div><div class="res-field-lbl">Age</div><div class="res-field-val"><?= h($ageStr ?: '—') ?></div></div>
            <div><div class="res-field-lbl">Address</div><div class="res-field-val"><?= h($address ?: '—') ?></div></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary strip -->
    <div class="rpt-summary">
        <div class="rpt-sum-cell"><div class="rpt-sum-val"><?= $total ?></div><div class="rpt-sum-lbl">Total Visits</div></div>
        <div class="rpt-sum-cell"><div class="rpt-sum-val" style="color:#1a5c35;"><?= $completed ?></div><div class="rpt-sum-lbl">Completed</div></div>
        <div class="rpt-sum-cell"><div class="rpt-sum-val" style="color:#7a5700;"><?= $ongoing ?></div><div class="rpt-sum-lbl">Ongoing</div></div>
        <div class="rpt-sum-cell"><div class="rpt-sum-val" style="color:#999;"><?= $other ?></div><div class="rpt-sum-lbl">Other</div></div>
    </div>

    <!-- Program breakdown -->
    <?php if (!empty($byProgram)): ?>
    <div style="margin-bottom:10px;font-size:8px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:#aaa;">Program Breakdown</div>
    <div class="prog-grid">
        <?php foreach ($byProgram as $prog => $cnt): ?>
        <div class="prog-cell">
            <div class="prog-val"><?= $cnt ?></div>
            <div class="prog-lbl"><?= h(str_replace('_',' ',$prog)) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Records table -->
    <?php if (!$rows): ?>
        <div style="padding:28px;text-align:center;color:#999;border:1px dashed #ddd;border-radius:2px;">No records found.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:75px;">Date</th>
                <?php if ($doc === 'report'): ?><th>Resident</th><?php endif; ?>
                <th style="width:90px;">Program</th>
                <th style="width:80px;">Sub Type</th>
                <th style="width:70px;">Status</th>
                <th>Chief Complaint</th>
                <th style="width:100px;">Health Worker</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $m      = $r['meta'];
            $prog   = $m['program']  ?? '';
            $sub    = $m['sub_type'] ?? '';
            $status = $m['status']   ?? '';
            $stCls  = match(strtolower($status)){ 'completed'=>'sb-completed','ongoing'=>'sb-ongoing', default=>'sb-dismissed' };
        ?>
            <tr>
                <td class="td-date"><?= h($r['consultation_date']) ?></td>
                <?php if ($doc === 'report'): ?><td class="td-name"><?= h($r['resident_name']) ?></td><?php endif; ?>
                <td><?php if($prog): ?><span class="prog-tag"><?= h(str_replace('_',' ',$prog)) ?></span><?php else: ?>—<?php endif; ?></td>
                <td><?php if($sub && $sub !== 'all'): ?><span class="sub-tag"><?= h(str_replace('_',' ',$sub)) ?></span><?php else: ?>—<?php endif; ?></td>
                <td><?php if($status): ?><span class="sb <?= $stCls ?>"><?= h($status) ?></span><?php else: ?>—<?php endif; ?></td>
                <td class="td-trunc"><?= h($r['complaint']) ?></td>
                <td style="font-size:9.5px;color:#666;"><?= h($m['health_worker'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="sigs">
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-lbl"><?= h($_SESSION['name'] ?? 'HC Nurse') ?></div>
            <div class="sig-title">Prepared by — Health Center Nurse</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-lbl">Barangay Captain</div>
            <div class="sig-title">Noted by — Barangay Bombongan</div>
        </div>
    </div>

<?php endif; ?>

<div class="rpt-footer">
    <span>Barangay Bombongan Health Center — MIS</span>
    <span>Printed: <?= date('d F Y h:i A') ?></span>
</div>
</body>
</html>
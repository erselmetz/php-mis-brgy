<?php
/**
 * Health Records Print
 * Replaces: public/hcnurse/health-records/print.php
 * Redesigned government-document print layout.
 */
require_once __DIR__ . '/../../includes/app.php';
requireHCNurse();

$type   = $_GET['type']   ?? 'maternal';
$period = $_GET['period'] ?? 'all';
$month  = $_GET['month']  ?? date('Y-m');
$search = trim($_GET['search'] ?? '');
$sub    = trim($_GET['sub']    ?? 'all');

$allowed = ['general','immunization','maternal','family_planning','prenatal','postnatal','child_nutrition'];
if (!in_array($type, $allowed, true)) $type = 'maternal';

function compute_range(string $p, string $m): array {
    $today = date('Y-m-d'); $ym = date('Y-m');
    if ($p === 'monthly') {
        if (!preg_match('/^\d{4}-\d{2}$/', $m)) $m = $ym;
        $f = $m.'-01'; return [$f, date('Y-m-t', strtotime($f))];
    }
    if ($p === 'daily')  return [$today, $today];
    if ($p === 'weekly') {
        return [date('Y-m-d',strtotime('monday this week')), date('Y-m-d',strtotime('sunday this week'))];
    }
    $f = $ym.'-01'; return [$f, date('Y-m-t', strtotime($f))];
}

[$from, $to] = compute_range($period, $month);

$sql    = "SELECT c.*, CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) AS resident_name
           FROM consultations c
           LEFT JOIN residents r ON r.id=c.resident_id
           WHERE c.consultation_date BETWEEN ? AND ?";
$params = [$from,$to]; $types = 'ss';
if ($search) {
    $sql .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ?)";
    $lk = "%{$search}%"; $params = array_merge($params,[$lk,$lk,$lk]); $types .= 'sss';
}
$sql .= ' ORDER BY c.consultation_date DESC';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$data = []; $cnt = ['total'=>0,'completed'=>0,'ongoing'=>0,'other'=>0];
while ($row = $res->fetch_assoc()) {
    $meta = meta_normalize(meta_decode($row['notes'] ?? ''));
    if (($meta['program'] ?? '') !== $type) continue;
    if ($sub !== 'all' && ($meta['sub_type'] ?? '') !== $sub) continue;
    $row['meta'] = $meta;
    $data[] = $row;
    $cnt['total']++;
    $s = strtolower($meta['status'] ?? '');
    if ($s === 'completed') $cnt['completed']++;
    elseif ($s === 'ongoing') $cnt['ongoing']++;
    else $cnt['other']++;
}

$typeLabels = [
    'maternal'=>'Maternal Records','family_planning'=>'Family Planning',
    'prenatal'=>'Prenatal Care','postnatal'=>'Postnatal Care',
    'child_nutrition'=>'Child Nutrition','immunization'=>'Immunization Records',
];
$pageTitle = $typeLabels[$type] ?? 'Health Records';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($pageTitle) ?> — Print</title>
<?= loadAsset('css','style.css') ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Source+Sans+3:wght@400;600;700&family=Source+Code+Pro:wght@400;600&display=swap');
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Source Sans 3',Arial,sans-serif;font-size:11px;color:#1a1a1a;background:#fff;padding:24px 32px;}
.no-print{margin-bottom:12px;}
@media print{.no-print{display:none;} body{padding:12px 18px;}}

/* letterhead */
.lh{border-bottom:3px solid #2d5a27;padding-bottom:12px;margin-bottom:14px;display:flex;align-items:flex-end;justify-content:space-between;gap:20px;}
.lh-left{}
.lh-brgy{font-family:'Source Serif 4',Georgia,serif;font-size:16px;font-weight:700;color:#2d5a27;letter-spacing:-.2px;}
.lh-addr{font-size:9px;color:#888;margin-top:2px;letter-spacing:.3px;}
.lh-right{text-align:right;}
.lh-doc-title{font-family:'Source Serif 4',Georgia,serif;font-size:13px;font-weight:700;color:#1a1a1a;}
.lh-doc-sub{font-size:9px;color:#888;margin-top:2px;}

/* meta strip */
.meta-strip{display:flex;gap:0;border:1px solid #d8d4cc;border-radius:2px;overflow:hidden;margin-bottom:12px;background:#f9f7f3;}
.ms-cell{flex:1;padding:8px 12px;border-right:1px solid #d8d4cc;}
.ms-cell:last-child{border-right:none;}
.ms-lbl{font-size:7px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#a0a0a0;margin-bottom:3px;}
.ms-val{font-family:'Source Code Pro',monospace;font-size:10.5px;font-weight:600;color:#1a1a1a;}

/* summary row */
.summary{display:flex;gap:8px;margin-bottom:14px;}
.sum-cell{padding:7px 14px;border-radius:2px;border:1px solid #d8d4cc;background:#fff;min-width:80px;text-align:center;}
.sum-n{font-family:'Source Code Pro',monospace;font-size:18px;font-weight:700;line-height:1;margin-bottom:2px;}
.sum-l{font-size:7.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#a0a0a0;}
.s-total .sum-n{color:#2d5a27;}
.s-ok    .sum-n{color:#1a5c35;}
.s-ong   .sum-n{color:#7a5700;}
.s-oth   .sum-n{color:#5a5a5a;}

/* table */
table{width:100%;border-collapse:collapse;margin-bottom:20px;}
thead th{padding:7px 10px;background:#f0ede6;border-bottom:2px solid #b8b4ac;font-size:7.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#5a5a5a;text-align:left;}
tbody tr{border-bottom:1px solid #f0ede8;}
tbody tr:hover{background:#fafaf8;}
td{padding:7px 10px;vertical-align:top;font-size:10.5px;}
.td-mono{font-family:'Source Code Pro',monospace;font-size:10px;color:#5a5a5a;white-space:nowrap;}
.td-name{font-weight:600;font-size:11px;}
.badge{display:inline-block;padding:2px 7px;border-radius:2px;font-size:8px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;border:1px solid;}
.b-comp{background:#edfaf3;color:#1a5c35;border-color:#a7f3d0;}
.b-ong {background:#fef9ec;color:#7a5700;border-color:#fde68a;}
.b-dis {background:#f3f1ec;color:#5a5a5a;border-color:#d8d4cc;}
.b-sub {background:#edf3fa;color:#1a3a5c;border-color:#bfdbfe;}
.td-trunc{max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

/* signatures */
.sigs{margin-top:36px;display:grid;grid-template-columns:1fr 1fr;gap:40px;}
.sig-block{text-align:center;}
.sig-line{border-top:1px solid #1a1a1a;margin-bottom:4px;margin-top:40px;}
.sig-name{font-weight:700;font-size:11px;}
.sig-role{font-size:9px;color:#888;margin-top:2px;}

/* footer */
.footer{margin-top:20px;padding-top:8px;border-top:1px solid #d8d4cc;display:flex;justify-content:space-between;font-size:8px;color:#a0a0a0;}
</style>
</head>
<body>
<div class="no-print" style="display:flex;gap:8px;align-items:center;">
    <button onclick="window.print()" style="padding:6px 18px;background:#2d5a27;color:#fff;border:none;border-radius:2px;font-size:11px;font-weight:700;cursor:pointer;">↗ Print</button>
    <button onclick="window.close()" style="padding:6px 18px;background:#fff;border:1.5px solid #b8b4ac;border-radius:2px;font-size:11px;cursor:pointer;">Close</button>
</div>

<!-- Letterhead -->
<div class="lh">
    <div class="lh-left">
        <div class="lh-brgy">Barangay Bombongan</div>
        <div class="lh-addr">Morong, Rizal · Barangay Health Center</div>
    </div>
    <div class="lh-right">
        <div class="lh-doc-title"><?= htmlspecialchars($pageTitle) ?></div>
        <div class="lh-doc-sub">Printed: <?= date('d F Y, h:i A') ?></div>
    </div>
</div>

<!-- Meta strip -->
<div class="meta-strip">
    <div class="ms-cell"><div class="ms-lbl">Program</div><div class="ms-val"><?= htmlspecialchars(ucwords(str_replace('_',' ',$type))) ?></div></div>
    <div class="ms-cell"><div class="ms-lbl">Period</div><div class="ms-val"><?= htmlspecialchars(ucfirst($period)) ?></div></div>
    <div class="ms-cell"><div class="ms-lbl">Date Range</div><div class="ms-val"><?= $from ?> → <?= $to ?></div></div>
    <div class="ms-cell"><div class="ms-lbl">Sub Type</div><div class="ms-val"><?= htmlspecialchars($sub === 'all' ? 'All' : ucwords(str_replace('_',' ',$sub))) ?></div></div>
    <?php if ($search): ?><div class="ms-cell"><div class="ms-lbl">Search</div><div class="ms-val"><?= htmlspecialchars($search) ?></div></div><?php endif; ?>
</div>

<!-- Summary -->
<div class="summary">
    <div class="sum-cell s-total"><div class="sum-n"><?= $cnt['total'] ?></div><div class="sum-l">Total</div></div>
    <div class="sum-cell s-ok">  <div class="sum-n"><?= $cnt['completed'] ?></div><div class="sum-l">Completed</div></div>
    <div class="sum-cell s-ong"> <div class="sum-n"><?= $cnt['ongoing'] ?></div><div class="sum-l">Ongoing</div></div>
    <div class="sum-cell s-oth"> <div class="sum-n"><?= $cnt['other'] ?></div><div class="sum-l">Other</div></div>
</div>

<!-- Table -->
<table>
    <thead>
        <tr>
            <th style="width:88px;">Date</th>
            <th>Patient</th>
            <th>Sub Type</th>
            <th>Health Worker</th>
            <th>Chief Complaint</th>
            <th>Diagnosis</th>
            <th style="width:72px;">Status</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$data): ?>
        <tr><td colspan="7" style="padding:20px;text-align:center;color:#a0a0a0;font-style:italic;">No records found for the selected filters.</td></tr>
    <?php else: foreach ($data as $row):
        $m  = $row['meta'];
        $st = strtolower($m['status'] ?? '');
        $bc = $st === 'completed' ? 'b-comp' : ($st === 'ongoing' ? 'b-ong' : 'b-dis');
        $stv = ucfirst($m['status'] ?? '—');
        $sub_v = $m['sub_type'] ?? '';
    ?>
        <tr>
            <td class="td-mono"><?= htmlspecialchars($row['consultation_date']) ?></td>
            <td class="td-name"><?= htmlspecialchars($row['resident_name']) ?></td>
            <td><?php if ($sub_v && $sub_v !== 'all'): ?><span class="badge b-sub"><?= htmlspecialchars($sub_v) ?></span><?php else: echo '—'; endif; ?></td>
            <td><?= htmlspecialchars($m['health_worker'] ?? '—') ?></td>
            <td class="td-trunc"><?= htmlspecialchars($row['complaint'] ?? '') ?></td>
            <td class="td-trunc"><?= htmlspecialchars($row['diagnosis'] ?? '—') ?></td>
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($stv) ?></span></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- Signatures -->
<div class="sigs">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-name"><?= htmlspecialchars($_SESSION['name'] ?? 'HC Nurse') ?></div>
        <div class="sig-role">Health Center Nurse</div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-name">Barangay Official</div>
        <div class="sig-role">Noted By</div>
    </div>
</div>

<div class="footer">
    <span>Barangay Bombongan Health Center — Official Record</span>
    <span>Generated: <?= date('d F Y h:i A') ?></span>
</div>

<script>window.onload=function(){window.print();}</script>
</body>
</html>
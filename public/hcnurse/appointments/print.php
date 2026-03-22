<?php
/**
 * Appointments Print
 * public/hcnurse/appointments/print.php
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$from   = $_GET['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? date('Y-m-d');
$status = $_GET['status'] ?? 'all';
$type   = $_GET['type']   ?? 'all';

$where  = "a.appt_date BETWEEN ? AND ?";
$params = [$from, $to]; $types = 'ss';
if ($status !== 'all') { $where .= " AND a.status=?"; $params[] = $status; $types .= 's'; }
if ($type   !== 'all') { $where .= " AND a.appt_type=?"; $params[] = $type; $types .= 's'; }

$st = $conn->prepare("
    SELECT a.*,
           CONCAT_WS(' ',r.first_name,r.middle_name,r.last_name) full_name,
           r.contact_no
    FROM appointments a
    INNER JOIN residents r ON r.id=a.resident_id AND r.deleted_at IS NULL
    WHERE {$where}
    ORDER BY a.appt_date ASC, a.appt_time ASC
    LIMIT 500
");
$st->bind_param($types,...$params); $st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$statusLabel = ['scheduled'=>'Scheduled','completed'=>'Completed','cancelled'=>'Cancelled','no_show'=>'No Show'];
$counts = array_count_values(array_column($rows,'status'));
function h($s){ return htmlspecialchars((string)($s??''),ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Appointments — Print</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Source+Sans+3:wght@400;600;700&family=Source+Code+Pro:wght@400;600&display=swap');
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Source Sans 3',Arial,sans-serif;font-size:11px;color:#1a1a1a;background:#fff;padding:24px 32px;}
.no-print{margin-bottom:12px;display:flex;gap:8px;}
@media print{.no-print{display:none;} body{padding:12px 18px;}}
.lh{border-bottom:3px solid #2d5a27;padding-bottom:10px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:flex-end;}
.lh-brgy{font-family:'Source Serif 4';font-size:16px;font-weight:700;color:#2d5a27;}
.lh-sub{font-size:9px;color:#888;margin-top:2px;}
.lh-right{text-align:right;}
.lh-title{font-family:'Source Serif 4';font-size:13px;font-weight:700;}
.lh-gen{font-size:9px;color:#888;margin-top:2px;}
.summary{display:flex;gap:14px;margin-bottom:12px;font-size:11px;}
.sum-cell{padding:6px 12px;background:#f9f7f3;border:1px solid #d8d4cc;border-radius:2px;}
.sum-val{font-family:'Source Code Pro';font-size:16px;font-weight:700;color:#1a1a1a;}
.sum-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.8px;color:#a0a0a0;}
table{width:100%;border-collapse:collapse;}
thead th{padding:7px 9px;background:#f0ede6;font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#5a5a5a;border-bottom:2px solid #b8b4ac;text-align:left;}
tbody tr{border-bottom:1px solid #f0ede8;}
td{padding:7px 9px;vertical-align:top;}
.mono{font-family:'Source Code Pro';font-size:9.5px;color:#5a5a5a;}
.badge{display:inline-block;padding:2px 7px;border-radius:2px;font-size:8px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;border:1px solid;}
.b-scheduled{background:#edf3fa;color:#1a3a5c;border-color:#bfdbfe;}
.b-completed{background:#edfaf3;color:#1a5c35;border-color:#a7f3d0;}
.b-cancelled{background:#f3f1ec;color:#5a5a5a;border-color:#d8d4cc;}
.b-no_show{background:#fdeeed;color:#7a1f1a;border-color:#fca5a5;}
.footer{margin-top:18px;padding-top:8px;border-top:1px solid #d8d4cc;display:flex;justify-content:space-between;font-size:8px;color:#a0a0a0;}
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="padding:6px 18px;background:#2d5a27;color:#fff;border:none;border-radius:2px;font-weight:700;cursor:pointer;">↗ Print</button>
    <button onclick="window.close()" style="padding:6px 18px;background:#fff;border:1.5px solid #b8b4ac;border-radius:2px;cursor:pointer;">Close</button>
</div>
<div class="lh">
    <div><div class="lh-brgy">Barangay Bombongan</div><div class="lh-sub">Health Center · Appointment Register</div></div>
    <div class="lh-right">
        <div class="lh-title">Appointments List</div>
        <div class="lh-gen"><?= h($from) ?> — <?= h($to) ?> · Printed: <?= date('d F Y h:i A') ?></div>
    </div>
</div>

<div class="summary">
    <div class="sum-cell"><div class="sum-val"><?= count($rows) ?></div><div class="sum-lbl">Total</div></div>
    <div class="sum-cell"><div class="sum-val" style="color:#1a3a5c;"><?= $counts['scheduled']??0 ?></div><div class="sum-lbl">Scheduled</div></div>
    <div class="sum-cell"><div class="sum-val" style="color:#1a5c35;"><?= $counts['completed']??0 ?></div><div class="sum-lbl">Completed</div></div>
    <div class="sum-cell"><div class="sum-val" style="color:#7a1f1a;"><?= $counts['no_show']??0 ?></div><div class="sum-lbl">No Show</div></div>
    <div class="sum-cell"><div class="sum-val" style="color:#5a5a5a;"><?= $counts['cancelled']??0 ?></div><div class="sum-lbl">Cancelled</div></div>
</div>

<table>
    <thead><tr>
        <th>Code</th><th>Date</th><th>Time</th><th>Patient</th><th>Contact</th>
        <th>Type</th><th>Purpose</th><th>Worker</th><th>Status</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="9" style="padding:18px;text-align:center;color:#a0a0a0;font-style:italic;">No appointments found for the selected filters.</td></tr>
    <?php else: foreach ($rows as $r):
        $sc='b-'.str_replace(' ','_',$r['status']??'scheduled');
        $sl=$statusLabel[$r['status']??'scheduled']??$r['status'];
    ?>
    <tr>
        <td class="mono"><?= h($r['appt_code']) ?></td>
        <td class="mono"><?= h($r['appt_date']) ?></td>
        <td class="mono"><?= h(substr($r['appt_time']??'',0,5)) ?></td>
        <td style="font-weight:600;"><?= h($r['full_name']) ?></td>
        <td class="mono"><?= h($r['contact_no']??'—') ?></td>
        <td><?= h(str_replace('_',' ',$r['appt_type']??'')) ?></td>
        <td><?= h($r['purpose']??'—') ?></td>
        <td><?= h($r['health_worker']??'—') ?></td>
        <td><span class="badge <?= $sc ?>"><?= h($sl) ?></span></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<div class="footer">
    <span>Barangay Bombongan Health Center · Official Record</span>
    <span>Generated: <?= date('d F Y h:i A') ?></span>
</div>
</body>
</html>
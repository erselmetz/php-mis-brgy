<?php
/**
 * Health Records Print Page — REDESIGNED
 * Replaces: public/hcnurse/health-records/print.php
 */
require_once __DIR__ . '/../../../includes/app.php';
requireHCNurse();

$type   = $_GET['type']   ?? 'maternal';
$period = $_GET['period'] ?? 'all';
$month  = $_GET['month']  ?? date('Y-m');
$search = trim($_GET['search'] ?? '');
$sub    = trim($_GET['sub']    ?? 'all');

$allowed = ['immunization','maternal','family_planning','prenatal','postnatal','child_nutrition'];
if (!in_array($type, $allowed, true)) $type = 'maternal';

function compute_range(string $period, string $month): array {
    $today = date('Y-m-d'); $ym = date('Y-m');
    if ($period === 'monthly') {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = $ym;
        return [$month.'-01', date('Y-m-t', strtotime($month.'-01'))];
    }
    if ($period === 'daily')  return [$today, $today];
    if ($period === 'weekly') return [date('Y-m-d',strtotime('monday this week')), date('Y-m-d',strtotime('sunday this week'))];
    return [$ym.'-01', date('Y-m-t', strtotime($ym.'-01'))];
}
[$from, $to] = compute_range($period, $month);

$sql  = "SELECT c.id, c.complaint, c.diagnosis, c.treatment, c.notes, c.consultation_date,
                CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
         FROM consultations c
         LEFT JOIN residents r ON r.id = c.resident_id
         WHERE c.consultation_date BETWEEN ? AND ?
         ORDER BY c.consultation_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$res  = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $meta = meta_normalize(meta_decode($row['notes'] ?? ''));
    if (($meta['program'] ?? '') !== $type) continue;
    if ($sub !== 'all' && ($meta['sub_type'] ?? '') !== $sub) continue;
    if ($search) {
        $name = strtolower($row['resident_name']);
        if (!str_contains($name, strtolower($search))) continue;
    }
    $row['meta'] = $meta;
    $data[] = $row;
}

$typeLabels = [
    'maternal'=>'Maternal','family_planning'=>'Family Planning',
    'prenatal'=>'Prenatal Care','postnatal'=>'Postnatal Care',
    'child_nutrition'=>'Child Nutrition','immunization'=>'Immunization'
];
$typeLabel = $typeLabels[$type] ?? ucfirst(str_replace('_',' ',$type));
$periodLabel = $period === 'monthly' ? 'Monthly — '.date('F Y',strtotime($month.'-01'))
    : ($period === 'daily' ? 'Daily — '.date('d F Y')
    : ($period === 'weekly' ? 'Weekly' : 'All Records'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Health Records — <?= htmlspecialchars($typeLabel) ?></title>
    <style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body {
        font-family:'Segoe UI', Arial, sans-serif;
        font-size:11px; color:#111;
        padding:24px 28px;
        background:#fff;
    }
    /* ── Report header ── */
    .rpt-header { margin-bottom:18px; padding-bottom:14px; border-bottom:2px solid #2d5a27; }
    .rpt-brgy   { font-size:9px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#777; margin-bottom:4px; }
    .rpt-title  { font-size:18px; font-weight:700; color:#1a1a1a; margin-bottom:3px; }
    .rpt-sub    { font-size:10px; color:#777; }
    .rpt-meta   { margin-top:8px; display:flex; gap:20px; font-size:9.5px; color:#555; }
    .rpt-meta span strong { color:#1a1a1a; }

    /* ── Summary strip ── */
    .rpt-summary {
        display:flex; gap:0; margin-bottom:14px;
        border:1px solid #e0e0e0; border-radius:2px; overflow:hidden;
    }
    .rpt-sum-cell {
        flex:1; padding:10px 14px; text-align:center;
        border-right:1px solid #e0e0e0;
    }
    .rpt-sum-cell:last-child { border-right:none; }
    .rpt-sum-val { font-size:20px; font-weight:700; font-family:'Courier New',monospace; color:#1a1a1a; line-height:1; margin-bottom:3px; }
    .rpt-sum-lbl { font-size:8px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#999; }

    /* ── Table ── */
    table { width:100%; border-collapse:collapse; font-size:10.5px; }
    thead th {
        padding:7px 10px; background:#f0ede6; text-align:left;
        font-size:8px; font-weight:700; letter-spacing:1px;
        text-transform:uppercase; color:#555;
        border-bottom:1.5px solid #2d5a27;
    }
    tbody tr { border-bottom:1px solid #eee; }
    tbody tr:nth-child(even) { background:#fafaf8; }
    tbody td { padding:7px 10px; vertical-align:top; }
    .td-date   { font-family:'Courier New',monospace; font-size:10px; white-space:nowrap; color:#555; }
    .td-name   { font-weight:600; }
    .td-trunc  { max-width:160px; word-wrap:break-word; color:#444; }
    .td-sub    { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;
                 background:#f0ede6; padding:1px 5px; border-radius:1px; color:#555; display:inline-block; }

    /* Status badges */
    .sb { font-size:8.5px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; padding:1px 6px; border-radius:1px; display:inline-block; }
    .sb-completed { background:#edfaf3; color:#1a5c35; }
    .sb-ongoing   { background:#fef9ec; color:#7a5700; }
    .sb-dismissed { background:#f3f1ec; color:#5a5a5a; }
    .sb-followup  { background:#edf3fa; color:#1a3a5c; }

    /* ── Footer ── */
    .rpt-footer {
        margin-top:20px; padding-top:10px;
        border-top:1px solid #ddd;
        display:flex; justify-content:space-between;
        font-size:8.5px; color:#999;
    }
    .sig-block { text-align:center; }
    .sig-line  { border-top:1px solid #aaa; width:180px; margin:30px auto 4px; }
    .sig-lbl   { font-size:8px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:#777; }
    .sigs { display:flex; justify-content:space-between; margin-top:30px; }

    @media print { body { padding:16px; } .no-print { display:none; } }
    </style>
</head>
<body>

    <!-- Print / Close buttons (hidden on print) -->
    <div class="no-print" style="margin-bottom:16px;display:flex;gap:8px;">
        <button onclick="window.print()" style="padding:7px 18px;background:#2d5a27;color:#fff;border:none;border-radius:2px;font-weight:700;font-size:11px;letter-spacing:.5px;text-transform:uppercase;cursor:pointer;">↗ Print</button>
        <button onclick="window.close()" style="padding:7px 18px;background:#fff;color:#555;border:1.5px solid #ccc;border-radius:2px;font-weight:700;font-size:11px;letter-spacing:.5px;text-transform:uppercase;cursor:pointer;">✕ Close</button>
    </div>

    <!-- Header -->
    <div class="rpt-header">
        <div class="rpt-brgy">Republic of the Philippines · Barangay Bombongan Health Center</div>
        <div class="rpt-title"><?= htmlspecialchars($typeLabel) ?> Records</div>
        <div class="rpt-sub"><?= htmlspecialchars($periodLabel) ?> · <?= htmlspecialchars($from) ?> to <?= htmlspecialchars($to) ?></div>
        <div class="rpt-meta">
            <span>Generated: <strong><?= date('d F Y, h:i A') ?></strong></span>
            <span>Sub Type: <strong><?= htmlspecialchars($sub !== 'all' ? ucfirst(str_replace('_',' ',$sub)) : 'All') ?></strong></span>
            <?php if ($search): ?><span>Search: <strong><?= htmlspecialchars($search) ?></strong></span><?php endif; ?>
        </div>
    </div>

    <!-- Summary -->
    <?php
    $total     = count($data);
    $completed = count(array_filter($data, fn($r) => ($r['meta']['status']??'') === 'Completed'));
    $ongoing   = count(array_filter($data, fn($r) => ($r['meta']['status']??'') === 'Ongoing'));
    $other     = $total - $completed - $ongoing;
    ?>
    <div class="rpt-summary">
        <div class="rpt-sum-cell"><div class="rpt-sum-val"><?= $total ?></div><div class="rpt-sum-lbl">Total Records</div></div>
        <div class="rpt-sum-cell"><div class="rpt-sum-val" style="color:#1a5c35;"><?= $completed ?></div><div class="rpt-sum-lbl">Completed</div></div>
        <div class="rpt-sum-cell"><div class="rpt-sum-val" style="color:#7a5700;"><?= $ongoing ?></div><div class="rpt-sum-lbl">Ongoing</div></div>
        <div class="rpt-sum-cell"><div class="rpt-sum-val" style="color:#999;"><?= $other ?></div><div class="rpt-sum-lbl">Other</div></div>
    </div>

    <!-- Table -->
    <?php if (!$data): ?>
        <div style="padding:28px;text-align:center;color:#999;border:1px dashed #ddd;border-radius:2px;">
            No records found for the selected filters.
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:80px;">Date</th>
                <th>Patient</th>
                <th style="width:90px;">Sub Type</th>
                <th style="width:70px;">Status</th>
                <th>Chief Complaint</th>
                <th>Diagnosis</th>
                <th>Health Worker</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row):
                $meta   = $row['meta'];
                $status = $meta['status'] ?? '';
                $stCls  = match(strtolower($status)){ 'completed'=>'sb-completed','ongoing'=>'sb-ongoing','dismissed'=>'sb-dismissed','follow-up'=>'sb-followup', default=>'sb-dismissed' };
                $stSub  = $meta['sub_type'] ?? '';
            ?>
            <tr>
                <td class="td-date"><?= htmlspecialchars($row['consultation_date']) ?></td>
                <td class="td-name"><?= htmlspecialchars($row['resident_name']) ?></td>
                <td><?php if($stSub && $stSub !== 'all'): ?><span class="td-sub"><?= htmlspecialchars(str_replace('_',' ',$stSub)) ?></span><?php else: ?>—<?php endif; ?></td>
                <td><?php if($status): ?><span class="sb <?= $stCls ?>"><?= htmlspecialchars($status) ?></span><?php else: ?>—<?php endif; ?></td>
                <td class="td-trunc"><?= htmlspecialchars($row['complaint']) ?></td>
                <td class="td-trunc"><?= htmlspecialchars($row['diagnosis'] ?? '—') ?></td>
                <td style="font-size:10px;color:#666;"><?= htmlspecialchars($meta['health_worker'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Signatures -->
    <div class="sigs">
        <div class="sig-block"><div class="sig-line"></div><div class="sig-lbl">Prepared by (HC Nurse)</div></div>
        <div class="sig-block"><div class="sig-line"></div><div class="sig-lbl">Noted by (Barangay Official)</div></div>
    </div>

    <div class="rpt-footer">
        <span>Barangay Bombongan Health Center — MIS</span>
        <span>Printed: <?= date('d F Y h:i A') ?></span>
    </div>

    <script>window.onload = function(){ window.print(); }</script>
</body>
</html>
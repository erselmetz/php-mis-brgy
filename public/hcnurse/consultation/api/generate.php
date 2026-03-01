<?php
require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse();

$doc = $_GET['doc'] ?? 'summary';          // summary|report|certificate
$period = $_GET['period'] ?? 'monthly';    // daily|weekly|monthly
$month = $_GET['month'] ?? date('Y-m');
$resident_id = (int)($_GET['resident_id'] ?? 0);
$purpose = trim($_GET['purpose'] ?? '');

// date range compute
function compute_range(string $period, string $month): array {
  $today = date('Y-m-d');

  if ($period === 'daily') {
    return [$today, $today, 'Daily'];
  }

  if ($period === 'weekly') {
    $from = date('Y-m-d', strtotime('monday this week'));
    $to = date('Y-m-d', strtotime('sunday this week'));
    return [$from, $to, 'Weekly'];
  }

  // monthly
  if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
  $from = $month . '-01';
  $to = date('Y-m-t', strtotime($from));
  return [$from, $to, 'Monthly'];
}

[$from, $to, $periodLabel] = compute_range($period, $month);

// validation
if ($doc !== 'report' && $resident_id <= 0) {
  die('Invalid resident.');
}
if (!in_array($doc, ['summary','report','certificate'], true)) $doc = 'summary';

// Fetch consultations in range (and optionally by resident)
$sql = "
  SELECT
    c.id, c.resident_id, c.complaint, c.diagnosis, c.treatment, c.notes, c.consultation_date,
    CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name) AS resident_name
  FROM consultations c
  LEFT JOIN residents r ON r.id = c.resident_id
  WHERE c.consultation_date BETWEEN ? AND ?
";

$params = [$from, $to];
$types = "ss";

if ($doc !== 'report') {
  $sql .= " AND c.resident_id = ? ";
  $params[] = $resident_id;
  $types .= "i";
}

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

function h($s){ return htmlspecialchars((string)$s); }

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Generate</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; color:#111; }
    .header { text-align:center; margin-bottom: 14px; }
    .muted { color:#444; }
    table { width:100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
    th { background:#eee; }
    .section { margin-top: 12px; }
    .sig { margin-top: 40px; display:flex; justify-content: space-between; gap: 24px; }
    .sig > div { width: 45%; text-align:center; }
    .line { border-top:1px solid #000; margin-top:40px; }
    @media print { .no-print { display:none; } }
  </style>
</head>
<body>

<div class="header">
  <div style="font-weight:700; font-size:14px;">BARANGAY HEALTH CENTER</div>
  <div class="muted">
    <?= h($doc === 'report' ? 'Consultation Report' : ($doc === 'certificate' ? 'Health Certificate' : 'Consultation Summary')); ?>
  </div>
  <div class="muted">Period: <?= h($periodLabel); ?> | Range: <?= h($from); ?> to <?= h($to); ?></div>
</div>

<?php if ($doc !== 'report'): ?>
  <div class="section">
    <div><b>Resident:</b> <?= h($residentName); ?></div>
    <?php if ($doc === 'certificate'): ?>
      <div><b>Purpose:</b> <?= h($purpose !== '' ? $purpose : '—'); ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($doc === 'certificate'): ?>
  <div class="section">
    <p>
      This is to certify that <b><?= h($residentName); ?></b> has records of consultation/health services
      at the Barangay Health Center within the period stated above.
    </p>
    <p>
      This certification is issued upon request for <b><?= h($purpose !== '' ? $purpose : 'valid purpose'); ?></b>.
    </p>

    <div class="sig">
      <div>
        <div class="line"></div>
        HC Nurse / Health Worker
      </div>
      <div>
        <div class="line"></div>
        Barangay Official
      </div>
    </div>

<?php else: ?>
  <table>
    <thead>
      <tr>
        <th style="width:90px;">Date</th>
        <?php if ($doc === 'report'): ?><th style="width:180px;">Resident</th><?php endif; ?>
        <th style="width:120px;">Type</th>
        <th style="width:120px;">Sub Type</th>
        <th style="width:90px;">Status</th>
        <th>Complaint / Notes</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($rows) === 0): ?>
        <tr><td colspan="<?= $doc==='report' ? 6 : 5; ?>" style="text-align:center;">No records found.</td></tr>
      <?php endif; ?>

      <?php foreach ($rows as $r): ?>
        <?php
          $m = $r['meta'] ?? [];
          $program = $m['program'] ?? '';
          $subtype = $m['sub_type'] ?? '';
          $status = $m['status'] ?? '';
        ?>
        <tr>
          <td><?= h($r['consultation_date']); ?></td>
          <?php if ($doc === 'report'): ?><td><?= h($r['resident_name']); ?></td><?php endif; ?>
          <td><?= h($program); ?></td>
          <td><?= h($subtype !== '' ? $subtype : '—'); ?></td>
          <td><?= h($status !== '' ? $status : '—'); ?></td>
          <td><?= nl2br(h($r['complaint'])); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($doc === 'summary'): ?>
    <div class="sig">
      <div>
        <div class="line"></div>
        Prepared by (HC Nurse)
      </div>
      <div>
        <div class="line"></div>
        Noted by
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
  window.onload = function(){ window.print(); }
    /**
     * close window after print (if opened as popup)
     * Note: onafterprint may not work in all browsers, so this is a fallback
     */
    setTimeout(function(){
        window.close();
    }, 1000);
</script>

</body>
</html>
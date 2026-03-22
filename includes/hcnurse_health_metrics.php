<?php
/**
 * Writes vitals into health_metrics when a consultation is saved (add/edit).
 * Keeps the health_metrics table actively used for longitudinal tracking.
 */
function hcnurse_table_exists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $r && $r->num_rows > 0;
}

/**
 * @param mixed $weight,$height,$bpSys,$bpDia,$temp — from POST / form (nullable)
 */
function hcnurse_sync_health_metrics_from_consultation(
    mysqli $conn,
    int $residentId,
    string $recordedDateYmd,
    $weight,
    $height,
    $bpSys,
    $bpDia,
    $temp
): void {
    if (!hcnurse_table_exists($conn, 'health_metrics') || $residentId <= 0) {
        return;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordedDateYmd)) {
        return;
    }

    $dw = ($weight !== null && $weight !== '') ? (float)$weight : null;
    $dh = ($height !== null && $height !== '') ? (float)$height : null;
    $dt = ($temp !== null && $temp !== '') ? (float)$temp : null;
    $bp = null;
    if ($bpSys !== null && $bpSys !== '' && $bpDia !== null && $bpDia !== '') {
        $bp = trim((string)$bpSys) . '/' . trim((string)$bpDia);
    }

    if ($dw === null && $dh === null && $bp === null && $dt === null) {
        return;
    }

    $stmt = $conn->prepare('
        INSERT INTO health_metrics (resident_id, weight, height, blood_pressure, temperature, recorded_at)
        VALUES (?,?,?,?,?,?)
    ');
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('iddsds', $residentId, $dw, $dh, $bp, $dt, $recordedDateYmd);
    $stmt->execute();
}

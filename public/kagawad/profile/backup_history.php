<?php
/**
 * Backup History API Endpoint
 * 
 * Returns a JSON list of past backups from the `backups` table.
 * Access: Secretary only
 */

require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

header('Content-Type: application/json');

$stmt = $conn->prepare(
    "SELECT id, filename, file_size, description, performed_by_name, created_at
     FROM backups
     ORDER BY created_at DESC
     LIMIT 50"
);

if (!$stmt) {
    json_err('Database error.');
}

$stmt->execute();
$result = $stmt->get_result();
$backups = [];

while ($row = $result->fetch_assoc()) {
    // Format file size nicely
    $bytes = (int) $row['file_size'];
    if ($bytes >= 1073741824) {
        $row['size_formatted'] = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $row['size_formatted'] = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $row['size_formatted'] = number_format($bytes / 1024, 2) . ' KB';
    } else {
        $row['size_formatted'] = $bytes . ' B';
    }

    // Format date
    $row['date_formatted'] = date('m-d-Y', strtotime($row['created_at']));
    $row['time_formatted'] = date('h:i A', strtotime($row['created_at']));

    $backups[] = $row;
}

$stmt->close();

echo json_encode(['status' => 'ok', 'data' => $backups]);
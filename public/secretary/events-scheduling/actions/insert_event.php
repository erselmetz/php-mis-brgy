<?php
// actions/insert_event.php
// Handles insertion of new events for MIS Barangay Inventory / Scheduling

require_once __DIR__ . '/../../includes/app.php';
requireAdmin(); // Only admins can insert events
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Unknown error occurred.'
];

/* =========================
   1️⃣ Validate POST data
========================= */
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$event_date  = $_POST['event_date'] ?? '';
$event_time  = $_POST['event_time'] ?? '';
$location    = trim($_POST['location'] ?? '');
$priority    = $_POST['priority'] ?? 'normal'; // normal / important / urgent

if (!$title || !$event_date) {
    $response['message'] = 'Title and Event Date are required.';
    echo json_encode($response);
    exit;
}

/* =========================
   2️⃣ Generate unique event code
========================= */
function generateEventCode(mysqli $conn): string {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM events WHERE event_code LIKE ?");
    $pattern = "EVT-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = ($row['count'] ?? 0) + 1;
    return "EVT-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

$event_code = generateEventCode($conn);
$created_by = $_SESSION['user_id'] ?? 0;

/* =========================
   3️⃣ Prepare & execute INSERT
========================= */
$sql = "
INSERT INTO events 
(event_code, title, description, event_date, event_time, location, priority, created_by) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssi",
    $event_code,
    $title,
    $description,
    $event_date,
    $event_time,
    $location,
    $priority,
    $created_by
);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Event created successfully.';
    $response['event_code'] = $event_code;
} else {
    $response['message'] = "Database error: " . $conn->error;
}

/* =========================
   4️⃣ Close connection and output JSON
========================= */
$stmt->close();
$conn->close();
echo json_encode($response);
?>

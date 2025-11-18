<?php
require_once '../../includes/app.php';
requireStaff(); // Only Staff and Admin can access

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Securely collect POST data
    $fields = [
        'household_no', 'head_name', 'address'
    ];

    $data = [];
    foreach ($fields as $f) {
        $data[$f] = isset($_POST[$f]) ? trim($_POST[$f]) : null;
    }

    // Basic validation
    if (empty($data['household_no']) || empty($data['head_name']) || empty($data['address'])) {
        echo json_encode(['status' => 'error', 'message' => 'Household number, head name, and address are required.']);
        exit;
    }

    // Check for duplicate household number
    $check = $conn->prepare("SELECT id FROM households WHERE household_no = ?");
    $check->bind_param("s", $data['household_no']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Household number already exists.']);
        $check->close();
        exit;
    }
    $check->close();

    // Insert new record securely using mysqli prepared statements
    $stmt = $conn->prepare("
        INSERT INTO households (
            household_no, head_name, address, total_members, created_at
        ) VALUES (
            ?, ?, ?, 0, NOW()
        )
    ");

    // Bind parameters in the correct order
    $stmt->bind_param(
        "sss",
        $data['household_no'],
        $data['head_name'],
        $data['address']
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Household saved successfully.']);
    } else {
        error_log('Household Save Error: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    
    $stmt->close();

} catch (Exception $e) {
    // Log error internally (not shown to user)
    error_log('Household Save Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}


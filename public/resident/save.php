<?php
require_once '../../includes/app.php';
requireLogin(); // Ensures user is logged in

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Securely collect POST data
    $fields = [
        'household_id', 'birthdate', 'first_name', 'middle_name', 'last_name', 'suffix',
        'gender', 'birthplace', 'civil_status', 'religion', 'occupation', 'citizenship',
        'contact_no', 'address', 'voter_status', 'remarks'
    ];

    $data = [];
    foreach ($fields as $f) {
        $data[$f] = isset($_POST[$f]) ? trim($_POST[$f]) : null;
    }

    // Basic validation
    if (empty($data['first_name']) || empty($data['last_name'])) {
        echo json_encode(['status' => 'error', 'message' => 'First and last name are required.']);
        exit;
    }

    if (!empty($data['birthdate']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birthdate'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid birthdate format.']);
        exit;
    }

    if (!empty($data['contact_no']) && !preg_match('/^09\d{9}$/', $data['contact_no'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid contact number format.']);
        exit;
    }

    // Insert new record securely
    $stmt = $conn->prepare("
        INSERT INTO residents (
            household_id, birthdate, first_name, middle_name, last_name, suffix,
            gender, birthplace, civil_status, religion, occupation, citizenship,
            contact_no, address, voter_status, remarks, created_at
        ) VALUES (
            :household_id, :birthdate, :first_name, :middle_name, :last_name, :suffix,
            :gender, :birthplace, :civil_status, :religion, :occupation, :citizenship,
            :contact_no, :address, :voter_status, :remarks, NOW()
        )
    ");

    foreach ($data as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }

    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Resident saved successfully.']);

} catch (PDOException $e) {
    // Log error internally (not shown to user)
    error_log('Resident Save Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

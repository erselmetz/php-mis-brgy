<?php

if ($action === 'add_account') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $position = trim($_POST['position'] ?? '');
    
    // Officer fields
    $isOfficer = isset($_POST['is_officer']) && $_POST['is_officer'] === '1';
    $officerPosition = trim($_POST['officer_position'] ?? '');
    $termStart = $_POST['term_start'] ?? '';
    $termEnd = $_POST['term_end'] ?? '';
    $officerStatus = $_POST['officer_status'] ?? 'Active';
    $residentId = !empty($_POST['resident_id']) ? intval($_POST['resident_id']) : null;

    // Step 1: Check if username already exists
    $checkSql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Username already exists
        $error = "⚠️ Username already exists! Please choose another one.";
        $stmt->close();
    } else {
        $stmt->close();
        
        // Step 2: Insert new account
        $insertSql = "INSERT INTO users (name, username, password, role, position)
                      VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $positionValue = $isOfficer ? null : ($position ?: null);
        $stmt->bind_param("sssss", $fullname, $username, $password, $role, $positionValue);

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();
            
            // Step 3: If user is an officer, create officer record
            if ($isOfficer && !empty($officerPosition) && !empty($termStart) && !empty($termEnd)) {
                // If adding an Active Barangay Captain, ensure only one active captain exists
                if (strtolower($officerPosition) === 'barangay captain' && strtolower($officerStatus) === 'active') {
                    $deactSql = "UPDATE officers SET status = 'Inactive' WHERE position LIKE 'Barangay Captain' AND status = 'Active'";
                    $conn->query($deactSql);
                }
                $officerSql = "INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status)
                              VALUES (?, ?, ?, ?, ?, ?)";
                $officerStmt = $conn->prepare($officerSql);
                $officerStmt->bind_param("iissss", $userId, $residentId, $officerPosition, $termStart, $termEnd, $officerStatus);
                
                if ($officerStmt->execute()) {
                    $success = "✅ Account and Officer record created successfully!";
                } else {
                    $error = "✅ Account created, but failed to create officer record: " . $officerStmt->error;
                }
                $officerStmt->close();
            } else {
                $success = "✅ Account created successfully!";
            }
        } else {
            $error = $conn->error;
            $error = "❌ Account creation failed: $error";
            $stmt->close();
        }
    }
}
?>
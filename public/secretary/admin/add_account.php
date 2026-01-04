<?php

if ($action === 'add_account') {
    $fullname = sanitizeString($_POST['fullname'] ?? '', false);
    $username = sanitizeString($_POST['username'] ?? '', false);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    $role = sanitizeString($_POST['role'] ?? '');
    $status = sanitizeString($_POST['status'] ?? 'active');
    
    // Officer fields
    $isOfficer = isset($_POST['is_officer']) && $_POST['is_officer'] === '1';
    $officerPosition = sanitizeString($_POST['officer_position'] ?? '');
    $termStart = $_POST['term_start'] ?? '';
    $termEnd = $_POST['term_end'] ?? '';
    $officerStatus = sanitizeString($_POST['officer_status'] ?? 'Active');
    $residentId = !empty($_POST['resident_id']) ? sanitizeInt($_POST['resident_id'], 1) : null;

    // Validate required fields
    if (empty($fullname) || empty($username) || empty($password) || empty($role) || empty($status)) {
        $error = "⚠️ All required fields must be filled.";
    } else {
        // Step 1: Check if username already exists
        $checkSql = "SELECT * FROM users WHERE LOWER(username) = LOWER(?)";
        $stmt = $conn->prepare($checkSql);
        if ($stmt === false) {
            error_log('Add Account Error - Duplicate check query preparation failed: ' . $conn->error);
            $error = "Database error. Please try again.";
        } else {
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
                // Position is set to role for non-officers, null for officers
                $insertSql = "INSERT INTO users (name, username, password, role, status, position)
                              VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertSql);
                if ($stmt === false) {
                    error_log('Add Account Error - Insert query preparation failed: ' . $conn->error);
                    $error = "Database error. Please try again.";
                } else {
                    $positionValue = $isOfficer ? null : ucfirst($role);
                    $stmt->bind_param("ssssss", $fullname, $username, $password, $role, $status, $positionValue);

                    if ($stmt->execute()) {
                        $userId = $stmt->insert_id;
                        $stmt->close();
                        
                        // Step 3: If user is an officer, create officer record
                        if ($isOfficer && !empty($officerPosition) && !empty($termStart) && !empty($termEnd)) {
                            // Validate date format
                            if (!validateDateFormat($termStart) || !validateDateFormat($termEnd)) {
                                $error = "✅ Account created, but invalid date format for term dates.";
                            } else {
                                $officerSql = "INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status)
                                              VALUES (?, ?, ?, ?, ?, ?)";
                                $officerStmt = $conn->prepare($officerSql);
                                if ($officerStmt === false) {
                                    error_log('Add Account Error - Officer insert query preparation failed: ' . $conn->error);
                                    $error = "✅ Account created, but failed to create officer record.";
                                } else {
                                    $officerStmt->bind_param("iissss", $userId, $residentId, $officerPosition, $termStart, $termEnd, $officerStatus);
                                    
                                    if ($officerStmt->execute()) {
                                        $success = "✅ Account and Officer record created successfully!";
                                    } else {
                                        error_log('Add Account Error - Officer record creation failed: ' . $officerStmt->error);
                                        $error = "✅ Account created, but failed to create officer record.";
                                    }
                                    $officerStmt->close();
                                }
                            }
                        } else {
                            $success = "✅ Account created successfully!";
                        }
                    } else {
                        error_log('Add Account Error - User insert failed: ' . $stmt->error);
                        $error = "❌ Account creation failed. Please try again.";
                        $stmt->close();
                    }
                }
            }
        }
    }
}
?>
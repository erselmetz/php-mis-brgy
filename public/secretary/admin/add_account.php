<?php

if ($action === 'add_account') {

    // Resident
    $residentId = !empty($_POST['resident_id']) ? sanitizeInt($_POST['resident_id'], 1) : null;

    // Fullname from resident lookup first, then POST fallback
    $fullname = '';
    if (!empty($residentId)) {
        $residentName = getResidentFullName($conn, (int)$residentId);
        if (!empty($residentName)) {
            $fullname = sanitizeString($residentName, false);
        }
    }
    if ($fullname === '') {
        $fullname = sanitizeString($_POST['fullname'] ?? '', false);
    }

    $username    = sanitizeString($_POST['username'] ?? '', false);
    $passwordRaw = $_POST['password'] ?? '';
    $password    = !empty($passwordRaw) ? password_hash($passwordRaw, PASSWORD_DEFAULT) : '';
    $role        = sanitizeString($_POST['role'] ?? '');
    $status      = sanitizeString($_POST['status'] ?? 'active');

    // Always officer — position defaults from role if empty
    $isOfficer       = true;
    $officerPosition = trim(sanitizeString($_POST['officer_position'] ?? ''));
    if ($officerPosition === '') {
        $officerPosition = default_officer_position_for_role($role);
    }
    $officerStatus   = mapUserStatusToOfficerStatus($status);

    // hcnurse has no elected term
    $isHcNurse = ($role === 'hcnurse');
    $termStart = $isHcNurse ? null : ($_POST['term_start'] ?? '');
    $termEnd   = $isHcNurse ? null : ($_POST['term_end'] ?? '');

    // Validate
    if (empty($fullname) || empty($username) || empty($password) || empty($role) || empty($status)) {
        $error = "⚠️ All required fields must be filled.";
    } elseif ($isOfficer && empty($officerPosition)) {
        $error = "⚠️ Position is required.";
    } elseif (!$isHcNurse && (empty($termStart) || empty($termEnd))) {
        $error = "⚠️ Term Start and Term End are required.";
    } elseif (!$isHcNurse && (!validateDateFormat($termStart) || !validateDateFormat($termEnd))) {
        $error = "⚠️ Invalid date format for term dates.";
    } else {

        $checkSql = "SELECT id FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1";
        $stmt     = $conn->prepare($checkSql);

        if ($stmt === false) {
            error_log('Add Account Error - Duplicate check failed: ' . $conn->error);
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $error = "⚠️ Username already exists! Please choose another one.";
                $stmt->close();
            } else {
                $stmt->close();

                $positionValue = null;
                $insertSql = "INSERT INTO users (name, username, password, role, status, position)
                              VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertSql);

                if ($stmt === false) {
                    error_log('Add Account Error - Insert prepare failed: ' . $conn->error);
                    $error = "Database error. Please try again.";
                } else {
                    $stmt->bind_param("ssssss", $fullname, $username, $password, $role, $status, $positionValue);

                    if ($stmt->execute()) {
                        $userId = (int)$stmt->insert_id;
                        $stmt->close();

                        if (!empty($residentId)) {
                            $rid         = (int)$residentId;
                            $officerSql  = "INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status)
                                            VALUES (?, ?, ?, ?, ?, ?)";
                            $officerStmt = $conn->prepare($officerSql);
                            if ($officerStmt === false) {
                                error_log('Add Account Error - Officer insert prepare failed: ' . $conn->error);
                                $error = "✅ Account created, but failed to create officer record.";
                            } else {
                                $officerStmt->bind_param("iissss", $userId, $rid, $officerPosition, $termStart, $termEnd, $officerStatus);
                                if ($officerStmt->execute()) {
                                    $success = "✅ Account and Officer record created successfully!";
                                } else {
                                    error_log('Add Account Error - Officer insert failed: ' . $officerStmt->error);
                                    $error = "✅ Account created, but failed to create officer record.";
                                }
                                $officerStmt->close();
                            }
                        } else {
                            $officerSql  = "INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status)
                                            VALUES (?, NULL, ?, ?, ?, ?)";
                            $officerStmt = $conn->prepare($officerSql);
                            if ($officerStmt === false) {
                                error_log('Add Account Error - Officer insert (NULL resident) prepare failed: ' . $conn->error);
                                $error = "✅ Account created, but failed to create officer record.";
                            } else {
                                $officerStmt->bind_param("issss", $userId, $officerPosition, $termStart, $termEnd, $officerStatus);
                                if ($officerStmt->execute()) {
                                    $success = "✅ Account and Officer record created successfully!";
                                } else {
                                    error_log('Add Account Error - Officer insert failed: ' . $officerStmt->error);
                                    $error = "✅ Account created, but failed to create officer record.";
                                }
                                $officerStmt->close();
                            }
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
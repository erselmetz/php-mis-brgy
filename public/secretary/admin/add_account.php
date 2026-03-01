<?php

if ($action === 'add_account') {

    // Resident
    $residentId = !empty($_POST['resident_id']) ? sanitizeInt($_POST['resident_id'], 1) : null;

    // ✅ Fullname should come from resident if selected
    $fullname = '';
    if (!empty($residentId)) {
        $residentName = getResidentFullName($conn, (int)$residentId);
        if (!empty($residentName)) {
            $fullname = sanitizeString($residentName, false);
        }
    }

    // fallback (just in case no resident chosen or lookup failed)
    if ($fullname === '') {
        $fullname = sanitizeString($_POST['fullname'] ?? '', false);
    }

    $username = sanitizeString($_POST['username'] ?? '', false);
    $passwordRaw = $_POST['password'] ?? '';
    $password = !empty($passwordRaw) ? password_hash($passwordRaw, PASSWORD_DEFAULT) : '';

    $role   = sanitizeString($_POST['role'] ?? '');
    $status = sanitizeString($_POST['status'] ?? 'active');

    // ✅ Always officer (checkbox removed in UI)
    $isOfficer = true;

    // Officer fields
    $officerPosition = sanitizeString($_POST['officer_position'] ?? '');
    $termStart = $_POST['term_start'] ?? '';
    $termEnd   = $_POST['term_end'] ?? '';
    $officerStatus = mapUserStatusToOfficerStatus($status);

    // Validate required fields
    if (empty($fullname) || empty($username) || empty($password) || empty($role) || empty($status)) {
        $error = "⚠️ All required fields must be filled.";
    } else if ($isOfficer && (empty($officerPosition) || empty($termStart) || empty($termEnd))) {
        // since always officer, enforce officer fields too
        $error = "⚠️ Officer fields (Position, Term Start, Term End) are required.";
    } else {

        // Validate date format for officer term
        if ($isOfficer && (!validateDateFormat($termStart) || !validateDateFormat($termEnd))) {
            $error = "⚠️ Invalid date format for term dates.";
        } else {

            // Step 1: Check if username already exists
            $checkSql = "SELECT id FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1";
            $stmt = $conn->prepare($checkSql);

            if ($stmt === false) {
                error_log('Add Account Error - Duplicate check query preparation failed: ' . $conn->error);
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

                    // Step 2: Insert new account
                    // Since always officer, position in users can be NULL (or keep role if you want)
                    $insertSql = "INSERT INTO users (name, username, password, role, status, position)
                                  VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($insertSql);
                    if ($stmt === false) {
                        error_log('Add Account Error - Insert query preparation failed: ' . $conn->error);
                        $error = "Database error. Please try again.";
                    } else {

                        // ✅ Always officer -> make position NULL (or change to ucfirst($role) if you prefer)
                        $positionValue = null;

                        // NOTE: bind_param needs variable, null works with "s" if mysqli is configured,
                        // but safest is still ok here because position column is likely VARCHAR NULL.
                        $stmt->bind_param("ssssss", $fullname, $username, $password, $role, $status, $positionValue);

                        if ($stmt->execute()) {
                            $userId = (int)$stmt->insert_id;
                            $stmt->close();

                            // Step 3: Create officer record (always)
                            // ✅ NULL-safe resident_id handling
                            if (!empty($residentId)) {
                                $officerSql = "INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status)
                                               VALUES (?, ?, ?, ?, ?, ?)";
                                $officerStmt = $conn->prepare($officerSql);

                                if ($officerStmt === false) {
                                    error_log('Add Account Error - Officer insert query preparation failed: ' . $conn->error);
                                    $error = "✅ Account created, but failed to create officer record.";
                                } else {
                                    $rid = (int)$residentId;
                                    $officerStmt->bind_param("iissss", $userId, $rid, $officerPosition, $termStart, $termEnd, $officerStatus);

                                    if ($officerStmt->execute()) {
                                        $success = "✅ Account and Officer record created successfully!";
                                    } else {
                                        error_log('Add Account Error - Officer record creation failed: ' . $officerStmt->error);
                                        $error = "✅ Account created, but failed to create officer record.";
                                    }
                                    $officerStmt->close();
                                }

                            } else {
                                // resident_id = NULL
                                $officerSql = "INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status)
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
                                        error_log('Add Account Error - Officer record creation failed: ' . $officerStmt->error);
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
}
?>
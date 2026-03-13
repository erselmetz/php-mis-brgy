<?php
/**
 * Edit Account Handler (FIXED)
 * - fullname comes from residents if resident_id is provided
 * - always officer (since UI removed checkbox)
 * - NULL-safe resident_id in officers table
 * - uses transaction for consistency
 */

// Handle form submission
if ($action === 'edit_account') {

    // Step 1: Validate and sanitize account ID
    $id = sanitizeInt($_POST['id'] ?? 0, 1);
    if (empty($id)) {
        $error = "Invalid account ID.";
    } else {

        // Step 2: Fetch existing account
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        if ($stmt === false) {
            error_log('Edit Account Error - Query preparation failed: ' . $conn->error);
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if (!$result || $result->num_rows === 0) {
                $error = "⚠️ Account not found.";
                $stmt->close();
            } else {
                $account = $result->fetch_assoc();
                $stmt->close();

                /**
                 * Step 3: Collect and sanitize form data
                 */
                $username = sanitizeString($_POST['username'] ?? '', false);
                $role = sanitizeString($_POST['role'] ?? '');
                $status = sanitizeString($_POST['status'] ?? '');
                $password = sanitizeString($_POST['password'] ?? '');

                // Officer fields (ALWAYS OFFICER now)
                $isOfficer = true;

                $officerId = !empty($_POST['officer_id']) ? sanitizeInt($_POST['officer_id'], 1) : null;
                $officerPosition = sanitizeString($_POST['officer_position'] ?? '');
                $termStart = $_POST['term_start'] ?? '';
                $termEnd = $_POST['term_end'] ?? '';
                $officerStatus = mapUserStatusToOfficerStatus($status);

                $residentId = !empty($_POST['resident_id']) ? sanitizeInt($_POST['resident_id'], 1) : null;

                // ✅ fullname comes from resident if selected
                $fullname = '';
                if (!empty($residentId)) {
                    $residentName = getResidentFullName($conn, (int) $residentId);
                    if (!empty($residentName)) {
                        $fullname = sanitizeString($residentName, false);
                    }
                }
                // fallback (if no resident or lookup fails)
                if ($fullname === '') {
                    $fullname = sanitizeString($_POST['fullname'] ?? '', false);
                }

                /**
                 * Step 4: Validate required fields
                 */
                if (empty($fullname) || empty($username) || empty($role) || empty($status)) {
                    $error = "⚠️ All required fields must be filled.";
                } else if ($isOfficer && empty($officerPosition)) {
                    $error = "⚠️ Position is required.";
                } else if ($isOfficer && $role !== 'hcnurse' && (empty($termStart) || empty($termEnd))) {
                    $error = "⚠️ Term Start and Term End are required.";
                } else if ($isOfficer && $role !== 'hcnurse' && (!validateDateFormat($termStart) || !validateDateFormat($termEnd))) {
                    $error = "⚠️ Invalid date format for term dates.";
                } else if ($isOfficer && $role === 'hcnurse' && ((!empty($termStart) && !validateDateFormat($termStart)) || (!empty($termEnd) && !validateDateFormat($termEnd)))) {
                    $error = "⚠️ Invalid date format for term dates.";

                    /**
                     * Step 5: Check duplicate username (excluding current)
                     */
                    $check = $conn->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ? LIMIT 1");
                    if ($check === false) {
                        error_log('Edit Account Error - Duplicate check query preparation failed: ' . $conn->error);
                        $error = "Database error. Please try again.";
                    } else {
                        $check->bind_param("si", $username, $id);
                        $check->execute();
                        $dup = $check->get_result();

                        if ($dup && $dup->num_rows > 0) {
                            $error = "⚠️ Username already exists! Please choose another one.";
                            $check->close();
                        } else {
                            $check->close();

                            // ✅ always officer -> position on users = NULL
                            $positionValue = null;

                            $conn->begin_transaction();

                            try {
                                /**
                                 * Step 6: Update user account
                                 */
                                if (!empty($password)) {
                                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                                    $sql = $conn->prepare("
                                        UPDATE users
                                        SET name = ?, username = ?, role = ?, status = ?, password = ?, position = ?
                                        WHERE id = ?
                                        LIMIT 1
                                    ");
                                    if ($sql === false) {
                                        throw new Exception('Edit Account Error - Update query preparation failed: ' . $conn->error);
                                    }
                                    $sql->bind_param("ssssssi", $fullname, $username, $role, $status, $hashed, $positionValue, $id);
                                } else {
                                    $sql = $conn->prepare("
                                        UPDATE users
                                        SET name = ?, username = ?, role = ?, status = ?, position = ?
                                        WHERE id = ?
                                        LIMIT 1
                                    ");
                                    if ($sql === false) {
                                        throw new Exception('Edit Account Error - Update query preparation failed: ' . $conn->error);
                                    }
                                    $sql->bind_param("sssssi", $fullname, $username, $role, $status, $positionValue, $id);
                                }

                                if (!$sql->execute()) {
                                    $err = $sql->error;
                                    $sql->close();
                                    throw new Exception('Edit Account Error - User update failed: ' . $err);
                                }
                                $sql->close();

                                /**
                                 * Step 7: Officer record (always create/update)
                                 * NULL-safe resident_id handling
                                 */
                                if ($officerId) {
                                    // UPDATE officer
                                    if (!empty($residentId)) {
                                        $rid = (int) $residentId;
                                        $officerSql = $conn->prepare("
                                            UPDATE officers
                                            SET resident_id = ?, position = ?, term_start = ?, term_end = ?, status = ?
                                            WHERE id = ?
                                        ");
                                        if ($officerSql === false) {
                                            throw new Exception('Edit Account Error - Officer update query preparation failed: ' . $conn->error);
                                        }
                                        $officerSql->bind_param("issssi", $rid, $officerPosition, $termStart, $termEnd, $officerStatus, $officerId);
                                    } else {
                                        // resident_id = NULL
                                        $officerSql = $conn->prepare("
                                            UPDATE officers
                                            SET resident_id = NULL, position = ?, term_start = ?, term_end = ?, status = ?
                                            WHERE id = ?
                                        ");
                                        if ($officerSql === false) {
                                            throw new Exception('Edit Account Error - Officer update (NULL resident) prepare failed: ' . $conn->error);
                                        }
                                        $officerSql->bind_param("ssssi", $officerPosition, $termStart, $termEnd, $officerStatus, $officerId);
                                    }

                                    if (!$officerSql->execute()) {
                                        $err = $officerSql->error;
                                        $officerSql->close();
                                        throw new Exception('Edit Account Error - Officer update failed: ' . $err);
                                    }
                                    $officerSql->close();
                                } else {
                                    // INSERT officer (if missing)
                                    if (!empty($residentId)) {
                                        $rid = (int) $residentId;
                                        $officerSql = $conn->prepare("
                                            INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status)
                                            VALUES (?, ?, ?, ?, ?, ?)
                                        ");
                                        if ($officerSql === false) {
                                            throw new Exception('Edit Account Error - Officer insert query preparation failed: ' . $conn->error);
                                        }
                                        $officerSql->bind_param("iissss", $id, $rid, $officerPosition, $termStart, $termEnd, $officerStatus);
                                    } else {
                                        $officerSql = $conn->prepare("
                                            INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status)
                                            VALUES (?, NULL, ?, ?, ?, ?)
                                        ");
                                        if ($officerSql === false) {
                                            throw new Exception('Edit Account Error - Officer insert (NULL resident) prepare failed: ' . $conn->error);
                                        }
                                        $officerSql->bind_param("issss", $id, $officerPosition, $termStart, $termEnd, $officerStatus);
                                    }

                                    if (!$officerSql->execute()) {
                                        $err = $officerSql->error;
                                        $officerSql->close();
                                        throw new Exception('Edit Account Error - Officer insert failed: ' . $err);
                                    }
                                    $officerSql->close();
                                }

                                $conn->commit();
                                $success = "✅ Account and Officer record updated successfully!";

                            } catch (Exception $e) {
                                $conn->rollback();
                                error_log($e->getMessage());
                                $error = "❌ Update failed. Please try again.";
                            }
                        }
                    }
                }
            }
        }
    }
}
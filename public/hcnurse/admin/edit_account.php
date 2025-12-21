<?php
/**
 * Edit Account Handler
 * 
 * Processes form submission to update existing user accounts.
 * Handles both regular accounts and officer records.
 * Uses prepared statements for all database operations.
 */

// Handle form submission
if ($action === 'edit_account') {
    /**
     * Step 1: Validate and sanitize account ID
     * Ensure we have a valid numeric ID before proceeding
     */
    $id = sanitizeInt($_POST['id'] ?? 0, 1);
    if (empty($id)) {
        $error = "Invalid account ID.";
    } else {
        /**
         * Step 2: Fetch existing account
         * Verify account exists before attempting to update
         */
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        if ($stmt === false) {
            error_log('Edit Account Error - Query preparation failed: ' . $conn->error);
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $error = "⚠️ Account not found.";
                $stmt->close();
            } else {
                $account = $result->fetch_assoc();
                $stmt->close();
                
                /**
                 * Step 3: Collect and sanitize form data
                 * All inputs are sanitized to prevent XSS and SQL injection
                 */
                $fullname = sanitizeString($_POST['fullname'] ?? '', false);
                $username = sanitizeString($_POST['username'] ?? '', false);
                $role = sanitizeString($_POST['role'] ?? '');
                $status = sanitizeString($_POST['status'] ?? '');
                $password = sanitizeString($_POST['password'] ?? '');
                $position = sanitizeString($_POST['position'] ?? '');
                
                // Officer-related fields
                $isOfficer = isset($_POST['is_officer']) && $_POST['is_officer'] === '1';
                $officerId = !empty($_POST['officer_id']) ? sanitizeInt($_POST['officer_id'], 1) : null;
                $officerPosition = sanitizeString($_POST['officer_position'] ?? '');
                $termStart = $_POST['term_start'] ?? '';
                $termEnd = $_POST['term_end'] ?? '';
                $officerStatus = sanitizeString($_POST['officer_status'] ?? 'Active');
                $residentId = !empty($_POST['resident_id']) ? sanitizeInt($_POST['resident_id'], 1) : null;

                // Validate required fields
                if (empty($fullname) || empty($username) || empty($role) || empty($status)) {
                    $error = "⚠️ All required fields must be filled.";
                } else {
                    /**
                     * Step 4: Check for duplicate username (excluding current account)
                     * Case-insensitive check to prevent similar usernames
                     */
                    $check = $conn->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?");
                    if ($check === false) {
                        error_log('Edit Account Error - Duplicate check query preparation failed: ' . $conn->error);
                        $error = "Database error. Please try again.";
                    } else {
                        $check->bind_param("si", $username, $id);
                        $check->execute();
                        $dup = $check->get_result();

                        if ($dup->num_rows > 0) {
                            $error = "⚠️ Username already exists! Please choose another one.";
                            $check->close();
                        } else {
                            $check->close();
        
                            /**
                             * Step 5: Update user account
                             * Handle password update only if new password is provided
                             */
                            $positionValue = $isOfficer ? null : ($position ?: null);
                            
                            if (!empty($password)) {
                                // Hash password if provided
                                $hashed = password_hash($password, PASSWORD_DEFAULT);
                                $sql = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ?, password = ?, position = ? WHERE id = ?");
                                if ($sql === false) {
                                    error_log('Edit Account Error - Update query preparation failed: ' . $conn->error);
                                    $error = "Database error. Please try again.";
                                } else {
                                    $sql->bind_param("ssssssi", $fullname, $username, $role, $status, $hashed, $positionValue, $id);
                                }
                            } else {
                                // Update without changing password
                                $sql = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ?, position = ? WHERE id = ?");
                                if ($sql === false) {
                                    error_log('Edit Account Error - Update query preparation failed: ' . $conn->error);
                                    $error = "Database error. Please try again.";
                                } else {
                                    $sql->bind_param("sssssi", $fullname, $username, $role, $status, $positionValue, $id);
                                }
                            }
                            
                            if (isset($sql) && $sql !== false) {
                                if ($sql->execute()) {
                                    $sql->close();
                                    
                                    /**
                                     * Step 6: Handle officer record
                                     * Create, update, or delete officer record based on form data
                                     */
                                    if ($isOfficer && !empty($termStart) && !empty($termEnd) && !empty($officerPosition)) {
                                        // Validate date format
                                        if (!validateDateFormat($termStart) || !validateDateFormat($termEnd)) {
                                            $error = "⚠️ Invalid date format for term dates.";
                                        } else {
                                            // Check if officer record exists
                                            if ($officerId) {
                                                // Update existing officer record
                                                $officerSql = $conn->prepare("UPDATE officers SET resident_id = ?, position = ?, term_start = ?, term_end = ?, status = ? WHERE id = ?");
                                                if ($officerSql === false) {
                                                    error_log('Edit Account Error - Officer update query preparation failed: ' . $conn->error);
                                                    $error = "✅ Account updated, but failed to update officer record.";
                                                } else {
                                                    $officerSql->bind_param("issssi", $residentId, $officerPosition, $termStart, $termEnd, $officerStatus, $officerId);
                                                }
                                            } else {
                                                // Create new officer record
                                                $officerSql = $conn->prepare("INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status) VALUES (?, ?, ?, ?, ?, ?)");
                                                if ($officerSql === false) {
                                                    error_log('Edit Account Error - Officer insert query preparation failed: ' . $conn->error);
                                                    $error = "✅ Account updated, but failed to create officer record.";
                                                } else {
                                                    $officerSql->bind_param("iissss", $id, $residentId, $officerPosition, $termStart, $termEnd, $officerStatus);
                                                }
                                            }
                                            
                                            if (isset($officerSql) && $officerSql !== false) {
                                                if ($officerSql->execute()) {
                                                    $success = "✅ Account and Officer record updated successfully!";
                                                } else {
                                                    error_log('Edit Account Error - Officer record update failed: ' . $officerSql->error);
                                                    $error = "✅ Account updated, but failed to update officer record.";
                                                }
                                                $officerSql->close();
                                            }
                                        }
                                    } else {
                                        // User is not an officer, delete officer record if it exists
                                        if ($officerId) {
                                            $deleteOfficerSql = $conn->prepare("DELETE FROM officers WHERE id = ?");
                                            if ($deleteOfficerSql === false) {
                                                error_log('Edit Account Error - Officer delete query preparation failed: ' . $conn->error);
                                            } else {
                                                $deleteOfficerSql->bind_param("i", $officerId);
                                                $deleteOfficerSql->execute();
                                                $deleteOfficerSql->close();
                                            }
                                        }
                                        $success = "✅ Account updated successfully!";
                                    }
                                } else {
                                    error_log('Edit Account Error - User update failed: ' . $sql->error);
                                    $error = "❌ Update failed. Please try again.";
                                    $sql->close();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

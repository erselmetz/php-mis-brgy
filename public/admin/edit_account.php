<?php

// --- Step 3: Handle form submission ---
if ($action === 'edit_account') {
    // --- Step 1: Validate ID ---
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $error = "Invalid account ID.";
    }

    $id = intval($_POST['id']);

    // --- Step 2: Fetch account ---
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = "⚠️ Account not found.";
    }

    $account = $result->fetch_assoc();
    $stmt->close();
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = trim($_POST['password'] ?? '');
    $position = trim($_POST['position'] ?? '');
    
    // Officer fields
    $isOfficer = isset($_POST['is_officer']) && $_POST['is_officer'] === '1';
    $officerId = !empty($_POST['officer_id']) ? intval($_POST['officer_id']) : null;
    $officerPosition = trim($_POST['officer_position'] ?? '');
    $termStart = $_POST['term_start'] ?? '';
    $termEnd = $_POST['term_end'] ?? '';
    $officerStatus = $_POST['officer_status'] ?? 'Active';
    $residentId = !empty($_POST['resident_id']) ? intval($_POST['resident_id']) : null;

    // --- Check duplicate username (ignore current account) ---
    $check = $conn->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?");
    $check->bind_param("si", $username, $id);
    $check->execute();
    $dup = $check->get_result();

    if ($dup->num_rows > 0) {
        $error = "⚠️ Username already exists! Please choose another one.";
        $check->close();
    } else {
        $check->close();
        
        // --- Update user account ---
        $positionValue = $isOfficer ? null : ($position ?: null);
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ?, password = ?, position = ? WHERE id = ?");
            $sql->bind_param("ssssssi", $fullname, $username, $role, $status, $hashed, $positionValue, $id);
        } else {
            $sql = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ?, position = ? WHERE id = ?");
            $sql->bind_param("sssssi", $fullname, $username, $role, $status, $positionValue, $id);
        }
        
        if ($sql->execute()) {
            $sql->close();
            
            // Handle officer record
            if ($isOfficer && !empty($termStart) && !empty($termEnd)) {
                // Check if officer record exists
                if ($officerId) {
                    // Update existing officer record
                    $officerSql = $conn->prepare("UPDATE officers SET resident_id = ?, position = ?, term_start = ?, term_end = ?, status = ? WHERE id = ?");
                    $officerSql->bind_param("issssi", $residentId, $officerPosition, $termStart, $termEnd, $officerStatus, $officerId);
                } else {
                    // Create new officer record
                    $officerSql = $conn->prepare("INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $officerSql->bind_param("iissss", $id, $residentId, $officerPosition, $termStart, $termEnd, $officerStatus);
                }
                
                if ($officerSql->execute()) {
                    $success = "✅ Account and Officer record updated successfully!";
                } else {
                    $error = "✅ Account updated, but failed to update officer record: " . $officerSql->error;
                }
                $officerSql->close();
            } else {
                // User is not an officer, delete officer record if it exists
                if ($officerId) {
                    $deleteOfficerSql = $conn->prepare("DELETE FROM officers WHERE id = ?");
                    $deleteOfficerSql->bind_param("i", $officerId);
                    $deleteOfficerSql->execute();
                    $deleteOfficerSql->close();
                }
                $success = "✅ Account updated successfully!";
            }
        } else {
            $error = "❌ Update failed: " . $conn->error;
            $sql->close();
        }
    }
}

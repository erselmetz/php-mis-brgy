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

    // --- Check duplicate username (ignore current account) ---
    $check = $conn->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?");
    $check->bind_param("si", $username, $id);
    $check->execute();
    $dup = $check->get_result();

    if ($dup->num_rows > 0) {
        $error = "⚠️ Username already exists! Please choose another one.";
    } else {
        // --- Update logic ---
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ?, password = ? WHERE id = ?");
            $sql->bind_param("ssssi", $fullname, $username, $role, $status, $hashed, $id);
        } else {
            $sql = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ? WHERE id = ?");
            $sql->bind_param("ssssi", $fullname, $username, $role, $status, $id);
        }
        if ($sql->execute()) {
            $success = "✅ Account updated successfully!";
        } else {
            $error = "❌ Update failed: " . $conn->error;
        }
        $sql->close();
    }
    $check->close();
}

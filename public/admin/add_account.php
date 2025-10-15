<?php

if ($action === 'add_account') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Step 1: Check if username already exists
    $checkSql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Username already exists
        $error = "⚠️ Username already exists! Please choose another one.";
    } else {
        // Step 2: Insert new account
        $insertSql = "INSERT INTO users (name, username, password, role)
                      VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("ssss", $fullname, $username, $password, $role);

        if ($stmt->execute()) {
            $success = "✅ Account created successfully!";
        } else {
            $error = $conn->error;
            $error = "❌ Account creation failed: $error";
        }
    }
    $stmt->close();
}
?>
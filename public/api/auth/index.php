<?php
/**
 * Auth API Entry Point
 * Handles authentication operations
 */

require_once '../../../includes/app.php';
require_once '../BaseController.php';
require_once '../ApiResponse.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['username']) || empty($data['password'])) {
        ApiResponse::validationError([
            'username' => 'Username and password are required'
        ]);
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        ApiResponse::error('Invalid username or password', 401);
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!password_verify($password, $user['password'])) {
        ApiResponse::error('Invalid username or password', 401);
    }
    
    if ($user['status'] !== 'active') {
        ApiResponse::error('Account is ' . $user['status'] . '. Please contact admin.', 403);
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    
    ApiResponse::success([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'role' => $user['role'],
        'message' => 'Login successful'
    ], 'Login successful');
} else {
    ApiResponse::methodNotAllowed();
}


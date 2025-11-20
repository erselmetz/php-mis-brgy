<?php
/**
 * Authentication Middleware
 * Handles API authentication and authorization
 */

class AuthMiddleware {
    /**
     * Check if user is authenticated
     */
    public static function authenticate() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            ApiResponse::unauthorized('Authentication required');
        }
    }

    /**
     * Check if user has required role
     */
    public static function authorize($allowedRoles) {
        self::authenticate();

        $userRole = $_SESSION['role'];

        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        if (!in_array($userRole, $allowedRoles)) {
            ApiResponse::forbidden('Insufficient permissions for this operation');
        }
    }

    /**
     * Check if user is admin
     */
    public static function requireAdmin() {
        self::authorize('admin');
    }

    /**
     * Check if user is staff or admin
     */
    public static function requireStaff() {
        self::authorize(['staff', 'admin']);
    }

    /**
     * Check if user is tanod or admin
     */
    public static function requireTanod() {
        self::authorize(['tanod', 'admin']);
    }

    /**
     * Get current user info
     */
    public static function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? ''
        ];
    }

    /**
     * Validate API key (if implemented)
     */
    public static function validateApiKey() {
        // Future implementation for API key authentication
        // Check Authorization header for Bearer token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            ApiResponse::unauthorized('Valid API key required');
        }

        $apiKey = $matches[1];

        // Validate API key against database
        global $conn;
        $stmt = $conn->prepare("SELECT user_id FROM api_keys WHERE key_value = ? AND active = 1");
        $stmt->bind_param("s", $apiKey);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            ApiResponse::unauthorized('Invalid or inactive API key');
        }

        $row = $result->fetch_assoc();
        $userId = $row['user_id'];

        // Set session-like context for API requests
        $_SESSION['user_id'] = $userId;
        $_SESSION['is_api_request'] = true;

        // Get user details
        $stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userResult = $stmt->get_result();

        if ($userResult->num_rows > 0) {
            $user = $userResult->fetch_assoc();
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
        }
    }
}

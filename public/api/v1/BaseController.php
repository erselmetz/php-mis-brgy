<?php
/**
 * Base Controller Class
 * Provides common functionality for all API controllers
 */

abstract class BaseController {
    protected $conn;
    protected $userId;
    protected $userRole;

    public function __construct() {
        global $conn;
        $this->conn = $conn;

        // Set user context from session
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->userRole = $_SESSION['role'] ?? null;
    }

    /**
     * Get JSON input from request body
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::error('Invalid JSON input', 400);
        }

        return $data;
    }

    /**
     * Get request method
     */
    protected function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get query parameters
     */
    protected function getQueryParams() {
        return $_GET;
    }

    /**
     * Get POST data
     */
    protected function getPostData() {
        return $_POST;
    }

    /**
     * Get request headers
     */
    protected function getHeaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerKey = str_replace('HTTP_', '', $key);
                $headerKey = str_replace('_', '-', $headerKey);
                $headers[$headerKey] = $value;
            }
        }
        return $headers;
    }

    /**
     * Check if user has required role
     */
    protected function requireRole($roles) {
        if (!$this->userRole) {
            ApiResponse::unauthorized();
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array($this->userRole, $roles)) {
            ApiResponse::forbidden('Insufficient permissions');
        }
    }

    /**
     * Validate required fields
     */
    protected function validateRequired($data, $requiredFields) {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            ApiResponse::validationError([
                'missing_fields' => $missing,
                'message' => 'Required fields are missing: ' . implode(', ', $missing)
            ]);
        }
    }

    /**
     * Sanitize input data
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return trim($data);
    }

    /**
     * Handle the request (to be implemented by child classes)
     */
    abstract public function handle();
}

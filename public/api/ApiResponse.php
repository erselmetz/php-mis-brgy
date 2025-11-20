<?php
/**
 * API Response Helper Class
 * Provides standardized JSON responses for API endpoints
 */

class ApiResponse {
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        self::send([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send error response
     */
    public static function error($message = 'An error occurred', $statusCode = 500, $data = null) {
        self::send([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::send([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::send([
            'status' => 'error',
            'message' => $message
        ], 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Access forbidden') {
        self::send([
            'status' => 'error',
            'message' => $message
        ], 403);
    }

    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::send([
            'status' => 'error',
            'message' => $message
        ], 404);
    }

    /**
     * Send method not allowed response
     */
    public static function methodNotAllowed($message = 'Method not allowed') {
        self::send([
            'status' => 'error',
            'message' => $message
        ], 405);
    }

    /**
     * Send custom response
     */
    public static function send($data, $statusCode = 200) {
        // Set HTTP status code
        http_response_code($statusCode);

        // Set content type
        header('Content-Type: application/json');

        // Prevent caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Ensure no output before JSON
        if (ob_get_length()) {
            ob_clean();
        }

        // Send JSON response
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send paginated response
     */
    public static function paginated($data, $total, $page, $perPage, $message = 'Success') {
        $totalPages = ceil($total / $perPage);

        self::send([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'page' => (int)$page,
                'per_page' => (int)$perPage,
                'total_pages' => (int)$totalPages,
                'has_more' => $page < $totalPages
            ]
        ]);
    }
}

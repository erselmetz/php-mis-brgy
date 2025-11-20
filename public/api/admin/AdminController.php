<?php
/**
 * Admin Controller
 * Handles all admin-related API operations (users, officers)
 */

class AdminController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        require_once __DIR__ . '/UserModel.php';
        $this->model = new UserModel();
    }

    public function handle() {
        // Authenticate user
        AuthMiddleware::authenticate();

        // Require admin role
        $this->requireRole(['admin']);

        $method = $this->getMethod();
        $queryParams = $this->getQueryParams();

        // Route based on HTTP method and action
        switch ($method) {
            case 'GET':
                if (isset($queryParams['id'])) {
                    $this->getById($queryParams['id']);
                } else {
                    $this->list();
                }
                break;

            case 'POST':
                $data = $this->getJsonInput();
                $action = $data['action'] ?? '';

                switch ($action) {
                    case 'create':
                        $this->create($data);
                        break;
                    case 'update':
                        $this->update($data);
                        break;
                    case 'delete':
                        $this->delete($data);
                        break;
                    default:
                        ApiResponse::error('Invalid action', 400);
                }
                break;

            default:
                ApiResponse::methodNotAllowed();
        }
    }

    /**
     * Get all users with officer information
     */
    private function list() {
        try {
            $queryParams = $this->getQueryParams();

            // Build filter conditions
            $conditions = [];
            $params = [];

            // Filter by role
            if (!empty($queryParams['role'])) {
                $conditions[] = 'u.role = ?';
                $params[] = $queryParams['role'];
            }

            // Filter by status
            if (!empty($queryParams['status'])) {
                $conditions[] = 'u.status = ?';
                $params[] = $queryParams['status'];
            }

            // Search by name or username
            if (!empty($queryParams['search'])) {
                $searchTerm = '%' . $queryParams['search'] . '%';
                $conditions[] = '(u.name LIKE ? OR u.username LIKE ?)';
                $params = array_merge($params, [$searchTerm, $searchTerm]);
            }

            // Pagination - if not explicitly requested, return all data for DataTables
            $hasPagination = isset($queryParams['page']) || isset($queryParams['per_page']);
            $page = (int)($queryParams['page'] ?? 1);
            $perPage = $hasPagination ? (int)($queryParams['per_page'] ?? 25) : 10000; // Large limit for DataTables
            $offset = ($page - 1) * $perPage;

            // Get total count
            $total = $this->model->count($conditions, $params);

            // Get users
            $orderBy = $queryParams['order_by'] ?? 'u.id DESC';
            $users = $this->model->getWithOfficerInfo($conditions, $params, $orderBy, $perPage, $offset);

            if ($hasPagination) {
                ApiResponse::paginated($users, $total, $page, $perPage, 'Users retrieved successfully');
            } else {
                ApiResponse::success($users, 'Users retrieved successfully');
            }

        } catch (Exception $e) {
            error_log('Admin list error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve users', 500);
        }
    }

    /**
     * Get user by ID
     */
    private function getById($id) {
        try {
            $users = $this->model->getWithOfficerInfo(['u.id = ?'], [$id], null, 1);
            
            if (empty($users)) {
                ApiResponse::notFound('User not found');
            }

            ApiResponse::success($users[0], 'User retrieved successfully');

        } catch (Exception $e) {
            error_log('Admin get error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve user', 500);
        }
    }

    /**
     * Create new user account
     */
    private function create($data) {
        try {
            // Validate required fields
            $this->validateRequired($data, [
                'name', 'username', 'password', 'role'
            ]);

            // Check if username already exists
            if ($this->model->usernameExists($data['username'])) {
                ApiResponse::validationError([
                    'username' => 'Username already exists'
                ]);
            }

            // Prepare user data
            $userData = [
                'name' => $this->sanitize($data['name']),
                'username' => $this->sanitize($data['username']),
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => $data['role'],
                'position' => $data['position'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $userId = $this->model->insert($userData);

            if (!$userId) {
                ApiResponse::error('Failed to create user', 500);
            }

            // Handle officer record if needed
            $isOfficer = isset($data['is_officer']) && $data['is_officer'] === '1';
            if ($isOfficer && !empty($data['officer_position']) && !empty($data['term_start']) && !empty($data['term_end'])) {
                // If adding an Active Barangay Captain, deactivate other active captains
                if (strtolower($data['officer_position']) === 'barangay captain' && 
                    strtolower($data['officer_status'] ?? 'Active') === 'active') {
                    $this->model->deactivateOtherCaptains();
                }

                $officerData = [
                    'user_id' => $userId,
                    'resident_id' => $data['resident_id'] ?? null,
                    'position' => $data['officer_position'],
                    'term_start' => $data['term_start'],
                    'term_end' => $data['term_end'],
                    'status' => $data['officer_status'] ?? 'Active'
                ];

                $officerId = $this->model->createOfficer($officerData);
                
                if (!$officerId) {
                    ApiResponse::error('User created but failed to create officer record', 500);
                }
            }

            ApiResponse::success([
                'id' => $userId,
                'message' => 'User created successfully'
            ], 'User created successfully', 201);

        } catch (Exception $e) {
            error_log('Admin create error: ' . $e->getMessage());
            ApiResponse::error('Failed to create user', 500);
        }
    }

    /**
     * Update user account
     */
    private function update($data) {
        try {
            // Validate required fields
            $this->validateRequired($data, ['id', 'name', 'username', 'role']);

            // Check if user exists
            $existing = $this->model->find($data['id']);
            if (!$existing) {
                ApiResponse::notFound('User not found');
            }

            // Check if username already exists (excluding current user)
            if ($this->model->usernameExists($data['username'], $data['id'])) {
                ApiResponse::validationError([
                    'username' => 'Username already exists'
                ]);
            }

            // Prepare user data
            $userData = [
                'name' => $this->sanitize($data['name']),
                'username' => $this->sanitize($data['username']),
                'role' => $data['role'],
                'status' => $data['status'] ?? 'active',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Update password if provided
            if (!empty($data['password'])) {
                $userData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Set position (null if officer, otherwise use provided value)
            $isOfficer = isset($data['is_officer']) && $data['is_officer'] === '1';
            $userData['position'] = $isOfficer ? null : ($data['position'] ?? null);

            $success = $this->model->update($data['id'], $userData);

            if (!$success) {
                ApiResponse::error('Failed to update user', 500);
            }

            // Handle officer record
            $existingOfficer = $this->model->getOfficerByUserId($data['id']);
            $officerId = $existingOfficer ? $existingOfficer['id'] : null;

            if ($isOfficer && !empty($data['term_start']) && !empty($data['term_end'])) {
                // If updating to Active Barangay Captain, deactivate other active captains
                if (strtolower($data['officer_position']) === 'barangay captain' && 
                    strtolower($data['officer_status'] ?? 'Active') === 'active') {
                    $this->model->deactivateOtherCaptains($officerId);
                }

                $officerData = [
                    'resident_id' => $data['resident_id'] ?? null,
                    'position' => $data['officer_position'],
                    'term_start' => $data['term_start'],
                    'term_end' => $data['term_end'],
                    'status' => $data['officer_status'] ?? 'Active'
                ];

                if ($officerId) {
                    // Update existing officer record
                    $this->model->updateOfficer($officerId, $officerData);
                } else {
                    // Create new officer record
                    $officerData['user_id'] = $data['id'];
                    $this->model->createOfficer($officerData);
                }
            } else {
                // User is not an officer, delete officer record if it exists
                if ($officerId) {
                    $this->model->deleteOfficer($officerId);
                }
            }

            ApiResponse::success([
                'id' => $data['id'],
                'message' => 'User updated successfully'
            ], 'User updated successfully');

        } catch (Exception $e) {
            error_log('Admin update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update user', 500);
        }
    }

    /**
     * Delete user account
     */
    private function delete($data) {
        try {
            // Validate required fields
            $this->validateRequired($data, ['id']);

            // Check if user exists
            $user = $this->model->find($data['id']);
            if (!$user) {
                ApiResponse::notFound('User not found');
            }

            // Prevent deleting own account
            if ($user['id'] == $this->userId) {
                ApiResponse::validationError([
                    'id' => 'Cannot delete your own account'
                ]);
            }

            // Delete officer record if exists
            $officer = $this->model->getOfficerByUserId($data['id']);
            if ($officer) {
                $this->model->deleteOfficer($officer['id']);
            }

            // Delete user
            $success = $this->model->delete($data['id']);

            if ($success) {
                ApiResponse::success([
                    'id' => $data['id'],
                    'message' => 'User deleted successfully'
                ], 'User deleted successfully');
            } else {
                ApiResponse::error('Failed to delete user', 500);
            }

        } catch (Exception $e) {
            error_log('Admin delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete user', 500);
        }
    }
}


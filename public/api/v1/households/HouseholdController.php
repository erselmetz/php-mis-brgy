<?php
/**
 * Household Controller
 * Handles all household-related API operations
 */

class HouseholdController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        require_once __DIR__ . '/HouseholdModel.php';
        $this->model = new HouseholdModel();
    }

    public function handle() {
        // Authenticate user
        AuthMiddleware::authenticate();

        $method = $this->getMethod();
        $queryParams = $this->getQueryParams();

        // Route based on HTTP method and action
        switch ($method) {
            case 'GET':
                if (isset($queryParams['id'])) {
                    if (isset($queryParams['action']) && $queryParams['action'] === 'members') {
                        $this->getMembers($queryParams['id']);
                    } else {
                        $this->getById($queryParams['id']);
                    }
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
     * Get all households with member count
     */
    private function list() {
        try {
            $queryParams = $this->getQueryParams();

            // Build filter conditions
            $conditions = [];
            $params = [];

            // Search by household number or head name
            if (!empty($queryParams['search'])) {
                $searchTerm = '%' . $queryParams['search'] . '%';
                $conditions[] = '(household_no LIKE ? OR head_name LIKE ?)';
                $params = array_merge($params, [$searchTerm, $searchTerm]);
            }

            // Pagination - if not explicitly requested, return all data for DataTables
            $hasPagination = isset($queryParams['page']) || isset($queryParams['per_page']);
            $page = (int)($queryParams['page'] ?? 1);
            $perPage = $hasPagination ? (int)($queryParams['per_page'] ?? 25) : 10000; // Large limit for DataTables
            $offset = ($page - 1) * $perPage;

            // Get total count
            $total = $this->model->countHouseholds($conditions, $params);

            // Get households
            $orderBy = $queryParams['order_by'] ?? 'h.id DESC';
            $households = $this->model->getWithMemberCount($conditions, $params, $orderBy, $perPage, $offset);

            if ($hasPagination) {
                ApiResponse::paginated($households, $total, $page, $perPage, 'Households retrieved successfully');
            } else {
                ApiResponse::success($households, 'Households retrieved successfully');
            }

        } catch (Exception $e) {
            error_log('Household list error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve households', 500);
        }
    }

    /**
     * Get household by ID
     */
    private function getById($id) {
        try {
            $household = $this->model->find($id);

            if (!$household) {
                ApiResponse::notFound('Household not found');
            }

            // Get member count
            $household['member_count'] = $this->model->count(['household_id = ?'], [$id]);

            ApiResponse::success($household, 'Household retrieved successfully');

        } catch (Exception $e) {
            error_log('Household get error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve household', 500);
        }
    }

    /**
     * Get household members
     */
    private function getMembers($householdId) {
        try {
            if (empty($householdId)) {
                ApiResponse::validationError(['household_id' => 'Household ID is required']);
            }

            $members = $this->model->getMembers($householdId);
            ApiResponse::success($members, 'Household members retrieved successfully');

        } catch (Exception $e) {
            error_log('Household members error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve household members', 500);
        }
    }

    /**
     * Create new household
     */
    private function create($data) {
        try {
            // Require staff or admin role
            $this->requireRole(['staff', 'admin']);

            // Validate required fields
            $this->validateRequired($data, [
                'household_no', 'head_name', 'address'
            ]);

            // Check for duplicate household number
            if ($this->model->householdNumberExists($data['household_no'])) {
                ApiResponse::validationError([
                    'household_no' => 'Household number already exists'
                ]);
            }

            // Prepare data for insertion
            $householdData = [
                'household_no' => $this->sanitize($data['household_no']),
                'head_name' => $this->sanitize($data['head_name']),
                'address' => $this->sanitize($data['address']),
                'total_members' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $householdId = $this->model->insert($householdData);

            if ($householdId) {
                ApiResponse::success([
                    'id' => $householdId,
                    'message' => 'Household created successfully'
                ], 'Household created successfully', 201);
            } else {
                ApiResponse::error('Failed to create household', 500);
            }

        } catch (Exception $e) {
            error_log('Household create error: ' . $e->getMessage());
            ApiResponse::error('Failed to create household', 500);
        }
    }

    /**
     * Update household
     */
    private function update($data) {
        try {
            // Require staff or admin role
            $this->requireRole(['staff', 'admin']);

            // Validate required fields
            $this->validateRequired($data, [
                'id', 'household_no', 'head_name', 'address'
            ]);

            // Check if household exists
            $existing = $this->model->find($data['id']);
            if (!$existing) {
                ApiResponse::notFound('Household not found');
            }

            // Check for duplicate household number (excluding current)
            if ($this->model->householdNumberExists($data['household_no'], $data['id'])) {
                ApiResponse::validationError([
                    'household_no' => 'Household number already exists'
                ]);
            }

            // Prepare data for update
            $householdData = [
                'household_no' => $this->sanitize($data['household_no']),
                'head_name' => $this->sanitize($data['head_name']),
                'address' => $this->sanitize($data['address']),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $success = $this->model->update($data['id'], $householdData);

            if ($success) {
                ApiResponse::success([
                    'id' => $data['id'],
                    'message' => 'Household updated successfully'
                ], 'Household updated successfully');
            } else {
                ApiResponse::error('Failed to update household', 500);
            }

        } catch (Exception $e) {
            error_log('Household update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update household', 500);
        }
    }

    /**
     * Delete household
     */
    private function delete($data) {
        try {
            // Require staff or admin role
            $this->requireRole(['staff', 'admin']);

            // Validate required fields
            $this->validateRequired($data, ['id']);

            // Check if household exists
            $household = $this->model->find($data['id']);
            if (!$household) {
                ApiResponse::notFound('Household not found');
            }

            // Check if household has members
            $memberCount = $this->model->count(['household_id = ?'], [$data['id']]);
            if ($memberCount > 0) {
                ApiResponse::validationError([
                    'members' => 'Cannot delete household with existing members. Please reassign or remove all members first.'
                ]);
            }

            $success = $this->model->delete($data['id']);

            if ($success) {
                ApiResponse::success([
                    'id' => $data['id'],
                    'message' => 'Household deleted successfully'
                ], 'Household deleted successfully');
            } else {
                ApiResponse::error('Failed to delete household', 500);
            }

        } catch (Exception $e) {
            error_log('Household delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete household', 500);
        }
    }
}

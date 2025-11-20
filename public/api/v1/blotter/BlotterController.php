<?php
/**
 * Blotter Controller
 * Handles all blotter-related API operations
 */

class BlotterController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        require_once __DIR__ . '/BlotterModel.php';
        $this->model = new BlotterModel();
    }

    public function handle() {
        // Authenticate user
        AuthMiddleware::authenticate();

        // Require tanod or admin role
        $this->requireRole(['tanod', 'admin']);

        $method = $this->getMethod();
        $queryParams = $this->getQueryParams();

        // Route based on HTTP method and action
        switch ($method) {
            case 'GET':
                if (isset($queryParams['id'])) {
                    $this->getById($queryParams['id']);
                } elseif (isset($queryParams['action']) && $queryParams['action'] === 'stats') {
                    $this->getStatistics();
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
                    case 'update_status':
                        $this->updateStatus($data);
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
     * Get all blotter cases
     */
    private function list() {
        try {
            $queryParams = $this->getQueryParams();

            // Build filter conditions
            $conditions = [];
            $params = [];

            // Filter by status
            if (!empty($queryParams['status'])) {
                $conditions[] = 'b.status = ?';
                $params[] = $queryParams['status'];
            }

            // Search by complainant or respondent name
            if (!empty($queryParams['search'])) {
                $searchTerm = '%' . $queryParams['search'] . '%';
                $conditions[] = '(b.complainant_name LIKE ? OR b.respondent_name LIKE ? OR b.case_number LIKE ?)';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            // Date range filter
            if (!empty($queryParams['date_from'])) {
                $conditions[] = 'b.incident_date >= ?';
                $params[] = $queryParams['date_from'];
            }
            if (!empty($queryParams['date_to'])) {
                $conditions[] = 'b.incident_date <= ?';
                $params[] = $queryParams['date_to'];
            }

            // Pagination - if not explicitly requested, return all data for DataTables
            $hasPagination = isset($queryParams['page']) || isset($queryParams['per_page']);
            $page = (int)($queryParams['page'] ?? 1);
            $perPage = $hasPagination ? (int)($queryParams['per_page'] ?? 25) : 10000; // Large limit for DataTables
            $offset = ($page - 1) * $perPage;

            // Get total count
            $total = $this->model->count($conditions, $params);

            // Get blotter cases
            $orderBy = $queryParams['order_by'] ?? 'b.created_at DESC';
            $blotterCases = $this->model->getWithCreatorInfo($conditions, $params, $orderBy, $perPage, $offset);

            if ($hasPagination) {
                ApiResponse::paginated($blotterCases, $total, $page, $perPage, 'Blotter cases retrieved successfully');
            } else {
                ApiResponse::success($blotterCases, 'Blotter cases retrieved successfully');
            }

        } catch (Exception $e) {
            error_log('Blotter list error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve blotter cases', 500);
        }
    }

    /**
     * Get blotter case by ID
     */
    private function getById($id) {
        try {
            $blotterCase = $this->model->find($id);

            if (!$blotterCase) {
                ApiResponse::notFound('Blotter case not found');
            }

            // Get creator info
            if ($blotterCase['created_by']) {
                $stmt = $this->conn->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->bind_param('i', $blotterCase['created_by']);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $blotterCase['created_by_name'] = $user ? $user['name'] : 'Unknown';
            }

            ApiResponse::success($blotterCase, 'Blotter case retrieved successfully');

        } catch (Exception $e) {
            error_log('Blotter get error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve blotter case', 500);
        }
    }

    /**
     * Get blotter statistics
     */
    private function getStatistics() {
        try {
            $stats = $this->model->getStatistics();
            ApiResponse::success($stats, 'Blotter statistics retrieved successfully');
        } catch (Exception $e) {
            error_log('Blotter stats error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve blotter statistics', 500);
        }
    }

    /**
     * Create new blotter case
     */
    private function create($data) {
        try {
            // Validate required fields
            $this->validateRequired($data, [
                'complainant_name', 'respondent_name', 'incident_date', 'incident_location', 'incident_description'
            ]);

            // Generate case number
            $caseNumber = $this->model->generateCaseNumber();

            // Prepare data for insertion
            $blotterData = [
                'case_number' => $caseNumber,
                'complainant_name' => $this->sanitize($data['complainant_name']),
                'complainant_address' => $this->sanitize($data['complainant_address'] ?? ''),
                'complainant_contact' => $this->sanitize($data['complainant_contact'] ?? ''),
                'respondent_name' => $this->sanitize($data['respondent_name']),
                'respondent_address' => $this->sanitize($data['respondent_address'] ?? ''),
                'respondent_contact' => $this->sanitize($data['respondent_contact'] ?? ''),
                'incident_date' => $data['incident_date'],
                'incident_time' => $data['incident_time'] ?? null,
                'incident_location' => $this->sanitize($data['incident_location']),
                'incident_description' => $this->sanitize($data['incident_description']),
                'status' => $data['status'] ?? 'pending',
                'created_by' => $this->userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $blotterId = $this->model->insert($blotterData);

            if ($blotterId) {
                ApiResponse::success([
                    'id' => $blotterId,
                    'case_number' => $caseNumber,
                    'message' => 'Blotter case created successfully'
                ], 'Blotter case created successfully', 201);
            } else {
                ApiResponse::error('Failed to create blotter case', 500);
            }

        } catch (Exception $e) {
            error_log('Blotter create error: ' . $e->getMessage());
            ApiResponse::error('Failed to create blotter case', 500);
        }
    }

    /**
     * Update blotter status
     */
    private function updateStatus($data) {
        try {
            // Validate required fields
            $this->validateRequired($data, ['id', 'status']);

            // Validate status
            $validStatuses = ['pending', 'under_investigation', 'resolved', 'dismissed'];
            if (!in_array($data['status'], $validStatuses)) {
                ApiResponse::validationError([
                    'status' => 'Invalid status value'
                ]);
            }

            // Check if blotter case exists
            $blotterCase = $this->model->find($data['id']);
            if (!$blotterCase) {
                ApiResponse::notFound('Blotter case not found');
            }

            // Update with resolution if provided
            $resolution = $data['resolution'] ?? null;
            $resolvedDate = $data['resolved_date'] ?? null;
            
            $success = $this->model->updateWithResolution(
                $data['id'], 
                $data['status'], 
                $resolution, 
                $resolvedDate
            );

            if ($success) {
                ApiResponse::success([
                    'id' => $data['id'],
                    'status' => $data['status'],
                    'message' => 'Blotter status updated successfully'
                ], 'Blotter status updated successfully');
            } else {
                ApiResponse::error('Failed to update blotter status', 500);
            }

        } catch (Exception $e) {
            error_log('Blotter status update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update blotter status', 500);
        }
    }
}

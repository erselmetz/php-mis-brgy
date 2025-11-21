<?php
/**
 * Resident Controller
 * Handles all resident-related API operations
 */

class ResidentController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        require_once __DIR__ . '/ResidentModel.php';
        require_once __DIR__ . '/../BaseModel.php';
        $this->model = new ResidentModel();
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
                    $this->getById($queryParams['id']);
                } elseif (isset($queryParams['action']) && $queryParams['action'] === 'list') {
                    $this->list();
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
     * Get all residents with optional filtering
     */
    private function list() {
        try {
            $queryParams = $this->getQueryParams();

            // Build filter conditions
            $conditions = [];
            $params = [];

            // Filter by household
            if (!empty($queryParams['household_id'])) {
                $conditions[] = 'household_id = ?';
                $params[] = $queryParams['household_id'];
            }

            // Filter by gender
            if (!empty($queryParams['gender'])) {
                $conditions[] = 'gender = ?';
                $params[] = $queryParams['gender'];
            }

            // Filter by civil status
            if (!empty($queryParams['civil_status'])) {
                $conditions[] = 'civil_status = ?';
                $params[] = $queryParams['civil_status'];
            }

            // Filter by voter status
            if (!empty($queryParams['voter_status'])) {
                $conditions[] = 'voter_status = ?';
                $params[] = $queryParams['voter_status'];
            }

            // Search by name
            if (!empty($queryParams['search'])) {
                $searchTerm = '%' . $queryParams['search'] . '%';
                $conditions[] = '(first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR suffix LIKE ?)';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }

            // Pagination - if not explicitly requested, return all data for DataTables
            $hasPagination = isset($queryParams['page']) || isset($queryParams['per_page']);
            $page = (int)($queryParams['page'] ?? 1);
            $perPage = $hasPagination ? (int)($queryParams['per_page'] ?? 25) : 10000; // Large limit for DataTables
            $offset = ($page - 1) * $perPage;

            // Get total count
            $total = $this->model->count($conditions, $params);

            // Get residents
            $orderBy = $queryParams['order_by'] ?? 'last_name, first_name';
            $residents = $this->model->findWhere($conditions, $params, $orderBy, $perPage, $offset);

            // Add computed fields
            foreach ($residents as &$resident) {
                $resident['full_name'] = trim($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix']);
                $resident['age'] = $this->calculateAge($resident['birthdate']);
            }

            if ($hasPagination) {
                ApiResponse::paginated($residents, $total, $page, $perPage, 'Residents retrieved successfully');
            } else {
                ApiResponse::success($residents, 'Residents retrieved successfully');
            }

        } catch (Exception $e) {
            error_log('Resident list error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve residents', 500);
        }
    }

    /**
     * Get resident by ID
     */
    private function getById($id) {
        try {
            $resident = $this->model->find($id);

            if (!$resident) {
                ApiResponse::notFound('Resident not found');
            }

            // Add computed fields
            $resident['full_name'] = trim($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix']);
            $resident['age'] = $this->calculateAge($resident['birthdate']);

            ApiResponse::success($resident, 'Resident retrieved successfully');

        } catch (Exception $e) {
            error_log('Resident get error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve resident', 500);
        }
    }

    /**
     * Create new resident
     */
    private function create($data) {
        try {
            // Require staff or admin role
            $this->requireRole(['staff', 'admin']);

            // Validate required fields (household_id is optional)
            $this->validateRequired($data, [
                'first_name', 'last_name', 'birthdate', 'gender', 'civil_status'
            ]);

            // Check for duplicate resident
            $existing = $this->model->findOne([
                'first_name = ? AND last_name = ? AND birthdate = ?'
            ], [$data['first_name'], $data['last_name'], $data['birthdate']]);

            if ($existing) {
                ApiResponse::validationError([
                    'duplicate' => 'A resident with the same name and birthdate already exists'
                ]);
            }

            // Prepare data for insertion
            $residentData = [
                'first_name' => $this->sanitize($data['first_name']),
                'middle_name' => $this->sanitize($data['middle_name'] ?? ''),
                'last_name' => $this->sanitize($data['last_name']),
                'suffix' => $this->sanitize($data['suffix'] ?? ''),
                'birthdate' => $data['birthdate'],
                'birthplace' => $this->sanitize($data['birthplace'] ?? ''),
                'gender' => $data['gender'],
                'civil_status' => $data['civil_status'],
                'religion' => $this->sanitize($data['religion'] ?? ''),
                'occupation' => $this->sanitize($data['occupation'] ?? ''),
                'monthly_income' => $this->sanitize($data['monthly_income'] ?? ''),
                'contact_no' => $this->sanitize($data['contact_no'] ?? ''),
                'contact_number' => $this->sanitize($data['contact_number'] ?? ''), // Alternative field
                'email' => $this->sanitize($data['email'] ?? ''),
                'address' => $this->sanitize($data['address'] ?? ''),
                'voter_status' => $data['voter_status'] ?? 'No',
                'disability_status' => $data['disability_status'] ?? 'No',
                'household_id' => !empty($data['household_id']) ? intval($data['household_id']) : null,
                'remarks' => $this->sanitize($data['remarks'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Handle contact number field mapping
            if (!empty($data['contact_number']) && empty($residentData['contact_no'])) {
                $residentData['contact_no'] = $residentData['contact_number'];
            }

            $residentId = $this->model->insert($residentData);

            if ($residentId) {
                // Update household member count if household_id is provided
                if (!empty($data['household_id'])) {
                    $this->updateHouseholdMemberCount($data['household_id']);
                }

                ApiResponse::success([
                    'id' => $residentId,
                    'message' => 'Resident created successfully'
                ], 'Resident created successfully', 201);
            } else {
                ApiResponse::error('Failed to create resident', 500);
            }

        } catch (Exception $e) {
            error_log('Resident create error: ' . $e->getMessage());
            ApiResponse::error('Failed to create resident', 500);
        }
    }

    /**
     * Update resident
     */
    private function update($data) {
        try {
            // Require staff or admin role
            $this->requireRole(['staff', 'admin']);

            // Validate required fields (household_id is optional)
            $this->validateRequired($data, [
                'id', 'first_name', 'last_name', 'birthdate', 'gender', 'civil_status'
            ]);

            // Check if resident exists
            $existing = $this->model->find($data['id']);
            if (!$existing) {
                ApiResponse::notFound('Resident not found');
            }

            // Prepare data for update
            $residentData = [
                'first_name' => $this->sanitize($data['first_name']),
                'middle_name' => $this->sanitize($data['middle_name'] ?? ''),
                'last_name' => $this->sanitize($data['last_name']),
                'suffix' => $this->sanitize($data['suffix'] ?? ''),
                'birthdate' => $data['birthdate'],
                'birthplace' => $this->sanitize($data['birthplace'] ?? ''),
                'gender' => $data['gender'],
                'civil_status' => $data['civil_status'],
                'religion' => $this->sanitize($data['religion'] ?? ''),
                'occupation' => $this->sanitize($data['occupation'] ?? ''),
                'monthly_income' => $this->sanitize($data['monthly_income'] ?? ''),
                'contact_no' => $this->sanitize($data['contact_no'] ?? ''),
                'contact_number' => $this->sanitize($data['contact_number'] ?? ''), // Alternative field
                'email' => $this->sanitize($data['email'] ?? ''),
                'address' => $this->sanitize($data['address'] ?? ''),
                'voter_status' => $data['voter_status'] ?? 'No',
                'disability_status' => $data['disability_status'] ?? 'No',
                'household_id' => !empty($data['household_id']) ? intval($data['household_id']) : null,
                'remarks' => $this->sanitize($data['remarks'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Handle contact number field mapping
            if (!empty($data['contact_number']) && empty($residentData['contact_no'])) {
                $residentData['contact_no'] = $residentData['contact_number'];
            }

            $success = $this->model->update($data['id'], $residentData);

            if ($success) {
                // Update household member counts if household changed
                $oldHouseholdId = $existing['household_id'];
                $newHouseholdId = !empty($data['household_id']) ? intval($data['household_id']) : null;
                
                if ($oldHouseholdId != $newHouseholdId) {
                    $this->updateHouseholdMemberCount($oldHouseholdId);
                    $this->updateHouseholdMemberCount($newHouseholdId);
                }

                ApiResponse::success([
                    'id' => $data['id'],
                    'message' => 'Resident updated successfully'
                ], 'Resident updated successfully');
            } else {
                ApiResponse::error('Failed to update resident', 500);
            }

        } catch (Exception $e) {
            error_log('Resident update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update resident: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete resident
     */
    private function delete($data) {
        try {
            // Require staff or admin role
            $this->requireRole(['staff', 'admin']);

            // Validate required fields
            $this->validateRequired($data, ['id']);

            // Check if resident exists
            $resident = $this->model->find($data['id']);
            if (!$resident) {
                ApiResponse::notFound('Resident not found');
            }

            $success = $this->model->delete($data['id']);

            if ($success) {
                // Update household member count
                $this->updateHouseholdMemberCount($resident['household_id']);

                ApiResponse::success([
                    'id' => $data['id'],
                    'message' => 'Resident deleted successfully'
                ], 'Resident deleted successfully');
            } else {
                ApiResponse::error('Failed to delete resident', 500);
            }

        } catch (Exception $e) {
            error_log('Resident delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete resident', 500);
        }
    }

    /**
     * Calculate age from birthdate
     */
    private function calculateAge($birthdate) {
        if (empty($birthdate)) {
            return null;
        }

        $birthDate = new DateTime($birthdate);
        $today = new DateTime();
        $age = $today->diff($birthDate);
        return $age->y;
    }

    /**
     * Update household member count
     */
    private function updateHouseholdMemberCount($householdId) {
        if (empty($householdId)) {
            return;
        }

        try {
            // Count residents in household
            $count = $this->model->count(['household_id = ?'], [$householdId]);

            // Update household
            $stmt = $this->conn->prepare("UPDATE households SET total_members = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $count, $householdId);
            $stmt->execute();
            $stmt->close();

        } catch (Exception $e) {
            error_log('Failed to update household member count: ' . $e->getMessage());
        }
    }
}

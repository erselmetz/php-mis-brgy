<?php
/**
 * Certificate Controller
 * Handles all certificate-related API operations
 */

class CertificateController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        require_once __DIR__ . '/CertificateModel.php';
        $this->model = new CertificateModel();
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
                } elseif (isset($queryParams['action'])) {
                    switch ($queryParams['action']) {
                        case 'by_type':
                            $this->getByType($queryParams['type'] ?? null, $queryParams['status'] ?? null);
                            break;
                        case 'by_resident':
                            $this->getByResident($queryParams['resident_id'] ?? null);
                            break;
                        case 'stats':
                            $this->getStatistics();
                            break;
                        default:
                            $this->list();
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
     * Get all certificate requests
     */
    private function list() {
        try {
            $queryParams = $this->getQueryParams();

            // Build filter conditions
            $conditions = [];
            $params = [];

            // Filter by status
            if (!empty($queryParams['status'])) {
                $conditions[] = 'cr.status = ?';
                $params[] = $queryParams['status'];
            }

            // Filter by certificate type
            if (!empty($queryParams['certificate_type'])) {
                $conditions[] = 'cr.certificate_type = ?';
                $params[] = $queryParams['certificate_type'];
            }

            // Search by resident name
            if (!empty($queryParams['search'])) {
                $searchTerm = '%' . $queryParams['search'] . '%';
                $conditions[] = '(r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ?)';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            // Pagination - if not explicitly requested, return all data for DataTables
            $hasPagination = isset($queryParams['page']) || isset($queryParams['per_page']);
            $page = (int)($queryParams['page'] ?? 1);
            $perPage = $hasPagination ? (int)($queryParams['per_page'] ?? 25) : 10000; // Large limit for DataTables
            $offset = ($page - 1) * $perPage;

            // Get total count
            $total = $this->model->count($conditions, $params);

            // Get certificates
            $orderBy = $queryParams['order_by'] ?? 'cr.requested_at DESC';
            $certificates = $this->model->getWithResidentInfo($conditions, $params, $orderBy, $perPage, $offset);

            // Add display names
            foreach ($certificates as &$cert) {
                $cert['certificate_type_display'] = $this->model->getCertificateTypeDisplayName($cert['certificate_type']);
            }

            if ($hasPagination) {
                ApiResponse::paginated($certificates, $total, $page, $perPage, 'Certificate requests retrieved successfully');
            } else {
                ApiResponse::success($certificates, 'Certificate requests retrieved successfully');
            }

        } catch (Exception $e) {
            error_log('Certificate list error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve certificate requests', 500);
        }
    }

    /**
     * Get certificate by ID
     */
    private function getById($id) {
        try {
            $certificate = $this->model->find($id);

            if (!$certificate) {
                ApiResponse::notFound('Certificate request not found');
            }

            // Get resident info
            $residentInfo = $this->getResidentInfo($certificate['resident_id']);
            $certificate = array_merge($certificate, $residentInfo);

            $certificate['certificate_type_display'] = $this->model->getCertificateTypeDisplayName($certificate['certificate_type']);

            ApiResponse::success($certificate, 'Certificate request retrieved successfully');

        } catch (Exception $e) {
            error_log('Certificate get error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve certificate request', 500);
        }
    }

    /**
     * Get certificates by type
     */
    private function getByType($type, $status) {
        try {
            if (empty($type)) {
                ApiResponse::validationError(['type' => 'Certificate type is required']);
            }

            if (!$this->model->isValidCertificateType($type)) {
                ApiResponse::validationError(['type' => 'Invalid certificate type']);
            }

            $certificates = $this->model->getByType($type, $status);

            // Add display names
            foreach ($certificates as &$cert) {
                $cert['certificate_type_display'] = $this->model->getCertificateTypeDisplayName($cert['certificate_type']);
            }

            ApiResponse::success($certificates, 'Certificates retrieved successfully');

        } catch (Exception $e) {
            error_log('Certificate by type error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve certificates', 500);
        }
    }

    /**
     * Get certificates by resident
     */
    private function getByResident($residentId) {
        try {
            if (empty($residentId)) {
                ApiResponse::validationError(['resident_id' => 'Resident ID is required']);
            }

            $certificates = $this->model->getByResident($residentId);

            // Add display names
            foreach ($certificates as &$cert) {
                $cert['certificate_type_display'] = $this->model->getCertificateTypeDisplayName($cert['certificate_type']);
            }

            ApiResponse::success($certificates, 'Resident certificates retrieved successfully');

        } catch (Exception $e) {
            error_log('Certificate by resident error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve resident certificates', 500);
        }
    }

    /**
     * Get certificate statistics
     */
    private function getStatistics() {
        try {
            $stats = $this->model->getStatistics();
            ApiResponse::success($stats, 'Certificate statistics retrieved successfully');
        } catch (Exception $e) {
            error_log('Certificate stats error: ' . $e->getMessage());
            ApiResponse::error('Failed to retrieve certificate statistics', 500);
        }
    }

    /**
     * Create new certificate request
     */
    private function create($data) {
        try {
            // Validate required fields
            $this->validateRequired($data, [
                'resident_id', 'certificate_type', 'purpose'
            ]);

            // Validate certificate type
            if (!$this->model->isValidCertificateType($data['certificate_type'])) {
                ApiResponse::validationError([
                    'certificate_type' => 'Invalid certificate type'
                ]);
            }

            // Check if resident exists
            if (!$this->residentExists($data['resident_id'])) {
                ApiResponse::validationError([
                    'resident_id' => 'Resident not found'
                ]);
            }

            // Prepare data for insertion
            $certificateData = [
                'resident_id' => intval($data['resident_id']),
                'certificate_type' => $data['certificate_type'],
                'purpose' => $this->sanitize(trim($data['purpose'])),
                'issued_by' => $this->userId,
                'status' => 'pending'
            ];

            $certificateId = $this->model->insert($certificateData);

            if ($certificateId) {
                ApiResponse::success([
                    'id' => $certificateId,
                    'message' => 'Certificate request submitted successfully'
                ], 'Certificate request submitted successfully', 201);
            } else {
                error_log("Failed to insert certificate: " . json_encode($certificateData));
                ApiResponse::error('Failed to submit certificate request', 500);
            }

        } catch (Exception $e) {
            error_log('Certificate create error: ' . $e->getMessage());
            ApiResponse::error('Failed to submit certificate request', 500);
        }
    }

    /**
     * Update certificate status
     */
    private function updateStatus($data) {
        try {
            // Require staff or admin role for status updates
            $this->requireRole(['staff', 'admin']);

            // Validate required fields
            $this->validateRequired($data, ['id', 'status']);

            // Validate status
            $validStatuses = ['pending', 'approved', 'rejected', 'completed'];
            if (!in_array($data['status'], $validStatuses)) {
                ApiResponse::validationError([
                    'status' => 'Invalid status value'
                ]);
            }

            // Check if certificate exists
            $certificate = $this->model->find($data['id']);
            if (!$certificate) {
                ApiResponse::notFound('Certificate request not found');
            }

            $success = $this->model->updateStatus($data['id'], $data['status'], $this->userId);

            if ($success) {
                ApiResponse::success([
                    'id' => $data['id'],
                    'status' => $data['status'],
                    'message' => 'Certificate status updated successfully'
                ], 'Certificate status updated successfully');
            } else {
                ApiResponse::error('Failed to update certificate status', 500);
            }

        } catch (Exception $e) {
            error_log('Certificate status update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update certificate status', 500);
        }
    }

    /**
     * Check if resident exists
     */
    private function residentExists($residentId) {
        $stmt = $this->conn->prepare("SELECT id FROM residents WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $residentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Get resident info for certificate
     */
    private function getResidentInfo($residentId) {
        $stmt = $this->conn->prepare("
            SELECT
                first_name, middle_name, last_name, suffix,
                contact_no, address,
                CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ', COALESCE(suffix, '')) as full_name
            FROM residents
            WHERE id = ?
        ");
        $stmt->bind_param('i', $residentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}

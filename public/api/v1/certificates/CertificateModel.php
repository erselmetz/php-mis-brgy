<?php
/**
 * Certificate Model
 * Handles database operations for certificate requests
 */

class CertificateModel extends BaseModel {
    private $validCertificateTypes = [
        'Barangay Clearance',
        'Indigency Certificate',
        'Residency Certificate'
    ];

    private $validStatuses = [
        'pending',
        'approved',
        'rejected',
        'completed',
        'Pending',  // Legacy support
        'Printed',  // Legacy support
        'Approved', // Legacy support
        'Rejected'  // Legacy support
    ];

    public function __construct() {
        parent::__construct();
        $this->table = 'certificate_request';
    }

    /**
     * Check if certificate type is valid
     */
    public function isValidCertificateType($type) {
        return in_array($type, $this->validCertificateTypes);
    }

    /**
     * Get display name for certificate type
     */
    public function getCertificateTypeDisplayName($type) {
        return $type; // Already in display format
    }

    /**
     * Get certificates with resident information
     */
    public function getWithResidentInfo($conditions = [], $params = [], $orderBy = null, $limit = null, $offset = null) {
        $whereClause = $this->buildWhereClause($conditions);
        $orderClause = $orderBy ? "ORDER BY {$orderBy}" : '';
        $limitClause = '';
        
        if ($limit !== null) {
            $limitClause = "LIMIT {$limit}";
            if ($offset !== null) {
                $limitClause .= " OFFSET {$offset}";
            }
        }
        
        $sql = "
            SELECT cr.*, 
                   r.first_name, r.middle_name, r.last_name, r.suffix,
                   r.address, r.contact_no,
                   CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, ' ', COALESCE(r.suffix, '')) as resident_name,
                   u.name as issued_by_name
            FROM certificate_request cr
            LEFT JOIN residents r ON cr.resident_id = r.id
            LEFT JOIN users u ON cr.issued_by = u.id
            {$whereClause}
            {$orderClause}
            {$limitClause}
        ";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Count certificates with conditions
     */
    public function count($conditions = [], $params = []) {
        $whereClause = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as count FROM certificate_request cr {$whereClause}";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }

    /**
     * Get certificates by type
     */
    public function getByType($type, $status = null) {
        $conditions = ['cr.certificate_type = ?'];
        $params = [$type];
        
        if ($status !== null) {
            $conditions[] = 'cr.status = ?';
            $params[] = $status;
        }
        
        return $this->getWithResidentInfo($conditions, $params, 'cr.requested_at DESC');
    }

    /**
     * Get certificates by resident
     */
    public function getByResident($residentId) {
        $conditions = ['cr.resident_id = ?'];
        $params = [$residentId];
        
        return $this->getWithResidentInfo($conditions, $params, 'cr.requested_at DESC');
    }

    /**
     * Get certificate statistics
     */
    public function getStatistics() {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('pending', 'Pending') THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status IN ('approved', 'Approved', 'Printed') THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status IN ('rejected', 'Rejected') THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN certificate_type = 'Barangay Clearance' THEN 1 ELSE 0 END) as clearance_count,
                SUM(CASE WHEN certificate_type = 'Indigency Certificate' THEN 1 ELSE 0 END) as indigency_count,
                SUM(CASE WHEN certificate_type = 'Residency Certificate' THEN 1 ELSE 0 END) as residency_count
            FROM certificate_request
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Update certificate status
     */
    public function updateStatus($id, $status, $userId = null) {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Map legacy status values
        if ($status === 'pending') {
            $data['status'] = 'Pending';
        } elseif ($status === 'approved') {
            $data['status'] = 'Approved';
        } elseif ($status === 'rejected') {
            $data['status'] = 'Rejected';
        }
        
        return $this->update($id, $data);
    }
}


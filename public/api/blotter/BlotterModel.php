<?php
/**
 * Blotter Model
 * Handles database operations for blotter cases
 */

class BlotterModel extends BaseModel {
    public function __construct() {
        parent::__construct();
        $this->table = 'blotter';
    }

    /**
     * Generate case number
     */
    public function generateCaseNumber() {
        $year = date('Y');
        $pattern = "BLT-$year-%";
        
        $count = $this->count(['case_number LIKE ?'], [$pattern]);
        $count = $count + 1;
        
        return "BLT-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get blotter cases with creator information
     */
    public function getWithCreatorInfo($conditions = [], $params = [], $orderBy = null, $limit = null, $offset = null) {
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
            SELECT b.*, u.name as created_by_name 
            FROM blotter b 
            LEFT JOIN users u ON b.created_by = u.id 
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
     * Count blotter cases with conditions
     */
    public function count($conditions = [], $params = []) {
        $whereClause = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as count FROM blotter b {$whereClause}";
        
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
     * Update blotter status
     */
    public function updateStatus($id, $status, $userId = null) {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Set resolved_date if status is resolved
        if ($status === 'resolved') {
            $data['resolved_date'] = date('Y-m-d');
        } else {
            // Clear resolved_date if status changed from resolved
            $current = $this->find($id);
            if ($current && $current['status'] === 'resolved' && $status !== 'resolved') {
                $data['resolved_date'] = null;
            }
        }
        
        return $this->update($id, $data);
    }

    /**
     * Update blotter with resolution details
     */
    public function updateWithResolution($id, $status, $resolution = null, $resolvedDate = null) {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($resolution !== null) {
            $data['resolution'] = $resolution;
        }
        
        if ($status === 'resolved' && $resolvedDate !== null) {
            $data['resolved_date'] = $resolvedDate;
        } elseif ($status !== 'resolved') {
            $data['resolved_date'] = null;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Get blotter statistics
     */
    public function getStatistics() {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) as under_investigation_count,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed_count
            FROM blotter
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}


<?php
/**
 * User Model
 * Handles database operations for users and officers
 */

class UserModel extends BaseModel {
    public function __construct() {
        parent::__construct();
        $this->table = 'users';
    }

    /**
     * Get users with officer and resident information
     */
    public function getWithOfficerInfo($conditions = [], $params = [], $orderBy = null, $limit = null, $offset = null) {
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
            SELECT u.*, 
                   o.id as officer_id, o.resident_id, o.position as officer_position, 
                   o.term_start, o.term_end, o.status as officer_status,
                   r.first_name, r.middle_name, r.last_name, r.suffix,
                   CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, ' ', COALESCE(r.suffix, '')) as resident_full_name
            FROM users u
            LEFT JOIN officers o ON u.id = o.user_id
            LEFT JOIN residents r ON o.resident_id = r.id
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
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null) {
        $conditions = ['LOWER(username) = LOWER(?)'];
        $params = [$username];
        
        if ($excludeId) {
            $conditions[] = 'id != ?';
            $params[] = $excludeId;
        }
        
        return $this->count($conditions, $params) > 0;
    }

    /**
     * Create officer record
     */
    public function createOfficer($data) {
        $sql = "INSERT INTO officers (user_id, resident_id, position, term_start, term_end, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iissss", $data['user_id'], $data['resident_id'], $data['position'], $data['term_start'], $data['term_end'], $data['status']);
        $success = $stmt->execute();
        $officerId = $success ? $stmt->insert_id : false;
        $stmt->close();
        return $officerId;
    }

    /**
     * Update officer record
     */
    public function updateOfficer($officerId, $data) {
        $sql = "UPDATE officers SET resident_id = ?, position = ?, term_start = ?, term_end = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssi", $data['resident_id'], $data['position'], $data['term_start'], $data['term_end'], $data['status'], $officerId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Delete officer record
     */
    public function deleteOfficer($officerId) {
        $sql = "DELETE FROM officers WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $officerId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Deactivate other active Barangay Captains
     */
    public function deactivateOtherCaptains($excludeOfficerId = null) {
        if ($excludeOfficerId) {
            $sql = "UPDATE officers SET status = 'Inactive' WHERE position LIKE 'Barangay Captain' AND status = 'Active' AND id != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $excludeOfficerId);
        } else {
            $sql = "UPDATE officers SET status = 'Inactive' WHERE position LIKE 'Barangay Captain' AND status = 'Active'";
            $stmt = $this->conn->prepare($sql);
        }
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Get officer by user ID
     */
    public function getOfficerByUserId($userId) {
        $sql = "SELECT * FROM officers WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $officer = $result->fetch_assoc();
        $stmt->close();
        return $officer;
    }
}


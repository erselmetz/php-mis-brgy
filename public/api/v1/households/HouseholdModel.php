<?php
/**
 * Household Model
 * Handles database operations for households
 */

class HouseholdModel extends BaseModel {
    public function __construct() {
        parent::__construct();
        $this->table = 'households';
    }

    /**
     * Get households with member count
     */
    public function getWithMemberCount($conditions = [], $params = [], $orderBy = null, $limit = null) {
        $whereClause = $this->buildWhereClause($conditions);
        $orderClause = $orderBy ? "ORDER BY {$orderBy}" : '';
        $limitClause = $limit ? "LIMIT {$limit}" : '';

        $sql = "
            SELECT h.*,
                   COUNT(r.id) as member_count
            FROM households h
            LEFT JOIN residents r ON r.household_id = h.id
            {$whereClause}
            GROUP BY h.id
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
     * Count households with optional conditions
     */
    public function countHouseholds($conditions = [], $params = []) {
        $whereClause = $this->buildWhereClause($conditions);

        $sql = "
            SELECT COUNT(DISTINCT h.id) as count
            FROM households h
            LEFT JOIN residents r ON r.household_id = h.id
            {$whereClause}
        ";

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
     * Check if household number already exists
     */
    public function householdNumberExists($householdNo, $excludeId = null) {
        $conditions = ['household_no = ?'];
        $params = [$householdNo];

        if ($excludeId) {
            $conditions[] = 'id != ?';
            $params[] = $excludeId;
        }

        return $this->count($conditions, $params) > 0;
    }

    /**
     * Get household members
     */
    public function getMembers($householdId) {
        $sql = "
            SELECT r.*,
                   CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, ' ', COALESCE(r.suffix, '')) as full_name,
                   TIMESTAMPDIFF(YEAR, r.birthdate, CURDATE()) as age
            FROM residents r
            WHERE r.household_id = ?
            ORDER BY r.last_name, r.first_name
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $householdId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update member count for household
     */
    public function updateMemberCount($householdId) {
        $memberCount = $this->count(['household_id = ?'], [$householdId]);

        $sql = "UPDATE households SET total_members = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $memberCount, $householdId);
        return $stmt->execute();
    }
}

<?php
/**
 * Base Model Class
 * Provides common database operations for all models
 */

class BaseModel {
    protected $conn;
    protected $table;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    /**
     * Build WHERE clause from conditions array
     */
    protected function buildWhereClause($conditions) {
        if (empty($conditions)) {
            return '';
        }
        return 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Find one record matching conditions
     */
    public function findOne($conditions = [], $params = []) {
        $whereClause = $this->buildWhereClause($conditions);
        $sql = "SELECT * FROM {$this->table} {$whereClause} LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Find records matching conditions
     */
    public function findWhere($conditions = [], $params = [], $orderBy = null, $limit = null, $offset = null) {
        $whereClause = $this->buildWhereClause($conditions);
        $orderClause = $orderBy ? "ORDER BY {$orderBy}" : '';
        $limitClause = '';
        
        if ($limit !== null) {
            $limitClause = "LIMIT {$limit}";
            if ($offset !== null) {
                $limitClause .= " OFFSET {$offset}";
            }
        }
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} {$orderClause} {$limitClause}";
        
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
     * Count records matching conditions
     */
    public function count($conditions = [], $params = []) {
        $whereClause = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        
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
     * Insert new record
     */
    public function insert($data) {
        // Filter out NULL values and handle them separately
        $fields = [];
        $placeholders = [];
        $values = [];
        $types = '';
        
        foreach ($data as $field => $value) {
            $fields[] = $field;
            if ($value === null) {
                $placeholders[] = 'NULL';
            } else {
                $placeholders[] = '?';
                $values[] = $value;
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Insert prepare error: " . $this->conn->error);
            return false;
        }
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        $success = $stmt->execute();
        
        if ($success) {
            $insertId = $this->conn->insert_id;
            $stmt->close();
            return $insertId;
        } else {
            error_log("Insert error: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Update record
     */
    public function update($id, $data) {
        // Handle NULL values separately
        $setParts = [];
        $values = [];
        $types = '';
        
        foreach ($data as $field => $value) {
            if ($value === null) {
                $setParts[] = "{$field} = NULL";
            } else {
                $setParts[] = "{$field} = ?";
                $values[] = $value;
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
        }
        
        $values[] = $id; // Add ID for WHERE clause
        $types .= 'i';
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Update prepare error: " . $this->conn->error);
            return false;
        }
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    /**
     * Delete record
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}


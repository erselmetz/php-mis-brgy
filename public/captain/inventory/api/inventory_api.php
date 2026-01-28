<?php
/**
 * Inventory API
 * 
 * Handles all inventory operations including:
 * - List, Add, Edit, Delete inventory items
 * - Category management
 * - Audit trail tracking
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireCaptain();

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? 'list';
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['username'] ?? 'Unknown';
$userRole = $_SESSION['role'] ?? '';

/**
 * Helper function to log audit trail
 * Silently fails if audit trail table doesn't exist
 */
function logAuditTrail($conn, $inventoryId, $assetCode, $actionType, $userId, $userName, $userRole, $data = []) {
    try {
        // Check if audit trail table exists
        $checkTable = $conn->query("SHOW TABLES LIKE 'inventory_audit_trail'");
        if (!$checkTable || $checkTable->num_rows === 0) {
            // Table doesn't exist, skip logging
            return;
        }
        
        $stmt = $conn->prepare("INSERT INTO inventory_audit_trail 
            (inventory_id, asset_code, action_type, user_id, user_name, user_role, 
             personnel_name, personnel_role, location, purpose, start_time, end_time, 
             old_value, new_value, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            error_log('Failed to prepare audit trail statement: ' . $conn->error);
            return;
        }
        
        $personnelName = $data['personnel_name'] ?? null;
        $personnelRole = $data['personnel_role'] ?? null;
        $location = $data['location'] ?? null;
        $purpose = $data['purpose'] ?? null;
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;
        $oldValue = $data['old_value'] ?? null;
        $newValue = $data['new_value'] ?? null;
        $notes = $data['notes'] ?? null;
        
        $stmt->bind_param('ississsssssssss', 
            $inventoryId, $assetCode, $actionType, $userId, $userName, $userRole,
            $personnelName, $personnelRole, $location, $purpose, $startTime, $endTime,
            $oldValue, $newValue, $notes
        );
        $stmt->execute();
    } catch (Exception $e) {
        // Silently fail - audit trail is optional
        error_log('Audit trail error: ' . $e->getMessage());
    }
}

// ==================== LIST INVENTORY ====================
if ($action === 'list') {
    try {
        $search = sanitizeString($_GET['search'] ?? '');
        $where = '';
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $where = "WHERE name LIKE ? OR asset_code LIKE ? OR category LIKE ? OR location LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
            $types = 'ssss';
        }
        
        // Check which columns exist to handle old table structures
        $columns = $conn->query("SHOW COLUMNS FROM inventory");
        $columnNames = [];
        if ($columns) {
            while ($col = $columns->fetch_assoc()) {
                $columnNames[] = $col['Field'];
            }
        }
        
        $hasStatus = in_array('status', $columnNames);
        $hasUpdatedAt = in_array('updated_at', $columnNames);
        
        // Build SELECT clause based on available columns
        $selectFields = ['id', 'asset_code', 'name', 'category', 'quantity', 'location', 'cond', 'description', 'created_at'];
        
        if ($hasStatus) {
            $selectFields[] = "COALESCE(status, 'available') as status";
        } else {
            $selectFields[] = "'available' as status";
        }
        
        if ($hasUpdatedAt) {
            $selectFields[] = 'updated_at';
        }
        
        $sql = "SELECT " . implode(', ', $selectFields) . " 
                FROM inventory {$where} 
                ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        
        $items = [];
        while ($row = $res->fetch_assoc()) {
            // Set default status if not set
            if (!isset($row['status']) || empty($row['status'])) {
                $row['status'] = 'available';
            }
            
            // Calculate currently using (in_use count from audit trail) - make it optional
            $row['currently_using'] = 0;
            try {
                $checkAudit = $conn->query("SHOW TABLES LIKE 'inventory_audit_trail'");
                if ($checkAudit && $checkAudit->num_rows > 0) {
                    $useStmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory_audit_trail 
                                              WHERE inventory_id = ? AND action_type = 'assigned' 
                                              AND (end_time IS NULL OR end_time > NOW())");
                    $useStmt->bind_param('i', $row['id']);
                    $useStmt->execute();
                    $useRes = $useStmt->get_result();
                    $useRow = $useRes->fetch_assoc();
                    $row['currently_using'] = (int)($useRow['count'] ?? 0);
                }
            } catch (Exception $e) {
                // Audit trail table doesn't exist, just set to 0
                $row['currently_using'] = 0;
            }
            
            $items[] = $row;
        }
        
        echo json_encode(['status' => 'ok', 'data' => $items]);
        exit;
    } catch (mysqli_sql_exception $e) {
        error_log('Inventory list error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to load inventory: ' . $e->getMessage(), 'data' => []]);
        exit;
    } catch (Exception $e) {
        error_log('Inventory list error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to load inventory', 'data' => []]);
        exit;
    }
}

// ==================== GET SINGLE ITEM ====================
if ($action === 'get' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $item = $res->fetch_assoc();
        
        if ($item) {
            echo json_encode(['status' => 'ok', 'data' => $item]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        }
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch item']);
        exit;
    }
}


// ==================== AUDIT TRAIL ====================
if ($action === 'audit_trail') {
    try {
        $inventoryId = (int)($_GET['inventory_id'] ?? 0);
        $assetCode = sanitizeString($_GET['asset_code'] ?? '');
        $dateFilter = $_GET['date'] ?? '';
        $personnelFilter = sanitizeString($_GET['personnel'] ?? '');
        
        $where = [];
        $params = [];
        $types = '';
        
        if ($inventoryId > 0) {
            $where[] = "inventory_id = ?";
            $params[] = $inventoryId;
            $types .= 'i';
        }
        
        if (!empty($assetCode)) {
            $where[] = "asset_code = ?";
            $params[] = $assetCode;
            $types .= 's';
        }
        
        if (!empty($dateFilter)) {
            $where[] = "DATE(created_at) = ?";
            $params[] = $dateFilter;
            $types .= 's';
        }
        
        if (!empty($personnelFilter)) {
            $where[] = "(personnel_name LIKE ? OR user_name LIKE ?)";
            $searchTerm = "%{$personnelFilter}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM inventory_audit_trail {$whereClause} ORDER BY created_at DESC LIMIT 500";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        
        $trails = [];
        while ($row = $res->fetch_assoc()) {
            $trails[] = $row;
        }
        
        echo json_encode(['status' => 'ok', 'data' => $trails]);
        exit;
    } catch (Exception $e) {
        error_log('Audit trail error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch audit trail', 'data' => []]);
        exit;
    }
}

// ==================== CATEGORY ENDPOINTS ====================
if ($action === 'list_categories') {
    try {
        $stmt = $conn->prepare("SELECT id, name FROM inventory_category_list ORDER BY name ASC");
        $stmt->execute();
        $res = $stmt->get_result();
        $cats = [];
        while ($row = $res->fetch_assoc()) {
            $cats[] = $row;
        }
        echo json_encode(['status' => 'ok', 'data' => $cats]);
        exit;
    } catch (mysqli_sql_exception $e) {
        error_log('Category list error: ' . $e->getMessage());
        echo json_encode(['status' => 'ok', 'data' => []]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);

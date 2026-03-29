<?php
/**
 * Inventory API
 *
 * Changes from original:
 *  - list: each item now includes  available_quantity  computed as:
 *      inventory.quantity  −  SUM(active borrows for that item)
 *    "active borrows" = borrowing_schedule WHERE status IN ('borrowed','overdue')
 */

require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

header('Content-Type: application/json; charset=utf-8');

$action   = $_REQUEST['action'] ?? 'list';
$userId   = $_SESSION['user_id']   ?? 0;
$userName = $_SESSION['username']  ?? 'Unknown';
$userRole = $_SESSION['role']      ?? '';

// ── Audit trail helper ────────────────────────────────────────────────────────
function logAuditTrail($conn, $inventoryId, $assetCode, $actionType,
                        $userId, $userName, $userRole, $data = []) {
    try {
        $checkTable = $conn->query("SHOW TABLES LIKE 'inventory_audit_trail'");
        if (!$checkTable || $checkTable->num_rows === 0) return;

        $stmt = $conn->prepare(
            "INSERT INTO inventory_audit_trail
             (inventory_id, asset_code, action_type, user_id, user_name, user_role,
              personnel_name, personnel_role, location, purpose,
              start_time, end_time, old_value, new_value, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        if (!$stmt) return;

        $personnelName = $data['personnel_name'] ?? null;
        $personnelRole = $data['personnel_role'] ?? null;
        $location      = $data['location']       ?? null;
        $purpose       = $data['purpose']        ?? null;
        $startTime     = $data['start_time']     ?? null;
        $endTime       = $data['end_time']       ?? null;
        $oldValue      = $data['old_value']      ?? null;
        $newValue      = $data['new_value']      ?? null;
        $notes         = $data['notes']          ?? null;

        $stmt->bind_param('ississsssssssss',
            $inventoryId, $assetCode, $actionType, $userId,
            $userName, $userRole,
            $personnelName, $personnelRole, $location, $purpose,
            $startTime, $endTime, $oldValue, $newValue, $notes
        );
        $stmt->execute();
    } catch (Exception $e) {
        error_log('Audit trail error: ' . $e->getMessage());
    }
}

// ── LIST ──────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    try {
        $search = sanitizeString($_GET['search'] ?? '');
        $where  = '';
        $params = [];
        $types  = '';

        if (!empty($search)) {
            $where  = "WHERE i.name LIKE ? OR i.asset_code LIKE ?
                          OR i.category LIKE ? OR i.location LIKE ?";
            $s      = "%{$search}%";
            $params = [$s, $s, $s, $s];
            $types  = 'ssss';
        }

        // Check which columns exist
        $columns     = $conn->query("SHOW COLUMNS FROM inventory");
        $columnNames = [];
        if ($columns) while ($col = $columns->fetch_assoc()) $columnNames[] = $col['Field'];

        $hasStatus    = in_array('status',     $columnNames);
        $hasUpdatedAt = in_array('updated_at', $columnNames);

        $statusExpr = $hasStatus
            ? "COALESCE(i.status, 'available')"
            : "'available'";

        $updatedAtExpr = $hasUpdatedAt ? ", i.updated_at" : "";

        // ── available_quantity sub-query ──────────────────────────────────────
        // Check whether borrowing_schedule table + inventory_id column exist
        $hasBorrowTable = false;
        $btCheck = $conn->query("SHOW TABLES LIKE 'borrowing_schedule'");
        if ($btCheck && $btCheck->num_rows > 0) $hasBorrowTable = true;

        $availExpr = $hasBorrowTable
            ? "(i.quantity - COALESCE((
                    SELECT SUM(b.quantity)
                    FROM borrowing_schedule b
                    WHERE b.inventory_id = i.id
                      AND b.status IN ('borrowed','overdue')
               ), 0))"
            : "i.quantity";

        $sql = "SELECT
                    i.id,
                    i.asset_code,
                    i.name,
                    i.category,
                    i.quantity,
                    {$availExpr} AS available_quantity,
                    i.location,
                    i.cond,
                    i.description,
                    i.created_at
                    {$updatedAtExpr},
                    {$statusExpr} AS status
                FROM inventory i
                {$where}
                ORDER BY i.created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $items = [];
        while ($row = $res->fetch_assoc()) {
            if (empty($row['status'])) $row['status'] = 'available';

            // Clamp available to 0 (can't be negative)
            $row['available_quantity'] = max(0, (int)($row['available_quantity'] ?? $row['quantity']));

            // Legacy field kept for backward compat
            $row['currently_using'] = (int)$row['quantity'] - $row['available_quantity'];

            $items[] = $row;
        }

        echo json_encode(['status' => 'ok', 'data' => $items]);
        exit;

    } catch (Exception $e) {
        error_log('Inventory list error: ' . $e->getMessage());
        echo json_encode(['status' => 'error',
            'message' => 'Failed to load inventory: ' . $e->getMessage(),
            'data'    => []]);
        exit;
    }
}

// ── GET SINGLE ────────────────────────────────────────────────────────────────
if ($action === 'get' && isset($_GET['id'])) {
    try {
        $id   = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();

        if ($item) {
            // Attach available_quantity
            $borrowed = 0;
            $btCheck  = $conn->query("SHOW TABLES LIKE 'borrowing_schedule'");
            if ($btCheck && $btCheck->num_rows > 0) {
                $bs = $conn->prepare(
                    "SELECT COALESCE(SUM(quantity),0) AS borrowed
                     FROM borrowing_schedule
                     WHERE inventory_id = ? AND status IN ('borrowed','overdue')"
                );
                $bs->bind_param('i', $id);
                $bs->execute();
                $borrowed = (int)$bs->get_result()->fetch_assoc()['borrowed'];
                $bs->close();
            }
            $item['available_quantity'] = max(0, (int)$item['quantity'] - $borrowed);
            $item['currently_using']    = $borrowed;

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

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    $name        = sanitizeString($_POST['name']        ?? '');
    $category    = sanitizeString($_POST['category']    ?? '');
    $quantity    = sanitizeInt($_POST['quantity']       ?? 1, 0);
    $location    = sanitizeString($_POST['location']    ?? '');
    $cond        = sanitizeString($_POST['condition']   ?? '');
    $status      = sanitizeString($_POST['status']      ?? 'available');
    $description = sanitizeString($_POST['description'] ?? '');

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Asset name is required']);
        exit;
    }

    // Generate asset code
    $year      = date('Y');
    $stmt      = $conn->prepare(
        "SELECT COUNT(*) as count FROM inventory WHERE asset_code LIKE ?"
    );
    $pattern   = "PROP-{$year}-%";
    $stmt->bind_param('s', $pattern);
    $stmt->execute();
    $count     = (int)$stmt->get_result()->fetch_assoc()['count'] + 1;
    $asset_code = "PROP-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);

    try {
        $stmt = $conn->prepare(
            "INSERT INTO inventory
             (asset_code,name,category,quantity,location,cond,status,description,created_by)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('sssissssi',
            $asset_code, $name, $category, $quantity,
            $location, $cond, $status, $description, $userId
        );
        $stmt->execute();
        $inventoryId = $stmt->insert_id;

        logAuditTrail($conn, $inventoryId, $asset_code, 'created', $userId, $userName, $userRole, [
            'new_value' => json_encode(['name' => $name, 'category' => $category, 'quantity' => $quantity]),
            'notes'     => 'New inventory item created',
        ]);

        echo json_encode(['status' => 'ok',
            'message' => 'Inventory item added successfully',
            'data'    => ['id' => $inventoryId, 'asset_code' => $asset_code]]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Inventory insert error: ' . $e->getMessage());
        echo json_encode(['status' => 'error',
            'message' => 'Failed to add item: ' . $e->getMessage()]);
    }
    exit;
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid item ID']);
        exit;
    }

    $oldStmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
    $oldStmt->bind_param('i', $id);
    $oldStmt->execute();
    $oldItem = $oldStmt->get_result()->fetch_assoc();
    if (!$oldItem) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        exit;
    }

    $name        = sanitizeString($_POST['name']        ?? $oldItem['name']);
    $category    = sanitizeString($_POST['category']    ?? $oldItem['category']);
    $quantity    = sanitizeInt($_POST['quantity']       ?? $oldItem['quantity'], 0);
    $location    = sanitizeString($_POST['location']    ?? $oldItem['location']);
    $cond        = sanitizeString($_POST['condition']   ?? $oldItem['cond']);
    $status      = sanitizeString($_POST['status']      ?? $oldItem['status']);
    $description = sanitizeString($_POST['description'] ?? $oldItem['description']);

    try {
        $stmt = $conn->prepare(
            "UPDATE inventory
             SET name=?, category=?, quantity=?, location=?, cond=?, status=?, description=?
             WHERE id=?"
        );
        $stmt->bind_param('ssissssi',
            $name, $category, $quantity,
            $location, $cond, $status, $description, $id
        );
        $stmt->execute();

        // Log changes
        $changes = [];
        if ($oldItem['quantity'] != $quantity)
            $changes['quantity'] = ['old' => $oldItem['quantity'], 'new' => $quantity];
        if ($oldItem['status'] !== $status)
            $changes['status']   = ['old' => $oldItem['status'],   'new' => $status];
        if ($oldItem['location'] !== $location)
            $changes['location'] = ['old' => $oldItem['location'], 'new' => $location];

        foreach ($changes as $field => $change) {
            $at = $field === 'location' ? 'location_changed'
                : ($field === 'quantity' ? 'quantity_changed' : 'updated');
            logAuditTrail($conn, $id, $oldItem['asset_code'], $at,
                $userId, $userName, $userRole, [
                    'old_value' => $change['old'],
                    'new_value' => $change['new'],
                    'notes'     => ucfirst($field) . ' updated',
                ]);
        }

        echo json_encode(['status' => 'ok', 'message' => 'Inventory item updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Inventory update error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to update item']);
    }
    exit;
}

// ── ASSIGN / RETURN ───────────────────────────────────────────────────────────
if (in_array($action, ['assign','return']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    $id            = (int)($_POST['id'] ?? 0);
    $personnelName = sanitizeString($_POST['personnel_name'] ?? '');
    $personnelRole = sanitizeString($_POST['personnel_role'] ?? '');
    $location      = sanitizeString($_POST['location']       ?? '');
    $purpose       = sanitizeString($_POST['purpose']        ?? '');
    $startTime     = $_POST['start_time'] ?? null;
    $endTime       = $_POST['end_time']   ?? null;

    if ($id <= 0 || empty($personnelName)) {
        http_response_code(400);
        echo json_encode(['status' => 'error',
            'message' => 'Item ID and personnel name are required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT asset_code, name, status FROM inventory WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        if (!$item) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Item not found']);
            exit;
        }

        $actionType = $action === 'assign' ? 'assigned' : 'returned';
        // Inventory status is managed manually — borrowing_schedule tracks active borrows
        // We only log the audit trail, no status change on the item itself

        logAuditTrail($conn, $id, $item['asset_code'], $actionType,
            $userId, $userName, $userRole, [
                'personnel_name' => $personnelName,
                'personnel_role' => $personnelRole,
                'location'       => $location,
                'purpose'        => $purpose,
                'start_time'     => $startTime,
                'end_time'       => $endTime,
                'notes'          => ucfirst($action) . 'd to ' . $personnelName,
            ]);

        echo json_encode(['status' => 'ok',
            'message' => 'Asset ' . $action . 'ed successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Asset ' . $action . ' error: ' . $e->getMessage());
        echo json_encode(['status' => 'error',
            'message' => 'Failed to ' . $action . ' asset']);
    }
    exit;
}

// ── AUDIT TRAIL ───────────────────────────────────────────────────────────────
if ($action === 'audit_trail') {
    try {
        $inventoryId     = (int)($_GET['inventory_id'] ?? 0);
        $assetCode       = sanitizeString($_GET['asset_code']  ?? '');
        $dateFilter      = $_GET['date']      ?? '';
        $personnelFilter = sanitizeString($_GET['personnel']   ?? '');

        $where  = [];
        $params = [];
        $types  = '';

        if ($inventoryId > 0) { $where[] = "inventory_id = ?"; $params[] = $inventoryId; $types .= 'i'; }
        if (!empty($assetCode)) { $where[] = "asset_code = ?";  $params[] = $assetCode;  $types .= 's'; }
        if (!empty($dateFilter)) { $where[] = "DATE(created_at) = ?"; $params[] = $dateFilter; $types .= 's'; }
        if (!empty($personnelFilter)) {
            $where[]  = "(personnel_name LIKE ? OR user_name LIKE ?)";
            $st       = "%{$personnelFilter}%";
            $params[] = $st; $params[] = $st;
            $types   .= 'ss';
        }

        $wc   = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql  = "SELECT * FROM inventory_audit_trail {$wc} ORDER BY created_at DESC LIMIT 500";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $trails = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $trails[] = $row;

        echo json_encode(['status' => 'ok', 'data' => $trails]);
        exit;
    } catch (Exception $e) {
        error_log('Audit trail error: ' . $e->getMessage());
        echo json_encode(['status' => 'error',
            'message' => 'Failed to fetch audit trail', 'data' => []]);
        exit;
    }
}

// ── CATEGORIES ────────────────────────────────────────────────────────────────
if ($action === 'list_categories') {
    try {
        $stmt = $conn->prepare("SELECT id, name FROM inventory_category_list ORDER BY name ASC");
        $stmt->execute();
        $res  = $stmt->get_result();
        $cats = [];
        while ($row = $res->fetch_assoc()) $cats[] = $row;
        echo json_encode(['status' => 'ok', 'data' => $cats]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'ok', 'data' => []]);
        exit;
    }
}

if ($action === 'add_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    $name = sanitizeString($_POST['category_name'] ?? '');
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Category name is required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO inventory_category_list (name) VALUES (?)");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $newId = $stmt->insert_id;
        echo json_encode(['status' => 'ok', 'data' => ['id' => $newId, 'name' => $name]]);
        exit;
    } catch (Exception $e) {
        if ($e->getCode() == 1062) {
            $q = $conn->prepare("SELECT id FROM inventory_category_list WHERE name = ? LIMIT 1");
            $q->bind_param('s', $name);
            $q->execute();
            $r = $q->get_result()->fetch_assoc();
            echo json_encode(['status' => 'ok',
                'data'    => ['id' => $r['id'], 'name' => $name],
                'message' => 'Category already exists']);
            exit;
        }
        error_log('Add category error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to add category']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
<?php
/**
 * Equipment / Items Borrowing Schedule API
 * Actions: list | save | delete | mark_returned
 *
 * Availability rule:
 *   available = inventory.quantity
 *              - SUM(borrowing_schedule.quantity
 *                    WHERE inventory_id = X
 *                    AND   status IN ('borrowed','overdue')
 *                    [AND  id != $currentId   -- on UPDATE])
 */

require_once __DIR__ . '/../../../includes/app.php';
requireCaptain();
header('Content-Type: application/json; charset=utf-8');

$action = trim($_REQUEST['action'] ?? '');

// ─── HELPERS ─────────────────────────────────────────────────────────────────

/**
 * Get total quantity and currently-borrowed quantity for an inventory item.
 * Returns ['total' => int, 'borrowed' => int, 'available' => int].
 * Returns null when the inventory item doesn't exist.
 */
function getInventoryAvailability(mysqli $conn, int $inventoryId, int $excludeBorrowId = 0): ?array
{
    // Total stock
    $stmt = $conn->prepare("SELECT quantity, name FROM inventory WHERE id = ?");
    $stmt->bind_param('i', $inventoryId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;

    $total = (int) $row['quantity'];
    $name  = $row['name'];

    // Active borrows (exclude current record when editing)
    if ($excludeBorrowId > 0) {
        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(quantity), 0) AS borrowed
             FROM borrowing_schedule
             WHERE inventory_id = ?
               AND status IN ('borrowed','overdue')
               AND id != ?"
        );
        $stmt->bind_param('ii', $inventoryId, $excludeBorrowId);
    } else {
        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(quantity), 0) AS borrowed
             FROM borrowing_schedule
             WHERE inventory_id = ?
               AND status IN ('borrowed','overdue')"
        );
        $stmt->bind_param('i', $inventoryId);
    }
    $stmt->execute();
    $borrowed = (int) $stmt->get_result()->fetch_assoc()['borrowed'];
    $stmt->close();

    return [
        'total'     => $total,
        'borrowed'  => $borrowed,
        'available' => max(0, $total - $borrowed),
        'name'      => $name,
    ];
}

// ─── LIST ────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    try {
        $search      = sanitizeString($_GET['search'] ?? '');
        $filterStat  = sanitizeString($_GET['filter_status'] ?? '');
        $filterDate  = $_GET['filter_date'] ?? '';

        $where  = [];
        $params = [];
        $types  = '';

        // Auto-mark overdue
        $conn->query(
            "UPDATE borrowing_schedule
             SET status = 'overdue'
             WHERE status = 'borrowed' AND return_date < CURDATE()"
        );

        if (!empty($search)) {
            $where[]  = "(b.borrower_name LIKE ? OR b.borrow_code LIKE ? OR b.item_name LIKE ?)";
            $s        = "%{$search}%";
            array_push($params, $s, $s, $s);
            $types   .= 'sss';
        }
        if (!empty($filterStat)) {
            $where[]  = "b.status = ?";
            $params[] = $filterStat;
            $types   .= 's';
        }
        if (!empty($filterDate)) {
            $where[]  = "b.borrow_date = ?";
            $params[] = $filterDate;
            $types   .= 's';
        }

        $wc  = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT b.*,
                       u.name  AS created_by_name,
                       u2.name AS updated_by_name,
                       i.asset_code,
                       i.name  AS inventory_name,
                       i.quantity AS inventory_total,
                       (i.quantity - COALESCE((
                           SELECT SUM(b2.quantity)
                           FROM borrowing_schedule b2
                           WHERE b2.inventory_id = b.inventory_id
                             AND b2.status IN ('borrowed','overdue')
                       ), 0)) AS inventory_available
                FROM borrowing_schedule b
                LEFT JOIN users     u   ON b.created_by   = u.id
                LEFT JOIN users     u2  ON b.updated_by   = u2.id
                LEFT JOIN inventory i   ON b.inventory_id = i.id
                {$wc}
                ORDER BY b.borrow_date DESC, b.created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Status counts
        $counts = ['borrowed' => 0, 'returned' => 0, 'overdue' => 0, 'cancelled' => 0];
        $cRes   = $conn->query(
            "SELECT status, COUNT(*) AS cnt FROM borrowing_schedule GROUP BY status"
        );
        while ($cr = $cRes->fetch_assoc()) {
            if (isset($counts[$cr['status']])) $counts[$cr['status']] = (int)$cr['cnt'];
        }

        echo json_encode(['status' => 'ok', 'data' => $rows, 'counts' => $counts]);

    } catch (Exception $e) {
        error_log('Borrow list error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to load records.', 'data' => []]);
    }
    exit;
}

// ─── AVAILABILITY CHECK (AJAX) ───────────────────────────────────────────────
if ($action === 'check_availability') {
    $inventoryId    = (int)($_GET['inventory_id'] ?? 0);
    $excludeId      = (int)($_GET['exclude_id']   ?? 0);

    if ($inventoryId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid inventory ID.']);
        exit;
    }

    $avail = getInventoryAvailability($conn, $inventoryId, $excludeId);
    if (!$avail) {
        echo json_encode(['status' => 'error', 'message' => 'Inventory item not found.']);
        exit;
    }

    echo json_encode(['status' => 'ok', 'data' => $avail]);
    exit;
}

// ─── SAVE (Insert / Update) ──────────────────────────────────────────────────
if ($action === 'save') {
    $id           = (int)($_POST['id']           ?? 0);
    $borrower     = sanitizeString($_POST['borrower_name']    ?? '');
    $contact      = sanitizeString($_POST['borrower_contact'] ?? '');
    $item_name    = sanitizeString($_POST['item_name']        ?? '');
    $inventory_id = (int)($_POST['inventory_id']  ?? 0);
    $quantity     = max(1, (int)($_POST['quantity'] ?? 1));
    $borrow_date  = $_POST['borrow_date']  ?? '';
    $return_date  = $_POST['return_date']  ?? '';
    $actual_return= $_POST['actual_return'] ?? '';
    $purpose      = sanitizeString($_POST['purpose']      ?? '');
    $status       = sanitizeString($_POST['status']       ?? 'borrowed');
    $cond_out     = sanitizeString($_POST['condition_out'] ?? '');
    $cond_in      = sanitizeString($_POST['condition_in']  ?? '');
    $notes        = sanitizeString($_POST['notes']         ?? '');

    // ── AUTO-LINK: fallback inventory_id lookup by item_name ─────────────────
    // If JS failed to set hidden field, find inventory row by exact name match
    if ($inventory_id <= 0 && !empty($item_name)) {
        $lkStmt = $conn->prepare(
            "SELECT id FROM inventory WHERE LOWER(name) = LOWER(?) LIMIT 1"
        );
        $lkStmt->bind_param('s', $item_name);
        $lkStmt->execute();
        $lkRow = $lkStmt->get_result()->fetch_assoc();
        $lkStmt->close();
        if ($lkRow) {
            $inventory_id = (int)$lkRow['id'];
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Required field validation
    if (empty($borrower) || empty($item_name) || empty($borrow_date) || empty($return_date)) {
        echo json_encode(['success' => false,
            'message' => 'Borrower Name, Item, Borrow Date, and Return Date are required.']);
        exit;
    }
    if (!in_array($status, ['borrowed','returned','overdue','cancelled'])) $status = 'borrowed';
    if ($borrow_date > $return_date) {
        echo json_encode(['success' => false,
            'message' => 'Return Date must be after Borrow Date.']);
        exit;
    }

    // ── AVAILABILITY CHECK ────────────────────────────────────────────────────
    // Only check when an inventory item is linked AND the borrow is active
    if ($inventory_id > 0 && in_array($status, ['borrowed','overdue'])) {
        $avail = getInventoryAvailability($conn, $inventory_id, $id);

        if (!$avail) {
            echo json_encode(['success' => false,
                'message' => 'Selected inventory item not found.']);
            exit;
        }

        if ($quantity > $avail['available']) {
            echo json_encode([
                'success'   => false,
                'message'   => "Not enough stock available for \"{$avail['name']}\". "
                             . "Total: {$avail['total']} | "
                             . "Currently borrowed: {$avail['borrowed']} | "
                             . "Available: {$avail['available']} | "
                             . "Requested: {$quantity}",
                'availability' => $avail,
            ]);
            exit;
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    $inv_id  = $inventory_id > 0 ? $inventory_id : null;
    $act_ret = !empty($actual_return) ? $actual_return : null;
    $userId  = $_SESSION['user_id'] ?? 0;

    try {
        if ($id > 0) {
            // UPDATE
            // Types: s s s i s s s s s s s s s i i
            //        ^borrower ^contact ^item ^inv_id(i) ^qty(s?→i below)
            // inventory_id is nullable int → pass as int, quantity as int
            // Full: borrower(s) contact(s) item_name(s) inv_id(i) quantity(i)
            //       borrow_date(s) return_date(s) actual_return(s)
            //       purpose(s) status(s) cond_out(s) cond_in(s) notes(s)
            //       updated_by(i) id(i)  = 13 strings + 2 ints → 'sssii sssssssss ii' → 'sssiissssssssii'
            $stmt = $conn->prepare(
                "UPDATE borrowing_schedule
                 SET borrower_name=?, borrower_contact=?, item_name=?,
                     inventory_id=?, quantity=?,
                     borrow_date=?, return_date=?, actual_return=?,
                     purpose=?, status=?,
                     condition_out=?, condition_in=?, notes=?,
                     updated_by=?
                 WHERE id=?"
            );
            $stmt->bind_param(
                'sssiissssssssii',
                $borrower, $contact, $item_name,
                $inv_id, $quantity,
                $borrow_date, $return_date, $act_ret,
                $purpose, $status,
                $cond_out, $cond_in, $notes,
                $userId, $id
            );
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true,
                'message' => 'Borrowing record updated successfully.']);

        } else {
            // INSERT — generate borrow code
            $year      = date('Y');
            $codeStmt  = $conn->prepare(
                "SELECT COUNT(*) AS cnt FROM borrowing_schedule WHERE borrow_code LIKE ?"
            );
            $p = "BRS-{$year}-%";
            $codeStmt->bind_param('s', $p);
            $codeStmt->execute();
            $cnt = (int) $codeStmt->get_result()->fetch_assoc()['cnt'];
            $codeStmt->close();
            $borrow_code = 'BRS-' . $year . '-' . str_pad($cnt + 1, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare(
                "INSERT INTO borrowing_schedule
                 (borrow_code, borrower_name, borrower_contact, item_name,
                  inventory_id, quantity, borrow_date, return_date, actual_return,
                  purpose, status, condition_out, condition_in, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param(
                'ssssiissssssssi',
                $borrow_code, $borrower, $contact, $item_name,
                $inv_id, $quantity,
                $borrow_date, $return_date, $act_ret,
                $purpose, $status,
                $cond_out, $cond_in, $notes,
                $userId
            );
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true,
                'message'     => 'Borrowing record created.',
                'borrow_code' => $borrow_code]);
        }

    } catch (Exception $e) {
        error_log('Borrow save error: ' . $e->getMessage());
        echo json_encode(['success' => false,
            'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ─── MARK RETURNED ──────────────────────────────────────────────────────────
if ($action === 'mark_returned') {
    $id      = (int)($_POST['id'] ?? 0);
    $cond_in = sanitizeString($_POST['condition_in'] ?? '');
    $userId  = $_SESSION['user_id'] ?? 0;
    $today   = date('Y-m-d');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }
    try {
        $stmt = $conn->prepare(
            "UPDATE borrowing_schedule
             SET status='returned', actual_return=?, condition_in=?, updated_by=?
             WHERE id=?"
        );
        $stmt->bind_param('ssii', $today, $cond_in, $userId, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Item marked as returned.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update.']);
    }
    exit;
}

// ─── DELETE ──────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }
    try {
        $stmt = $conn->prepare("DELETE FROM borrowing_schedule WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Record deleted.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
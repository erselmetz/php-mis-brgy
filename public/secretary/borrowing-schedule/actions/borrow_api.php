<?php
/**
 * Equipment / Items Borrowing Schedule API
 * Actions: list | save | delete | mark_returned
 */

require_once __DIR__ . '/../../../../includes/app.php';
requireSecretary();
header('Content-Type: application/json; charset=utf-8');

$action = trim($_REQUEST['action'] ?? '');

// ─── LIST ────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    try {
        $search = sanitizeString($_GET['search'] ?? '');
        $filterStat = sanitizeString($_GET['filter_status'] ?? '');
        $filterDate = $_GET['filter_date'] ?? '';

        $where = [];
        $params = [];
        $types = '';

        // Auto-mark overdue
        $conn->query("UPDATE borrowing_schedule SET status='overdue' WHERE status='borrowed' AND return_date < CURDATE()");

        if (!empty($search)) {
            $where[] = "(b.borrower_name LIKE ? OR b.borrow_code LIKE ? OR b.item_name LIKE ?)";
            $s = "%{$search}%";
            array_push($params, $s, $s, $s);
            $types .= 'sss';
        }
        if (!empty($filterStat)) {
            $where[] = "b.status = ?";
            $params[] = $filterStat;
            $types .= 's';
        }
        if (!empty($filterDate)) {
            $where[] = "b.borrow_date = ?";
            $params[] = $filterDate;
            $types .= 's';
        }

        $wc = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT b.*, u.name AS created_by_name, u2.name AS updated_by_name,
                       i.asset_code, i.name AS inventory_name
                FROM borrowing_schedule b
                LEFT JOIN users u     ON b.created_by    = u.id
                LEFT JOIN users u2    ON b.updated_by    = u2.id
                LEFT JOIN inventory i ON b.inventory_id  = i.id
                {$wc}
                ORDER BY b.borrow_date DESC, b.created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params))
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Counts
        $counts = ['borrowed' => 0, 'returned' => 0, 'overdue' => 0, 'cancelled' => 0];
        $cRes = $conn->query("SELECT status, COUNT(*) AS cnt FROM borrowing_schedule GROUP BY status");
        while ($cr = $cRes->fetch_assoc()) {
            if (isset($counts[$cr['status']]))
                $counts[$cr['status']] = (int) $cr['cnt'];
        }

        echo json_encode(['status' => 'ok', 'data' => $rows, 'counts' => $counts]);
    } catch (Exception $e) {
        error_log('Borrow list error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to load records.', 'data' => []]);
    }
    exit;
}

// ─── SAVE ────────────────────────────────────────────────────────────────────
if ($action === 'save') {
    $id = (int) ($_POST['id'] ?? 0);
    $borrower = sanitizeString($_POST['borrower_name'] ?? '');
    $contact = sanitizeString($_POST['borrower_contact'] ?? '');
    $item_name = sanitizeString($_POST['item_name'] ?? '');
    $inventory_id = (int) ($_POST['inventory_id'] ?? 0);
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $borrow_date = $_POST['borrow_date'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    $actual_return = $_POST['actual_return'] ?? '';
    $purpose = sanitizeString($_POST['purpose'] ?? '');
    $status = sanitizeString($_POST['status'] ?? 'borrowed');
    $cond_out = sanitizeString($_POST['condition_out'] ?? '');
    $cond_in = sanitizeString($_POST['condition_in'] ?? '');
    $notes = sanitizeString($_POST['notes'] ?? '');

    if (empty($borrower) || empty($item_name) || empty($borrow_date) || empty($return_date)) {
        echo json_encode(['success' => false, 'message' => 'Borrower Name, Item, Borrow Date, and Return Date are required.']);
        exit;
    }
    if (!in_array($status, ['borrowed', 'returned', 'overdue', 'cancelled']))
        $status = 'borrowed';
    if ($borrow_date > $return_date) {
        echo json_encode(['success' => false, 'message' => 'Return Date must be after Borrow Date.']);
        exit;
    }

    $inv_id = $inventory_id > 0 ? $inventory_id : null;
    $act_ret = !empty($actual_return) ? $actual_return : null;
    $userId = $_SESSION['user_id'] ?? 0;

    try {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE borrowing_schedule SET borrower_name=?,borrower_contact=?,item_name=?,inventory_id=?,quantity=?,borrow_date=?,return_date=?,actual_return=?,purpose=?,status=?,condition_out=?,condition_in=?,notes=?,updated_by=? WHERE id=?");
            $stmt->bind_param('sssiiissssssii' . 'i', $borrower, $contact, $item_name, $inv_id, $quantity, $borrow_date, $return_date, $act_ret, $purpose, $status, $cond_out, $cond_in, $notes, $userId, $id);
            // Fix: correct types string
            $stmt2 = $conn->prepare("UPDATE borrowing_schedule SET borrower_name=?,borrower_contact=?,item_name=?,inventory_id=?,quantity=?,borrow_date=?,return_date=?,actual_return=?,purpose=?,status=?,condition_out=?,condition_in=?,notes=?,updated_by=? WHERE id=?");
            $stmt2->bind_param('ssssisssssssiii', $borrower, $contact, $item_name, $inv_id, $quantity, $borrow_date, $return_date, $act_ret, $purpose, $status, $cond_out, $cond_in, $notes, $userId, $id);
            $stmt2->execute();
            $stmt2->close();
            echo json_encode(['success' => true, 'message' => 'Borrowing record updated successfully.']);
        } else {
            $year = date('Y');
            $codeStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM borrowing_schedule WHERE borrow_code LIKE ?");
            $p = "BRS-{$year}-%";
            $codeStmt->bind_param('s', $p);
            $codeStmt->execute();
            $cnt = (int) $codeStmt->get_result()->fetch_assoc()['cnt'];
            $codeStmt->close();
            $borrow_code = 'BRS-' . $year . '-' . str_pad($cnt + 1, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO borrowing_schedule (borrow_code,borrower_name,borrower_contact,item_name,inventory_id,quantity,borrow_date,return_date,actual_return,purpose,status,condition_out,condition_in,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssissssssssi' . 'i', $borrow_code, $borrower, $contact, $item_name, $inv_id, $quantity, $borrow_date, $return_date, $act_ret, $purpose, $status, $cond_out, $cond_in, $notes, $userId);
            // Fix: correct types
            $stmt2 = $conn->prepare("INSERT INTO borrowing_schedule (borrow_code,borrower_name,borrower_contact,item_name,inventory_id,quantity,borrow_date,return_date,actual_return,purpose,status,condition_out,condition_in,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt2->bind_param('ssssisssssssssi', $borrow_code, $borrower, $contact, $item_name, $inv_id, $quantity, $borrow_date, $return_date, $act_ret, $purpose, $status, $cond_out, $cond_in, $notes, $userId);
            $stmt2->execute();
            $stmt2->close();
            echo json_encode(['success' => true, 'message' => 'Borrowing record created.', 'borrow_code' => $borrow_code]);
        }
    } catch (Exception $e) {
        error_log('Borrow save error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ─── MARK RETURNED ──────────────────────────────────────────────────────────
if ($action === 'mark_returned') {
    $id = (int) ($_POST['id'] ?? 0);
    $cond_in = sanitizeString($_POST['condition_in'] ?? '');
    $userId = $_SESSION['user_id'] ?? 0;
    $today = date('Y-m-d');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }
    try {
        $stmt = $conn->prepare("UPDATE borrowing_schedule SET status='returned', actual_return=?, condition_in=?, updated_by=? WHERE id=?");
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
    $id = (int) ($_POST['id'] ?? 0);
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
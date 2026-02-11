<?php
// api/medicine_inventory_api.php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/app.php';
requireHCNurse(); // Only HC Nurse can access

require_once __DIR__ . '/helpers.php';

// optional: protect role (if meron ka nang requireHCNurse())
// require_once __DIR__ . '/../includes/app.php';
// requireHCNurse();

$action = $_GET['action'] ?? '';

try {
  switch ($action) {

    /* =========================
       CATEGORIES
    ========================= */
    case 'list_categories': {
      $res = $conn->query("SELECT id, name FROM medicine_categories ORDER BY name ASC");
      $rows = [];
      while ($r = $res->fetch_assoc()) $rows[] = $r;
      json_ok($rows);
    }

    case 'add_category': {
      $name = req_str('name', 100, true);
      if (!$name) json_err('Category name is required');

      $stmt = $conn->prepare("INSERT INTO medicine_categories (name) VALUES (?)");
      $stmt->bind_param("s", $name);

      if (!$stmt->execute()) {
        // handle duplicate
        if ($conn->errno === 1062) json_err('Category already exists', 409);
        json_err('Failed to add category');
      }
      json_ok(['id' => $stmt->insert_id, 'name' => $name], 'Category added');
    }

    /* =========================
       MEDICINES LIST (datatable)
    ========================= */
    case 'list': {
      $search = trim((string)($_GET['search'] ?? ''));

      $sql = "
        SELECT
          m.id,
          m.name,
          COALESCE(c.name, '') AS category,
          m.stock_qty,
          m.reorder_level,
          m.unit,
          m.expiration_date
        FROM medicines m
        LEFT JOIN medicine_categories c ON c.id = m.category_id
      ";

      $params = [];
      $types = "";

      if ($search !== '') {
        $sql .= " WHERE m.name LIKE ? OR c.name LIKE ? ";
        $like = "%{$search}%";
        $params = [$like, $like];
        $types = "ss";
      }

      $sql .= " ORDER BY m.id DESC LIMIT 200";

      if ($types !== "") {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
      } else {
        $res = $conn->query($sql);
      }

      $rows = [];
      while ($r = $res->fetch_assoc()) {
        $r['status'] = compute_medicine_status($r['expiration_date'] ?? null, (int)$r['stock_qty'], (int)$r['reorder_level']);
        // UI-friendly code like MED-0001
        $r['medicine_code'] = 'MED-' . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT);
        $rows[] = $r;
      }

      json_ok($rows);
    }

    case 'get': {
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) json_err('Invalid id');

      $stmt = $conn->prepare("
        SELECT id, category_id, name, description, stock_qty, reorder_level, unit, expiration_date
        FROM medicines WHERE id = ?
      ");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res->fetch_assoc();
      if (!$row) json_err('Not found', 404);

      json_ok($row);
    }

    case 'add': {
      $name = req_str('name', 150, true);
      if (!$name) json_err('Medicine name is required');

      $category_id   = req_int('category_id', false);
      $description   = req_str('description', 2000, false);
      $stock_qty     = req_int('stock_qty', true);
      $reorder_level = req_int('reorder_level', true);
      $unit          = req_str('unit', 50, false) ?? 'pcs';
      $expiration    = req_date('expiration_date', false);

      if ($stock_qty === null || $stock_qty < 0) json_err('Invalid stock qty');
      if ($reorder_level === null || $reorder_level < 0) json_err('Invalid reorder level');
      if ($category_id !== null && $category_id <= 0) $category_id = null;
      if ($expiration === null && isset($_POST['expiration_date']) && trim((string)$_POST['expiration_date']) !== '') {
        json_err('Invalid expiration date (use YYYY-MM-DD)');
      }

      $stmt = $conn->prepare("
        INSERT INTO medicines (category_id, name, description, stock_qty, reorder_level, unit, expiration_date)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param(
        "issiiis",
        $category_id,
        $name,
        $description,
        $stock_qty,
        $reorder_level,
        $unit,
        $expiration
      );

      if (!$stmt->execute()) {
        if ($conn->errno === 1062) json_err('Medicine name already exists', 409);
        json_err('Failed to add medicine');
      }

      json_ok(['id' => $stmt->insert_id], 'Medicine added');
    }

    case 'update': {
      $id = req_int('id', true);
      if ($id === null || $id <= 0) json_err('Invalid id');

      $name = req_str('name', 150, true);
      if (!$name) json_err('Medicine name is required');

      $category_id   = req_int('category_id', false);
      $description   = req_str('description', 2000, false);
      $stock_qty     = req_int('stock_qty', true);
      $reorder_level = req_int('reorder_level', true);
      $unit          = req_str('unit', 50, false) ?? 'pcs';
      $expiration    = req_date('expiration_date', false);

      if ($stock_qty === null || $stock_qty < 0) json_err('Invalid stock qty');
      if ($reorder_level === null || $reorder_level < 0) json_err('Invalid reorder level');
      if ($category_id !== null && $category_id <= 0) $category_id = null;
      if ($expiration === null && isset($_POST['expiration_date']) && trim((string)$_POST['expiration_date']) !== '') {
        json_err('Invalid expiration date (use YYYY-MM-DD)');
      }

      $stmt = $conn->prepare("
        UPDATE medicines
        SET category_id=?, name=?, description=?, stock_qty=?, reorder_level=?, unit=?, expiration_date=?
        WHERE id=?
      ");
      $stmt->bind_param(
        "issiiisi",
        $category_id,
        $name,
        $description,
        $stock_qty,
        $reorder_level,
        $unit,
        $expiration,
        $id
      );

      if (!$stmt->execute()) json_err('Failed to update medicine');
      json_ok(null, 'Medicine updated');
    }

    /* =========================
       DISPENSE (optional, for medicine history)
    ========================= */
    case 'dispense': {
      $resident_id = req_int('resident_id', true);
      $medicine_id = req_int('medicine_id', true);
      $qty         = req_int('quantity', true);
      $date        = req_date('dispense_date', true);
      $notes       = req_str('notes', 2000, false);

      if (!$resident_id || $resident_id <= 0) json_err('Invalid resident');
      if (!$medicine_id || $medicine_id <= 0) json_err('Invalid medicine');
      if ($qty === null || $qty <= 0) json_err('Invalid quantity');
      if (!$date) json_err('Invalid dispense date (use YYYY-MM-DD)');

      // deduct stock safely
      $conn->begin_transaction();

      $stmt = $conn->prepare("SELECT stock_qty FROM medicines WHERE id=? FOR UPDATE");
      $stmt->bind_param("i", $medicine_id);
      $stmt->execute();
      $cur = $stmt->get_result()->fetch_assoc();
      if (!$cur) { $conn->rollback(); json_err('Medicine not found', 404); }

      $stock = (int)$cur['stock_qty'];
      if ($stock < $qty) { $conn->rollback(); json_err('Not enough stock'); }

      $stmt = $conn->prepare("UPDATE medicines SET stock_qty = stock_qty - ? WHERE id=?");
      $stmt->bind_param("ii", $qty, $medicine_id);
      if (!$stmt->execute()) { $conn->rollback(); json_err('Failed to update stock'); }

      $stmt = $conn->prepare("
        INSERT INTO medicine_dispense (resident_id, medicine_id, quantity, dispense_date, notes)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("iiiss", $resident_id, $medicine_id, $qty, $date, $notes);
      if (!$stmt->execute()) { $conn->rollback(); json_err('Failed to save dispense record'); }

      $conn->commit();
      json_ok(null, 'Dispensed successfully');
    }

    default:
      json_err('Invalid action', 404);
  }
} catch (Throwable $e) {
  json_err('Server error: ' . $e->getMessage(), 500);
}

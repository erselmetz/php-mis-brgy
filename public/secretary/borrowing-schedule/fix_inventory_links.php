<?php
/**
 * One-time repair: link existing borrowing_schedule rows to inventory by name.
 * Drop in public/secretary/borrowing-schedule/fix_inventory_links.php
 * Visit once, then DELETE THIS FILE.
 */
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();
header('Content-Type: text/plain; charset=utf-8');

echo "=== Fixing NULL inventory_id in borrowing_schedule ===\n\n";

// Find all borrows with NULL inventory_id that have an item_name
$res = $conn->query("
    SELECT b.id, b.item_name
    FROM borrowing_schedule b
    WHERE b.inventory_id IS NULL
    AND b.item_name != ''
");

$fixed = 0;
$notFound = [];

while ($row = $res->fetch_assoc()) {
    // Find matching inventory item (case-insensitive)
    $stmt = $conn->prepare("SELECT id, name FROM inventory WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt->bind_param('s', $row['item_name']);
    $stmt->execute();
    $inv = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($inv) {
        $upd = $conn->prepare("UPDATE borrowing_schedule SET inventory_id = ? WHERE id = ?");
        $upd->bind_param('ii', $inv['id'], $row['id']);
        $upd->execute();
        $upd->close();
        echo "  Fixed borrow id={$row['id']} | item='{$row['item_name']}' → inventory id={$inv['id']}\n";
        $fixed++;
    } else {
        $notFound[] = $row['item_name'];
        echo "  SKIPPED borrow id={$row['id']} | item='{$row['item_name']}' → no matching inventory\n";
    }
}

echo "\nFixed: $fixed records\n";
if ($notFound) {
    echo "Could not match: " . implode(', ', array_unique($notFound)) . "\n";
    echo "(These item names don't exactly match any inventory name)\n";
}

echo "\n=== Now checking availability ===\n\n";

$res = $conn->query("
    SELECT i.id, i.name, i.quantity AS total,
        COALESCE((
            SELECT SUM(b.quantity)
            FROM borrowing_schedule b
            WHERE b.inventory_id = i.id AND b.status IN ('borrowed','overdue')
        ), 0) AS borrowed
    FROM inventory i
    ORDER BY i.id
");

while ($r = $res->fetch_assoc()) {
    $available = $r['total'] - $r['borrowed'];
    echo "  {$r['name']}: total={$r['total']} | borrowed={$r['borrowed']} | available={$available}\n";
}

echo "\nDone. DELETE THIS FILE.\n";
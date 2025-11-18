<?php

if ($action === 'add_household') {
    // --- Collect and sanitize inputs ---
    $household_no = trim($_POST['household_no']);
    $head_name = trim($_POST['head_name']);
    $address = trim($_POST['address']);

    // --- Step 1: Check for duplicate household number ---
    $check = $conn->prepare("
        SELECT id FROM households 
        WHERE household_no = ?
    ");
    $check->bind_param("s", $household_no);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $error = "⚠️ Household number already exists in the database!";
    } else {
        // --- Step 2: Insert new record ---
        $sql = $conn->prepare("
            INSERT INTO households 
            (household_no, head_name, address, total_members)
            VALUES (?, ?, ?, 0)
        ");
        $sql->bind_param(
            "sss",
            $household_no,
            $head_name,
            $address
        );

        if ($sql->execute()) {
            // success — return to household list
            header("Location: /household/households?success=1");
            exit();
        } else {
            $error = "❌ Error adding household: " . $conn->error;
        }

        $sql->close();
    }

    $check->close();
}
?>

<!-- ✅ Optional: show error message if form was redisplayed -->
<?php if (isset($error)): ?>
    <div class="p-3 mb-2 bg-red-100 border border-red-300 text-red-700 rounded-lg">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

